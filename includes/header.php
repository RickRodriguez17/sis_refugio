<?php
// Cabecera compartida: valida sesión, calcula página activa y pinta la navegación global.
verificarLogin();
$flash = getFlash();
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - <?= $titulo ?? 'Panel' ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        <span class="logo-icon">🐾</span>
        <span class="logo-text"><?= SITE_NAME ?></span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $pagina_actual=='dashboard.php'?'active':'' ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>
        <a href="animales.php" class="nav-item <?= $pagina_actual=='animales.php'?'active':'' ?>">
            <span class="nav-icon">🐶</span> Mascotas
        </a>
        <a href="galeria.php" class="nav-item <?= $pagina_actual=='galeria.php'?'active':'' ?>">
            <span class="nav-icon">🖼️</span> Galería
        </a>
        <a href="adopciones.php" class="nav-item <?= $pagina_actual=='adopciones.php'?'active':'' ?>">
            <span class="nav-icon">🏠</span> Adopciones
        </a>
        <a href="historial_adopciones.php" class="nav-item <?= $pagina_actual=='historial_adopciones.php'?'active':'' ?>">
            <span class="nav-icon">📜</span> Historial
        </a>
        <a href="donaciones.php" class="nav-item <?= $pagina_actual=='donaciones.php'?'active':'' ?>">
            <span class="nav-icon">💝</span> Donaciones
        </a>
        <a href="gastos.php" class="nav-item <?= $pagina_actual=='gastos.php'?'active':'' ?>">
            <span class="nav-icon">💸</span> Gastos
        </a>
        <a href="inventario.php" class="nav-item <?= $pagina_actual=='inventario.php'?'active':'' ?>">
            <span class="nav-icon">📦</span> Inventario
        </a>
        <a href="seguimientos.php" class="nav-item <?= $pagina_actual=='seguimientos.php'?'active':'' ?>">
            <span class="nav-icon">🩺</span> Seguimiento
        </a>
        <a href="reportes.php" class="nav-item <?= $pagina_actual=='reportes.php'?'active':'' ?>">
            <span class="nav-icon">📄</span> Reportes
        </a>
        <a href="voluntarios.php" class="nav-item <?= $pagina_actual=='voluntarios.php'?'active':'' ?>">
            <span class="nav-icon">🙋</span> Voluntarios
        </a>
        <?php if ($_SESSION['rol'] === 'administrador'): ?>
        <a href="usuarios.php" class="nav-item <?= $pagina_actual=='usuarios.php'?'active':'' ?>">
            <span class="nav-icon">👤</span> Usuarios
        </a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <span class="user-avatar">👤</span>
            <div>
                <div class="user-name"><?= e($_SESSION['nombre']) ?></div>
                <div class="user-role"><?= e($_SESSION['rol']) ?></div>
            </div>
        </div>
        <a href="../logout.php" class="btn-logout">Salir</a>
    </div>
</div>

<!-- CONTENIDO PRINCIPAL -->
<div class="main-content">
    <div class="topbar">
        <div>
            <h1 class="page-title"><?= $titulo ?? 'Panel' ?></h1>
            <p class="page-subtitle">Administración integral del refugio de animales</p>
        </div>
        <button type="button" class="theme-toggle" onclick="toggleTheme()">🌙 Modo oscuro</button>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['tipo'] ?>">
        <?= e($flash['mensaje']) ?>
    </div>
    <?php endif; ?>

    <div class="content-body">
<script>
// Modo oscuro: se guarda en el navegador para que el usuario conserve su preferencia.
function toggleTheme() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('refugio_theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
}
if (localStorage.getItem('refugio_theme') === 'dark') {
    document.body.classList.add('dark-mode');
}
</script>
