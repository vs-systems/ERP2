<?php
/**
 * VS System ERP - Gestión de Clientes (Enhanced)
 */
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/modules/clientes/Client.php';

use Vsys\Modules\Clientes\Client;

$clientModule = new Client();
$message = '';
$status = '';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entity'])) {
    $data = [
        'type' => 'client',
        'tax_id' => $_POST['tax_id'],
        'document_number' => $_POST['document_number'],
        'name' => $_POST['name'],
        'fantasy_name' => $_POST['fantasy_name'],
        'contact' => $_POST['contact_person'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'mobile' => $_POST['mobile'],
        'address' => $_POST['address'],
        'delivery_address' => $_POST['delivery_address'],
        'default_voucher' => $_POST['default_voucher_type'] ?? 'Factura',
        'tax_category' => $_POST['tax_category'] ?? 'No Aplica',
        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
        'retention' => isset($_POST['is_retention']) ? 1 : 0,
        'payment_condition' => $_POST['payment_condition'],
        'payment_method' => $_POST['payment_method']
    ];

    if ($clientModule->saveClient($data)) {
        $message = "Cliente guardado correctamente.";
        $status = "success";
    } else {
        $message = "Error al guardar el cliente.";
        $status = "error";
    }
}

// Get all clients
$sql = "SELECT * FROM entities WHERE type = 'client' ORDER BY name ASC";
$db = Vsys\Lib\Database::getInstance();
$clients = $db->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes - VS System</title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="js/entity-format.js"></script>
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #818cf8;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border-radius: 6px;
            background: #1e293b;
            color: #fff;
            border: 1px solid #334155;
        }

        .btn-edit {
            background: #ca8a04;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
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
            <a href="clientes.php" class="nav-link active"><i class="fas fa-users"></i> CLIENTES</a>
            <a href="proveedores.php" class="nav-link"><i class="fas fa-truck-loading"></i> PROVEEDORES</a>
            <a href="compras.php" class="nav-link"><i class="fas fa-cart-arrow-down"></i> COMPRAS</a>
            <a href="importar.php" class="nav-link"><i class="fas fa-upload"></i> IMPORTAR</a>
            <a href="crm.php" class="nav-link"><i class="fas fa-handshake"></i> CRM</a>
            <a href="cotizador.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> COTIZADOR</a>
        </nav>

        <main class="content">

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $status; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="card">
                <h3><i class="fas fa-user-plus"></i> Nuevo / Editar Cliente</h3>
                <form method="POST" id="client-form">
                    <input type="hidden" name="save_entity" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Raz&oacute;n Social / Nombre</label>
                            <input type="text" name="name" id="name" required placeholder="RAZON SOCIAL">
                        </div>
                        <div class="form-group">
                            <label>Nombre de Fantas&iacute;a</label>
                            <input type="text" name="fantasy_name" id="fantasy_name" placeholder="NOMBRE COMERCIAL">
                        </div>
                        <div class="form-group">
                            <label>CUIT/CUIL</label>
                            <input type="text" name="tax_id" id="tax_id" class="mask-cuit" placeholder="00-00000000-0">
                        </div>
                        <div class="form-group">
                            <label>DNI / Documento</label>
                            <input type="text" name="document_number" id="document_number" class="mask-dni"
                                placeholder="00.000.000">
                        </div>
                        <div class="form-group">
                            <label>Categor&iacute;a Fiscal</label>
                            <select name="tax_category" id="tax_category">
                                <option value="Responsable Inscripto">Responsable Inscripto</option>
                                <option value="Monotributo">Monotributo</option>
                                <option value="Exento">Exento</option>
                                <option value="No Aplica" selected>No Aplica</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Persona de Contacto</label>
                            <input type="text" name="contact_person" id="contact_person">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="email" class="no-upper">
                        </div>
                        <div class="form-group">
                            <label>Tel&eacute;fono Fijo</label>
                            <input type="text" name="phone" id="phone">
                        </div>
                        <div class="form-group">
                            <label>Celular / WhatsApp</label>
                            <input type="text" name="mobile" id="mobile">
                        </div>
                        <div class="form-group">
                            <label>Comprobante por Defecto</label>
                            <select name="default_voucher_type" id="default_voucher_type">
                                <option value="Factura">Factura</option>
                                <option value="Remito">Remito</option>
                                <option value="Ninguno">Ninguno</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Condici&oacute;n de Pago</label>
                            <select name="payment_condition" id="payment_condition">
                                <option value="Factura Anticipada">Factura Anticipada</option>
                                <option value="Contado">Contado</option>
                                <option value="2 d&iacute;as">2 d&iacute;as</option>
                                <option value="7 d&iacute;as">7 d&iacute;as</option>
                                <option value="15 d&iacute;as">15 d&iacute;as</option>
                                <option value="30 d&iacute;as">30 d&iacute;as</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Forma de Pago Preferida</label>
                            <select name="payment_method" id="payment_method">
                                <option value="Transferencia">Transferencia</option>
                                <option value="Mercado Pago">Mercado Pago</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Efectivo USD">Efectivo USD</option>
                                <option value="Otra">Otra</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="form-group">
                            <label>Domicilio Legal</label>
                            <textarea name="address" id="address" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Lugar de Entrega</label>
                            <textarea name="delivery_address" id="delivery_address" rows="2"></textarea>
                        </div>
                    </div>

                    <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 1rem;">
                        <label class="toggle"><input type="checkbox" name="is_retention" id="is_retention"> Agente
                            Retenci&oacute;n (+7%)</label>
                        <label class="toggle"><input type="checkbox" name="is_enabled" id="is_enabled" checked>
                            Habilitado</label>
                    </div>

                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> GUARDAR CLIENTE</button>
                </form>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <h3><i class="fas fa-users"></i> Listado de Clientes</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre / Raz&oacute;n Social</th>
                                <th>CUIT / DNI</th>
                                <th>Contacto</th>
                                <th>Cat. Fiscal</th>
                                <th>Email / Tel</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $c): ?>
                                <tr style="<?php echo !$c['is_enabled'] ? 'opacity: 0.5' : ''; ?>">
                                    <td>
                                        <strong><?php echo $c['name']; ?></strong><br>
                                        <small style="color: #818cf8;"><?php echo $c['fantasy_name']; ?></small>
                                    </td>
                                    <td>
                                        <?php echo $c['tax_id']; ?><br>
                                        <small><?php echo $c['document_number']; ?></small>
                                    </td>
                                    <td><?php echo $c['contact_person']; ?></td>
                                    <td><span class="badge"
                                            style="background: rgba(139, 92, 246, 0.2);"><?php echo $c['tax_category']; ?></span>
                                    </td>
                                    <td>
                                        <?php echo $c['email']; ?><br>
                                        <small><?php echo $c['mobile'] ?: $c['phone']; ?></small>
                                    </td>
                                    <td>
                                        <?php if ($c['is_enabled']): ?>
                                            <span style="color: #10b981">ACTIVO</span>
                                        <?php else: ?>
                                            <span style="color: #ef4444">INACTIVO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-edit" onclick='editEntity(<?php echo json_encode($c); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>

    <script>
        function editEntity(data) {
            document.getElementById('name').value = data.name;
            document.getElementById('fantasy_name').value = data.fantasy_name;
            document.getElementById('tax_id').value = data.tax_id;
            document.getElementById('document_number').value = data.document_number;
            document.getElementById('contact_person').value = data.contact_person;
            document.getElementById('email').value = data.email;
            document.getElementById('phone').value = data.phone;
            document.getElementById('mobile').value = data.mobile;
            document.getElementById('address').value = data.address;
            document.getElementById('delivery_address').value = data.delivery_address;
            document.getElementById('tax_category').value = data.tax_category || 'No Aplica';
            document.getElementById('default_voucher_type').value = data.default_voucher_type;
            document.getElementById('is_retention').checked = (data.is_retention_agent == 1);
            document.getElementById('is_enabled').checked = (data.is_enabled == 1);
            document.getElementById('payment_condition').value = data.payment_condition || 'Factura Anticipada';
            document.getElementById('payment_method').value = data.preferred_payment_method || 'Transferencia';

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>

</html>