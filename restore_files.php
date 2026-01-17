<?php
// restore_files.php - Restauración de Archivos Críticos (Versión 3.0 - SQL Fix)
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Restaurador de Archivos Críticos v3 (SQL Fix)</h1>";

/**
 * Helper to write file safely
 */
function writeFile($path, $content)
{
    echo "<p>Escribiendo: $path ... ";
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<span style='color:orange'> (Directorio creado) </span>";
        } else {
            echo "<span style='color:red'> [ERROR: No se pudo crear directorio] </span>";
            return false;
        }
    }

    // Force delete if exists
    if (file_exists($path)) {
        unlink($path);
    }

    if (file_put_contents($path, $content) !== false) {
        echo "<span style='color:green'> [OK] </span> (" . strlen($content) . " bytes)</p>";
        // Invalidate OPcache
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
        return true;
    } else {
        echo "<span style='color:red'> [ERROR DE ESCRITURA] </span></p>";
        return false;
    }
}

// ---------------------------------------------------------
// 1. OperationAnalysis.php
// ---------------------------------------------------------
$contentAnalysis = <<<'PHP'
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

        // Alias fields to match View expectations explicitly
        // Fixed: unit_price_usd instead of unit_price
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
            $totalCost += (($item['unit_cost'] ?? 0) * ($item['qty'] ?? 0));
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
PHP;

$pathAnalysis = __DIR__ . '/src/modules/analysis/OperationAnalysis.php';
writeFile($pathAnalysis, $contentAnalysis);

// ---------------------------------------------------------
// 2. CRM.php (Keep checking just in case)
// ---------------------------------------------------------
$contentCRM = <<<'PHP'
<?php
/**
 * VS System ERP - CRM Module Logic
 */

namespace Vsys\Modules\CRM;

require_once __DIR__ . '/../../lib/Database.php';

use Vsys\Lib\Database;

