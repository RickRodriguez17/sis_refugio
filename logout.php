<?php
require_once 'includes/config.php';
// Cerrar sesión elimina tanto la sesión actual como el token de "recordar sesión".
setcookie('refugio_recordar', '', time() - 3600, '', '', false, true);
session_destroy();
header("Location: index.php");
exit;
