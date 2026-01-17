<?php
/**
 * VS System ERP - Operations Analysis Module
 */

namespace Vsys\Modules\Analysis;

require_once __DIR__ . '/../../lib/Database.php';

use Vsys\Lib\Database;

class OperationAnalysis
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
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
                     WHERE q.id = :id";
        $quote = $this->db->prepare($sqlQuote);
        $quote->execute([':id' => $quoteId]);
        $header = $quote->fetch();

        if (!$header)
            return null;

        $sqlItems = "SELECT qi.*, p.description, p.unit_cost_usd as catalog_cost 
                     FROM quotation_items qi 
                     LEFT JOIN products p ON qi.product_id = p.id 
                     WHERE qi.quotation_id = :id";
        $itemsStmt = $this->db->prepare($sqlItems);
        $itemsStmt->execute([':id' => $quoteId]);
        $items = $itemsStmt->fetchAll();

        return [
            'header' => $header,
            'items' => $items
        ];
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
            ? "SELECT SUM(subtotal_usd) FROM quotations WHERE is_confirmed = 1"
            : "SELECT SUM(subtotal_usd) FROM quotations WHERE status = 'Aceptado'";
        $totalSales = $this->db->query($salesSql)->fetchColumn() ?: 0;

        // Net Purchases (USD)
        $purchasesSql = $hasPurchasePaymentStatus
            ? "SELECT SUM(subtotal_usd) FROM purchases WHERE payment_status = 'Pagado'"
            : "SELECT SUM(subtotal_usd) FROM purchases WHERE status = 'Pagado'";
        $totalPurchases = $this->db->query($purchasesSql)->fetchColumn() ?: 0;

        // Effectiveness
        $totalQuotes = $this->db->query("SELECT COUNT(*) FROM quotations")->fetchColumn() ?: 0;
        $acceptedQuotesSql = $hasQuoteConfirmed
            ? "SELECT COUNT(*) FROM quotations WHERE is_confirmed = 1"
            : "SELECT COUNT(*) FROM quotations WHERE status = 'Aceptado'";
        $acceptedQuotes = $this->db->query($acceptedQuotesSql)->fetchColumn() ?: 0;
        $effectiveness = $totalQuotes > 0 ? ($acceptedQuotes / $totalQuotes) * 100 : 0;

        // Commercial Status Summaries
        $pendingCollections = 0;
        if ($hasQuoteConfirmed && $hasQuotePaymentStatus) {
            $pendingCollSql = "SELECT SUM(subtotal_usd) FROM quotations WHERE is_confirmed = 1 AND payment_status = 'Pendiente'";
            $pendingCollections = $this->db->query($pendingCollSql)->fetchColumn() ?: 0;
        }

        $pendingPayments = 0;
        if ($hasPurchaseConfirmed && $hasPurchasePaymentStatus) {
            $pendingPaySql = "SELECT SUM(subtotal_usd) FROM purchases WHERE is_confirmed = 1 AND payment_status = 'Pendiente'";
            $pendingPayments = $this->db->query($pendingPaySql)->fetchColumn() ?: 0;
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
