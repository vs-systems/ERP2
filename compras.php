<?php
require_once 'auth_check.php';
/**
 * VS System ERP - Gestión de Compras
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/purchases/Purchases.php';

use Vsys\Modules\Purchases\Purchases;

$purchasesModule = new Purchases();
$purchaseNumber = $purchasesModule->generatePurchaseNumber();
$exchangeRate = 1480; // Default/Fetched rate

// Fetch suppliers for the dropdown or search
$db = Vsys\Lib\Database::getInstance();
$suppliers = $db->query("SELECT id, name, fantasy_name FROM entities WHERE type IN ('supplier', 'provider') ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Compras - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .purchase-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 2rem;
        }

        .item-search-container {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #1e293b;
            border: 1px solid var(--accent-violet);
            border-radius: 8px;
            z-index: 1001;
            max-height: 250px;
            overflow-y: auto;
            display: none;
        }

        .search-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .search-item:hover {
            background: rgba(139, 92, 246, 0.2);
        }

        .total-box {
            background: var(--gradient-premium);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: right;
            margin-top: 2rem;
        }

        .total-box h2 {
            margin: 0;
            font-size: 2rem;
        }
    </style>
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" alt="VS System" class="logo-large" style="height: 50px; width: auto;">
            <div
                style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem; letter-spacing: 1px; text-shadow: 0 0 10px rgba(139, 92, 246, 0.4);">
                Vecino Seguro <span
                    style="background: linear-gradient(90deg, #8b5cf6, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Sistemas</span>
                by Javier Gozzi - 2026
            </div>
        </div>
        <div class="header-right" style="color: #cbd5e1;">
            <span class="user-badge"><i class="fas fa-user-circle"></i> Admin</span>
        </div>
    </header>

    <div class="dashboard-container">
        <nav class="sidebar">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="analisis.php" class="nav-link"><i class="fas fa-chart-line"></i> AN&Aacute;LISIS OP.</a>
            <a href="productos.php" class="nav-link"><i class="fas fa-box-open"></i> PRODUCTOS</a>
            <a href="presupuestos.php" class="nav-link"><i class="fas fa-history"></i> PRESUPUESTOS</a>
            <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link active"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
        </nav>

        <main class="content">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3><i class="fas fa-plus-circle"></i> Nueva Orden de Compra</h3>
                    <span class="badge" style="background: var(--accent-violet);">
                        <?php echo $purchaseNumber; ?>
                    </span>
                </div>

                <form id="purchase-form">
                    <div class="purchase-header">
                        <div class="form-group" style="position: relative;">
                            <label>Proveedor (Buscar por nombre)</label>
                            <input type="text" id="supplier-search" placeholder="Escriba para buscar proveedor..."
                                autocomplete="off">
                            <input type="hidden" name="entity_id" id="entity_id" required>
                            <div id="supplier-results" class="search-dropdown" style="top: 100%;"></div>
                        </div>
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="date" name="purchase_date" id="purchase_date"
                                value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Estado Entrega</label>
                            <select name="status" id="status">
                                <option value="Pendiente">Pendiente</option>
                                <option value="En Camino">En Camino</option>
                                <option value="Recibido">Recibido</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Estado Pago</label>
                            <select name="payment_status" id="payment_status">
                                <option value="Pendiente">Pendiente</option>
                                <option value="Pagado">Pagado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tasa de Cambio (ARS/USD)</label>
                            <input type="number" step="0.01" name="exchange_rate_usd" id="exchange_rate_usd"
                                value="<?php echo $exchangeRate; ?>" onchange="updateExchangeRate(this.value)" required>
                        </div>
                        <div style="display: flex; align-items: center; padding-top: 1.5rem;">
                            <label class="toggle"><input type="checkbox" name="is_confirmed" id="is_confirmed" checked>
                                Confirmada</label>
                        </div>
                    </div>

                    <div class="item-search-container">
                        <label><i class="fas fa-search"></i> Buscar Producto (SKU o Nombre)</label>
                        <input type="text" id="product-search" placeholder="Empiece a escribir para buscar productos..."
                            autocomplete="off">
                        <div id="search-results" class="search-results"></div>
                    </div>

                    <div class="table-responsive">
                        <table id="items-table">
                            <thead>
                                <tr>
                                    <th width="80">Cant.</th>
                                    <th width="120">SKU</th>
                                    <th>Descripci&oacute;n</th>
                                    <th width="120" style="text-align: right;">Unit. ARS</th>
                                    <th width="120" style="text-align: right;">Unit. USD</th>
                                    <th width="100" style="text-align: center;">IVA</th>
                                    <th width="120" style="text-align: right;">Total USD</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody id="items-tbody">
                                <!-- Items will be added here -->
                            </tbody>
                        </table>
                    </div>

                    <div class="total-box">
                        <div
                            style="display: flex; justify-content: flex-end; gap: 40px; margin-bottom: 10px; opacity: 0.8;">
                            <span>Subtotal: <strong id="subtotal-display">USD 0.00</strong></span>
                            <span>IVA 10.5%: <strong id="iva105-display">USD 0.00</strong></span>
                            <span>IVA 21%: <strong id="iva21-display">USD 0.00</strong></span>
                        </div>
                        <p style="margin:0; opacity: 0.8;">TOTAL ESTIMADO (BRUTO)</p>
                        <h2 id="grand-total">USD 0.00</h2>
                        <small>ARS <span id="total-ars-display">0.00</span> (TC: <input type="number" step="0.01"
                                id="exchange-rate-input" value="<?php echo $exchangeRate; ?>"
                                style="width: 80px; background: transparent; border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; color: white; padding: 2px 5px; text-align: center; font-weight: bold;">)</small>
                    </div>

                    <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" class="btn-primary" style="background: #1e293b;"
                            onclick="location.reload()">
                            <i class="fas fa-redo"></i> LIMPIAR
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> GUARDAR COMPRA
                        </button>
                    </div>
                </form>
            </div>

            <div style="margin-top: 1.5rem;">
                <label style="color: var(--text-muted); font-size: 0.9rem;"><i class="fas fa-comment-alt"></i>
                    Observaciones de la Compra</label>
                <textarea id="purchase-observations"
                    placeholder="Ej: Referencia OC del proveedor, Notas de envío, etc..."
                    style="width: 100%; height: 60px; background: rgba(255,255,255,0.05); border: 1px solid var(--accent-violet); border-radius: 8px; color: white; padding: 10px; font-family: inherit; margin-top: 5px;"></textarea>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <h3><i class="fas fa-history"></i> Historial de Compras Recientes</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nro Orden</th>
                                <th>Proveedor</th>
                                <th>Fecha</th>
                                <th style="text-align: right;">Total USD</th>
                                <th style="text-align: center;">Confirmada</th>
                                <th style="text-align: center;">Pago</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $history = $purchasesModule->getAllPurchases();
                            foreach ($history as $p):
                                ?>
                                <tr>
                                    <td><strong><?php echo $p['purchase_number']; ?></strong></td>
                                    <td><?php echo $p['supplier_name']; ?></td>
                                    <td><?php echo $p['purchase_date']; ?></td>
                                    <td style="text-align: right; font-weight: 600;">
                                        $<?php echo number_format($p['total_usd'], 2); ?></td>
                                    <td style="text-align: center;">
                                        <button class="btn-secondary"
                                            onclick="toggleStatus(<?php echo $p['id']; ?>, 'purchase', 'is_confirmed', <?php echo ($p['is_confirmed'] ?? 0) ? 0 : 1; ?>)"
                                            style="color: <?php echo ($p['is_confirmed'] ?? 0) ? '#10b981' : '#64748b'; ?>; background: transparent; border: none; cursor: pointer;">
                                            <i
                                                class="fas <?php echo ($p['is_confirmed'] ?? 0) ? 'fa-check-circle' : 'fa-circle'; ?>"></i>
                                        </button>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge"
                                            style="background: <?php echo ($p['payment_status'] ?? 'Pendiente') === 'Pagado' ? '#8b5cf6' : '#f59e0b'; ?>; cursor: pointer;"
                                            onclick="toggleStatus(<?php echo $p['id']; ?>, 'purchase', 'payment_status', '<?php echo ($p['payment_status'] ?? 'Pendiente') === 'Pagado' ? 'Pendiente' : 'Pagado'; ?>')">
                                            <?php echo $p['payment_status'] ?? 'Pendiente'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 10px;">
                                            <a href="imprimir_compra.php?id=<?php echo $p['id']; ?>" target="_blank"
                                                class="btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;"
                                                title="Ver PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            <button class="btn-secondary"
                                                style="padding: 5px 10px; font-size: 0.8rem; color: #ef4444;"
                                                onclick="deletePurchase(<?php echo $p['id']; ?>)" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        let items = [];
        let currentExchangeRate = <?php echo $exchangeRate; ?>;

        document.getElementById('exchange-rate-input').addEventListener('change', function () {
            currentExchangeRate = parseFloat(this.value) || 0;
            renderTable();
        });

        // Supplier search logic
        const supplierSearchInput = document.getElementById('supplier-search');
        const supplierResultsDiv = document.getElementById('supplier-results');
        const entityIdInput = document.getElementById('entity_id');

        supplierSearchInput.addEventListener('input', function () {
            const q = this.value;
            if (q.length < 2) {
                supplierResultsDiv.style.display = 'none';
                return;
            }

            fetch(`ajax_search_suppliers.php?q=${q}`)
                .then(r => r.json())
                .then(data => {
                    supplierResultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(s => {
                            const div = document.createElement('div');
                            div.className = 'search-item';
                            div.innerHTML = `<strong>${s.name}</strong> ${s.fantasy_name ? `(${s.fantasy_name})` : ''}`;
                            div.onclick = () => selectSupplier(s);
                            supplierResultsDiv.appendChild(div);
                        });
                        supplierResultsDiv.style.display = 'block';
                    } else {
                        supplierResultsDiv.style.display = 'none';
                    }
                });
        });

        function selectSupplier(supplier) {
            supplierSearchInput.value = supplier.name + (supplier.fantasy_name ? ` (${supplier.fantasy_name})` : '');
            entityIdInput.value = supplier.id;
            supplierResultsDiv.style.display = 'none';
        }

        // Product search logic
        const searchInput = document.getElementById('product-search');
        const resultsDiv = document.getElementById('search-results');

        searchInput.addEventListener('input', function () {
            const q = this.value;
            if (q.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }

            fetch(`ajax_search_products.php?q=${q}`)
                .then(r => r.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(p => {
                            const div = document.createElement('div');
                            div.className = 'search-item';
                            div.innerHTML = `<strong>${p.sku}</strong> - ${p.description} <span style="float:right; color:var(--accent-blue)">$${p.cost_usd}</span>`;
                            div.onclick = () => addItem(p);
                            resultsDiv.appendChild(div);
                        });
                    }

                    // Always show "Add New" option if there's text
                    const addDiv = document.createElement('div');
                    addDiv.className = 'search-item';
                    addDiv.style.borderTop = '1px solid var(--accent-violet)';
                    addDiv.style.background = 'rgba(16, 185, 129, 0.1)';
                    addDiv.innerHTML = `<i class="fas fa-plus-circle"></i> <strong>Crear Nuevo: "${q}"</strong>`;
                    addDiv.onclick = () => quickAddProduct(q);
                    resultsDiv.appendChild(addDiv);

                    resultsDiv.style.display = 'block';
                });
        });

        function quickAddProduct(sku) {
            const desc = prompt("Ingrese descripción para el nuevo producto:", "");
            if (!desc) return;
            const cost = prompt("Ingrese costo unitario USD:", "0.00");
            if (cost === null) return;

            const newProd = {
                id: 'new-' + Date.now(),
                sku: sku.toUpperCase(),
                description: desc,
                cost_usd: parseFloat(cost) || 0
            };
            addItem(newProd);
        }

        let currentExchangeRate = <?php echo $exchangeRate; ?>;

        function updateExchangeRate(val) {
            currentExchangeRate = parseFloat(val) || 0;
            items.forEach(item => {
                item.unit_price_ars = item.unit_price_usd * currentExchangeRate;
            });
            renderTable();
        }

        function addItem(product) {
            const existing = items.find(i => i.sku === product.sku);
            if (existing) {
                existing.qty++;
            } else {
                const costUsd = parseFloat(product.cost_usd) || 0;
                items.push({
                    product_id: product.id,
                    sku: product.sku,
                    description: product.description,
                    qty: 1,
                    unit_price_usd: costUsd,
                    unit_price_ars: costUsd * currentExchangeRate,
                    iva_rate: 21
                });
            }
            resultsDiv.style.display = 'none';
            searchInput.value = '';
            renderTable();
        }

        function removeItem(index) {
            items.splice(index, 1);
            renderTable();
        }

        function updateQty(index, val) {
            items[index].qty = parseInt(val) || 1;
            renderTable();
        }

        function updatePrice(index, val) {
            items[index].unit_price_usd = parseFloat(val) || 0;
            items[index].unit_price_ars = items[index].unit_price_usd * currentExchangeRate;
            renderTable();
        }

        function updatePriceARS(index, val) {
            const arsValue = parseFloat(val) || 0;
            items[index].unit_price_ars = arsValue;
            items[index].unit_price_usd = arsValue / currentExchangeRate;
            renderTable();
        }

        function updateIVA(index, val) {
            items[index].iva_rate = parseFloat(val);
            renderTable();
        }

        function renderTable() {
            const tbody = document.getElementById('items-tbody');
            tbody.innerHTML = '';
            let subtotal = 0;
            let iva105 = 0;
            let iva21 = 0;

            items.forEach((item, index) => {
                const lineNet = item.qty * item.unit_price_usd;
                subtotal += lineNet;

                if (item.iva_rate == 10.5) iva105 += (lineNet * 0.105);
                else if (item.iva_rate == 21) iva21 += (lineNet * 0.21);

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input type="number" value="${item.qty}" min="1" onchange="updateQty(${index}, this.value)" style="width: 50px; background: rgba(255,255,255,0.05); border: 1px solid var(--accent-violet); border-radius: 4px; color: white;"></td>
                    <td style="font-size: 0.9rem; font-weight: 600;">${item.sku}</td>
                    <td style="font-size: 0.85rem;">${item.description}</td>
                    <td><input type="number" step="0.01" value="${item.unit_price_ars.toFixed(2)}" onchange="updatePriceARS(${index}, this.value)" style="width: 100px; text-align: right; background: rgba(255,255,255,0.05); border: 1px solid #27ae60; border-radius: 4px; color: #10b981;"></td>
                    <td><input type="number" step="0.01" value="${item.unit_price_usd.toFixed(2)}" onchange="updatePrice(${index}, this.value)" style="width: 90px; text-align: right; background: rgba(255,255,255,0.05); border: 1px solid var(--accent-violet); border-radius: 4px; color: var(--accent-blue); font-weight: 600;"></td>
                    <td style="text-align: center;">
                        <select onchange="updateIVA(${index}, this.value)" style="width: 70px; background: rgba(255,255,255,0.05); border: 1px solid var(--accent-violet); border-radius: 4px; color: white; padding: 2px;">
                            <option value="21" ${item.iva_rate == 21 ? 'selected' : ''}>21%</option>
                            <option value="10.5" ${item.iva_rate == 10.5 ? 'selected' : ''}>10.5%</option>
                            <option value="0" ${item.iva_rate == 0 ? 'selected' : ''}>0%</option>
                        </select>
                    </td>
                    <td style="text-align: right; font-weight: 600;">$${lineNet.toFixed(2)}</td>
                    <td><button type="button" class="btn-primary" onclick="removeItem(${index})" style="background:rgba(239,68,68,0.2); padding: 5px 10px;"><i class="fas fa-trash"></i></button></td>
                `;
                tbody.appendChild(tr);
            });

            const grandTotal = subtotal + iva105 + iva21;

            document.getElementById('subtotal-display').innerText = `USD ${subtotal.toFixed(2)}`;
            document.getElementById('iva105-display').innerText = `USD ${iva105.toFixed(2)}`;
            document.getElementById('iva21-display').innerText = `USD ${iva21.toFixed(2)}`;
            document.getElementById('grand-total').innerText = `USD ${grandTotal.toFixed(2)}`;
            document.getElementById('total-ars-display').innerText = (grandTotal * currentExchangeRate).toFixed(2);
        }


        // Save logic
        document.getElementById('purchase-form').onsubmit = function (e) {
            e.preventDefault();
            if (items.length === 0) {
                alert('Debe agregar al menos un producto.');
                return;
            }

            const subtotal_usd = items.reduce((acc, i) => acc + (i.qty * i.unit_price_usd), 0);
            const total_iva = items.reduce((acc, i) => {
                const lineNet = i.qty * i.unit_price_usd;
                return acc + (lineNet * (i.iva_rate / 100));
            }, 0);
            const total_usd = subtotal_usd + total_iva;

            const formData = {
                purchase_number: '<?php echo $purchaseNumber; ?>',
                entity_id: document.getElementById('entity_id').value,
                purchase_date: document.getElementById('purchase_date').value,
                status: document.getElementById('status').value,
                is_confirmed: document.getElementById('is_confirmed').checked ? 1 : 0,
                payment_status: document.getElementById('payment_status').value,
                exchange_rate_usd: currentExchangeRate,
                subtotal_usd: subtotal_usd,
                subtotal_ars: subtotal_usd * currentExchangeRate,
                total_usd: total_usd,
                total_ars: total_usd * currentExchangeRate,
                notes: document.getElementById('purchase-observations').value,
                items: items
            };

            fetch('ajax_save_purchase.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert('Compra guardada correctamente.');
                        location.reload();
                    } else {
                        alert('Error: ' + res.error);
                    }
                });
        };

        // Close search list on click outside
        document.addEventListener('click', function (e) {
            if (e.target !== searchInput) resultsDiv.style.display = 'none';
        });

        function toggleStatus(id, type, field, val) {
            fetch('ajax_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, type: type, field: field, value: val })
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + res.error);
                    }
                });
        }

        function deletePurchase(id) {
            if (!confirm('¿Está seguro de eliminar esta compra?')) return;
            fetch('ajax_delete_purchase.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert('Error: ' + res.error);
                });
        }
    </script>
</body>

</html>
