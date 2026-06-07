

<?php
require_once '../includes/config.php';
$titulo = 'Gestión de Animales';
$pdo = conectar();

// ---- ACCIONES ----
$accion = $_GET['accion'] ?? 'listar';
$id     = (int)($_GET['id'] ?? 0);

// GUARDAR (nuevo o editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $nombre        = trim($_POST['nombre']);
    $especie       = $_POST['especie'];
    $raza          = trim($_POST['raza']);
    $edad_anios    = (int)$_POST['edad_anios'];
    $edad_meses    = (int)$_POST['edad_meses'];
    $sexo          = $_POST['sexo'];
    $peso          = (float)str_replace(',', '.', $_POST['peso']);
    $color         = trim($_POST['color']);
    $estado_salud  = $_POST['estado_salud'];
    $fecha_rescate = $_POST['fecha_rescate'];
    $lugar_rescate = trim($_POST['lugar_rescate']);
    $estado        = $_POST['estado'];
    $observaciones = trim($_POST['observaciones']);

    if ($id) {
        $pdo->prepare("UPDATE animales SET nombre=?, especie=?, raza=?,
            edad_años=?, edad_meses=?, sexo=?, peso=?,
            color=?, estado_salud=?, fecha_rescate=?,
            lugar_rescate=?, estado=?, observaciones=?
            WHERE id=?")->execute([
            $nombre, $especie, $raza,
            $edad_anios, $edad_meses, $sexo, $peso,
            $color, $estado_salud, $fecha_rescate,
            $lugar_rescate, $estado, $observaciones,
            $id
        ]);
        setFlash('success', 'Animal actualizado correctamente.');
    } else {
        $pdo->prepare("INSERT INTO animales (nombre, especie, raza, edad_años, edad_meses, sexo, peso,
            color, estado_salud, fecha_rescate, lugar_rescate, estado, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $nombre, $especie, $raza,
            $edad_anios, $edad_meses, $sexo, $peso,
            $color, $estado_salud, $fecha_rescate,
            $lugar_rescate, $estado, $observaciones
        ]);
        setFlash('success', 'Animal registrado correctamente.');
    }
    header("Location: animales.php");
    exit;
}

// ELIMINAR
if ($accion === 'eliminar' && $id) {
    $pdo->prepare("DELETE FROM animales WHERE id=?")->execute([$id]);
    setFlash('success', 'Animal eliminado.');
    header("Location: animales.php");
    exit;
}

// Cargar datos para editar
$animal = [];
if (($accion === 'editar' || $accion === 'ver') && $id) {
    $stmt = $pdo->prepare("SELECT * FROM animales WHERE id=?");
    $stmt->execute([$id]);
    $animal = $stmt->fetch();
}

include '../includes/header.php';

