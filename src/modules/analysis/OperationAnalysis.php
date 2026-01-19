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
        // Check for columns to avoid Fatal error if migration hasn't run
        $quoteCols = $this->db->query("DESCRIBE quotations")->fetchAll(\PDO::FETCH_COLUMN);
        $purchaseCols = $this->db->query("DESCRIBE purchases")->fetchAll(\PDO::FETCH_COLUMN);

        $hasQuoteConfirmed = in_array('is_confirmed', $quoteCols);
        $hasPurchaseConfirmed = in_array('is_confirmed', $purchaseCols);
        $hasQuotePaymentStatus = in_array('payment_status', $quoteCols);
        $hasPurchasePaymentStatus = in_array('payment_status', $purchaseCols);

        // Net Sales (USD)
        $salesSql = $hasQuoteConfirmed
            ? "SELECT SUM(subtotal_usd) FROM quotations WHERE is_confirmed = 1 AND company_id = ?"
            : "SELECT SUM(subtotal_usd) FROM quotations WHERE status = 'Aceptado' AND company_id = ?";
        $stmtSales = $this->db->prepare($salesSql);
        $stmtSales->execute([$this->company_id]);
        $totalSales = $stmtSales->fetchColumn() ?: 0;

        // Net Purchases (USD)
        $purchasesSql = $hasPurchasePaymentStatus
            ? "SELECT SUM(subtotal_usd) FROM purchases WHERE payment_status = 'Pagado' AND company_id = ?"
            : "SELECT SUM(subtotal_usd) FROM purchases WHERE status = 'Pagado' AND company_id = ?";
        $stmtPurchases = $this->db->prepare($purchasesSql);
        $stmtPurchases->execute([$this->company_id]);
        $totalPurchases = $stmtPurchases->fetchColumn() ?: 0;

        // Effectiveness
        $stmtTotalQuotes = $this->db->prepare("SELECT COUNT(*) FROM quotations WHERE company_id = ?");
        $stmtTotalQuotes->execute([$this->company_id]);
        $totalQuotes = $stmtTotalQuotes->fetchColumn() ?: 0;

        $acceptedQuotesSql = $hasQuoteConfirmed
            ? "SELECT COUNT(*) FROM quotations WHERE is_confirmed = 1 AND company_id = ?"
            : "SELECT COUNT(*) FROM quotations WHERE status = 'Aceptado' AND company_id = ?";
        $stmtAccepted = $this->db->prepare($acceptedQuotesSql);
        $stmtAccepted->execute([$this->company_id]);
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
