<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/clientes/Client.php';

use Vsys\Modules\Clientes\Client;
$clientModule = new Client();
$db = Vsys\Lib\Database::getInstance();

$type = $_GET['type'] ?? 'client'; // 'client' or 'supplier'
$id = $_GET['edit'] ?? null;
$message = '';
$status = '';

// Data for editing
$editData = [
    'id' => '',
    'name' => '',
    'fantasy_name' => '',
    'tax_id' => '',
    'document_number' => '',
    'contact_person' => '',
    'email' => '',
    'phone' => '',
    'mobile' => '',
    'address' => '',
    'delivery_address' => '',
    'tax_category' => ($type == 'client' ? 'Consumidor Final' : 'No Aplica'),
    'default_voucher_type' => 'Factura',
    'payment_condition' => 'Contado',
    'preferred_payment_method' => 'Transferencia',
    'is_enabled' => 1,
    'is_retention_agent' => 0,
    'seller_id' => null,
    'client_profile' => 'Otro',
    'is_verified' => 0,
    'city' => '',
    'lat' => '',
    'lng' => ''
];

if ($id) {
    $stmt = $db->prepare("SELECT * FROM entities WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    if ($res)
        $editData = $res;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => $_POST['id'] ?? null,
        'type' => $type,
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
        'default_voucher' => $_POST['default_voucher_type'],
        'tax_category' => $_POST['tax_category'],
        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
        'retention' => isset($_POST['is_retention_agent']) ? 1 : 0,
        'payment_condition' => $_POST['payment_condition'],
        'payment_method' => $_POST['payment_method'],
        'seller_id' => !empty($_POST['seller_id']) ? $_POST['seller_id'] : null,
        'client_profile' => $_POST['client_profile'] ?? 'Otro',
        'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
        'city' => $_POST['city'] ?? null,
        'lat' => $_POST['lat'] ?? null,
        'lng' => $_POST['lng'] ?? null
    ];

    if ($clientModule->saveClient($data)) {
        header("Location: " . ($type == 'client' ? 'clientes.php' : 'proveedores.php') . "?success=1");
        exit;
    } else {
        $message = "Error al guardar.";
        $status = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>
        <?php echo $id ? 'Editar' : 'Nuevo'; ?>
        <?php echo $type == 'client' ? 'Cliente' : 'Proveedor'; ?> - VS System
    </title>
    <link rel="stylesheet" href="css/style_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    </style>
</head>

<body>
    <header
        style="background: #020617; border-bottom: 2px solid var(--accent-violet); display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <img src="logo_display.php?v=2" alt="VS System" class="logo-large"class="logo-large"style="height: 50px;">
            <div style="color:white; font-weight:700; font-size:1.4rem;">
                <?php echo $id ? 'EDITAR' : 'NUEVO'; ?> <span>
                    <?php echo strtoupper($type == 'client' ? 'Cliente' : 'Proveedor'); ?>
                </span>
            </div>
        </div>
    </header>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        <main class="content">
            <div class="card">
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                    <div class="form-grid">
                        <div class="form-group"><label>Razó³n Social / Nombre</label><input type="text" name="name"
                                value="<?php echo $editData['name']; ?>" required></div>
                        <div class="form-group"><label>Nombre de Fantasó­a</label><input type="text" name="fantasy_name"
                                value="<?php echo $editData['fantasy_name']; ?>"></div>
                        <div class="form-group"><label>CUIT/CUIL</label><input type="text" name="tax_id"
                                value="<?php echo $editData['tax_id']; ?>" placeholder="00-00000000-0"></div>
                        <div class="form-group"><label>DNI / Documento</label><input type="text" name="document_number"
                                value="<?php echo $editData['document_number']; ?>"></div>
                        <div class="form-group"><label>Categoró­a Fiscal</label>
                            <select name="tax_category">
                                <?php if ($type == 'client'): ?>
                                    <option value="Responsable Inscripto" <?php echo $editData['tax_category'] == 'Responsable Inscripto' ? 'selected' : ''; ?>>Responsable
                                        Inscripto</option>
                                    <option value="Monotributo" <?php echo $editData['tax_category'] == 'Monotributo' ? 'selected' : ''; ?>>Monotributo</option>
                                    <option value="Exento" <?php echo $editData['tax_category'] == 'Exento' ? 'selected' : ''; ?>
                                        >Exento</option>
                                    <option value="Consumidor Final" <?php echo $editData['tax_category'] == 'Consumidor Final' ? 'selected' : ''; ?>>Consumidor Final
                                    </option>
                                <?php else: ?>
                                    <option value="Responsable Inscripto" <?php echo $editData['tax_category'] == 'Responsable Inscripto' ? 'selected' : ''; ?>>Responsable
                                        Inscripto</option>
                                    <option value="Monotributo" <?php echo $editData['tax_category'] == 'Monotributo' ? 'selected' : ''; ?>>Monotributo</option>
                                    <option value="No Aplica" <?php echo $editData['tax_category'] == 'No Aplica' ? 'selected' : ''; ?>>No Aplica</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Persona de Contacto</label><input type="text"
                                name="contact_person" value="<?php echo $editData['contact_person']; ?>"></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email"
                                value="<?php echo $editData['email']; ?>"></div>
                        <div class="form-group"><label>Teló©fono</label><input type="text" name="phone"
                                value="<?php echo $editData['phone']; ?>"></div>
                        <div class="form-group"><label>Mó³vil / WhatsApp</label><input type="text" name="mobile"
                                value="<?php echo $editData['mobile']; ?>"></div>
                        <div class="form-group"><label>Comprobante Defecto</label>
                            <select name="default_voucher_type">
                                <option value="Factura" <?php echo $editData['default_voucher_type'] == 'Factura' ? 'selected' : ''; ?>>Factura</option>
                                <option value="Remito" <?php echo $editData['default_voucher_type'] == 'Remito' ? 'selected' : ''; ?>>Remito</option>
                                <option value="Ninguno" <?php echo $editData['default_voucher_type'] == 'Ninguno' ? 'selected' : ''; ?>>Ninguno</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Condició³n Pago</label>
                            <select name="payment_condition">
                                <option value="Contado" <?php echo $editData['payment_condition'] == 'Contado' ? 'selected' : ''; ?>>Contado</option>
                                <option value="Cta Cte" <?php echo strpos($editData['payment_condition'], 'Cta Cte') !== false ? 'selected' : ''; ?>>Cta Cte</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Forma Pago</label>
                            <select name="payment_method">
                                <option value="Transferencia" <?php echo $editData['preferred_payment_method'] == 'Transferencia' ? 'selected' : ''; ?>
                                    >Transferencia</option>
                                <option value="Efectivo" <?php echo $editData['preferred_payment_method'] == 'Efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                                <option value="Mercado Pago" <?php echo $editData['preferred_payment_method'] == 'Mercado Pago' ? 'selected' : ''; ?>>Mercado
                                    Pago</option>
                            </select>
                        </div>
                        <?php if ($type == 'client'): ?>
                        <div class="form-group"><label>Vendedor Asignado</label>
                            <select name="seller_id" style="width: 100%; padding: 10px; background: #1e293b; border: 1px solid #334155; color: white; border-radius: 6px;">
                                <option value="">-- Sin Vendedor --</option>
                                <?php 
                                    $sellers = $db->query("SELECT id, username FROM users WHERE role = 'Vendedor'")->fetchAll();
                                    foreach($sellers as $s): 
                                ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $editData['seller_id'] == $s['id'] ? 'selected' : ''; ?>><?php echo $s['username']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Perfil Cliente</label>
                            <select name="client_profile" style="width: 100%; padding: 10px; background: #1e293b; border: 1px solid #334155; color: white; border-radius: 6px;">
                                <option value="Otro" <?php echo $editData['client_profile'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                <option value="Gremio" <?php echo $editData['client_profile'] == 'Gremio' ? 'selected' : ''; ?>>Gremio</option>
                                <option value="Web" <?php echo $editData['client_profile'] == 'Web' ? 'selected' : ''; ?>>Web</option>
                                <option value="ML" <?php echo $editData['client_profile'] == 'ML' ? 'selected' : ''; ?>>Mercado Libre</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group"><label>Domicilio</label><textarea name="address"
                                rows="2"><?php echo $editData['address']; ?></textarea></div>
                        <div class="form-group"><label>Lugar Entrega</label><textarea name="delivery_address"
                                rows="2"><?php echo $editData['delivery_address']; ?></textarea></div>
                    </div>
                    <div class="form-grid" style="margin-top:20px;">
                        <div class="form-group">
                            <label>Ciudad / Localidad</label>
                            <div style="display: flex; gap: 5px;">
                                <input type="text" id="geo_city" name="city" value="<?php echo $editData['city']; ?>" style="flex: 1;">
                                <button type="button" onclick="geocodeCity()" class="btn-primary" style="padding: 5px 10px; background: #334155; font-size: 0.7rem;" title="Buscar coordenadas automáticamente">
                                    <i class="fa fa-location-dot"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group"><label>Latitud (Geolocalización)</label><input type="text" id="geo_lat" name="lat" value="<?php echo $editData['lat']; ?>" placeholder="-34.6037"></div>
                        <div class="form-group"><label>Longitud (Geolocalización)</label><input type="text" id="geo_lng" name="lng" value="<?php echo $editData['lng']; ?>" placeholder="-58.3816"></div>
                    </div>
                    <div style="margin-top:20px; display:flex; gap:20px;">
                        <label><input type="checkbox" name="is_enabled" <?php echo $editData['is_enabled'] ? 'checked' : ''; ?>> Habilitado</label>
                        <?php if ($type == 'client'): ?><label><input type="checkbox" name="is_retention_agent" <?php echo $editData['is_retention_agent'] ? 'checked' : ''; ?>> Agente Retenció³n</label>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top:30px; display:flex; gap:15px;">
                        <button type="submit" class="btn-primary"
                            style="background:var(--accent-violet); border:none;">GUARDAR</button>
                        <a href="<?php echo $type == 'client' ? 'clientes.php' : 'proveedores.php'; ?>" class="btn-primary"
                            style="background:#475569; text-decoration:none;">CANCELAR</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        async function geocodeCity() {
            const city = document.getElementById('geo_city').value;
            if (!city) {
                alert('Por favor, ingrese una ciudad.');
                return;
            }

            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            btn.disabled = true;

            try {
                // We add ", Argentina" to narrow down search results
                const query = encodeURIComponent(city + ', Argentina');
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`);
                const data = await response.json();

                if (data && data.length > 0) {
                    document.getElementById('geo_lat').value = parseFloat(data[0].lat).toFixed(6);
                    document.getElementById('geo_lng').value = parseFloat(data[0].lon).toFixed(6);
                    
                    // Add visual feedback
                    document.getElementById('geo_lat').style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
                    document.getElementById('geo_lng').style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
                    setTimeout(() => {
                        document.getElementById('geo_lat').style.backgroundColor = '';
                        document.getElementById('geo_lng').style.backgroundColor = '';
                    }, 2000);
                } else {
                    alert('No se encontraron coordenadas para esta ciudad. Por favor, verifique el nombre o ingréselas manualmente.');
                }
            } catch (error) {
                console.error('Error geocoding:', error);
                alert('Error al conectar con el servicio de geolocalización.');
            } finally {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        }

        // Auto-geocode on blur if coords are empty
        document.getElementById('geo_city').addEventListener('blur', function() {
            const lat = document.getElementById('geo_lat').value;
            const lng = document.getElementById('geo_lng').value;
            if (this.value && (!lat || !lng)) {
                geocodeCity();
            }
        });
    </script>
</body>
</html>






