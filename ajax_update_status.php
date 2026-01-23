<?php
/**
 * VS System ERP - AJAX Update Status (Confirmed / Paid)
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/billing/Billing.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';

use Vsys\Modules\Billing\Billing;
use Vsys\Modules\Cotizador\Cotizador;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$type = $input['type'] ?? ''; // 'quotation' or 'purchase'
$field = $input['field'] ?? ''; // 'is_confirmed' or 'payment_status'
$value = $input['value'] ?? null;

if (!$id || !$type || !$field) {
    echo json_encode(['success' => false, 'error' => 'Paró¡metros incompletos']);
    exit;
}

try {
    $db = Vsys\Lib\Database::getInstance();
    $table = ($type === 'quotation') ? 'quotations' : 'purchases';

    $sql = "UPDATE $table SET $field = :val WHERE id = :id";
    $stmt = $db->prepare($sql);
    $res = $stmt->execute(['val' => $value, 'id' => $id]);

    // If marking as paid, also ensure it's confirmed
    if ($field === 'payment_status' && $value === 'Pagado') {
        $db->prepare("UPDATE $table SET is_confirmed = 1 WHERE id = ?")->execute([$id]);

        // --- CRM AUTOMATION ---
        if ($type === 'quotation') {
            // 1. Fetch quote details
            $stmtQ = $db->prepare("SELECT quote_number, client_id FROM quotations WHERE id = ?");
            $stmtQ->execute([$id]);
            $quote = $stmtQ->fetch();

            if ($quote) {
                // 2. Find and Update CRM Lead to 'Ganado'
                // Search by name (linked in CRM.php logInteraction) or other link
                $stmtC = $db->prepare("SELECT name FROM entities WHERE id = ?");
                $stmtC->execute([$quote['client_id']]);
                $clientName = $stmtC->fetchColumn();

                if ($clientName) {
                    $db->prepare("UPDATE crm_leads SET status = 'Ganado', updated_at = NOW() WHERE name = ?")
                        ->execute([$clientName]);

                    // Log interaction in CRM
                    $userId = $_SESSION['user_id'] ?? 0;
                    $db->prepare("INSERT INTO crm_interactions (entity_id, entity_type, user_id, type, description, interaction_date) 
                                 SELECT id, 'lead', ?, 'Venta', ?, NOW() FROM crm_leads WHERE name = ? LIMIT 1")
                        ->execute([$userId, "Pedido #{$quote['quote_number']} marcado como cobrado.", $clientName]);
                }

                // --- LOGISTICS AUTOMATION ---
                // Initialize in 'En reserva' when paid/confirmed
                $db->prepare("INSERT INTO logistics_process (quote_number, current_phase) 
                             VALUES (?, 'En reserva') 
                             ON DUPLICATE KEY UPDATE updated_at = NOW()")
                    ->execute([$quote['quote_number']]);

                // --- CURRENT ACCOUNT INTEGRATION (Payment) ---
                // Register a Credit (Haber) movement in the current account
                require_once __DIR__ . '/src/modules/billing/CurrentAccounts.php';
                $currentAccounts = new \Vsys\Modules\Billing\CurrentAccounts();

                // Fetch full quote details for total_ars
                $stmtFull = $db->prepare("SELECT total_ars FROM quotations WHERE id = ?");
                $stmtFull->execute([$id]);
                $totalArs = $stmtFull->fetchColumn();

                if ($totalArs > 0) {
                    // Check if a payment for this quote was already registered to avoid duplicates
                    $stmtCheck = $db->prepare("SELECT id FROM client_movements WHERE reference_id = ? AND type = 'Recibo'");
                    $stmtCheck->execute([$id]);
                    if (!$stmtCheck->fetch()) {
                        $currentAccounts->addMovement(
                            $quote['client_id'],
                            'Recibo',
                            $id,
                            $totalArs,
                            "Cobro automático de Presupuesto #{$quote['quote_number']}"
                        );
                    }
                }
            }
        }
    }

    // Sync status and is_confirmed for quotations
    if ($type === 'quotation') {
        if ($field === 'status') {
            $isConfirmed = ($value === 'Aceptado' || $value === 'Pedido') ? 1 : 0;
            $db->prepare("UPDATE quotations SET is_confirmed = ? WHERE id = ?")->execute([$isConfirmed, $id]);
            $triggerBilling = ($isConfirmed == 1);
        } elseif ($field === 'is_confirmed') {
            $status = ($value == 1) ? 'Aceptado' : 'Pendiente';
            $db->prepare("UPDATE quotations SET status = ? WHERE id = ?")->execute([$status, $id]);
            $triggerBilling = ($value == 1);
        }

        // --- AUTOMATIC BILLING & CURRENT ACCOUNT ---
        if (isset($triggerBilling) && $triggerBilling) {
            // Check if already invoiced to avoid duplicates
            $stmtCheck = $db->prepare("SELECT id FROM invoices WHERE quote_id = ?");
            $stmtCheck->execute([$id]);
            if (!$stmtCheck->fetch()) {
                $cotizador = new Cotizador();
                $quote = $cotizador->getQuotation($id);
                $items = $cotizador->getQuotationItems($id);

                if ($quote) {
                    $billing = new Billing();

                    // Prepare items for billing module
                    $billItems = [];
                    foreach ($items as $item) {
                        $billItems[] = [
                            'description' => $item['description'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price_usd'],
                            'iva_rate' => $item['iva_rate'],
                            'subtotal' => $item['subtotal_usd']
                        ];
                    }

                    $billingData = [
                        'client_id' => $quote['client_id'],
                        'quote_id' => $id,
                        'type' => 'X', // 'X' for internal/proforma
                        'date' => date('Y-m-d'),
                        'total_net' => $quote['subtotal_usd'],
                        'total_iva' => $quote['total_usd'] - $quote['subtotal_usd'],
                        'total_amount' => $quote['total_usd'],
                        'currency' => 'USD',
                        'exchange_rate' => $quote['exchange_rate_usd'],
                        'items' => $billItems,
                        'notes' => "Generada automáticamente desde Presupuesto {$quote['quote_number']}"
                    ];

                    // For Current Account, we use ARS if it's the primary tracking currency
                    // But our createInvoice uses total_amount for addMovement.
                    // Let's modify the total_amount to ARS if we want the movement in ARS.
                    // Actually, let's keep the invoice in USD but the MOVEMENT in ARS.
                    // To do this, I need to modify Billing.php or pass ARS to addMovement manually.

                    // Improved approach: Pass ARS to Billing so it uses it for the movement.
                    $billingData['total_amount_ars'] = $quote['total_ars'];

                    $billing->createInvoice($billingData);
                }
            }
        }
    }

    if ($type === 'purchase' && $field === 'is_confirmed' && $value == 1) {
        require_once __DIR__ . '/src/modules/billing/ProviderAccounts.php';
        require_once __DIR__ . '/src/modules/purchases/Purchases.php';
        $purchasesModule = new \Vsys\Modules\Purchases\Purchases();
        $purchase = $purchasesModule->getPurchase($id);

        if ($purchase) {
            $checkStmt = $db->prepare("SELECT id FROM provider_movements WHERE reference_id = ? AND type = 'Compra'");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                $providerAccounts = new \Vsys\Modules\Billing\ProviderAccounts();
                // Ensure we use total_ars and correct entity_id
                $amount = $purchase['total_ars'] ?? 0;
                if ($amount > 0) {
                    $providerAccounts->addMovement($purchase['provider_id'] ?? $purchase['entity_id'], 'Compra', $id, $amount, "Compra #{$purchase['purchase_number']}");
                }
            }
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}





