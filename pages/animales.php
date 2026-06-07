

<?php
require_once '../includes/config.php';
$titulo = 'Gestión de Mascotas';
$pdo = conectar();

// ---- ACCIONES ----
$accion = $_GET['accion'] ?? 'listar';
$id     = (int)($_GET['id'] ?? 0);

// Guarda la ficha clínica/social de la mascota y procesa las fotografías subidas.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $nombre        = trim($_POST['nombre']);
    $especie       = $_POST['especie'];
    $raza          = trim($_POST['raza']);
    $edad_anios    = (int)$_POST['edad_anios'];
    $edad_meses    = (int)$_POST['edad_meses'];
    $tamanio       = $_POST['tamanio'];
    $sexo          = $_POST['sexo'];
    $peso          = (float)str_replace(',', '.', $_POST['peso']);
    $color         = trim($_POST['color']);
    $estado_salud  = $_POST['estado_salud'];
    $vacunacion    = $_POST['vacunacion_estado'];
    $esterilizacion = $_POST['esterilizacion_estado'];
    $fecha_rescate = $_POST['fecha_rescate'];
    $fecha_ingreso = $_POST['fecha_ingreso'];
    $lugar_rescate = trim($_POST['lugar_rescate']);
    $personalidad  = trim($_POST['personalidad']);
    $historia      = trim($_POST['historia_rescate']);
    $estado        = $_POST['estado'];
    $observaciones = trim($_POST['observaciones']);
    $obs_extra     = trim($_POST['observaciones_adicionales']);
    $fotoPrincipal = uploadFile('foto_principal', 'animales');

    if ($id) {
        $sqlFoto = $fotoPrincipal ? ", foto=?" : "";
        $datos = [
            $nombre, $especie, $raza, $edad_anios, $edad_meses, $tamanio, $sexo, $peso,
            $color, $estado_salud, $vacunacion, $esterilizacion, $fecha_rescate, $fecha_ingreso,
            $lugar_rescate, $personalidad, $historia, $estado, $observaciones, $obs_extra,
        ];
        if ($fotoPrincipal) {
            $datos[] = $fotoPrincipal;
        }
        $datos[] = $id;
        $pdo->prepare("UPDATE animales SET nombre=?, especie=?, raza=?,
            edad_años=?, edad_meses=?, tamanio=?, sexo=?, peso=?,
            color=?, estado_salud=?, vacunacion_estado=?, esterilizacion_estado=?, fecha_rescate=?, fecha_ingreso=?,
            lugar_rescate=?, personalidad=?, historia_rescate=?, estado=?, observaciones=?, observaciones_adicionales=? $sqlFoto
            WHERE id=?")->execute($datos);
        logAudit($pdo, 'actualizar', 'animales', $id, "Ficha de $nombre actualizada");
        setFlash('success', 'Animal actualizado correctamente.');
    } else {
        $pdo->prepare("INSERT INTO animales (nombre, especie, raza, edad_años, edad_meses, tamanio, sexo, peso,
            color, estado_salud, vacunacion_estado, esterilizacion_estado, fecha_rescate, fecha_ingreso,
            lugar_rescate, personalidad, historia_rescate, estado, foto, observaciones, observaciones_adicionales)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $nombre, $especie, $raza, $edad_anios, $edad_meses, $tamanio, $sexo, $peso,
            $color, $estado_salud, $vacunacion, $esterilizacion, $fecha_rescate, $fecha_ingreso,
            $lugar_rescate, $personalidad, $historia, $estado, $fotoPrincipal, $observaciones, $obs_extra
        ]);
        $id = (int)$pdo->lastInsertId();
        logAudit($pdo, 'crear', 'animales', $id, "Mascota $nombre registrada");
        setFlash('success', 'Animal registrado correctamente.');
    }
    foreach (uploadMultipleFiles('fotos_adicionales', 'animales') as $ruta) {
        $pdo->prepare("INSERT INTO animal_fotos (animal_id, ruta, tipo) VALUES (?,?, 'galeria')")->execute([$id, $ruta]);
    }
    header("Location: animales.php");
    exit;
}

// El borrado se confirma desde la interfaz para evitar eliminaciones accidentales.
if ($accion === 'eliminar' && $id) {
    $pdo->prepare("DELETE FROM animales WHERE id=?")->execute([$id]);
    logAudit($pdo, 'eliminar', 'animales', $id, 'Mascota eliminada');
    setFlash('success', 'Animal eliminado.');
    header("Location: animales.php");
    exit;
}

// Carga datos para editar/ver, incluyendo galería de fotos.
$animal = [];
$fotos = [];
if (($accion === 'editar' || $accion === 'ver') && $id) {
    $stmt = $pdo->prepare("SELECT * FROM animales WHERE id=?");
    $stmt->execute([$id]);
    $animal = $stmt->fetch();
    $sf = $pdo->prepare("SELECT * FROM animal_fotos WHERE animal_id=? ORDER BY creado_en DESC");
    $sf->execute([$id]);
    $fotos = $sf->fetchAll();
}

include '../includes/header.php';

