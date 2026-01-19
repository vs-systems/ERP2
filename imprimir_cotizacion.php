<?php
/**
 * VS System ERP - Print Quotation (Premium Refinement)
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';

use Vsys\Modules\Cotizador\Cotizador;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cot = new Cotizador();
$quote = $cot->getQuotation($id);
$items = $cot->getQuotationItems($id);

if (!$quote)
    die("Presupuesto no encontrado.");

// Calculate IVA discrimination
$iva21 = 0;
$iva105 = 0;
foreach ($items as $item) {
    if ($item['iva_rate'] == 21) {
        $iva21 += ($item['subtotal_usd'] * 0.21);
    } elseif ($item['iva_rate'] == 10.5) {
        $iva105 += ($item['subtotal_usd'] * 0.105);
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuesto <?php echo $quote['quote_number']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .page-container {
            padding: 40px;
            position: relative;
            min-height: 100vh;
            box-sizing: border-box;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            opacity: 0.05;
            z-index: -1;
            width: 500px;
            pointer-events: none;
        }

        .header-table {
            width: 100%;
            border-bottom: 3px solid #136dec;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            width: 220px;
        }

        .quote-info {
            text-align: right;
        }

        .quote-info h1 {
            margin: 0;
            color: #136dec;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .quote-info p {
            margin: 4px 0;
            font-size: 13px;
            color: #64748b;
        }

        .entity-grid {
            width: 100%;
            margin-bottom: 40px;
            border-collapse: collapse;
        }

        .entity-box {
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            width: 46%;
            vertical-align: top;
            font-size: 13px;
            line-height: 1.6;
        }

        .box-title {
            display: block;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 1px;
            color: #136dec;
            margin-bottom: 8px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .items-table th {
            background: #1e293b;
            color: #fff;
            padding: 12px 15px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }

        .totals-table {
            width: 320px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px 0;
            font-size: 13px;
        }

        .totals-table .label {
            color: #64748b;
            text-align: right;
            padding-right: 20px;
        }

        .totals-table .val {
            text-align: right;
            font-weight: 700;
            color: #1e293b;
        }

        .total-row td {
            padding-top: 15px !important;
            border-top: 2px solid #136dec;
            font-size: 20px !important;
            font-weight: 900 !important;
            color: #136dec !important;
        }

        .ars-row td {
            font-size: 24px !important;
            color: #10b981 !important;
            padding-top: 5px !important;
        }

        .footer {
            margin-top: auto;
            border-top: 1px solid #e2e8f0;
            padding-top: 25px;
            font-size: 11px;
            color: #94a3b8;
        }

        .obs-box {
            background: #fff;
            border: 1px dashed #cbd5e1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .system-footer {
            text-align: center;
            margin-top: 40px;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #cbd5e1;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }

            .page-container {
                padding: 30px;
            }

            .watermark {
                opacity: 0.03;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 100;">
        <button onclick="window.print()"
            style="background: #136dec; color: #fff; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(19, 109, 236, 0.4); display: flex; items-center: center; gap: 8px;">
            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 012-2H5a2 2 0 012 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            GUARDAR PDF
        </button>
    </div>

    <div class="page-container">
        <!-- Watermark -->
        <img src="https://vecinoseguro.com.ar/Logos/VSLogo.png" class="watermark" alt="VS Watermark">

        <table class="header-table">
            <tr>
                <td class="quote-info" style="text-align: left;">
                    <h1>PRESUPUESTO</h1>
                    <p>Referencia: <strong>#<?php echo $quote['quote_number']; ?></strong></p>
                    <p>Fecha de Emisión: <strong><?php echo date('d/m/Y', strtotime($quote['created_at'])); ?></strong>
                    </p>
                    <p>Validez de Oferta: <strong>48 Horas</strong></p>
                </td>
                <td style="text-align: right;"><img src="https://vecinoseguro.com.ar/Logos/VSLogo.png" class="logo"
                        alt="Vecino Seguro"></td>
            </tr>
        </table>

        <table class="entity-grid">
            <tr>
                <td class="entity-box">
                    <span class="box-title">PROVEEDOR</span>
                    <strong>Vecino Seguro</strong><br>
                    Sarmiento 1113 4to Piso, CABA<br>
                    CUIT: 30-71644781-4<br>
                    Contacto: javier@vecinoseguro.com.ar
                </td>
                <td width="8%"></td>
                <td class="entity-box">
                    <span class="box-title">CLIENTE</span>
                    <strong><?php echo $quote['client_name']; ?></strong><br>
                    <?php echo $quote['tax_id'] ? "ID Fiscal: " . $quote['tax_id'] . "<br>" : ""; ?>
                    <?php echo $quote['address'] ? $quote['address'] . "<br>" : ""; ?>
                    Tel: <?php echo $quote['phone']; ?>
                    <?php if (!empty($quote['preferred_transport'])): ?>
                        <br><span style="color: #64748b; font-size: 11px;">Transporte Preferido:
                            <strong><?php echo $quote['preferred_transport']; ?></strong></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="8%" class="text-center">CANT.</th>
                    <th width="15%">SKU</th>
                    <th>DESCRIPCIÓN</th>
                    <th width="8%" class="text-center">IVA %</th>
                    <th width="15%" class="text-right">UNIT. USD</th>
                    <th width="15%" class="text-right">SUBTOTAL USD</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td style="font-weight: 700;"><?php echo $item['sku']; ?></td>
                        <td><?php echo $item['description']; ?></td>
                        <td class="text-center"><?php echo number_format($item['iva_rate'], 1); ?>%</td>
                        <td class="text-right">$ <?php echo number_format($item['unit_price_usd'], 2); ?></td>
                        <td class="text-right" style="font-weight: 700;">$
                            <?php echo number_format($item['subtotal_usd'], 2); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal (Neto):</td>
                    <td class="val">USD <?php echo number_format($quote['subtotal_usd'], 2); ?></td>
                </tr>
                <?php if ($iva105 > 0): ?>
                    <tr>
                        <td class="label">IVA 10.5%:</td>
                        <td class="val">USD <?php echo number_format($iva105, 2); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($iva21 > 0): ?>
                    <tr>
                        <td class="label">IVA 21%:</td>
                        <td class="val">USD <?php echo number_format($iva21, 2); ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td class="label">TOTAL USD:</td>
                    <td class="val">$ <?php echo number_format($quote['total_usd'], 2); ?></td>
                </tr>
                <tr>
                    <td class="label" style="font-size: 11px; padding-top: 10px;">TC BNA (Venta):</td>
                    <td class="val" style="font-size: 11px; padding-top: 10px;">$
                        <?php echo number_format($quote['exchange_rate_usd'], 2); ?>
                    </td>
                </tr>
                <tr class="ars-row">
                    <td class="label">TOTAL ARS:</td>
                    <td class="val">$ <?php echo number_format($quote['total_ars'], 0, ',', '.'); ?></td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <div class="obs-box">
                <p
                    style="margin: 0; font-weight: 800; font-size: 9px; uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 5px;">
                    NOTAS IMPORTANTES:</p>
                <p style="margin: 0; line-height: 1.4;">Los precios en pesos están sujetos a cambios sin previo aviso
                    según la cotización del dólar BNA Billete Venta del día de pago.
                    Forma de pago:
                    <strong><?php echo $quote['payment_method'] == 'bank' ? 'Transferencia Bancaria' : 'Contado / Efectivo'; ?></strong>.
                </p>
                <?php if (!empty($quote['observations'])): ?>
                    <p style="margin-top: 10px; margin-bottom: 0;"><strong>Obs:</strong>
                        <?php echo $quote['observations']; ?></p>
                <?php endif; ?>
            </div>
            <p style="text-align: center; margin-top: 20px; font-weight: 700; color: #64748b;">¡Gracias por confiar en
                el equipo de Vecino Seguro!</p>
        </div>

        <div class="system-footer">
            VS Sistemas by Javier Gozzi - 2026
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.location.search.includes('autoprint')) {
                window.print();
            }
        });
    </script>
</body>

</html>