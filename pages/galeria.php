<?php
require_once '../includes/config.php';
$titulo = 'Galería de Mascotas Disponibles';
$pdo = conectar();

// La galería solo muestra mascotas disponibles y reutiliza los filtros principales de adopción.
$buscar = trim($_GET['buscar'] ?? '');
$especie = $_GET['especie'] ?? '';
$sexo = $_GET['sexo'] ?? '';
$tamanio = $_GET['tamanio'] ?? '';

$where = "WHERE estado='disponible'";
$params = [];
if ($buscar) {
    $where .= " AND (nombre LIKE ? OR raza LIKE ? OR personalidad LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}
if ($especie) { $where .= " AND especie=?"; $params[] = $especie; }
if ($sexo) { $where .= " AND sexo=?"; $params[] = $sexo; }
if ($tamanio) { $where .= " AND tamanio=?"; $params[] = $tamanio; }

$stmt = $pdo->prepare("SELECT * FROM animales $where ORDER BY COALESCE(fecha_ingreso, fecha_rescate) DESC, nombre");
$stmt->execute($params);
$mascotas = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="hero-panel">
    <div>
        <span class="hero-badge">🐾 Adopciones responsables</span>
        <h2>Encuentra una familia para cada mascota</h2>
        <p>Tarjetas visuales con fotografía, datos clave y acceso al formulario completo de adopción.</p>
    </div>
    <a href="animales.php?accion=nuevo" class="btn btn-primary">➕ Registrar mascota</a>
</div>

<div class="card filters-card">
    <form method="GET" class="filter-grid">
        <div class="form-group"><label>Buscar</label><input type="text" name="buscar" value="<?= e($buscar) ?>" placeholder="Nombre, raza o personalidad"></div>
        <div class="form-group"><label>Especie</label><select name="especie"><option value="">Todas</option><?php foreach(['perro','gato','ave','conejo','otro'] as $v): ?><option value="<?= $v ?>" <?= $especie===$v?'selected':'' ?>><?= ucfirst($v) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Sexo</label><select name="sexo"><option value="">Todos</option><option value="macho" <?= $sexo==='macho'?'selected':'' ?>>Macho</option><option value="hembra" <?= $sexo==='hembra'?'selected':'' ?>>Hembra</option></select></div>
        <div class="form-group"><label>Tamaño</label><select name="tamanio"><option value="">Todos</option><?php foreach(['pequeño','mediano','grande','gigante'] as $v): ?><option value="<?= $v ?>" <?= $tamanio===$v?'selected':'' ?>><?= ucfirst($v) ?></option><?php endforeach; ?></select></div>
        <div class="form-actions inline"><button class="btn btn-primary">Filtrar</button><a href="galeria.php" class="btn btn-secondary">Limpiar</a></div>
    </form>
</div>

<div class="pet-gallery">
    <?php foreach ($mascotas as $m): ?>
    <a class="pet-card" href="animales.php?accion=ver&id=<?= $m['id'] ?>">
        <div class="pet-card-photo">
            <?php if (!empty($m['foto'])): ?>
            <img src="<?= e(publicPath($m['foto'])) ?>" alt="<?= e($m['nombre']) ?>">
            <?php else: ?>
            <div class="pet-placeholder">🐾</div>
            <?php endif; ?>
            <span class="badge badge-verde">Disponible</span>
        </div>
        <div class="pet-card-body">
            <h3><?= e($m['nombre']) ?></h3>
            <p><?= ucfirst(e($m['especie'])) ?> · <?= edadAnimal($m) ?></p>
            <div class="pet-tags">
                <span><?= ucfirst(e($m['sexo'])) ?></span>
                <span><?= ucfirst(e($m['tamanio'] ?? 'mediano')) ?></span>
                <span><?= ucfirst(e($m['estado_salud'])) ?></span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
    <?php if (!$mascotas): ?>
    <div class="empty-state">No hay mascotas disponibles con esos filtros.</div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
