<?php
// Configuración central: todas las páginas cargan este archivo antes de consultar la base de datos.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'refugio_animales');
define('SITE_NAME', 'Refugio Patitas Felices');
define('CURRENCY_SYMBOL', 'Bs.');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function conectar() {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        asegurarEsquema($pdo);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . e($e->getMessage()));
    }
}

function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../index.php");
        exit;
    }
}

function e($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function setFlash($tipo, $mensaje) {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function getFlash() {
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function moneyBs($value) {
    return CURRENCY_SYMBOL . ' ' . number_format((float)$value, 2, ',', '.');
}

function fechaLatina($fecha) {
    return $fecha ? date('d/m/Y', strtotime($fecha)) : '-';
}

function edadAnimal($animal) {
    $anios = (int)($animal['edad_años'] ?? 0);
    $meses = (int)($animal['edad_meses'] ?? 0);
    $partes = [];
    if ($anios > 0) {
        $partes[] = $anios . ' año' . ($anios === 1 ? '' : 's');
    }
    if ($meses > 0) {
        $partes[] = $meses . ' mes' . ($meses === 1 ? '' : 'es');
    }
    return $partes ? implode(' ', $partes) : 'Sin edad';
}

function publicPath($path) {
    if (!$path) {
        return '';
    }
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $prefix = strpos($script, '/pages/') !== false ? '../' : '';
    return $prefix . ltrim($path, '/');
}

function uploadFile($field, $folder) {
    if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $permitidos = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $permitidos, true)) {
        throw new RuntimeException('Solo se permiten imágenes JPG, PNG, WEBP o GIF.');
    }

    $root = dirname(__DIR__);
    $folder = trim($folder, '/');
    $destino = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($destino)) {
        mkdir($destino, 0775, true);
    }

    $nombre = date('YmdHis') . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
    $rutaFisica = $destino . DIRECTORY_SEPARATOR . $nombre;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $rutaFisica)) {
        throw new RuntimeException('No se pudo guardar la imagen subida.');
    }

    return 'uploads/' . $folder . '/' . $nombre;
}

function uploadMultipleFiles($field, $folder) {
    if (empty($_FILES[$field]['name']) || !is_array($_FILES[$field]['name'])) {
        return [];
    }

    $rutas = [];
    $files = $_FILES[$field];
    foreach ($files['name'] as $i => $name) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !$name) {
            continue;
        }
        $_FILES['_multi_upload'] = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
        $rutas[] = uploadFile('_multi_upload', $folder);
    }
    unset($_FILES['_multi_upload']);
    return $rutas;
}

