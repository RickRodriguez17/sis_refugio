<?php
// =============================================
// CONFIGURACIÓN DE BASE DE DATOS
// Archivo: includes/config.php
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Cambia por tu usuario
define('DB_PASS', '');            // Cambia por tu contraseña
define('DB_NAME', 'refugio_animales');
define('SITE_NAME', 'Refugio Animal');

// Conexión a MySQL usando PDO
function conectar() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../index.php");
        exit;
    }
}

// Escapar HTML para prevenir XSS
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Mensaje flash
function setFlash($tipo, $mensaje) {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
