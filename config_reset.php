<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Acceso denegado.");
}

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_module'])) {
    $module = $_POST['module'];
    $confirm = $_POST['confirm_word'];

    if ($confirm !== 'ELIMINAR') {
        $message = "Palabra de confirmación incorrecta.";
        $status = "error";
    } else {
        $db = Vsys\Lib\Database::getInstance();
        $cid = $_SESSION['company_id'];

        try {
            switch ($module) {
                case 'crm':
                    $db->prepare("DELETE FROM crm_leads WHERE company_id = ?")->execute([$cid]);
                    break;
                case 'quotations':
                    $db->prepare("DELETE FROM quotation_items WHERE quotation_id IN (SELECT id FROM quotations WHERE company_id = ?)")->execute([$cid]);
                    $db->prepare("DELETE FROM quotations WHERE company_id = ?")->execute([$cid]);
                    break;
                case 'products':
                    $db->prepare("DELETE FROM products WHERE company_id = ?")->execute([$cid]);
                    break;
                case 'purchases':
                    $db->prepare("DELETE FROM purchase_items WHERE purchase_id IN (SELECT id FROM purchases WHERE company_id = ?)")->execute([$cid]);
                    $db->prepare("DELETE FROM purchases WHERE company_id = ?")->execute([$cid]);
                    break;
            }
            $message = "Módulo $module reseteado correctamente.";
            $status = "success";
            Vsys\Lib\Logger::event('RESET_MODULE', 'module', 0, "Reseteo de módulo: $module");
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $status = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reset de Módulos - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: "class" };
    </script>
</head>

<body class="bg-[#101822] text-white p-10">
    <div class="max-w-2xl mx-auto space-y-8">
        <div>
            <h1 class="text-3xl font-black text-red-500 uppercase tracking-tighter">Zona de Peligro: Reset de Datos</h1>
            <p class="text-slate-400 mt-2">Esta acción es irreversible y borrará todos los datos del módulo seleccionado
                para su empresa.</p>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 rounded-xl <?php echo $status === 'success' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'; ?> font-bold text-center">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6 bg-[#16202e] p-8 rounded-3xl border border-white/5">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Módulo a
                    Resetear</label>
                <select name="module" class="w-full bg-[#101822] border-[#233348] rounded-xl text-white">
                    <option value="crm">CRM (Leads y Embudo)</option>
                    <option value="quotations">Presupuestos (Cotizaciones realizadas)</option>
                    <option value="purchases">Compras (Ordenes de compra)</option>
                    <option value="products">Catálogo (¡Cuidado! Borrará todos sus productos)</option>
                </select>
            </div>

            <div class="p-4 bg-red-500/5 border border-red-500/10 rounded-xl space-y-4">
                <p class="text-[11px] text-red-400 font-bold uppercase tracking-widest">Advertencia de Seguridad</p>
                <p class="text-xs text-slate-400">Para confirmar, escriba la palabra <span
                        class="text-white font-black">ELIMINAR</span> exactamente como se muestra.</p>
                <input type="text" name="confirm_word" required placeholder="Escriba ELIMINAR aquí..."
                    class="w-full bg-[#101822] border-red-500/30 rounded-xl text-white placeholder-red-500/20 font-black text-center tracking-widest">
            </div>

            <button type="submit" name="reset_module"
                class="w-full bg-red-600 hover:bg-red-500 text-white font-black py-4 rounded-xl uppercase tracking-tighter shadow-2xl shadow-red-500/20 active:scale-95 transition-all">
                BORRAR DATOS DEL MÓDULO
            </button>
        </form>

        <div class="text-center">
            <a href="dashboard.php" class="text-slate-500 hover:text-white text-xs underline">Volver al Panel</a>
        </div>
    </div>
</body>

</html>