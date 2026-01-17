<?php
require_once 'auth_check.php';
/**
 * VS System ERP - Gestión de Clientes
 */
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';

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
        'tax_category' => $_POST['tax_category'] ?? 'No Aplica', // Default if empty
        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
        'retention' => isset($_POST['is_retention_agent']) ? 1 : 0,
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <?php include 'sidebar.php'; ?>

        <main class="content">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                <h1>Directorios de Clientes</h1>
                <a href="config_entities.php?type=client" class="btn-primary"
                    style="background:var(--accent-violet); text-decoration:none;">
                    <i class="fas fa-plus"></i> NUEVO CLIENTE
                </a>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <h3><i class="fas fa-list-alt"></i> Listado de Clientes</h3>
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
                                        <strong>
                                            <?php echo $c['name']; ?>
                                        </strong><br>
                                        <small style="color: #818cf8;">
                                            <?php echo $c['fantasy_name']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo $c['tax_id']; ?><br>
                                        <small>
                                            <?php echo $c['document_number']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo $c['contact_person']; ?>
                                    </td>
                                    <td><span class="badge"
                                            style="background: rgba(139, 92, 246, 0.2);"><?php echo $c['tax_category']; ?></span>
                                    </td>
                                    <td>
                                        <?php echo $c['email']; ?><br>
                                        <small>
                                            <?php echo $c['mobile'] ?: $c['phone']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($c['is_enabled']): ?>
                                            <span style="color: #10b981">ACTIVO</span>
                                        <?php else: ?>
                                            <span style="color: #ef4444">INACTIVO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="config_entities.php?type=client&edit=<?php echo $c['id']; ?>"
                                            class="btn-edit" style="text-decoration:none;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>

    </main>
    </div>
</body>

</html>