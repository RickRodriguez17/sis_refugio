<?php
require_once '../includes/config.php';
$titulo = 'Seguimiento Post-Adopción';
$pdo = conectar();

$accion = $_GET['accion'] ?? 'listar';

// Registra visitas posteriores a la adopción, con fotos opcionales como evidencia.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $fotos = uploadMultipleFiles('fotos', 'seguimientos');
    $pdo->prepare("INSERT INTO seguimientos (adopcion_id, animal_id, fecha, estado_mascota, comentarios, fotos, responsable) VALUES (?,?,?,?,?,?,?)")->execute([
        $_POST['adopcion_id'] ?: null,
        (int)$_POST['animal_id'],
        $_POST['fecha'],
        trim($_POST['estado_mascota']),
        trim($_POST['comentarios']),
        json_encode($fotos, JSON_UNESCAPED_SLASHES),
        trim($_POST['responsable']),
    ]);
    $seguimientoId = (int)$pdo->lastInsertId();
    if (!empty($_POST['adopcion_id'])) {
        $pdo->prepare("UPDATE adopciones SET seguimiento_estado='en_seguimiento' WHERE id=?")->execute([(int)$_POST['adopcion_id']]);
    }
    logAudit($pdo, 'crear', 'seguimientos', $seguimientoId, 'Seguimiento post-adopción registrado');
    setFlash('success', 'Seguimiento registrado.');
    header("Location: seguimientos.php");
    exit;
}

include '../includes/header.php';

if ($accion === 'nuevo'):
$adopciones = $pdo->query("
    SELECT a.id, a.animal_id, an.nombre animal_nombre, ad.nombre_completo
    FROM adopciones a
    JOIN animales an ON a.animal_id=an.id
    JOIN adoptantes ad ON a.adoptante_id=ad.id
    WHERE a.estado IN ('aprobada','entregada')
    ORDER BY a.creado_en DESC
")->fetchAll();
$animales = $pdo->query("SELECT id,nombre FROM animales ORDER BY nombre")->fetchAll();
?>
<div class="form-card">
    <h2>➕ Registrar seguimiento</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group"><label>Adopción vinculada</label><select name="adopcion_id"><option value="">Sin adopción vinculada</option><?php foreach($adopciones as $a): ?><option value="<?= $a['id'] ?>" data-animal="<?= $a['animal_id'] ?>"><?= e($a['animal_nombre']) ?> — <?= e($a['nombre_completo']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Mascota</label><select name="animal_id" required><?php foreach($animales as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['nombre']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Fecha</label><input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
            <div class="form-group"><label>Responsable</label><input type="text" name="responsable" value="<?= e($_SESSION['nombre'] ?? '') ?>"></div>
            <div class="form-group full"><label>Estado de la mascota</label><input type="text" name="estado_mascota" placeholder="Ej: sana, adaptada, requiere revisión" required></div>
            <div class="form-group full"><label>Comentarios</label><textarea name="comentarios"></textarea></div>
            <div class="form-group full"><label>Fotografías del seguimiento</label><input type="file" name="fotos[]" accept="image/*" multiple></div>
        </div>
        <div class="form-actions"><button name="guardar" class="btn btn-primary">Guardar seguimiento</button><a href="seguimientos.php" class="btn btn-secondary">Cancelar</a></div>
    </form>
</div>
<?php else:
$seguimientos = $pdo->query("
    SELECT s.*, an.nombre animal_nombre
    FROM seguimientos s
    JOIN animales an ON s.animal_id=an.id
    ORDER BY s.fecha DESC, s.id DESC
")->fetchAll();
?>
<div class="card">
    <div class="card-header"><h2>🩺 Seguimientos post-adopción</h2><a href="seguimientos.php?accion=nuevo" class="btn btn-primary btn-sm">➕ Nuevo</a></div>
    <div class="timeline">
        <?php foreach($seguimientos as $s): $fotos = json_decode($s['fotos'] ?: '[]', true) ?: []; ?>
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-card">
                <h3><?= e($s['animal_nombre']) ?> · <?= fechaLatina($s['fecha']) ?></h3>
                <p><strong>Estado:</strong> <?= e($s['estado_mascota']) ?></p>
                <p><?= nl2br(e($s['comentarios'])) ?></p>
                <small>Responsable: <?= e($s['responsable'] ?: '-') ?></small>
                <?php if ($fotos): ?><div class="mini-gallery"><?php foreach($fotos as $foto): ?><img src="<?= e(publicPath($foto)) ?>" alt="Foto seguimiento"><?php endforeach; ?></div><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$seguimientos): ?><div class="empty-state">Sin seguimientos registrados.</div><?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