// Vista detallada: muestra ficha completa y acceso directo al proceso de adopción.
if ($accion === 'ver' && $animal):
?>
<div class="pet-detail">
    <div class="pet-detail-photo">
        <?php if (!empty($animal['foto'])): ?>
        <img src="<?= e(publicPath($animal['foto'])) ?>" alt="<?= e($animal['nombre']) ?>">
        <?php else: ?>
        <div class="pet-placeholder">🐾</div>
        <?php endif; ?>
    </div>
    <div class="pet-detail-info">
        <span class="badge badge-verde"><?= ucfirst(str_replace('_',' ', e($animal['estado']))) ?></span>
        <h2><?= e($animal['nombre']) ?></h2>
        <p><?= ucfirst(e($animal['especie'])) ?> · <?= e($animal['raza'] ?: 'Sin raza') ?> · <?= edadAnimal($animal) ?></p>
        <div class="detail-grid">
            <div><strong>Sexo:</strong> <?= ucfirst(e($animal['sexo'])) ?></div>
            <div><strong>Tamaño:</strong> <?= ucfirst(e($animal['tamanio'] ?? 'mediano')) ?></div>
            <div><strong>Peso:</strong> <?= e($animal['peso']) ?> kg</div>
            <div><strong>Color:</strong> <?= e($animal['color']) ?></div>
            <div><strong>Salud:</strong> <?= ucfirst(e($animal['estado_salud'])) ?></div>
            <div><strong>Vacunas:</strong> <?= ucfirst(e($animal['vacunacion_estado'] ?? 'pendiente')) ?></div>
            <div><strong>Esterilización:</strong> <?= ucfirst(str_replace('_',' ', e($animal['esterilizacion_estado'] ?? 'pendiente'))) ?></div>
            <div><strong>Ingreso:</strong> <?= fechaLatina($animal['fecha_ingreso'] ?? $animal['fecha_rescate']) ?></div>
        </div>
        <h3>Personalidad</h3>
        <p><?= nl2br(e($animal['personalidad'] ?? 'Sin información registrada.')) ?></p>
        <h3>Historia del rescate</h3>
        <p><?= nl2br(e($animal['historia_rescate'] ?? $animal['observaciones'] ?? 'Sin historia registrada.')) ?></p>
        <div class="form-actions">
            <?php if ($animal['estado'] === 'disponible'): ?>
            <a href="adopciones.php?accion=nueva&animal_id=<?= $animal['id'] ?>" class="btn btn-primary">🏠 Iniciar adopción</a>
            <?php endif; ?>
            <a href="animales.php?accion=editar&id=<?= $animal['id'] ?>" class="btn btn-warning">✏️ Editar ficha</a>
            <a href="animales.php" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>
