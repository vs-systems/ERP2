<?php
/**
 * VS System ERP - Public Catalog
 */
session_start();
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/catalogo/Catalog.php';

require_once __DIR__ . '/src/modules/config/PriceList.php';

use Vsys\Modules\Catalogo\Catalog;
use Vsys\Modules\Config\PriceList;

$catalog = new Catalog();
$priceListModule = new PriceList();

$allProducts = $catalog->getAllProducts();

// Sort products: In stock first, then by SKU
usort($allProducts, function ($a, $b) {
    if (($a['stock_current'] ?? 0) > 0 && ($b['stock_current'] ?? 0) <= 0)
        return -1;
    if (($a['stock_current'] ?? 0) <= 0 && ($b['stock_current'] ?? 0) > 0)
        return 1;
    return strcmp($a['sku'], $b['sku']);
});

$categories = $catalog->getCategories();

// Fetch exchange rate
$db = Vsys\Lib\Database::getInstance();
$stmt = $db->query("SELECT rate FROM exchange_rates WHERE currency_to = 'ARS' ORDER BY fetched_at DESC LIMIT 1");
$currentRate = $stmt->fetchColumn() ?: 1455.00;

// Fetch unique brands for filtering
$brands = array_unique(array_filter(array_column($allProducts, 'brand')));
sort($brands);