class CRM
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get General CRM Stats for Dashboard
     */
    public function getStats($date = null)
    {
        try {
            // Active Quotes (Presupuestado)
            $activeQuotes = $this->db->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'Presupuestado'")->fetchColumn();

            // Orders Today (Ganado today) - Use provided date or CURDATE()
            $dateFilter = $date ? $date : date('Y-m-d');
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM crm_leads WHERE status = 'Ganado' AND DATE(updated_at) = ?");
            $stmt->execute([$dateFilter]);
            $ordersToday = $stmt->fetchColumn();

            // Efficiency (Won / Total)
            $total = $this->db->query("SELECT COUNT(*) FROM crm_leads")->fetchColumn();
            $won = $this->db->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'Ganado'")->fetchColumn();
            $efficiency = $total > 0 ? round(($won / $total) * 100, 1) : 0;

            return [
                'active_quotes' => $activeQuotes ?: 0,
                'orders_today' => $ordersToday ?: 0,
                'efficiency' => $efficiency
            ];
        } catch (\Exception $e) {
            return [
                'active_quotes' => 0,
                'orders_today' => 0,
                'efficiency' => 0
            ];
        }
    }

    public function getLeadsStats()
    {
        try {
            $sql = "SELECT status, COUNT(*) as total FROM crm_leads GROUP BY status";
            return $this->db->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get specific Leads by status for the pipeline
     */
    public function getLeadsByStatus($status)
    {
        try {
            $sql = "SELECT * FROM crm_leads WHERE status = :status ORDER BY updated_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':status' => $status]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Save/Create a new lead
     */
    public function saveLead($data)
    {
        if (isset($data['id']) && $data['id'] > 0) {
            $sql = "UPDATE crm_leads SET 
                    name = :name, contact_person = :contact, email = :email, 
                    phone = :phone, status = :status, notes = :notes 
                    WHERE id = :id";
            $params = [
                ':name' => $data['name'],
                ':contact' => $data['contact_person'] ?? '',
                ':email' => $data['email'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':status' => $data['status'] ?? 'Nuevo',
                ':notes' => $data['notes'] ?? '',
                ':id' => $data['id']
            ];
        } else {
            $sql = "INSERT INTO crm_leads (name, contact_person, email, phone, status, notes) 
                    VALUES (:name, :contact, :email, :phone, :status, :notes)";
            $params = [
                ':name' => $data['name'],
                ':contact' => $data['contact_person'] ?? '',
                ':email' => $data['email'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':status' => $data['status'] ?? 'Nuevo',
                ':notes' => $data['notes'] ?? ''
            ];
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Log a new interaction and optionally sync with Lead Pipeline
     */
    public function logInteraction($entityId, $type, $description, $userId, $entityType = 'entity')
    {
        // 1. If it's an 'entity' (Client), check if we should create/link a Lead
        if ($entityType === 'entity') {
            $stmt = $this->db->prepare("SELECT name, contact_person, email, phone FROM entities WHERE id = ?");
            $stmt->execute([$entityId]);
            $ent = $stmt->fetch();

            if ($ent) {
                // Check if lead already exists based on name
                $stmtLead = $this->db->prepare("SELECT id FROM crm_leads WHERE name = ? LIMIT 1");
                $stmtLead->execute([$ent['name']]);
                $leadId = $stmtLead->fetchColumn();

                if (!$leadId) {
                    // Create Lead
                    $this->saveLead([
                        'name' => $ent['name'],
                        'contact_person' => $ent['contact_person'],
                        'email' => $ent['email'],
                        'phone' => $ent['phone'],
                        'status' => ($type === 'Presupuesto' || $type === 'Envío Presupuesto') ? 'Presupuestado' : 'Contactado',
                        'notes' => 'Auto-generado desde interacción con Cliente'
                    ]);
                } else {
                    // Update existing lead status
                    $newStatus = ($type === 'Presupuesto' || $type === 'Envío Presupuesto') ? 'Presupuestado' : 'Contactado';
                    $this->db->prepare("UPDATE crm_leads SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $leadId]);
                }
            }
        } elseif ($entityType === 'lead') {
            // Update existing lead status
            $newStatus = ($type === 'Presupuesto' || $type === 'Envío Presupuesto') ? 'Presupuestado' : 'Contactado';
            $this->db->prepare("UPDATE crm_leads SET status = ?, updated_at = NOW() WHERE id = ? AND status IN ('Nuevo', 'Contactado')")->execute([$newStatus, $entityId]);
        }

        $sql = "INSERT INTO crm_interactions (entity_id, entity_type, user_id, type, description, interaction_date) 
                VALUES (:eid, :etype, :uid, :type, :desc, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':eid' => $entityId,
            ':etype' => $entityType,
            ':uid' => $userId,
            ':type' => $type,
            ':desc' => $description
        ]);
    }

    /**
     * Get recent interactions
     */
    public function getRecentInteractions($limit = 10)
    {
        try {
            $sql = "SELECT i.*, 
                           i.type as interaction_type,
                           i.interaction_date as created_at,
                           CASE 
                            WHEN i.entity_type = 'entity' THEN (SELECT name FROM entities WHERE id = i.entity_id LIMIT 1)
                            WHEN i.entity_type = 'lead' THEN (SELECT name FROM crm_leads WHERE id = i.entity_id LIMIT 1)
                           END as client_name,
                           CASE 
                            WHEN i.entity_type = 'entity' THEN (SELECT name FROM entities WHERE id = i.entity_id LIMIT 1)
                            WHEN i.entity_type = 'lead' THEN (SELECT name FROM crm_leads WHERE id = i.entity_id LIMIT 1)
                           END as entity_name,
                           u.full_name as user_name 
                    FROM crm_interactions i 
                    LEFT JOIN users u ON i.user_id = u.id 
                    ORDER BY i.interaction_date DESC LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int) $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Move lead to next/prev stage
     */
    public function moveLead($id, $direction)
    {
        $statuses = ['Nuevo', 'Contactado', 'Presupuestado', 'Ganado', 'Perdido'];

        $stmt = $this->db->prepare("SELECT status FROM crm_leads WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();

        if (!$current)
            return false;

        $idx = array_search($current, $statuses);
        if ($direction === 'next' && $idx < count($statuses) - 1)
            $idx++;
        elseif ($direction === 'prev' && $idx > 0)
            $idx--;
        else
            return true; // No movement possible but ok

        $stmt = $this->db->prepare("UPDATE crm_leads SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$statuses[$idx], $id]);
    }
    /**
     * Get Sales Funnel Stats (30 Days)
     */
    public function getFunnelStats()
    {
        try {
            // 1. Clicks (Interactions of type 'Consulta Web' or public logs)
            $clicks = $this->db->query("SELECT COUNT(*) FROM crm_interactions 
                                        WHERE type = 'Consulta Web' 
                                        AND interaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

            // 2. Quoted (Quotations created)
            $quoted = $this->db->query("SELECT COUNT(*) FROM quotations 
                                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

            // 3. Sold (Confirmed Sales)
            // Check if is_confirmed exists, otherwise fallback to status
            $cols = $this->db->query("DESCRIBE quotations")->fetchAll(\PDO::FETCH_COLUMN);
            $soldSql = in_array('is_confirmed', $cols)
                ? "SELECT COUNT(*) FROM quotations WHERE is_confirmed = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
                : "SELECT COUNT(*) FROM quotations WHERE status = 'Aceptado' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $sold = $this->db->query($soldSql)->fetchColumn();

            return [
                'clicks' => $clicks ?: 0,
                'quoted' => $quoted ?: 0,
                'sold' => $sold ?: 0
            ];
        } catch (\Exception $e) {
            return ['clicks' => 0, 'quoted' => 0, 'sold' => 0];
        }
    }
}
PHP;

$pathCRM = __DIR__ . '/src/modules/crm/CRM.php';
writeFile($pathCRM, $contentCRM);

echo "<hr><p>Proceso de corrección SQL Completado. <a href='analisis.php'>Ver Análisis</a></p>";
?>