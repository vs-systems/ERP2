<?php
namespace Vsys\Modules\Analysis;

/**
 * VS System ERP - Operations Analysis Module
 */

require_once __DIR__ . '/../../lib/Database.php';

use Vsys\Lib\Database;

class OperationAnalysis
{
    private $db;
    private $company_id;

    public function __construct($company_id = null)
    {
        $this->db = Database::getInstance();
        $this->company_id = $company_id ?: ($_SESSION['company_id'] ?? null);
    }

    /**
     * Get detailed data for a quotation and its potential/actual purchase costs
     */
    public function getQuotationAnalysis($quoteId)
    {
        // 1. Get Quotation Header and Items
        $sqlQuote = "SELECT q.*, e.name as client_name, e.tax_category, e.is_retention_agent 
                     FROM quotations q 
                     JOIN entities e ON q.client_id = e.id 
                     WHERE q.id = :id AND q.company_id = :cid";
        $quote = $this->db->prepare($sqlQuote);
        $quote->execute([':id' => $quoteId, ':cid' => $this->company_id]);
        $header = $quote->fetch();

        if (!$header)
            return null;

        $sqlItems = "SELECT qi.*, 
                            qi.quantity as qty,
                            qi.unit_price_usd as unit_price,
                            p.sku,
                            p.description, 
                            p.unit_cost_usd as unit_cost 
                     FROM quotation_items qi 
                     LEFT JOIN products p ON qi.product_id = p.id 
                     WHERE qi.quotation_id = :id";
        $itemsStmt = $this->db->prepare($sqlItems);
        $itemsStmt->execute([':id' => $quoteId]);
        $items = $itemsStmt->fetchAll();

        // MERGE header fields into the top level array so $analysis['quote_number'] works
        $result = $header;
        $result['items'] = $items;

        // Calculate dynamic totals for the view
        $totalCost = 0;
        foreach ($items as $item) {
            $totalCost += (($item['unit_cost_usd'] ?? 0) * $item['qty']);
        }
        $result['total_revenue'] = $header['subtotal_usd']; // Assuming subtotal is net
        $result['total_cost'] = $totalCost;
        $result['profit'] = $result['total_revenue'] - $totalCost;
        $result['margin_percent'] = $result['total_revenue'] > 0 ? ($result['profit'] / $result['total_revenue']) * 100 : 0;
        $result['taxes'] = $result['total_revenue'] * 0.035; // Est. 3.5% IIBB
        $result['date'] = date('d/m/Y', strtotime($header['created_at']));

        return $result;
    }

