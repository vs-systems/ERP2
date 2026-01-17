<?php
session_start();
require_once __DIR__ . '/src/config/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hardcoded credentials for simplicity/reliability in migration phase
    // User can request DB integration later if needed.
    // Default: admin / vsys2026
    if ($username === 'admin' && $password === 'vsys2026') {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_name'] = 'Administrador';
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #020617;
            color: #cbd5e1;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-card {
            background: #0f172a;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #1e293b;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #94a3b8;
        }

        input {
            width: 100%;
            padding: 0.75rem;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #fff;
            margin-bottom: 0.5rem;
            box-sizing: border-box;
            /* Fix padding issue */
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(90deg, #8b5cf6, #d946ef);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
        }

        .error {
            color: #ef4444;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <h2>Acceso VS System</h2>
        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>

</html>
