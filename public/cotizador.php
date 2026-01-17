<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/modules/cotizador/Cotizador.php';
require_once __DIR__ . '/../src/lib/BCRAClient.php';

use Vsys\Modules\Cotizador\Cotizador;
use Vsys\Lib\BCRAClient;

$cot = new Cotizador();
$currency = new BCRAClient();
$quoteNumber = $cot->generateQuoteNumber(1);
$currentRate = $currency->getCurrentRate('oficial') ?? 850.00; // Default if API fails
$today = date('d/m/y');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nuevo Presupuesto - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fee-toggles {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            background: var(--secondary-blue);
            padding: 15px;
            border-radius: 8px;
        }

        .toggle-item {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .toggle-item input {
            width: auto;
            margin: 0;
        }

        .search-dropdown {
            position: absolute;
            background: var(--primary-blue);
            width: 100%;
            z-index: 100;
            border: 1px solid var(--accent-violet);
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }

        .search-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .search-item:hover {
            background: var(--accent-violet);
        }
    </style>
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=1" class="logo-large" style="height: 50px; width: auto;">
            <div
                style="color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.4rem; letter-spacing: 1px; text-shadow: 0 0 10px rgba(139, 92, 246, 0.4);">
                Vecino Seguro <span
                    style="background: linear-gradient(90deg, #8b5cf6, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Sistemas</span>
                by Javier Gozzi - 2026
            </div>
        </div>
        <div class="header-info" style="color: #cbd5e1; font-size: 1rem;">
            Dólar BNA (Venta): <strong id="current-rate-display" style="color: var(--accent-violet);">$
                <?php echo $currentRate; ?></strong>
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
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link active"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
        </nav>

        <main class="content">
            <div class="card">
                <h2>Cerrar Presupuesto: <?php echo $quoteNumber; ?></h2>
                <div class="grid-3">
                    <div style="position: relative;">
                        <label>Cliente (Buscar por nombre o CUIT)</label>
                        <input type="text" id="client-search" placeholder="Buscar cliente..." autocomplete="off">
                        <input type="hidden" id="selected-client-id" value="1">
                        <div id="client-results" class="search-dropdown" style="display: none;"></div>
                    </div>
                    <div>
                        <label>Nombre / Razón Social</label>
                        <input type="text" id="client-name-display" readonly>
                    </div>
                    <div>
                        <label>CUIT / CUIL</label>
                        <input type="text" id="client-tax-display" readonly>
                    </div>
                </div>
                <div class="grid-3" style="margin-top: 1rem;">
                    <div>
                        <label>Dirección</label>
                        <input type="text" id="client-address-display" readonly>
                    </div>
                    <div style="display: flex; gap: 20px; align-items: center; padding-top: 25px;">
                        <label class="toggle-item">
                            <input type="checkbox" id="is-bank"> Transf. Bancaria (+3%)
                        </label>
                        <label class="toggle-item">
                            <input type="checkbox" id="is-retention"> Agente de Retención (+7%)
                        </label>
                        <label class="toggle-item">
                            <input type="checkbox" id="with-iva" checked> ¿Venta con IVA?
                        </label>
                    </div>
                </div>

                <div class="product-search-bar card" style="margin-top: 1.5rem; position: relative;">
                    <h3><i class="fas fa-search"></i> Agregar Productos</h3>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="product-search"
                            placeholder="Buscar por SKU (Ej: CY-NVR), nombre, marca..." style="flex: 1;">
                    </div>
                    <div id="search-results" class="search-dropdown" style="top: 100%;"></div>
                </div>

                <div class="table-responsive" style="margin-top: 1.5rem;">
                    <table class="table-compact" id="quote-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Cant.</th>
                                <th>SKU</th>
                                <th>Descripción</th>
                                <th style="text-align: right; width: 120px;">Unit. USD</th>
                                <th style="text-align: right; width: 120px;">Unit. ARS</th>
                                <th style="text-align: center; width: 80px;">IVA</th>
                                <th style="text-align: right; width: 130px;">Total USD</th>
                                <th style="text-align: center; width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="quote-items">
                            <!-- Items placeholder -->
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 1.5rem;">
                    <label style="color: var(--text-muted); font-size: 0.9rem;"><i class="fas fa-comment-alt"></i>
                        Observaciones / Referencias Internas</label>
                    <textarea id="quote-observations"
                        placeholder="Ej: Referencia Orden de Compra #1234, Entrega pactada para el viernes..."
                        style="width: 100%; height: 80px; background: rgba(255,255,255,0.05); border: 1px solid var(--accent-violet); border-radius: 8px; color: white; padding: 10px; font-family: inherit; margin-top: 5px;"></textarea>
                </div>

                <div class="footer-summary grid-3" style="margin-top: 1.5rem;">
                    <div>
                        <p>Subtotal USD: <span id="total-neto-usd">0.00</span></p>
                        <p>IVA USD: <span id="total-iva-usd">0.00</span></p>
                        <h3 style="color: var(--accent-violet);">Total USD: <span id="total-general-usd">0.00</span>
                        </h3>
                    </div>
                    <div>
                        <p>Cotización BNA: <input type="number" step="0.01" id="bcra-reference"
                                value="<?php echo $currentRate; ?>"
                                style="width: 100px; background: rgba(255,255,255,0.05); border: 1px solid var(--accent-violet); border-radius: 4px; color: white; padding: 2px 5px; text-align: center; font-weight: bold;">
                        </p>
                        <h3 style="color: #27ae60;">Total ARS: $ <span id="total-general-ars">0.00</span></h3>
                    </div>
                    <div style="text-align: right; display: flex; flex-direction: column; gap: 10px;">
                        <button class="btn-primary" onclick="saveQuotation()"><i class="fas fa-save"></i> GRABAR Y
                            PDF</button>
                        <button class="btn-primary" style="background: #25d366;" onclick="sendWhatsApp()"><i
                                class="fab fa-whatsapp"></i> ENVIAR WHATSAPP</button>
                    </div>
                </div>

                <div class="leyenda" style="margin-top: 2rem; font-size: 0.8rem; color: var(--text-muted);">
                    Leyenda final: Cotización válida por 48hs sujeto a cambio de cotización y stock.
                </div>
            </div>
        </main>
    </div>

    <script>
        let bnaRate = <?php echo $currentRate; ?>;
        let items = [];
        let searchTimeout;

        // Client Search Logic
        const clientSearch = document.getElementById('client-search');
        const clientResults = document.getElementById('client-results');
        const productSearch = document.getElementById('product-search'); // Define productSearch here
        const productResults = document.getElementById('search-results'); // Define productResults here

        clientSearch.addEventListener('input', function () {
            const query = this.value;
            if (query.length < 2) {
                clientResults.style.display = 'none';
                return;
            }

            fetch(`ajax_search_clients.php?q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    clientResults.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(client => {
                            const div = document.createElement('div');
                            div.className = 'search-item';
                            const contact = client.contact_person ? ` | <span style="color:#818cf8">${client.contact_person}</span>` : '';
                            div.innerHTML = `<strong>${client.name}</strong>${contact}<br><small>${client.tax_id || client.document_number || 'S/D'}</small>`;
                            div.onclick = () => selectClient(client);
                            clientResults.appendChild(div);
                        });
                        clientResults.style.display = 'block';
                    } else {
                        clientResults.style.display = 'none';
                    }
                });
        });

        function selectClient(client) {
            document.getElementById('selected-client-id').value = client.id;
            document.getElementById('client-search').value = client.name;
            document.getElementById('client-name-display').value = client.name;
            document.getElementById('client-tax-display').value = client.tax_id;
            document.getElementById('client-address-display').value = client.address;

            // Auto-check retention if client is agent
            document.getElementById('is-retention').checked = (client.is_retention_agent == 1);

            // Auto-check bank if preferred (search matches "transferencia" or "banco")
            const pref = (client.preferred_payment_method || '').toLowerCase();
            document.getElementById('is-bank').checked = (pref.includes('transferencia') || pref.includes('banco') || pref.includes('deposito'));

            clientResults.style.display = 'none';
            renderTable(); // Re-render to apply potential retention changes
        }

        // Close dropdowns on click outside
        document.addEventListener('click', function (e) {
            if (e.target !== clientSearch && !clientResults.contains(e.target)) clientResults.style.display = 'none';
            if (e.target !== productSearch && !productResults.contains(e.target)) productResults.style.display = 'none';
        });

        // Búsqueda de productos en tiempo real
        document.getElementById('product-search').addEventListener('input', function (e) {
            clearTimeout(searchTimeout);
            const query = e.target.value;
            const dropdown = document.getElementById('search-results');

            if (query.length < 2) {
                dropdown.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`ajax_search_products.php?q=${query}`)
                    .then(res => res.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if (data.length > 0) {
                            dropdown.style.display = 'block';
                            data.forEach(prod => {
                                const div = document.createElement('div');
                                div.className = 'search-item';
                                const priceARS = (parseFloat(prod.unit_price_usd) * bnaRate).toLocaleString('es-AR', { minimumFractionDigits: 2 });
                                div.innerHTML = `<strong>${prod.sku}</strong> - ${prod.description} (${prod.brand}) - <span style="color:var(--accent-violet)">USD ${prod.unit_price_usd}</span> | <span style="color:#27ae60">ARS ${priceARS}</span>`;
                                div.onclick = () => addItem(prod);
                                dropdown.appendChild(div);
                            });
                        } else {
                            dropdown.style.display = 'none';
                        }
                    });
            }, 300);
        });

        function addItem(prod) {
            const existing = items.find(i => i.sku === prod.sku);
            if (existing) {
                existing.qty++;
            } else {
                items.push({
                    id: prod.id,
                    sku: prod.sku,
                    desc: prod.description,
                    price: parseFloat(prod.unit_price_usd),
                    iva: parseFloat(prod.iva_rate),
                    qty: 1
                });
            }
            document.getElementById('product-search').value = '';
            document.getElementById('search-results').style.display = 'none';
            renderTable();
        }

        function renderTable() {
            const tbody = document.getElementById('quote-items');
            tbody.innerHTML = '';

            const isRetention = document.getElementById('is-retention').checked;
            const isBank = document.getElementById('is-bank').checked;
            const withIva = document.getElementById('with-iva').checked;

            items.forEach((item, index) => {
                // Calculate adjusted unit price for this row (Sequential)
                let adjustedUnitPrice = item.price;
                if (isRetention) adjustedUnitPrice *= 1.07;
                if (isBank) adjustedUnitPrice *= 1.03;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input type="number" class="qty-input" value="${item.qty}" min="1" onchange="updateQty(${index}, this.value)" style="width: 60px; background: rgba(255,255,255,0.05); border: 1px solid var(--accent-violet); border-radius: 4px; color: white;"></td>
                    <td>${item.sku}</td>
                    <td style="font-size: 0.9rem;">${item.desc}</td>
                    <td style="text-align: right;">
                        <input type="number" step="0.01" value="${adjustedUnitPrice.toFixed(2)}" 
                            onchange="updatePrice(${index}, this.value, 'usd')" 
                            style="width: 100px; text-align: right; background: rgba(255,255,255,0.05); border: 1px solid var(--accent-violet); border-radius: 4px; color: var(--accent-blue); font-weight: 600;">
                    </td>
                    <td style="text-align: right;">
                        <input type="number" step="0.01" value="${(adjustedUnitPrice * bnaRate).toFixed(2)}" 
                            onchange="updatePrice(${index}, this.value, 'ars')" 
                            style="width: 110px; text-align: right; background: rgba(255,255,255,0.05); border: 1px solid #27ae60; border-radius: 4px; color: #10b981; font-weight: 600;">
                    </td>
                    <td style="text-align: center;">${item.iva}%</td>
                    <td style="text-align: right; font-weight: 700;">$ ${(adjustedUnitPrice * item.qty).toFixed(2)}</td>
                    <td style="text-align: center;">
                        <button class="btn-primary" onclick="removeItem(${index})" style="padding: 5px 10px; background: rgba(255,0,0,0.2);">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            calculateTotals();
        }

        function updateQty(index, val) {
            items[index].qty = parseInt(val) || 1;
            renderTable();
        }

        function updatePrice(index, val, unit) {
            let enteredPrice = parseFloat(val) || 0;
            const isRetention = document.getElementById('is-retention').checked;
            const isBank = document.getElementById('is-bank').checked;

            // If ARS, convert back to USD first
            if (unit === 'ars') {
                enteredPrice = enteredPrice / bnaRate;
            }

            // Reverse adjustments to get the base price
            let basePrice = enteredPrice;
            if (isBank) basePrice /= 1.03;
            if (isRetention) basePrice /= 1.07;

            items[index].price = basePrice;
            renderTable();
        }

        function removeItem(index) {
            items.splice(index, 1);
            renderTable();
        }

        function calculateTotals() {
            let subtotal = 0;
            let totalIva = 0;
            const isRetention = document.getElementById('is-retention').checked;
            const isBank = document.getElementById('is-bank').checked;
            const withIva = document.getElementById('with-iva').checked;

            items.forEach(item => {
                let adjustedPrice = item.price;
                if (isRetention) adjustedPrice *= 1.07;
                if (isBank) adjustedPrice *= 1.03;

                let lineTotal = adjustedPrice * item.qty;
                subtotal += lineTotal;

                if (withIva) {
                    totalIva += (lineTotal * (item.iva / 100));
                }
            });

            document.getElementById('total-neto-usd').innerText = subtotal.toFixed(2);
            document.getElementById('total-iva-usd').innerText = totalIva.toFixed(2);
            const totalGeneral = subtotal + totalIva;
            document.getElementById('total-general-usd').innerText = totalGeneral.toFixed(2);
            document.getElementById('total-general-ars').innerText = (totalGeneral * bnaRate).toLocaleString('es-AR');
        }

        document.getElementById('is-retention').addEventListener('change', renderTable);
        document.getElementById('is-bank').addEventListener('change', renderTable);
        document.getElementById('with-iva').addEventListener('change', renderTable);
        document.getElementById('bcra-reference').addEventListener('change', function () {
            bnaRate = parseFloat(this.value) || 0;
            renderTable();
        });

        function saveQuotation() {
            if (items.length === 0) {
                alert('Agregue al menos un producto.');
                return;
            }

            const data = {
                quote_number: '<?php echo $quoteNumber; ?>',
                client_id: document.getElementById('selected-client-id').value,
                payment_method: document.getElementById('is-bank').checked ? 'bank' : 'cash',
                is_retention: document.getElementById('is-retention').checked,
                is_bank: document.getElementById('is-bank').checked,
                with_iva: document.getElementById('with-iva').checked,
                exchange_rate_usd: bnaRate,
                subtotal_usd: parseFloat(document.getElementById('total-neto-usd').innerText),
                total_usd: parseFloat(document.getElementById('total-general-usd').innerText),
                total_ars: parseFloat(document.getElementById('total-general-ars').innerText.replace(/[^\d.,]/g, '').replace(/\./g, '').replace(',', '.')),
                observations: document.getElementById('quote-observations').value,
                items: items
            };

            fetch('ajax_save_quotation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        // Log to CRM
                        logCrmInteraction(data.client_id, 'Email/PDF', `Generó presupuesto ${data.quote_number}`);

                        // Try to open PDF immediately to avoid popup blockers
                        const pdfWindow = window.open('imprimir_cotizacion.php?id=' + res.id, '_blank');
                        if (!pdfWindow) {
                            alert('Presupuesto guardado, pero el bloqueador de popups impidió abrir el PDF. Puede encontrarlo en el historial.');
                        } else {
                            alert('Presupuesto guardado correctamente.');
                        }
                        location.reload();
                    } else {
                        alert('Error: ' + res.error);
                    }
                });
        }

        function sendWhatsApp() {
            if (items.length === 0) {
                alert('Agregue productos primero.');
                return;
            }

            const clientName = document.getElementById('client-name-display').value || 'Cliente';
            const clientId = document.getElementById('selected-client-id').value;
            const quoteNo = '<?php echo $quoteNumber; ?>';
            const totalUSD = document.getElementById('total-general-usd').innerText;
            const totalARS = document.getElementById('total-general-ars').innerText;

            let text = `*Presupuesto VS System - ${quoteNo}*\n`;
            text += `Hola *${clientName}*, aquí tienes la cotización solicitada:\n\n`;

            items.forEach(i => {
                const isRetention = document.getElementById('is-retention').checked;
                const isBank = document.getElementById('is-bank').checked;
                let p = i.price;
                if (isRetention) p *= 1.07;
                if (isBank) p *= 1.03;
                text += `- ${i.qty}x ${i.desc} (*$${p.toFixed(2)}*)\n`;
            });

            text += `\n*TOTAL USD: $${totalUSD}*\n`;
            text += `*TOTAL ARS: $${totalARS}*\n\n`;
            text += `_Cotización BNA: ${bnaRate}_\n\n`;
            text += `Válido por 48hs o hasta agotar stock.`;

            // Log to CRM
            logCrmInteraction(clientId, 'WhatsApp', `Envió presupuesto ${quoteNo} por WhatsApp`);

            const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
            window.open(url, '_blank');
        }

        function logCrmInteraction(entityId, type, desc) {
            if (!entityId || entityId == "1") return; // Don't log for generic client
            fetch('ajax_log_crm.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entity_id: entityId, type: type, description: desc })
            });
        }
    </script>
</body>

</html>