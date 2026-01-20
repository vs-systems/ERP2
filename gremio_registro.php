<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Vsys\Lib\Database::getInstance();
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $cuit = $_POST['cuit'] ?? '';
        $rubro = $_POST['rubro'] ?? '';
        $empleo = $_POST['empleo'] ?? '';
        $obs = $_POST['obs'] ?? '';

        $details = "Registro Gremio: CUIT: $cuit, Rubro: $rubro, Tipo: $empleo, Obs: $obs";

        $stmt = $db->prepare("INSERT INTO crm_leads (name, phone, email, status, source, details, company_id) VALUES (?, ?, ?, 'Nuevo', 'Web Gremio', ?, 1)");
        $stmt->execute([$name, $phone, $email, $details]);

        $message = "Sus datos han sido recibidos para verificación. En breve nos pondremos en contacto vía WhatsApp o email.";
    } catch (Exception $e) {
        $message = "Error al procesar el registro: " . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Gremio - VS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-950 text-white flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl">
        <div class="text-center mb-8">
            <img src="logo_display.php" alt="VS System" class="h-12 mx-auto mb-4">
            <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">
                Registro Profesional Gremio</h1>
            <p class="text-slate-400 text-sm mt-2">Acceda a precios mayoristas y beneficios exclusivos.</p>
        </div>

        <?php if ($message): ?>
            <div
                class="mb-6 p-4 rounded-2xl <?php echo $messageType === 'success' ? 'bg-green-500/10 border border-green-500/20 text-green-400' : 'bg-red-500/10 border border-red-500/20 text-red-400'; ?> text-center font-bold text-sm">
                <?php echo $message; ?>
                <?php if ($messageType === 'success'): ?>
                    <div class="mt-4">
                        <a href="catalogo.php" class="text-white underline">Volver al catálogo</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$message || $messageType === 'error'): ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 ml-1">Nombre o
                        Razó³n Social</label>
                    <input type="text" name="name" required
                        class="w-full bg-slate-800 border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 ml-1">Telé©fono
                            (WhatsApp)</label>
                        <input type="tel" name="phone" required
                            class="w-full bg-slate-800 border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 ml-1">CUIT/CUIL</label>
                        <input type="text" name="cuit" required
                            class="w-full bg-slate-800 border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 ml-1">Email</label>
                    <input type="email" name="email" required
                        class="w-full bg-slate-800 border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 ml-1">Rubro</label>
                        <select name="rubro"
                            class="w-full bg-slate-800 border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="Instalador">Instalador</option>
                            <option value="Distribuidor">Distribuidor</option>
                            <option value="Revendedor">Revendedor</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 ml-1">Tipo
                            de Empleo</label>
                        <select name="empleo"
                            class="w-full bg-slate-800 border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="Empresa">Empresa</option>
                            <option value="Particular">Particular / Independiente</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 ml-1">Observaciones
                        / Localidad</label>
                    <textarea name="obs" rows="2"
                        class="w-full bg-slate-800 border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500 transition-all"></textarea>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white font-bold py-4 rounded-2xl shadow-lg shadow-blue-500/20 transition-all active:scale-[0.98] uppercase text-xs tracking-widest mt-4">
                    SOLICITAR REGISTRO
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-8 text-center">
            <a href="catalogo.php"
                class="text-slate-500 hover:text-slate-300 transition-all text-xs flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Volver al Catálogo
            </a>
        </div>
    </div>
</body>

</html>