<?php
/**
 * VS System ERP - Optimized Price List Generator
 * Features: Thumbnails, Categorization, Dynamic Margins.
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once 'auth_check.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';

use Vsys\Modules\Catalogo\Catalog;
use Vsys\Modules\Config\PriceList;

$catalog = new Catalog();
$priceListModule = new PriceList();

$listName = $_GET['list'] ?? 'Gremio';
$lists = $priceListModule->getAll();
$activeList = null;
foreach ($lists as $l) {
    if ($l['name'] === $listName) {
        $activeList = $l;
        break;
    }
}
$margin = $activeList ? $activeList['margin_percent'] : 30;

$products = $catalog->getAllProducts();
// Group products by category
$grouped = [];
foreach ($products as $p) {
    if ($p['stock_current'] <= 0 && ($_GET['hide_no_stock'] ?? 0))
        continue;
    $cat = $p['category'] ?: 'General';
    $grouped[$cat][] = $p;
}
ksort($grouped);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Lista de Precios
        <?php echo $listName; ?> - VS System
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 40px;
            background: #fff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 3px solid #136dec;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            width: 180px;
        }

        .title-box {
            text-align: right;
        }

        .title-box h1 {
            margin: 0;
            color: #136dec;
            font-size: 24px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .title-box p {
            margin: 4px 0;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }

        .cat-section {
            margin-bottom: 40px;
            break-inside: avoid;
        }

        .cat-title {
            background: #f1f5f9;
            padding: 10px 15px;
            border-left: 4px solid #136dec;
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #94a3b8;
            padding: 10px 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
            vertical-align: middle;
        }

        .thumb {
            width: 45px;
            height: 45px;
            object-fit: contain;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
        }

        .sku {
            font-weight: 800;
            color: #136dec;
            font-size: 11px;
        }

        .desc {
            font-weight: 500;
            color: #1e293b;
            display: block;
            margin-top: 2px;
        }

        .price {
            font-weight: 900;
            font-size: 14px;
            text-align: right;
            white-space: nowrap;
        }

        .stock-pill {
            padding: 3px 8px;
            rounded: 4px;
            font-size: 9px;
            font-weight: 800;
        }

        .in-stock {
            color: #10b981;
        }

        .out-stock {
            color: #ef4444;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 10px;
            color: #cbd5e1;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @media print {
            body {
                padding: 20px;
            }

            .no-print {
                display: none;
            }

            td {
                padding: 8px 12px;
            }
        }
    </style>
</head>

<body>
    <div class="no-print"
        style="position: fixed; top: 20px; right: 20px; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; z-index: 100;">
        <p style="margin: 0 0 10px 0; font-size: 11px; font-weight: 800; color: #64748b;">OPCIONES DE LISTA</p>
        <div style="display: flex; gap: 10px;">
            <select onchange="location.href='?list='+this.value"
                style="padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 12px; font-weight: 600;">
                <?php foreach ($lists as $l): ?>
                    <option value="<?php echo $l['name']; ?>" <?php echo $listName == $l['name'] ? 'selected' : ''; ?>>
                        <?php echo $l['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button onclick="window.print()"
                style="background: #136dec; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 12px;">IMPRIMIR</button>
        </div>
    </div>

    <div class="header">
        <img src="https://vecinoseguro.com.ar/Logos/VSLogo.png" class="logo" alt="VS Logo">
        <div class="title-box">
            <h1>LISTA DE PRECIOS -
                <?php echo $listName; ?>
            </h1>
            <p>Emisión:
                <?php echo date('d/m/Y'); ?> | Precios en USD (Sujetos a cambio BNA)
            </p>
        </div>
    </div>

    <?php foreach ($grouped as $category => $items): ?>
        <div class="cat-section">
            <div class="cat-title">
                <?php echo $category; ?>
            </div>
            <table>
                <thead>
                    <tr>
                        <th width="60">Imagen</th>
                        <th width="120">Código SKU</th>
                        <th>Descripción del Producto</th>
                        <th width="80" style="text-align: center;">Stock</th>
                        <th width="100" style="text-align: right;">Precio final</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item):
                        $price = $item['unit_cost_usd'] * (1 + ($margin / 100));
                        $img = $item['image_url'] ?: 'https://vecinoseguro.com.ar/Logos/placeholder.png';
                        ?>
                        <tr>
                            <td><img src="<?php echo $img; ?>" class="thumb"
                                    onerror="this.src='https://vecinoseguro.com.ar/Logos/placeholder.png'"></td>
                            <td class="sku">
                                <?php echo $item['sku']; ?>
                            </td>
                            <td class="desc">
                                <?php echo $item['description']; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($item['stock_current'] > 0): ?>
                                    <span class="stock-pill in-stock">DISPONIBLE</span>
                                <?php else: ?>
                                    <span class="stock-pill out-stock">CONSULTAR</span>
                                <?php endif; ?>
                            </td>
                            <td class="price">USD
                                <?php echo number_format($price, 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <div class="footer">
        VS Sistemas by Javier Gozzi - 2026 | Vecino Seguro - www.vecinoseguro.com.ar
    </div>
</body>

</html>