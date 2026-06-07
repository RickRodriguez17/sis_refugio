<?php
// includes/header.php
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
            <span class="nav-icon">🐶</span> Animales
        </a>
        <a href="adopciones.php" class="nav-item <?= $pagina_actual=='adopciones.php'?'active':'' ?>">
            <span class="nav-icon">🏠</span> Adopciones
        </a>
        <a href="donaciones.php" class="nav-item <?= $pagina_actual=='donaciones.php'?'active':'' ?>">
            <span class="nav-icon">💝</span> Donaciones
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
        <h1 class="page-title"><?= $titulo ?? 'Panel' ?></h1>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['tipo'] ?>">
        <?= e($flash['mensaje']) ?>
    </div>
    <?php endif; ?>

    <div class="content-body">
