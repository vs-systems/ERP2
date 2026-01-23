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
$configPath = __DIR__ . '/config_catalogs.json';
$catConfig = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : ['maintenance_mode' => 0];
if (($catConfig['maintenance_mode'] ?? 0) == 1 && !isset($_SESSION['user_id'])) {
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
            <div class="flex items-center gap-6">
                <div
                    class="hidden md:flex gap-4 text-[10px] font-bold text-slate-300 uppercase tracking-tighter overflow-hidden max-w-[500px]">
                    <?php foreach (array_slice($brands, 0, 10) as $b): ?>
                        <span class="hover:text-violet-400 transition-colors cursor-default"><?php echo $b; ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="h-6 w-px bg-slate-700 mx-2"></div>
                <a href="login.php"
                    class="text-white hover:text-violet-400 flex items-center gap-2 font-bold text-xs transition-colors">
                    <span class="material-symbols-outlined text-sm">login</span>
                    ACCESO ERP
                </a>
                <div class="h-6 w-px bg-slate-700 mx-2"></div>
                <button class="relative text-white hover:text-violet-400 transition-colors" onclick="toggleCart()">
                    <span class="material-symbols-outlined text-[28px]">shopping_bag</span>
                    <span
                        class="absolute -top-1 -right-1 bg-accent-green text-black text-[10px] font-bold h-5 w-5 rounded-full flex items-center justify-center border-2 border-slate-900"
                        id="cartBadge">0</span>
                </button>
            </div>
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
                        <div class="flex gap-2">
                            <a href="https://wa.me/<?php echo COMPANY_WHATSAPP; ?>?text=<?php echo urlencode("Hola! Me interesa este producto: " . $p['sku'] . " - " . $p['description']); ?>"
                                target="_blank" class="btn-whatsapp"
                                onclick="logClick('<?php echo addslashes($p['sku']); ?>', '<?php echo addslashes($p['description']); ?>')">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <?php
                            // Prepare product data for JS
                            $pData = [
                                'sku' => $p['sku'],
                                'description' => $p['description'],
                                'image_url' => $p['image_url'],
                                'price_final_usd' => number_format($priceMostradorArs / $currentRate, 2, '.', '')
                            ];
                            ?>
                            <button onclick='addToCart(<?php echo json_encode($pData); ?>)'
                                class="bg-emerald-500 hover:bg-emerald-600 text-white flex-1 py-2 rounded-xl flex items-center justify-center gap-2 transition-all active:scale-95 shadow-lg shadow-emerald-500/10">
                                <span class="material-symbols-outlined text-lg">add_shopping_cart</span>
                                <span class="text-xs font-bold uppercase">Agregar</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Cart Sidebar / Modal -->
    <div id="overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[2000] hidden" onclick="toggleCart()"></div>
    <div id="cartModal"
        class="fixed right-0 top-0 h-full w-full max-w-md bg-[#111827] border-l border-[#233348] z-[2001] translate-x-full transition-transform duration-500 shadow-2xl flex flex-col">
        <div
            class="p-6 border-b border-[#233348] flex items-center justify-between bg-[#111827]/50 backdrop-blur sticky top-0">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[#3b82f6]">shopping_basket</span>
                <h3 class="text-lg font-bold">Tu Selección</h3>
            </div>
            <button onclick="toggleCart()" class="text-slate-400 hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-4" id="cartContent">
            <!-- Items injected by JS -->
        </div>

        <div class="p-6 border-t border-[#233348] bg-[#0d1117] space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-slate-400 font-medium">Subtotal estimado</span>
                <span class="text-2xl font-bold text-emerald-500" id="cartTotal">USD 0.00</span>
            </div>
            <button
                class="w-full bg-emerald-500 hover:bg-emerald-600 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-3 transition-all active:scale-[0.98] shadow-xl shadow-emerald-500/20"
                onclick="showCheckout()">
                CONTINUAR CON EL PEDIDO <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </div>
    </div>

    <footer
        style="text-align: center; padding: 4rem 1rem; color: var(--text-muted); border-top: 1px solid var(--border-color); margin-top: 4rem;">
        <p>&copy; 2026 Vecino Seguro - Seguridad Electró³nica by Javier Gozzi</p>
        <p style="font-size: 0.8rem; margin-top: 10px;">Los precios estó¡n sujetos a cambios sin previo aviso.</p>
    </footer>

    <script>
        const searchInput = document.getElementById('search-text');
        const categorySelect = document.getElementById('filter-category');
        const brandSelect = document.getElementById('filter-brand');
        const cards = Array.from(document.getElementsByClassName('product-card'));
        const noResults = document.getElementById('no-results');

        function filter() {
            const query = searchInput.value.toLowerCase();
            const categoryValue = categorySelect.value;
            const brand = brandSelect.value;
            let visibleCount = 0;

            let selectedCat = '';
            let selectedSub = '';
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

        // Carrito Logic
        let cart = [];

        function toggleCart() {
            const modal = document.getElementById('cartModal');
            const overlay = document.getElementById('overlay');
            modal.classList.toggle('translate-x-full');
            overlay.classList.toggle('hidden');
        }

        function addToCart(product) {
            const exists = cart.find(i => i.sku === product.sku);
            if (exists) exists.qty++;
            else cart.push({ ...product, qty: 1 });
            updateUI();
            if (!document.getElementById('cartModal').classList.contains('translate-x-full')) return;
            toggleCart();
        }

        function updateUI() {
            const badge = document.getElementById('cartBadge');
            const content = document.getElementById('cartContent');
            const total = document.getElementById('cartTotal');
            if (badge) badge.innerText = cart.reduce((acc, i) => acc + i.qty, 0);
            content.innerHTML = cart.length === 0 ? '<div class="h-64 flex flex-col items-center justify-center text-slate-500 gap-4"><span class="material-symbols-outlined text-5xl">shopping_cart_off</span><p class="font-medium text-sm">Tu carrito está vacío</p></div>' : '';
            let sum = 0;
            cart.forEach((item, idx) => {
                sum += parseFloat(item.price_final_usd) * item.qty;
                content.innerHTML += `
                    <div class="bg-[#16202e] border border-[#233348] p-4 rounded-xl flex gap-4 group">
                        <div class="h-16 w-16 bg-white p-2 rounded-lg flex items-center justify-center shrink-0">
                            <img src="${item.image_url}" class="max-h-full mix-blend-multiply">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white text-xs font-bold truncate">${item.description}</p>
                            <p class="text-emerald-500 text-xs font-bold mt-1">USD ${item.price_final_usd}</p>
                            <div class="flex items-center gap-3 mt-2">
                                <button onclick="changeQty(${idx}, -1)" class="h-6 w-6 flex items-center justify-center bg-[#0d1117] hover:bg-slate-800 rounded border border-[#233348] text-xs">-</button>
                                <span class="text-xs font-bold">${item.qty}</span>
                                <button onclick="changeQty(${idx}, 1)" class="h-6 w-6 flex items-center justify-center bg-[#0d1117] hover:bg-slate-800 rounded border border-[#233348] text-xs">+</button>
                                <button onclick="removeItem(${idx})" class="ml-auto text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"><span class="material-symbols-outlined text-lg">delete</span></button>
                            </div>
                        </div>
                    </div>
                `;
            });
            total.innerText = `USD ${sum.toFixed(2)}`;
        }

        function changeQty(idx, delta) {
            cart[idx].qty += delta;
            if (cart[idx].qty <= 0) cart.splice(idx, 1);
            updateUI();
        }

        function removeItem(idx) {
            cart.splice(idx, 1);
            updateUI();
        }

        function showCheckout() {
            if (cart.length === 0) return;
            let text = "Hola! Quiero realizar un pedido:\n\n";
            cart.forEach(item => {
                text += `- ${item.sku} | ${item.description} (Cant: ${item.qty}) | USD ${item.price_final_usd}\n`;
            });
            const total = document.getElementById('cartTotal').innerText;
            text += `\n*TOTAL ESTIMADO (DÓLARES): ${total}*`;
            const url = `https://wa.me/<?php echo COMPANY_WHATSAPP; ?>?text=${encodeURIComponent(text)}`;
            window.open(url, '_blank');
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