// Check Maintenance Mode
$catConfig = json_decode(file_get_contents(__DIR__ . '/config_catalogs.json') ?: '{"maintenance_mode": 0}', true);
if (($catConfig['maintenance_mode'] ?? 0) && !isset($_SESSION['user_id'])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Sito en Mantenimiento - Vecino Seguro</title>
        <link rel="stylesheet" href="css/style_premium.css">
        <style>
            body {
                background: #020617;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                font-family: 'Inter', sans-serif;
                text-align: center;
            }

            .maint-container {
                max-width: 500px;
                padding: 2rem;
            }

            .maint-logo {
                width: 300px;
                margin-bottom: 2rem;
            }

            h1 {
                font-size: 2rem;
                margin-bottom: 1rem;
                color: #3b82f6;
            }

            p {
                color: #94a3b8;
            }
        </style>
    </head>

    <body>
        <div class="maint-container">
            <img src="src/img/VSLogo_v2.jpg" alt="Vecino Seguro" class="maint-logo">
            <h1>Sitio en mantenimiento</h1>
            <p>Por favor regrese en unos minutos. Gracias.</p>
        </div>
    </body>

    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - Vecino Seguro</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --card-bg: rgba(30, 41, 59, 0.4);
            --card-hover: rgba(30, 41, 59, 0.8);
        }

        .catalog-header {
            text-align: center;
            padding: 4rem 1rem;
            background: radial-gradient(circle at center, rgba(139, 92, 246, 0.15) 0%, transparent 70%);
        }

        .catalog-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(90deg, #f8fafc, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 2rem;
            justify-content: center;
            position: sticky;
            top: 80px;
            z-index: 900;
            background: rgba(2, 6, 23, 0.8);
            backdrop-filter: blur(12px);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .filter-item {
            min-width: 150px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .product-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
            <?php if (($p['stock_current'] ?? 0) <= 0)
                echo 'opacity: 0.7; filter: grayscale(0.5); background: rgba(15, 23, 42, 0.6);'; ?>
        }

        .product-card:hover {
            transform: translateY(-10px);
            background: var(--card-hover);
            border-color: var(--accent-violet);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            position: relative;
        }

        .product-image i {
            font-size: 4rem;
            color: var(--accent-violet);
            opacity: 0.3;
        }

        .product-brand {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--accent-blue);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .product-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }

        .product-sku {
            font-family: 'Courier New', Courier, monospace;
            background: rgba(255, 255, 255, 0.05);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            color: var(--text-muted);
            align-self: flex-start;
            margin-bottom: 1rem;
        }

        .product-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .product-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: #10b981;
        }

        .btn-whatsapp {
            background: #25d366;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-whatsapp:hover {
            background: #128c7e;
            transform: scale(1.05);
        }

        #no-results {
            text-align: center;
            padding: 5rem;
            display: none;
        }

        @media (max-width: 768px) {
            .catalog-header h1 {
                font-size: 2rem;
            }

            .filter-container {
                top: 70px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div
                style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.5rem; display: flex; flex-direction: column;">
                <span style="font-size: 1.8rem;">Vecino Seguro</span>
                <span
                    style="font-size: 0.9rem; font-weight: 600; color: #3b82f6; text-transform: uppercase; letter-spacing: 1px;">Catálogo
                    Tecnológico</span>
            </div>
        </div>
        <div class="header-right">
            <a href="cotizador.php" class="btn-primary"
                style="padding: 8px 15px; font-size: 0.8rem; text-decoration: none;">
                <i class="fas fa-sign-in-alt"></i> ACCESO ERP
            </a>
        </div>
    </header>

    <div class="catalog-header">
        <h1>Explora nuestra Tecnología</h1>
        <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto;">Equipamiento de seguridad electrónica de
            alta gama. Cámaras, NVRs y soluciones de videovigilancia profesional.</p>
    </div>

    <main class="content" style="max-width: 1400px; margin: 0 auto; padding-top: 0;">
        <div class="filter-container">
            <div class="filter-item">
                <input type="text" id="search-text" placeholder="Buscar producto..." style="margin-top:0;">
            </div>
            <div class="filter-item">
                <select id="filter-category" style="margin-top:0;">
                    <option value="">Todas las Categorías</option>
                    <?php
                    $catTree = $catalog->getCategoriesWithSubcategories();
                    foreach ($catTree as $cat => $subs):
                        ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"
                            class="font-bold bg-slate-200 text-black disabled" disabled>
                            — <?php echo htmlspecialchars($cat); ?> —
                        </option>
                        <option value="<?php echo htmlspecialchars($cat); ?>">
                            Todo en <?php echo htmlspecialchars($cat); ?>
                        </option>
                        <?php foreach ($subs as $sub): ?>
                            <option value="<?php echo htmlspecialchars($cat . '|' . $sub); ?>">
                                &nbsp;&nbsp;&nbsp;<?php echo htmlspecialchars($sub); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <select id="filter-brand" style="margin-top:0;">
                    <option value="">Todas las Marcas</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo htmlspecialchars($brand); ?>">
                            <?php echo htmlspecialchars($brand); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="no-results">
            <i class="fas fa-search" style="font-size: 3rem; color: var(--text-muted); opacity: 0.3;"></i>
            <h3>No encontramos productos que coincidan</h3>
            <p style="color: var(--text-muted);">Prueba con otros filtros o tó©rminos de bóºsqueda.</p>
        </div>

        <div class="product-grid" id="product-grid">
            <?php foreach ($allProducts as $p): ?>
                <div class="product-card" data-category="<?php echo htmlspecialchars($p['category']); ?>"
                    data-brand="<?php echo htmlspecialchars($p['brand']); ?>"
                    data-search="<?php echo htmlspecialchars(strtolower($p['sku'] . ' ' . $p['description'] . ' ' . $p['brand'])); ?>">

                    <div class="product-image">
                        <?php if (!empty($p['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($p['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($p['description']); ?>"
                                style="max-width: 100%; max-height: 100%; border-radius: 12px; object-fit: contain;">
                        <?php else: ?>
                            <img src="https://www.vecinoseguro.com/src/img/VSLogo_v2.jpg" alt="Falta imagen"
                                style="max-width: 80%; max-height: 80%; opacity: 0.5; filter: grayscale(1); border-radius: 12px; object-fit: contain;">
                        <?php endif; ?>
                    </div>

                    <span class="product-brand">
                        <?php echo htmlspecialchars($p['brand']); ?>
                    </span>
                    <h3 class="product-title">
                        <?php echo htmlspecialchars($p['description']); ?>
                    </h3>
                    <!-- Hidden Subcategory for JS -->
                    <span class="hidden product-subcategory"><?php echo htmlspecialchars($p['subcategory'] ?? ''); ?></span>
                    <span class="product-sku">
                        <?php echo htmlspecialchars($p['sku']); ?>
                    </span>

                    <!-- Semáforo de Stock -->
                    <?php
                    $stock = (int) ($p['stock_current'] ?? 0);
                    $stockColor = 'bg-red-500';
                    $statusText = 'Sin Stock';
                    if ($stock > 0) {
                        if ($stock <= 15)
                            $stockColor = 'bg-red-500';
                        elseif ($stock <= 50)
                            $stockColor = 'bg-yellow-500';
                        else
                            $stockColor = 'bg-green-500';
                        $statusText = $stock . ' unidades';
                    }

                    $cardOpacity = ($stock <= 0) ? 'opacity: 0.6; filter: grayscale(0.8);' : '';
                    ?>
                    <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                        <div
                            style="height: 6px; width: 40px; border-radius: 3px; background: rgba(255,255,255,0.1); overflow: hidden;">
                            <div style="height: 100%; width: 100%;" class="<?php echo $stockColor; ?>"></div>
                        </div>
                        <span
                            style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            <?php echo $statusText; ?>
                        </span>
                    </div>

                    <div class="product-footer">
                        <div class="product-price-container">
                            <?php
                            $cost = (float) $p['unit_cost_usd'];
                            $iva = (float) $p['iva_rate'];

                            // Determine which price to show
                            // Public (default): Mostrador
                            // Logged in (Admin/Vend/Logistica): Gremio + Web
                        
                            $isLoggedIn = isset($_SESSION['user_id']);
                            $priceMostradorArs = $priceListModule->getPriceByListName($cost, $iva, 'Mostrador', $currentRate, true);
                            ?>

                            <?php if ($isLoggedIn):
                                $priceGremioArs = $priceListModule->getPriceByListName($cost, $iva, 'Gremio', $currentRate, true);
                                $priceWebArs = $priceListModule->getPriceByListName($cost, $iva, 'Web', $currentRate, true);
                                ?>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <div
                                        style="font-size: 0.7rem; color: #10b981; opacity: 0.8; text-transform: uppercase; font-weight: bold;">
                                        Gremio (+IVA)
                                    </div>
                                    <div style="font-size: 1.1rem; font-weight: 700; color: #10b981;">
                                        $ <?php echo number_format($priceGremioArs, 0, ',', '.'); ?>
                                    </div>

                                    <div
                                        style="font-size: 0.7rem; color: #3b82f6; opacity: 0.8; text-transform: uppercase; font-weight: bold; margin-top: 4px;">
                                        Web (+IVA)
                                    </div>
                                    <div style="font-size: 1.1rem; font-weight: 700; color: #3b82f6;">
                                        $ <?php echo number_format($priceWebArs, 0, ',', '.'); ?>
                                    </div>

                                    <div
                                        style="font-size: 0.7rem; color: #f59e0b; opacity: 0.8; text-transform: uppercase; font-weight: bold; margin-top: 4px;">
                                        Mostrador (+IVA)
                                    </div>
                                    <div style="font-size: 1.1rem; font-weight: 700; color: #f59e0b;">
                                        $ <?php echo number_format($priceMostradorArs, 0, ',', '.'); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="product-price" style="display: flex; flex-direction: column;">
                                    <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Precio
                                        Lista</span>
                                    $ <?php echo number_format($priceMostradorArs, 0, ',', '.'); ?>
                                    <span style="font-size: 0.6rem; color: var(--text-muted); margin-top:2px;">(Lista
                                        Mostrador)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="https://wa.me/<?php echo COMPANY_WHATSAPP; ?>?text=<?php echo urlencode("Hola! Me interesa este producto: " . $p['sku'] . " - " . $p['description']); ?>"
                            target="_blank" class="btn-whatsapp"
                            onclick="logClick('<?php echo addslashes($p['sku']); ?>', '<?php echo addslashes($p['description']); ?>')">
                            <i class="fab fa-whatsapp"></i> Consultar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer
        style="text-align: center; padding: 4rem 1rem; color: var(--text-muted); border-top: 1px solid var(--border-color); margin-top: 4rem;">
        <p>&copy; 2026 Vecino Seguro - Seguridad Electró³nica by Javier Gozzi</p>
        <p style="font-size: 0.8rem; margin-top: 10px;">Los precios estó¡n sujetos a cambios sin previo aviso.</p>
    </footer>

    <script>
        const searchInput = document.getElementById('search-text');
        const categorySelect = document.getElementById('filter-category');
        const brandSelect = document.getElementById('filter-brand');
        const grid = document.getElementById('product-grid');
        const cards = Array.from(document.getElementsByClassName('product-card'));
        const noResults = document.getElementById('no-results');

        function filter() {
            const query = searchInput.value.toLowerCase();
            const category = categorySelect.value;
            const brand = brandSelect.value;
            let visibleCount = 0;

            if (categoryValue.includes('|')) {
                [selectedCat, selectedSub] = categoryValue.split('|');
            } else {
                selectedCat = categoryValue;
            }

            cards.forEach(card => {
                const text = card.dataset.search.toLowerCase();
                const cardCat = card.dataset.category;
                const cardSub = card.querySelector('.product-subcategory').innerText;
                const cardBrand = card.dataset.brand;

                const matchesSearch = text.includes(query);
                const matchesBrand = !brand || cardBrand === brand;

                let matchesCategory = true;
                if (selectedCat) {
                    if (selectedSub) {
                        matchesCategory = (cardCat === selectedCat && cardSub === selectedSub);
                    } else {
                        matchesCategory = (cardCat === selectedCat);
                    }
                }

                if (matchesSearch && matchesCategory && matchesBrand) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        function logClick(sku, desc) {
            fetch('ajax_log_catalog_click.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sku: sku, desc: desc })
            });
        }

        searchInput.addEventListener('input', filter);
        categorySelect.addEventListener('change', filter);
        brandSelect.addEventListener('change', filter);
    </script>
</body>

</html>