<?php if ($fotos): ?>
<div class="card">
    <div class="card-header"><h2>🖼️ Fotografías adicionales</h2></div>
    <div class="gallery-strip">
        <?php foreach ($fotos as $foto): ?>
        <img src="<?= e(publicPath($foto['ruta'])) ?>" alt="Foto adicional">
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
// ===== FORMULARIO (nuevo / editar) =====
elseif ($accion === 'nuevo' || $accion === 'editar'):
$es_editar = $accion === 'editar';
?>
<div class="form-card">
    <h2 style="margin-bottom:20px;"><?= $es_editar ? '✏️ Editar Mascota' : '➕ Registrar Mascota' ?></h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group full">
                <label>Fotografía principal</label>
                <input type="file" name="foto_principal" accept="image/*">
                <?php if (!empty($animal['foto'])): ?>
                <img src="<?= e(publicPath($animal['foto'])) ?>" class="preview-img" alt="Foto actual">
                <?php endif; ?>
            </div>
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
                <label>Tamaño</label>
                <select name="tamanio">
                    <?php foreach(['pequeño'=>'Pequeño','mediano'=>'Mediano','grande'=>'Grande','gigante'=>'Gigante'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= ($animal['tamanio']??'mediano')===$k?'selected':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
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
                <label>Vacunación</label>
                <select name="vacunacion_estado">
                    <?php foreach(['completa'=>'Completa','parcial'=>'Parcial','pendiente'=>'Pendiente','desconocida'=>'Desconocida'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= ($animal['vacunacion_estado']??'pendiente')===$k?'selected':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Esterilización</label>
                <select name="esterilizacion_estado">
                    <?php foreach(['esterilizado'=>'Esterilizado','no_esterilizado'=>'No esterilizado','pendiente'=>'Pendiente','desconocido'=>'Desconocido'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= ($animal['esterilizacion_estado']??'pendiente')===$k?'selected':'' ?>><?= $v ?></option>
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
                <label>Fecha de Ingreso al Refugio *</label>
                <input type="date" name="fecha_ingreso" value="<?= e($animal['fecha_ingreso'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="form-group">
                <label>Lugar de Rescate</label>
                <input type="text" name="lugar_rescate" value="<?= e($animal['lugar_rescate'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Personalidad o comportamiento</label>
                <textarea name="personalidad" placeholder="Ej: sociable, protector, tímido, juguetón..."><?= e($animal['personalidad'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Historia o motivo del rescate</label>
                <textarea name="historia_rescate" placeholder="Describe el contexto del rescate y necesidades especiales."><?= e($animal['historia_rescate'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="observaciones"><?= e($animal['observaciones'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Observaciones adicionales</label>
                <textarea name="observaciones_adicionales"><?= e($animal['observaciones_adicionales'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Fotografías adicionales</label>
                <input type="file" name="fotos_adicionales[]" accept="image/*" multiple>
                <?php if ($fotos): ?>
                <div class="mini-gallery">
                    <?php foreach ($fotos as $foto): ?>
                    <img src="<?= e(publicPath($foto['ruta'])) ?>" alt="Foto de <?= e($animal['nombre'] ?? 'mascota') ?>">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" name="guardar" class="btn btn-primary">
                <?= $es_editar ? '💾 Guardar Cambios' : '➕ Registrar Mascota' ?>
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
$sexo    = $_GET['sexo'] ?? '';
$tamanio = $_GET['tamanio'] ?? '';
$edad    = $_GET['edad'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';

$where  = "WHERE 1=1";
$params = [];
if ($buscar)  { $where .= " AND (nombre LIKE ? OR raza LIKE ? OR color LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
if ($especie) { $where .= " AND especie=?"; $params[] = $especie; }
if ($estado)  { $where .= " AND estado=?"; $params[] = $estado; }
if ($sexo)    { $where .= " AND sexo=?"; $params[] = $sexo; }
if ($tamanio) { $where .= " AND tamanio=?"; $params[] = $tamanio; }
if ($edad !== '') { $where .= " AND edad_años=?"; $params[] = (int)$edad; }
if ($fecha_desde) { $where .= " AND COALESCE(fecha_ingreso, fecha_rescate) >= ?"; $params[] = $fecha_desde; }

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
            <div class="form-group" style="min-width:140px; margin:0;">
                <label>Sexo</label>
                <select name="sexo">
                    <option value="">Todos</option>
                    <option value="macho" <?= $sexo==='macho'?'selected':'' ?>>Macho</option>
                    <option value="hembra" <?= $sexo==='hembra'?'selected':'' ?>>Hembra</option>
                </select>
            </div>
            <div class="form-group" style="min-width:140px; margin:0;">
                <label>Tamaño</label>
                <select name="tamanio">
                    <option value="">Todos</option>
                    <?php foreach(['pequeño','mediano','grande','gigante'] as $tam): ?>
                    <option value="<?= $tam ?>" <?= $tamanio===$tam?'selected':'' ?>><?= ucfirst($tam) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="min-width:120px; margin:0;">
                <label>Edad (años)</label>
                <input type="number" name="edad" min="0" value="<?= e($edad) ?>">
            </div>
            <div class="form-group" style="min-width:160px; margin:0;">
                <label>Ingreso desde</label>
                <input type="date" name="fecha_desde" value="<?= e($fecha_desde) ?>">
            </div>
            <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
            <a href="animales.php" class="btn btn-secondary">Limpiar</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>🐾 Mascotas Registradas (<?= $total ?>)</h2>
        <a href="animales.php?accion=nuevo" class="btn btn-primary btn-sm">➕ Nuevo Animal</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Foto</th><th>Nombre</th><th>Especie</th><th>Raza</th>
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
                <td>
                    <?php if (!empty($a['foto'])): ?>
                    <img src="<?= e(publicPath($a['foto'])) ?>" class="table-img" alt="<?= e($a['nombre']) ?>">
                    <?php else: ?>
                    <span class="table-placeholder">🐾</span>
                    <?php endif; ?>
                </td>
                <td><strong><?= e($a['nombre']) ?></strong><br><small><?= ucfirst(e($a['tamanio'] ?? 'mediano')) ?></small></td>
                <td><?= ucfirst(e($a['especie'])) ?></td>
                <td><?= e($a['raza']) ?: '-' ?></td>
                <td><?= edadAnimal($a) ?></td>
                <td><?= ucfirst(e($a['sexo'])) ?></td>
                <td><span class="badge <?= $badgeSalud[$a['estado_salud']] ?? 'badge-gris' ?>"><?= ucfirst(e($a['estado_salud'])) ?></span></td>
                <td><span class="badge <?= $badgeEst[$a['estado']] ?? 'badge-gris' ?>"><?= ucfirst(str_replace('_',' ',$a['estado'])) ?></span></td>
                <td><?= date('d/m/Y', strtotime($a['fecha_rescate'])) ?></td>
                <td>
                    <a href="animales.php?accion=ver&id=<?= $a['id'] ?>" class="btn btn-primary btn-sm">👁️</a>
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
        <a href="?pag=<?= $p ?>&buscar=<?= urlencode($buscar) ?>&especie=<?= urlencode($especie) ?>&estado=<?= urlencode($estado) ?>&sexo=<?= urlencode($sexo) ?>&tamanio=<?= urlencode($tamanio) ?>&edad=<?= urlencode($edad) ?>&fecha_desde=<?= urlencode($fecha_desde) ?>"
           class="<?= $p==$pagina?'activa':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php include '../includes/footer.php'; ?>