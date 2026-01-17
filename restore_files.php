<?php
// restore_files.php - Restauración de Archivos Críticos v12 (Filters & Analytics)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

echo "<h1>Restaurador de Archivos Críticos v12 (Filters & Analytics)</h1>";

function writeFile($path, $content)
{
    echo "<p>Escribiendo: $path ... ";
    $dir = dirname($path);
    if (!is_dir($dir))
        mkdir($dir, 0755, true);
    if (file_exists($path))
        unlink($path);
    if (file_put_contents($path, $content) !== false) {
        echo "<span style='color:green'> [OK] </span></p>";
        if (function_exists('opcache_invalidate'))
            opcache_invalidate($path, true);
        return true;
    } else {
        echo "<span style='color:red'> [ERROR] </span></p>";
        return false;
    }
}

// 1. src/modules/catalogo/PublicCatalog.php
$contentPublicCat = <<<'PHP'
<?php
namespace Vsys\Modules\Catalogo;

use Vsys\Lib\Database;
use Vsys\Modules\Config\PriceList;

class PublicCatalog
{
    private $db;
    private $priceListModule;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->priceListModule = new PriceList();
    }

    public function getExchangeRate()
    {
        $stmt = $this->db->prepare("SELECT rate FROM exchange_rates ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $rate = $stmt->fetchColumn();
        return $rate ? (float) $rate : 1455.00;
    }

    public function getFilterOptions()
    {
        $brands = $this->db->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand")->fetchAll(\PDO::FETCH_COLUMN);
        $categories = $this->db->query("SELECT DISTINCT name FROM categories ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);
        $subcategories = $this->db->query("SELECT DISTINCT subcategory FROM products WHERE subcategory IS NOT NULL AND subcategory != '' ORDER BY subcategory")->fetchAll(\PDO::FETCH_COLUMN);

        return [
            'brands' => $brands,
            'categories' => $categories,
            'subcategories' => $subcategories
        ];
    }

    public function getProductsForWeb()
    {
        $rate = $this->getExchangeRate();
        $lists = $this->priceListModule->getAll();
        $webMargin = 40; 
        foreach ($lists as $l) {
            if ($l['name'] === 'Web') {
                $webMargin = (float) $l['margin_percent'];
                break;
            }
        }

        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.brand, p.description";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $products = $stmt->fetchAll();

        $webProducts = [];
        foreach ($products as $p) {
            $cost = (float) $p['unit_cost_usd'];
            $iva = (float) $p['iva_rate'];
            $priceUsd = $cost * (1 + ($webMargin / 100));
            $priceUsdWithIva = $priceUsd * (1 + ($iva / 100));
            $priceArs = $priceUsdWithIva * $rate;

            if ($priceArs > 0) {
                $p['price_final_ars'] = round($priceArs, 0); 
                $p['price_final_formatted'] = number_format($p['price_final_ars'], 0, ',', '.');
                $p['image_url'] = !empty($p['image_url']) ? $p['image_url'] : 'https://placehold.co/300x300?text=No+Image';
                $p['category_name'] = $p['category_name'] ?? 'General';
                $webProducts[] = $p;
            }
        }

        return [
            'rate' => $rate,
            'products' => $webProducts
        ];
    }
}
PHP;
writeFile(__DIR__ . '/src/modules/catalogo/PublicCatalog.php', $contentPublicCat);

// 2. catalogo_publico.php
$contentFrontend = <<<'PHP'
<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';
require_once __DIR__ . '/src/modules/catalogo/PublicCatalog.php';

use Vsys\Modules\Catalogo\PublicCatalog;

$publicCatalog = new PublicCatalog();
$data = $publicCatalog->getProductsForWeb();
$filters = $publicCatalog->getFilterOptions();
$products = $data['products'];
$exchangeRate = $data['rate'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo Online - Vecino Seguro Sistemas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #8b5cf6; --primary-dark: #7c3aed; --bg: #0f172a; --card-bg: #1e293b; --text: #f8fafc; --text-muted: #94a3b8; --accent: #d946ef; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text); padding-bottom: 2rem; }
        
        header { 
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #334155; padding: 1rem 5%; 
            display: flex; justify-content: center; align-items: center; position: relative;
        }
        .logo { font-size: 1.5rem; font-weight: 800; background: linear-gradient(to right, var(--primary), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .cart-icon-header { position: absolute; right: 5%; cursor: pointer; font-size: 1.2rem; }
        .cart-badge { position: absolute; top: -8px; right: -8px; background: var(--accent); color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; }

        .filters-section { margin: 2rem 5%; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .filter-group select, .search-input { width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid #334155; background: var(--card-bg); color: white; font-size: 0.9rem; }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem; padding: 0 5% 5rem 5%; }
        .product-card { background: var(--card-bg); border: 1px solid #334155; border-radius: 16px; overflow: hidden; transition: transform 0.2s; display: flex; flex-direction: column; }
        .product-card:hover { transform: translateY(-5px); border-color: var(--primary); }
        .product-img { width: 100%; height: 200px; object-fit: cover; background: #020617; }
        .product-info { padding: 1.5rem; flex-grow: 1; display: flex; flex-direction: column; }
        .product-brand { color: var(--accent); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem; }
        .product-title { font-size: 1.1rem; font-weight: 600; margin-bottom: auto; line-height: 1.4; }
        .product-price { font-size: 1.5rem; font-weight: 800; color: #fff; margin: 1rem 0 0.5rem 0; }
        .iva-label { font-size: 0.75rem; color: #10b981; font-weight: 600; margin-bottom: 1rem; }
        .btn-add { width: 100%; padding: 0.8rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }

        /* Cart Modal */
        .cart-modal { position: fixed; top: 0; right: -400px; width: 100%; max-width: 400px; height: 100vh; background: #0f172a; border-left: 1px solid #334155; z-index: 1000; transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1); padding: 2rem; display: flex; flex-direction: column; box-shadow: -10px 0 30px rgba(0,0,0,0.5); }
        .cart-modal.open { right: 0; }
        .cart-items { flex-grow: 1; overflow-y: auto; margin: 1rem 0; }
        .total-row { display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 800; margin-bottom: 1.5rem; border-top: 1px solid #334155; padding-top: 1rem; }
        .checkout-form { display: none; }
        .checkout-form input { width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #334155; background: rgba(255,255,255,0.05); color: white; }

        footer { border-top: 1px solid #334155; padding: 2rem 5%; text-align: center; color: var(--text-muted); font-size: 0.9rem; margin-top: 3rem; }
        footer a { color: var(--primary); text-decoration: none; margin: 0 10px; font-weight: 600; }
    </style>
</head>
<body>
    <header>
        <div class="logo">VECINO SEGURO SISTEMAS</div>
        <div class="cart-icon-header" onclick="toggleCart()">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-badge" id="cartBadge">0</span>
        </div>
    </header>

    <div class="filters-section">
        <input type="text" class="search-input" placeholder="Buscar productos..." id="searchInput">
        <select id="brandFilter">
            <option value="">Todas las Marcas</option>
            <?php foreach($filters['brands'] as $b): ?><option value="<?php echo strtolower($b); ?>"><?php echo $b; ?></option><?php endforeach; ?>
        </select>
        <select id="categoryFilter">
            <option value="">Todas las Categorías</option>
            <?php foreach($filters['categories'] as $c): ?><option value="<?php echo strtolower($c); ?>"><?php echo $c; ?></option><?php endforeach; ?>
        </select>
        <select id="subcatFilter">
            <option value="">Todas las Subcategorías</option>
            <?php foreach($filters['subcategories'] as $s): ?><option value="<?php echo strtolower($s); ?>"><?php echo $s; ?></option><?php endforeach; ?>
        </select>
    </div>

    <div class="product-grid" id="productGrid">
        <?php foreach ($products as $p): ?>
            <div class="product-card" 
                 data-title="<?php echo strtolower($p['description']); ?>" 
                 data-sku="<?php echo strtolower($p['sku']); ?>" 
                 data-brand="<?php echo strtolower($p['brand']); ?>"
                 data-cat="<?php echo strtolower($p['category_name']); ?>"
                 data-subcat="<?php echo strtolower($p['subcategory'] ?? ''); ?>">
                <img src="<?php echo $p['image_url']; ?>" class="product-img" loading="lazy">
                <div class="product-info">
                    <div class="product-brand"><?php echo $p['brand']; ?></div>
                    <h3 class="product-title"><?php echo $p['description']; ?></h3>
                    <div class="product-price">$ <?php echo $p['price_final_formatted']; ?></div>
                    <div class="iva-label"><i class="fas fa-check-circle"></i> IVA INCLUIDO</div>
                    <button class="btn-add" onclick='addToCart(<?php echo json_encode($p); ?>)'>AGREGAR AL CARRITO</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <footer>
        <p>VS Gestion by Javier Gozzi</p>
        <div style="margin-top: 10px;">
            <a href="https://wa.me/5492235772165" target="_blank"><i class="fab fa-whatsapp"></i> +5492235772165</a>
            <a href="mailto:javier@vecinoseguro.com.ar"><i class="far fa-envelope"></i> javier@vecinoseguro.com.ar</a>
        </div>
    </footer>

    <div class="cart-modal" id="cartModal">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
            <h3>Tu Carrito</h3>
            <button onclick="toggleCart()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="cart-items" id="cartItems"></div>
        <div class="total-row"><span>Total:</span><span id="cartTotal">$ 0</span></div>
        <div id="checkoutActions">
            <p style="text-align:center; font-size:0.8rem; color:var(--text-muted); margin-bottom:1rem;">Precios con IVA incluido.</p>
            <button class="btn-add" style="background:var(--accent);" onclick="showCheckoutForm()">INICIAR COMPRA</button>
        </div>
        <div class="checkout-form" id="checkoutForm">
            <h4 style="margin-bottom:10px; color:var(--primary);">Datos de Contacto</h4>
            <input type="text" id="custName" placeholder="Nombre completo">
            <input type="tel" id="custPhone" placeholder="WhatsApp">
            <input type="email" id="custEmail" placeholder="Email">
            <button class="btn-add" onclick="submitOrder()">CONFIRMAR PEDIDO</button>
            <button onclick="hideCheckoutForm()" style="width:100%; margin-top:5px; background:none; border:none; color:var(--text-muted); cursor:pointer;">Volver</button>
        </div>
        <div id="orderSuccess" style="display:none; text-align:center;">
            <i class="fas fa-check-circle" style="font-size:3rem; color:#10b981; margin-bottom:1rem;"></i>
            <p>¡Pedido enviado con éxito!</p>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-top:10px;">se corrobora stock y se pondran en contacto con Ud a la brevedad.</p>
            <button onclick="location.reload()" style="margin-top:20px; padding:10px; background:var(--card-bg); border:1px solid #334155; color:white; border-radius:8px; cursor:pointer;">Cerrar</button>
        </div>
    </div>

    <script>
        let cart = JSON.parse(localStorage.getItem('vsys_cart')) || [];
        function saveCart() { localStorage.setItem('vsys_cart', JSON.stringify(cart)); renderCart(); updateBadge(); }
        function updateBadge() { document.getElementById('cartBadge').innerText = cart.reduce((sum, item) => sum + item.quantity, 0); }
        function toggleCart() { document.getElementById('cartModal').classList.toggle('open'); }
        function showCheckoutForm() { document.getElementById('checkoutActions').style.display='none'; document.getElementById('checkoutForm').style.display='block'; }
        function hideCheckoutForm() { document.getElementById('checkoutActions').style.display='block'; document.getElementById('checkoutForm').style.display='none'; }
        
        function addToCart(p) {
            const ext = cart.find(i => i.sku === p.sku);
            if (ext) ext.quantity++;
            else cart.push({ sku: p.sku, title: p.description, price: p.price_final_ars, quantity: 1 });
            saveCart(); toggleCart();
        }

        function renderCart() {
            const container = document.getElementById('cartItems');
            container.innerHTML = '';
            let total = 0;
            cart.forEach(item => {
                total += item.price * item.quantity;
                container.innerHTML += `<div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.9rem;">
                    <div>${item.title}<br><small>$ ${new Intl.NumberFormat('es-AR').format(item.price)} x ${item.quantity}</small></div>
                    <button onclick="removeFromCart('${item.sku}')" style="background:none; border:none; color:#ef4444; cursor:pointer;"><i class="fas fa-trash"></i></button>
                </div>`;
            });
            document.getElementById('cartTotal').innerText = '$ ' + new Intl.NumberFormat('es-AR').format(total);
        }
        function removeFromCart(sku) { cart = cart.filter(i => i.sku !== sku); saveCart(); }

        function submitOrder() {
            const payload = {
                customer: { name: document.getElementById('custName').value, phone: document.getElementById('custPhone').value, email: document.getElementById('custEmail').value },
                cart: cart, total: cart.reduce((a, b) => a + (b.price * b.quantity), 0)
            };
            fetch('ajax_checkout.php', { method: 'POST', body: JSON.stringify(payload) })
            .then(r => r.json()).then(d => {
                if (d.status === 'success') { 
                    cart = []; saveCart(); 
                    document.getElementById('checkoutForm').style.display = 'none';
                    document.getElementById('orderSuccess').style.display = 'block';
                }
            });
        }

        // Filters Search
        function applyFilters() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            const brand = document.getElementById('brandFilter').value;
            const cat = document.getElementById('categoryFilter').value;
            const subcat = document.getElementById('subcatFilter').value;
            
            document.querySelectorAll('.product-card').forEach(card => {
                const matchesSearch = card.getAttribute('data-title').includes(q) || card.getAttribute('data-sku').includes(q);
                const matchesBrand = brand === '' || card.getAttribute('data-brand') === brand;
                const matchesCat = cat === '' || card.getAttribute('data-cat') === cat;
                const matchesSubcat = subcat === '' || card.getAttribute('data-subcat') === subcat;
                card.style.display = (matchesSearch && matchesBrand && matchesCat && matchesSubcat) ? 'flex' : 'none';
            });
        }
        document.querySelectorAll('.filters-section input, .filters-section select').forEach(el => el.addEventListener('change', applyFilters));
        document.getElementById('searchInput').addEventListener('input', applyFilters);

        renderCart(); updateBadge();
    </script>
</body>
</html>
PHP;
writeFile(__DIR__ . '/catalogo_publico.php', $contentFrontend);

// 3. index.php
$contentIndex = <<<'PHP'
<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = Vsys\Lib\Database::getInstance();
$rate = $db->query("SELECT rate FROM exchange_rates ORDER BY id DESC LIMIT 1")->fetchColumn() ?: 1455.00;

// CRM Stats for Pie Chart
$pendingLeads = $db->query("SELECT COUNT(*) FROM crm_leads WHERE status IN ('Nuevo', 'Contactado')")->fetchColumn() ?: 0;
$quotedLeads = $db->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'Presupuestado'")->fetchColumn() ?: 0;
$wonLeads = $db->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'Ganado'")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        header { background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: center; align-items: center; padding: 15px 20px; position: relative; }
        .usd-tag { position: absolute; left: 20px; background: #1e293b; padding: 5px 12px; border-radius: 20px; border: 1px solid #8b5cf6; font-size: 0.85rem; color: #f8fafc; font-weight: 600; }
        .header-title { color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem; letter-spacing: 1px; }
        .header-title span { background: linear-gradient(90deg, #8b5cf6, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        footer { border-top: 1px solid #334155; padding: 2rem; text-align: center; color: #94a3b8; font-size: 0.85rem; margin-top: 2rem; width: 100%; }
        footer a { color: #8b5cf6; text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>
    <header>
        <div class="usd-tag"><i class="fas fa-dollar-sign"></i> Dólar: $ <?php echo number_format($rate, 2); ?></div>
        <div class="header-title">VECINO SEGURO <span>SISTEMAS</span></div>
    </header>

    <div class="dashboard-container">
        <nav class="sidebar">
            <a href="index.php" class="nav-link active"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="analisis.php" class="nav-link"><i class="fas fa-chart-line"></i> ANÁLISIS OP.</a>
            <a href="productos.php" class="nav-link"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
            <a href="catalogo_publico.php" class="nav-link" target="_blank" style="color: #25d366;"><i class="fas fa-external-link-alt"></i> VER CATÁLOGO</a>
        </nav>

        <main class="content">
            <?php
            require_once __DIR__ . '/src/modules/analysis/OperationAnalysis.php';
            $analysis = new \Vsys\Modules\Analysis\OperationAnalysis();
            $summary = $analysis->getDashboardSummary();
            ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                <h1>Control Operativo</h1>
                <div style="color:var(--accent-violet); font-weight:600;">Bienvenido Admin</div>
            </div>

            <div class="grid-3">
                <div class="card">
                    <h3>Ventas Totales</h3>
                    <div class="metric" style="color:var(--accent-blue);">USD <?php echo number_format($summary['total_sales'], 2); ?></div>
                </div>
                <div class="card">
                    <h3>Compras Totales</h3>
                    <div class="metric" style="color:#ef4444;">USD <?php echo number_format($summary['total_purchases'], 2); ?></div>
                </div>
                <div class="card">
                    <h3>Conversión CRM</h3>
                    <div class="metric" style="color:#10b981;"><?php echo $summary['effectiveness']; ?>%</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 2rem;">
                <div class="card">
                    <h3>Evolución de Operaciones</h3>
                    <canvas id="mainChart" style="max-height: 300px;"></canvas>
                </div>
                <div class="card">
                    <h3>Pendientes CRM</h3>
                    <canvas id="crmPieChart" style="max-height: 250px;"></canvas>
                </div>
            </div>

            <footer>
                <p>VS Gestion by Javier Gozzi</p>
                <div>
                    <a href="https://wa.me/5492235772165" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                    <a href="mailto:javier@vecinoseguro.com.ar"><i class="far fa-envelope"></i> Email</a>
                </div>
            </footer>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctxMain = document.getElementById('mainChart').getContext('2d');
        new Chart(ctxMain, {
            type: 'bar',
            data: {
                labels: ['Ventas', 'Compras', 'Resultado'],
                datasets: [{
                    data: [<?php echo $summary['total_sales']; ?>, <?php echo $summary['total_purchases']; ?>, <?php echo $summary['total_profit']; ?>],
                    backgroundColor: ['rgba(99, 102, 241, 0.5)', 'rgba(239, 68, 68, 0.5)', 'rgba(16, 185, 129, 0.5)'],
                    borderColor: ['#6366f1', '#ef4444', '#10b981'],
                    borderWidth: 1
                }]
            },
            options: { plugins: { legend: { display: false } } }
        });

        const ctxPie = document.getElementById('crmPieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Pendientes', 'Presupuestados', 'Ganados'],
                datasets: [{
                    data: [<?php echo $pendingLeads; ?>, <?php echo $quotedLeads; ?>, <?php echo $wonLeads; ?>],
                    backgroundColor: ['#f59e0b', '#3b82f6', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: { plugins: { legend: { position: 'bottom', labels: { color: 'white', font: { size: 10 } } } } }
        });
    </script>
</body>
</html>
PHP;
writeFile(__DIR__ . '/index.php', $contentIndex);

// 4. crm.php
$contentCRM = <<<'PHP'
<?php
/**
 * CRM Dashboard - Pipeline View - Standard Layout
 */
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';

use Vsys\Modules\CRM\CRM;

$crm = new CRM();
$stats = $crm->getStats();
$db = Vsys\Lib\Database::getInstance();
$rate = $db->query("SELECT rate FROM exchange_rates ORDER BY id DESC LIMIT 1")->fetchColumn() ?: 1455.00;

// Fetch leads
$leadsNuevo = $crm->getLeadsByStatus('Nuevo');
$leadsContactado = $crm->getLeadsByStatus('Contactado');
$leadsPresupuesto = $crm->getLeadsByStatus('Presupuestado');
$leadsGanado = $crm->getLeadsByStatus('Ganado');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRM Pipeline - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        header { background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: center; align-items: center; padding: 15px 20px; position: relative; }
        .usd-tag { position: absolute; left: 20px; background: #1e293b; padding: 5px 12px; border-radius: 20px; border: 1px solid #8b5cf6; font-size: 0.85rem; color: #f8fafc; font-weight: 600; }
        .header-title { color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem; letter-spacing: 1px; }
        .header-title span { background: linear-gradient(90deg, #8b5cf6, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .pipeline-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; height: calc(100vh - 250px); min-height: 500px; }
        .pipeline-col { background: rgba(30, 41, 59, 0.5); border-radius: 12px; padding: 10px; display: flex; flex-direction: column; }
        .col-header { padding: 10px; font-weight: bold; border-bottom: 2px solid #334155; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .col-header .count { background: #334155; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
        .pipeline-col.nuevo .col-header { border-color: #8b5cf6; color: #8b5cf6; }
        .pipeline-col.contactado .col-header { border-color: #f59e0b; color: #f59e0b; }
        .pipeline-col.presupuesto .col-header { border-color: #3b82f6; color: #3b82f6; }
        .pipeline-col.ganado .col-header { border-color: #10b981; color: #10b981; }

        .cards-container { flex-grow: 1; overflow-y: auto; padding-right: 5px; }
        .lead-card { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 12px; margin-bottom: 10px; cursor: pointer; transition: transform 0.2s; position: relative; }
        .lead-card:hover { transform: translateY(-2px); border-color: #8b5cf6; }
        .lead-name { font-weight: 600; margin-bottom: 5px; color: #f8fafc; }
        .lead-contact { font-size: 0.85rem; color: #94a3b8; }
        .lead-source { font-size: 0.70rem; background: #334155; padding: 2px 6px; border-radius: 4px; display: inline-block; color: #cbd5e1; }
        .lead-date { position: absolute; top: 10px; right: 10px; font-size: 0.7rem; color: #64748b; }
        
        .crm-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .crm-stat-card { background: #1e293b; padding: 1rem; border-radius: 8px; border: 1px solid #334155; }
        .crm-stat-val { font-size: 1.8rem; font-weight: 800; color: #f8fafc; }
        .crm-stat-label { font-size: 0.85rem; color: #94a3b8; }

        footer { border-top: 1px solid #334155; padding: 2rem; text-align: center; color: #94a3b8; font-size: 0.85rem; margin-top: 2rem; width: 100%; }
        footer a { color: #8b5cf6; text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>
    <header>
        <div class="usd-tag"><i class="fas fa-dollar-sign"></i> Dólar: $ <?php echo number_format($rate, 2); ?></div>
        <div class="header-title">VECINO SEGURO <span>SISTEMAS</span></div>
    </header>

    <div class="dashboard-container">
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="analisis.php" class="nav-link"><i class="fas fa-chart-line"></i> ANÁLISIS OP.</a>
            <a href="productos.php" class="nav-link"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link active"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
            <a href="catalogo_publico.php" class="nav-link" target="_blank" style="color: #25d366; font-weight: 700;"><i
                    class="fas fa-external-link-alt"></i> VER CATÁLOGO</a>
        </nav>

        <main class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h1><i class="fas fa-funnel-dollar" style="color: #8b5cf6; margin-right: 10px;"></i> CRM Pipeline</h1>
                <button onclick="location.reload()" class="btn-primary" style="background: #334155;"><i class="fas fa-sync-alt"></i> Actualizar</button>
            </div>

            <div class="crm-stats">
                <div class="crm-stat-card"><div class="crm-stat-val" style="color: #3b82f6;"><?php echo $stats['active_quotes']; ?></div><div class="crm-stat-label">Presupuestos Activos</div></div>
                <div class="crm-stat-card"><div class="crm-stat-val" style="color: #10b981;"><?php echo $stats['orders_today']; ?></div><div class="crm-stat-label">Pedidos de Hoy</div></div>
                <div class="crm-stat-card"><div class="crm-stat-val" style="color: #8b5cf6;"><?php echo $stats['efficiency']; ?>%</div><div class="crm-stat-label">Eficiencia de Cierre</div></div>
            </div>

            <div class="pipeline-container">
                <div class="pipeline-col nuevo"><div class="col-header"><span>NUEVO</span> <span class="count"><?php echo count($leadsNuevo); ?></span></div><div class="cards-container"><?php foreach ($leadsNuevo as $l) renderCard($l); ?></div></div>
                <div class="pipeline-col contactado"><div class="col-header"><span>CONTACTADO</span> <span class="count"><?php echo count($leadsContactado); ?></span></div><div class="cards-container"><?php foreach ($leadsContactado as $l) renderCard($l); ?></div></div>
                <div class="pipeline-col presupuesto"><div class="col-header"><span>PRESUPUESTADO</span> <span class="count"><?php echo count($leadsPresupuesto); ?></span></div><div class="cards-container"><?php foreach ($leadsPresupuesto as $l) renderCard($l); ?></div></div>
                <div class="pipeline-col ganado"><div class="col-header"><span>GANADO</span> <span class="count"><?php echo count($leadsGanado); ?></span></div><div class="cards-container"><?php foreach ($leadsGanado as $l) renderCard($l); ?></div></div>
            </div>

            <footer>
                <p>VS Gestion by Javier Gozzi</p>
                <div>
                    <a href="https://wa.me/5492235772165" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                    <a href="mailto:javier@vecinoseguro.com.ar"><i class="far fa-envelope"></i> Email</a>
                </div>
            </footer>
        </main>
    </div>
    <script>
    function moveLead(id, direction) { alert('Funcionalidad de mover en desarrollo.'); }
    function renderCard(lead) { /* Handled in PHP */ }
    </script>
</body>
</html>
<?php
function renderCard($lead) {
    if (!isset($lead['id'])) return;
    $date = date('d/m', strtotime($lead['created_at']));
    $source = isset($lead['source']) ? $lead['source'] : 'Manual';
    echo "
    <div class='lead-card' onclick='alert(\"Leads Details shown in CRM module.\")'>
        <div class='lead-date'>$date</div>
        <div class='lead-name'>{$lead['name']}</div>
        <div class='lead-contact'>
            <i class='fas fa-envelope'></i> ".($lead['email']??'')."<br>
            <i class='fas fa-phone'></i> ".($lead['phone']??'')."
        </div>
        <div class='lead-source'>$source</div>
        <div class='actions' style='margin-top:10px; text-align:right;'>
            <button class='btn-move' onclick='event.stopPropagation(); moveLead({$lead['id']}, \"next\")'>Mover ></button>
        </div>
    </div>
    ";
}
?>
PHP;
writeFile(__DIR__ . '/crm.php', $contentCRM);

echo "<hr><p>¡Actualización v12 Completa! Filtros, Dashboard Analytics, CRM y Footer.</p>";
?>