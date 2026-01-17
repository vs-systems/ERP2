<?php
/**
 * Catálogo Público - VS System
 * No Authentication Required
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/config/PriceList.php';
require_once __DIR__ . '/src/modules/catalogo/PublicCatalog.php';

use Vsys\Modules\Catalogo\PublicCatalog;

$publicCatalog = new PublicCatalog();
$data = $publicCatalog->getProductsForWeb();
$products = $data['products'];
$exchangeRate = $data['rate'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo Online - Vecino Seguro Sistemas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #d946ef;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            padding-bottom: 80px;
            /* Space for mobile cart */
        }

        /* Header */
        header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid #334155;
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(139, 92, 246, 0.5);
        }

        .cart-icon-header {
            position: relative;
            cursor: pointer;
            font-size: 1.2rem;
            color: white;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }

        /* Filters */
        .search-bar {
            margin: 2rem 5%;
            display: flex;
            gap: 1rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid #334155;
            background: var(--card-bg);
            color: white;
            font-size: 1rem;
        }

        /* Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            padding: 0 5%;
        }

        .product-card {
            background: var(--card-bg);
            border: 1px solid #334155;
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .product-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #020617;
        }

        .product-info {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-brand {
            color: var(--accent);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: auto;
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            margin: 1rem 0;
        }

        .btn-add {
            width: 100%;
            padding: 0.8rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-add:hover {
            background: var(--primary-dark);
        }

        /* Cart Sidebar/Modal */
        .cart-modal {
            position: fixed;
            top: 0;
            right: -400px;
            width: 100%;
            max-width: 400px;
            height: 100vh;
            background: #0f172a;
            border-left: 1px solid #334155;
            z-index: 1000;
            transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.5);
        }

        .cart-modal.open {
            right: 0;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid #334155;
            padding-bottom: 1rem;
        }

        .cart-items {
            flex-grow: 1;
            overflow-y: auto;
            margin-bottom: 1rem;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .cart-item-info h4 {
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }

        .cart-item-price {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .cart-actions button {
            background: #334155;
            border: none;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 4px;
            cursor: pointer;
        }

        .cart-summary {
            border-top: 1px solid #334155;
            padding-top: 1rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .checkout-form {
            display: none;
            /* Hidden until checkout click */
        }

        .checkout-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 6px;
            border: 1px solid #334155;
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
            z-index: 900;
            display: none;
        }

        .overlay.active {
            display: block;
        }
    </style>
</head>

<body>

    <header>
        <div class="logo">VS SYSTEMS <span style="font-weight:400; font-size: 1rem;">| SHOP</span></div>
        <div class="cart-icon-header" onclick="toggleCart()">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-badge" id="cartBadge">0</span>
        </div>
    </header>

    <div class="search-bar">
        <input type="text" class="search-input" placeholder="Buscar productos..." id="searchInput">
    </div>

    <div class="product-grid" id="productGrid">
        <?php foreach ($products as $p): ?>
            <div class="product-card" data-title="<?php echo strtolower($p['description']); ?>"
                data-sku="<?php echo strtolower($p['sku']); ?>" data-brand="<?php echo strtolower($p['brand']); ?>">
                <img src="<?php echo $p['image_url']; ?>" alt="<?php echo $p['description']; ?>" class="product-img"
                    loading="lazy">
                <div class="product-info">
                    <div class="product-brand">
                        <?php echo $p['brand']; ?>
                    </div>
                    <h3 class="product-title">
                        <?php echo $p['description']; ?>
                    </h3>
                    <div class="product-price">$
                        <?php echo $p['price_final_formatted']; ?>
                    </div>
                    <button class="btn-add" onclick='addToCart(<?php echo json_encode($p); ?>)'>
                        AGREGAR AL CARRITO
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleCart()"></div>

    <!-- Cart Modal -->
    <div class="cart-modal" id="cartModal">
        <div class="cart-header">
            <h3>Tu Carrito</h3>
            <button onclick="toggleCart()"
                style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;">&times;</button>
        </div>

        <div class="cart-items" id="cartItems">
            <!-- Items injected here -->
        </div>

        <div class="cart-summary">
            <div class="total-row">
                <span>Total:</span>
                <span id="cartTotal">$ 0</span>
            </div>

            <div id="checkoutActions">
                <button class="btn-add" style="background: var(--accent);" onclick="showCheckoutForm()">
                    INICIAR COMPRA
                </button>
            </div>

            <div class="checkout-form" id="checkoutForm">
                <h4 style="margin-bottom: 10px; color: var(--primary);">Datos de Contacto</h4>
                <input type="text" id="custName" placeholder="Nombre completo" required>
                <input type="text" id="custDni" placeholder="DNI / CUIT" required>
                <input type="tel" id="custPhone" placeholder="WhatsApp (con código de área)" required>
                <input type="email" id="custEmail" placeholder="Email" required>
                <button class="btn-add" onclick="submitOrder()">CONFIRMAR PEDIDO</button>
                <button onclick="hideCheckoutForm()"
                    style="width: 100%; margin-top: 5px; padding: 5px; background: none; border: 1px solid #334155; color: #94a3b8; border-radius: 8px; cursor: pointer;">Volver</button>
            </div>

            <div id="orderSuccess" style="display: none; text-align: center; color: #4ade80;">
                <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>¡Pedido enviado con éxito!</p>
                <p style="font-size: 0.9rem; color: #94a3b8; margin-top: 5px;">Te contactaremos a la brevedad.</p>
                <button onclick="clearCartAndClose()"
                    style="margin-top: 15px; padding: 10px; background: #334155; border: none; color: white; border-radius: 8px; cursor: pointer;">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        // State
        let cart = JSON.parse(localStorage.getItem('vsys_cart')) || [];

        // --- Functions ---

        function saveCart() {
            localStorage.setItem('vsys_cart', JSON.stringify(cart));
            renderCart();
            updateBadge();
        }

        function addToCart(product) {
            const existing = cart.find(item => item.sku === product.sku);
            if (existing) {
                existing.quantity++;
            } else {
                cart.push({
                    sku: product.sku,
                    title: product.description,
                    price: product.price_final_ars,
                    priceFormatted: product.price_final_formatted,
                    quantity: 1
                });
            }
            saveCart();
            toggleCart(true); // Open cart on add
        }

        function removeFromCart(sku) {
            cart = cart.filter(item => item.sku !== sku);
            saveCart();
        }

        function updateQuantity(sku, change) {
            const item = cart.find(i => i.sku === sku);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    removeFromCart(sku);
                } else {
                    saveCart();
                }
            }
        }

        function renderCart() {
            const container = document.getElementById('cartItems');
            container.innerHTML = '';
            let total = 0;

            if (cart.length === 0) {
                container.innerHTML = '<p style="text-align:center; color: #64748b; margin-top: 2rem;">El carrito está vacío.</p>';
                document.getElementById('checkoutActions').style.display = 'none';
            } else {
                document.getElementById('checkoutActions').style.display = 'block';
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;

                    const div = document.createElement('div');
                    div.className = 'cart-item';
                    div.innerHTML = `
                        <div class="cart-item-info">
                            <h4>${item.title}</h4>
                            <div class="cart-item-price">$ ${new Intl.NumberFormat('es-AR').format(item.price)} x ${item.quantity}</div>
                        </div>
                        <div class="cart-actions" style="display:flex; gap: 5px; align-items: center;">
                            <button onclick="updateQuantity('${item.sku}', -1)">-</button>
                            <span style="font-size: 0.9rem; min-width: 20px; text-align: center;">${item.quantity}</span>
                            <button onclick="updateQuantity('${item.sku}', 1)">+</button>
                        </div>
                    `;
                    container.appendChild(div);
                });
            }

            document.getElementById('cartTotal').innerText = '$ ' + new Intl.NumberFormat('es-AR').format(total);
        }

        function updateBadge() {
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cartBadge').innerText = count;
        }

        function toggleCart(forceOpen = false) {
            const modal = document.getElementById('cartModal');
            const overlay = document.getElementById('overlay');
            if (forceOpen || !modal.classList.contains('open')) {
                modal.classList.add('open');
                overlay.classList.add('active');
            } else {
                modal.classList.remove('open');
                overlay.classList.remove('active');
            }
        }

        function showCheckoutForm() {
            document.getElementById('checkoutActions').style.display = 'none';
            document.getElementById('checkoutForm').style.display = 'block';
        }

        function hideCheckoutForm() {
            document.getElementById('checkoutActions').style.display = 'block';
            document.getElementById('checkoutForm').style.display = 'none';
        }

        function submitOrder() {
            const name = document.getElementById('custName').value.trim();
            const dni = document.getElementById('custDni').value.trim();
            const phone = document.getElementById('custPhone').value.trim();
            const email = document.getElementById('custEmail').value.trim();

            if (!name || !dni || !phone || !email) {
                alert('Por favor complete todos los datos de contacto.');
                return;
            }

            if (cart.length === 0) {
                alert('El carrito está vacío.');
                return;
            }

            const payload = {
                customer: { name, dni, phone, email },
                cart: cart,
                total: cart.reduce((acc, item) => acc + (item.price * item.quantity), 0)
            };

            // Loading state
            const btn = document.querySelector('#checkoutForm button');
            const originalText = btn.innerText;
            btn.innerText = 'Enviando...';
            btn.disabled = true;

            fetch('ajax_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Success
                        cart = [];
                        saveCart();
                        document.getElementById('checkoutForm').style.display = 'none';
                        document.getElementById('orderSuccess').style.display = 'block';
                    } else {
                        alert('Error al enviar pedido: ' + data.message);
                        btn.innerText = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error de conexión.');
                    btn.innerText = originalText;
                    btn.disabled = false;
                });
        }

        function clearCartAndClose() {
            document.getElementById('orderSuccess').style.display = 'none';
            toggleCart(); // Close
            document.getElementById('checkoutActions').style.display = 'block'; // Reset for next time
        }

        // Search Filter
        document.getElementById('searchInput').addEventListener('input', function (e) {
            const q = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                const text = card.getAttribute('data-title') + ' ' + card.getAttribute('data-sku') + ' ' + card.getAttribute('data-brand');
                if (text.includes(q)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Initialize
        renderCart();
        updateBadge();

    </script>
</body>

</html>