function logAudit($pdo, $accion, $tabla, $registroId = null, $detalle = '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalle, ip) VALUES (?,?,?,?,?,?)");
        $stmt->execute([
            $_SESSION['usuario_id'] ?? null,
            $accion,
            $tabla,
            $registroId,
            $detalle,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        return;
    }
}

function tableExists($pdo, $table) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function addColumnIfMissing($pdo, $table, $column, $definition) {
    if (tableExists($pdo, $table) && !columnExists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function asegurarEsquema($pdo) {
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;

    // Migraciones ligeras: permiten que instalaciones existentes reciban campos nuevos sin perder datos.
    $pdo->exec("CREATE TABLE IF NOT EXISTS animal_fotos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        animal_id INT NOT NULL,
        ruta VARCHAR(255) NOT NULL,
        tipo ENUM('principal','galeria') DEFAULT 'galeria',
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_animal_fotos_animal (animal_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gastos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        categoria ENUM('alimento','medicamentos','veterinario','limpieza','transporte','servicios','otros') NOT NULL,
        fecha DATE NOT NULL,
        monto DECIMAL(10,2) NOT NULL DEFAULT 0,
        descripcion VARCHAR(255) NOT NULL,
        proveedor VARCHAR(150),
        comprobante VARCHAR(100),
        responsable_id INT DEFAULT NULL,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_gastos_fecha (fecha),
        INDEX idx_gastos_categoria (categoria)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inventario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(150) NOT NULL,
        categoria ENUM('alimentos','medicamentos','accesorios','limpieza') NOT NULL,
        unidad VARCHAR(40) NOT NULL DEFAULT 'unidad',
        cantidad DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0,
        fecha_vencimiento DATE DEFAULT NULL,
        ubicacion VARCHAR(120),
        observaciones TEXT,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_inventario_categoria (categoria),
        INDEX idx_inventario_stock (cantidad, stock_minimo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS seguimientos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adopcion_id INT DEFAULT NULL,
        animal_id INT NOT NULL,
        fecha DATE NOT NULL,
        estado_mascota VARCHAR(120) NOT NULL,
        comentarios TEXT,
        fotos TEXT,
        responsable VARCHAR(150),
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_seguimientos_animal (animal_id),
        INDEX idx_seguimientos_fecha (fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS auditoria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT DEFAULT NULL,
        accion VARCHAR(80) NOT NULL,
        tabla_afectada VARCHAR(80) NOT NULL,
        registro_id INT DEFAULT NULL,
        detalle TEXT,
        ip VARCHAR(45),
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_auditoria_fecha (creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    addColumnIfMissing($pdo, 'animales', 'tamanio', "ENUM('pequeño','mediano','grande','gigante') DEFAULT 'mediano' AFTER edad_meses");
    addColumnIfMissing($pdo, 'animales', 'vacunacion_estado', "ENUM('completa','parcial','pendiente','desconocida') DEFAULT 'pendiente' AFTER estado_salud");
    addColumnIfMissing($pdo, 'animales', 'esterilizacion_estado', "ENUM('esterilizado','no_esterilizado','pendiente','desconocido') DEFAULT 'pendiente' AFTER vacunacion_estado");
    addColumnIfMissing($pdo, 'animales', 'fecha_ingreso', "DATE DEFAULT NULL AFTER fecha_rescate");
    addColumnIfMissing($pdo, 'animales', 'personalidad', "TEXT AFTER lugar_rescate");
    addColumnIfMissing($pdo, 'animales', 'historia_rescate', "TEXT AFTER personalidad");
    addColumnIfMissing($pdo, 'animales', 'observaciones_adicionales', "TEXT AFTER observaciones");

    addColumnIfMissing($pdo, 'adoptantes', 'fecha_nacimiento', "DATE DEFAULT NULL AFTER dni");
    addColumnIfMissing($pdo, 'adoptantes', 'ciudad', "VARCHAR(100) AFTER direccion");
    addColumnIfMissing($pdo, 'adoptantes', 'profesion', "VARCHAR(120) AFTER ciudad");
    addColumnIfMissing($pdo, 'adoptantes', 'referencia_personal', "VARCHAR(200) AFTER profesion");
    addColumnIfMissing($pdo, 'adoptantes', 'motivo_adopcion', "TEXT AFTER referencia_personal");
    addColumnIfMissing($pdo, 'adoptantes', 'tipo_vivienda', "ENUM('casa','departamento','habitacion','quinta','otro') DEFAULT 'casa' AFTER motivo_adopcion");
    addColumnIfMissing($pdo, 'adoptantes', 'tiene_patio', "TINYINT(1) DEFAULT 0 AFTER tipo_vivienda");
    addColumnIfMissing($pdo, 'adoptantes', 'posee_otras_mascotas', "TINYINT(1) DEFAULT 0 AFTER tiene_patio");
    addColumnIfMissing($pdo, 'adoptantes', 'cantidad_personas_hogar', "TINYINT UNSIGNED DEFAULT 1 AFTER posee_otras_mascotas");
    addColumnIfMissing($pdo, 'adoptantes', 'observaciones', "TEXT AFTER cantidad_personas_hogar");

    addColumnIfMissing($pdo, 'adopciones', 'fecha_adopcion', "DATE DEFAULT NULL AFTER fecha_solicitud");
    addColumnIfMissing($pdo, 'adopciones', 'seguimiento_estado', "ENUM('pendiente','programado','en_seguimiento','completado','alerta') DEFAULT 'pendiente' AFTER estado");

    addColumnIfMissing($pdo, 'donaciones', 'cantidad', "DECIMAL(10,2) DEFAULT 0 AFTER monto");
    addColumnIfMissing($pdo, 'donaciones', 'unidad', "VARCHAR(40) DEFAULT NULL AFTER cantidad");
    addColumnIfMissing($pdo, 'donaciones', 'observaciones', "TEXT AFTER descripcion");
    if (tableExists($pdo, 'donaciones')) {
        try {
            $pdo->exec("ALTER TABLE donaciones MODIFY tipo ENUM('dinero','alimento','medicina','accesorios','monetaria','especie') NOT NULL DEFAULT 'dinero'");
        } catch (Throwable $e) {
            return;
        }
    }
}