    /**
     * Calculate Summary for Dashboard
     * Returns: Total Sales (Net), Total Purchases (Net), Total Expenses, Total Profit
     */
    public function getDashboardSummary()
    {
        try {
            $quoteCols = $this->db->query("DESCRIBE quotations")->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            $quoteCols = [];
        }

        $purchaseCols = [];
        try {
            $purchaseCols = $this->db->query("DESCRIBE purchases")->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            // Table might be missing or different
            try {
                $purchaseCols = $this->db->query("DESCRIBE purchase_orders")->fetchAll(\PDO::FETCH_COLUMN);
            } catch (\Exception $e2) {
                $purchaseCols = [];
            }
        }

        $hasQuoteConfirmed = in_array('is_confirmed', $quoteCols);
        $hasPurchaseConfirmed = in_array('is_confirmed', $purchaseCols);
        $hasQuotePaymentStatus = in_array('payment_status', $quoteCols);
        $hasPurchasePaymentStatus = in_array('payment_status', $purchaseCols);
        $hasPurchaseCompanyId = in_array('company_id', $purchaseCols);
        $hasQuoteCompanyId = in_array('company_id', $quoteCols);

        // Net Sales (USD)
        $salesSql = $hasQuoteConfirmed
            ? "SELECT SUM(subtotal_usd) FROM quotations WHERE is_confirmed = 1"
            : "SELECT SUM(subtotal_usd) FROM quotations WHERE status = 'Aceptado'";

        if ($hasQuoteCompanyId) {
            $salesSql .= " AND company_id = ?";
        }

        $stmtSales = $this->db->prepare($salesSql);
        $hasQuoteCompanyId ? $stmtSales->execute([$this->company_id]) : $stmtSales->execute();
        $totalSales = $stmtSales->fetchColumn() ?: 0;

        // Net Purchases (USD)
        $totalPurchases = 0;
        if (!empty($purchaseCols)) {
            $pTable = in_array('purchase_number', $purchaseCols) ? 'purchases' : 'purchase_orders';
            $purchasesSql = $hasPurchasePaymentStatus
                ? "SELECT SUM(subtotal_usd) FROM $pTable WHERE payment_status = 'Pagado'"
                : "SELECT SUM(subtotal_usd) FROM $pTable WHERE status = 'Pagado'";

            if ($hasPurchaseCompanyId) {
                $purchasesSql .= " AND company_id = ?";
            }

            $stmtPurchases = $this->db->prepare($purchasesSql);
            $hasPurchaseCompanyId ? $stmtPurchases->execute([$this->company_id]) : $stmtPurchases->execute();
            $totalPurchases = $stmtPurchases->fetchColumn() ?: 0;
        }

        // Effectiveness
        $qSqlShort = "SELECT COUNT(*) FROM quotations";
        if ($hasQuoteCompanyId)
            $qSqlShort .= " WHERE company_id = ?";
        $stmtTotalQuotes = $this->db->prepare($qSqlShort);
        $hasQuoteCompanyId ? $stmtTotalQuotes->execute([$this->company_id]) : $stmtTotalQuotes->execute();
        $totalQuotes = $stmtTotalQuotes->fetchColumn() ?: 0;

        $acceptedQuotesSql = $hasQuoteConfirmed
            ? "SELECT COUNT(*) FROM quotations WHERE is_confirmed = 1"
            : "SELECT COUNT(*) FROM quotations WHERE status = 'Aceptado'";

        if ($hasQuoteCompanyId) {
            $acceptedQuotesSql .= " AND company_id = ?";
        }

        $stmtAccepted = $this->db->prepare($acceptedQuotesSql);
        $hasQuoteCompanyId ? $stmtAccepted->execute([$this->company_id]) : $stmtAccepted->execute();
        $acceptedQuotes = $stmtAccepted->fetchColumn() ?: 0;
        $effectiveness = $totalQuotes > 0 ? ($acceptedQuotes / $totalQuotes) * 100 : 0;

        // Commercial Status Summaries
        $pendingCollections = 0;
        if ($hasQuoteConfirmed && $hasQuotePaymentStatus) {
            $pendingCollSql = "SELECT SUM(subtotal_usd) FROM quotations WHERE is_confirmed = 1 AND payment_status = 'Pendiente' AND company_id = ?";
            $stmtPendingColl = $this->db->prepare($pendingCollSql);
            $stmtPendingColl->execute([$this->company_id]);
            $pendingCollections = $stmtPendingColl->fetchColumn() ?: 0;
        }

        $pendingPayments = 0;
        if ($hasPurchaseConfirmed && $hasPurchasePaymentStatus) {
            $pendingPaySql = "SELECT SUM(subtotal_usd) FROM purchases WHERE is_confirmed = 1 AND payment_status = 'Pendiente' AND company_id = ?";
            $stmtPendingPay = $this->db->prepare($pendingPaySql);
            $stmtPendingPay->execute([$this->company_id]);
            $pendingPayments = $stmtPendingPay->fetchColumn() ?: 0;
        }

        return [
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'total_profit' => $totalSales - $totalPurchases,
            'pending_collections' => $pendingCollections,
            'pending_payments' => $pendingPayments,
            'quotations_total' => $totalQuotes,
            'orders_total' => $acceptedQuotes,
            'effectiveness' => round($effectiveness, 2)
        ];
    }
}
