<?php
require_once 'includes/config.php';

// Si el usuario eligió "recordar sesión", el token persistente restaura la sesión.
if (!isset($_SESSION['usuario_id']) && !empty($_COOKIE['refugio_recordar'])) {
    $parts = explode(':', $_COOKIE['refugio_recordar'], 2);
    if (count($parts) === 2) {
        $pdo = conectar();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND estado = 1");
        $stmt->execute([(int)$parts[0]]);
        $usuario = $stmt->fetch();
        if ($usuario && hash_equals(hash('sha256', $usuario['password']), $parts[1])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['rol'] = $usuario['rol'];
        }
    }
}

if (isset($_SESSION['usuario_id'])) {
    header("Location: pages/dashboard.php");
    exit;
}

$error = '';
$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    if (isset($_POST['recuperar'])) {
        $info = "Si el correo existe, el administrador recibirá una solicitud de recuperación.";
    } elseif ($email && strlen($password) >= 6) {
        $pdo = conectar();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND estado = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // La contraseña se valida con password_hash/password_verify para evitar texto plano.
        if ($usuario && password_verify($password, $usuario['password'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['rol'] = $usuario['rol'];
            if (!empty($_POST['recordar'])) {
                setcookie('refugio_recordar', $usuario['id'] . ':' . hash('sha256', $usuario['password']), time() + 60 * 60 * 24 * 30, '', '', false, true);
            }
            logAudit($pdo, 'login', 'usuarios', $usuario['id'], 'Inicio de sesión correcto');
            header("Location: pages/dashboard.php");
            exit;
        }
        $error = "No pudimos iniciar sesión. Revisa tu correo y contraseña.";
    } else {
        $error = "Ingresa un correo válido y una contraseña de al menos 6 caracteres.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Iniciar Sesión</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-hero">
        <span class="hero-badge">🐾 Plataforma integral</span>
        <h1>Gestión moderna para refugios de animales</h1>
        <p>Controla rescates, adopciones, donaciones, inventario y seguimientos desde un panel visual e intuitivo.</p>
    </div>
    <div class="login-card">
        <div class="login-logo">🐾</div>
        <h1 class="login-title"><?= SITE_NAME ?></h1>
        <p class="login-sub">Sistema de Gestión Integral</p>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($info): ?>
        <div class="alert alert-info"><?= e($info) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Correo electrónico</label>
                <input type="email" name="email" placeholder="usuario@refugio.com"
                       value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="••••••••" minlength="6" required>
            </div>
            <div class="login-options">
                <label class="check-row"><input type="checkbox" name="recordar" value="1"> Recordar sesión</label>
                <button type="submit" name="recuperar" value="1" class="link-button">Recuperar contraseña</button>
            </div>
            <button type="submit" class="btn btn-primary btn-login">Ingresar</button>
        </form>

        <p class="login-demo">
            Demo: admin@refugio.com / password
        </p>
    </div>
</div>
</body>
</html>