// ===== FORMULARIO (nuevo / editar) =====
if ($accion === 'nuevo' || $accion === 'editar'):
$es_editar = $accion === 'editar';
?>
<div class="form-card">
    <h2 style="margin-bottom:20px;"><?= $es_editar ? '✏️ Editar Animal' : '➕ Registrar Animal' ?></h2>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" value="<?= e($animal['nombre'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Especie *</label>
                <select name="especie" required>
                    <?php foreach(['perro','gato','ave','conejo','otro'] as $esp): ?>
                    <option value="<?= $esp ?>" <?= ($animal['especie']??'')===$esp?'selected':'' ?>><?= ucfirst($esp) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Raza</label>
                <input type="text" name="raza" value="<?= e($animal['raza'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Sexo *</label>
                <select name="sexo" required>
                    <option value="macho"  <?= ($animal['sexo']??'')==='macho' ?'selected':'' ?>>Macho</option>
                    <option value="hembra" <?= ($animal['sexo']??'')==='hembra'?'selected':'' ?>>Hembra</option>
                </select>
            </div>
            <div class="form-group">
                <label>Edad (años)</label>
                <input type="number" name="edad_anios" min="0" max="30" value="<?= e($animal['edad_años'] ?? 0) ?>">
            </div>
            <div class="form-group">
                <label>Edad (meses)</label>
                <input type="number" name="edad_meses" min="0" max="11" value="<?= e($animal['edad_meses'] ?? 0) ?>">
            </div>
            <div class="form-group">
                <label>Peso (kg)</label>
                <input type="number" step="0.1" name="peso" value="<?= e($animal['peso'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color" value="<?= e($animal['color'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Estado de Salud *</label>
                <select name="estado_salud" required>
                    <?php foreach(['excelente'=>'Excelente','bueno'=>'Bueno','regular'=>'Regular','critico'=>'Crítico'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= ($animal['estado_salud']??'')===$k?'selected':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Estado Actual *</label>
                <select name="estado" required>
                    <?php foreach(['disponible'=>'Disponible','en_tratamiento'=>'En Tratamiento','reservado'=>'Reservado','adoptado'=>'Adoptado'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= ($animal['estado']??'disponible')===$k?'selected':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de Rescate *</label>
                <input type="date" name="fecha_rescate" value="<?= e($animal['fecha_rescate'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="form-group">
                <label>Lugar de Rescate</label>
                <input type="text" name="lugar_rescate" value="<?= e($animal['lugar_rescate'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="observaciones"><?= e($animal['observaciones'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" name="guardar" class="btn btn-primary">
                <?= $es_editar ? '💾 Guardar Cambios' : '➕ Registrar Animal' ?>
            </button>
            <a href="animales.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php
// ===== LISTADO =====
else:
// Filtros
$buscar  = trim($_GET['buscar'] ?? '');
$especie = $_GET['especie'] ?? '';
$estado  = $_GET['estado'] ?? '';

$where  = "WHERE 1=1";
$params = [];
if ($buscar)  { $where .= " AND (nombre LIKE ? OR raza LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
if ($especie) { $where .= " AND especie=?"; $params[] = $especie; }
if ($estado)  { $where .= " AND estado=?"; $params[] = $estado; }

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM animales $where");
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();

$pagina  = max(1, (int)($_GET['pag'] ?? 1));
$limit   = 10;
$offset  = ($pagina - 1) * $limit;
$paginas = ceil($total / $limit);

$stmt = $pdo->prepare("SELECT * FROM animales $where ORDER BY creado_en DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$animales = $stmt->fetchAll();
?>

<!-- Filtros -->
<div class="card" style="margin-bottom:16px;">
    <div style="padding:14px 18px;">
        <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
            <div class="form-group" style="flex:1; min-width:180px; margin:0;">
                <label>Buscar</label>
                <input type="text" name="buscar" placeholder="Nombre o raza..." value="<?= e($buscar) ?>">
            </div>
            <div class="form-group" style="min-width:140px; margin:0;">
                <label>Especie</label>
                <select name="especie">
                    <option value="">Todas</option>
                    <?php foreach(['perro','gato','ave','conejo','otro'] as $esp): ?>
                    <option value="<?= $esp ?>" <?= $especie===$esp?'selected':'' ?>><?= ucfirst($esp) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="min-width:140px; margin:0;">
                <label>Estado</label>
                <select name="estado">
                    <option value="">Todos</option>
                    <?php foreach(['disponible','en_tratamiento','reservado','adoptado'] as $est): ?>
                    <option value="<?= $est ?>" <?= $estado===$est?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$est)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
            <a href="animales.php" class="btn btn-secondary">Limpiar</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>🐾 Animales Registrados (<?= $total ?>)</h2>
        <a href="animales.php?accion=nuevo" class="btn btn-primary btn-sm">➕ Nuevo Animal</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Nombre</th><th>Especie</th><th>Raza</th>
                    <th>Edad</th><th>Sexo</th><th>Salud</th><th>Estado</th>
                    <th>Fecha Rescate</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($animales as $a):
                $badgeSalud = ['excelente'=>'badge-verde','bueno'=>'badge-azul','regular'=>'badge-amarillo','critico'=>'badge-rojo'];
                $badgeEst   = ['disponible'=>'badge-verde','adoptado'=>'badge-azul','en_tratamiento'=>'badge-amarillo','reservado'=>'badge-gris'];
            ?>
            <tr>
                <td><?= $a['id'] ?></td>
                <td><strong><?= e($a['nombre']) ?></strong></td>
                <td><?= ucfirst(e($a['especie'])) ?></td>
                <td><?= e($a['raza']) ?: '-' ?></td>
                <td><?= $a['edad_años'] ?>a <?= $a['edad_meses'] ?>m</td>
                <td><?= ucfirst(e($a['sexo'])) ?></td>
                <td><span class="badge <?= $badgeSalud[$a['estado_salud']] ?? 'badge-gris' ?>"><?= ucfirst(e($a['estado_salud'])) ?></span></td>
                <td><span class="badge <?= $badgeEst[$a['estado']] ?? 'badge-gris' ?>"><?= ucfirst(str_replace('_',' ',$a['estado'])) ?></span></td>
                <td><?= date('d/m/Y', strtotime($a['fecha_rescate'])) ?></td>
                <td>
                    <a href="animales.php?accion=editar&id=<?= $a['id'] ?>" class="btn btn-warning btn-sm" title="Editar">✏️</a>
                    <a href="animales.php?accion=eliminar&id=<?= $a['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('¿Eliminar este animal?')" title="Eliminar">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($animales)): ?>
            <tr><td colspan="10" style="text-align:center; padding:30px; color:#9ca3af;">No se encontraron animales.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($paginas > 1): ?>
    <div class="paginacion" style="padding:14px;">
        <?php for($p=1; $p<=$paginas; $p++): ?>
        <a href="?pag=<?= $p ?>&buscar=<?= urlencode($buscar) ?>&especie=<?= urlencode($especie) ?>&estado=<?= urlencode($estado) ?>"
           class="<?= $p==$pagina?'activa':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php include '../includes/footer.php'; ?>