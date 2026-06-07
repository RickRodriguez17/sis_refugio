<?php
require_once '../includes/config.php';
$titulo = 'Gestión de Adopciones';
$pdo = conectar();

$accion = $_GET['accion'] ?? 'listar';
$id     = (int)($_GET['id'] ?? 0);

// GUARDAR nueva adopción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    // Crear adoptante si es nuevo
    $adoptante_id = (int)$_POST['adoptante_id'];

    if (!$adoptante_id && !empty($_POST['nuevo_nombre'])) {
        $pdo->prepare("INSERT INTO adoptantes (nombre_completo,dni,telefono,email,direccion,ocupacion,estado_civil)
            VALUES (?,?,?,?,?,?,?)")->execute([
            trim($_POST['nuevo_nombre']),
            trim($_POST['nuevo_dni']),
            trim($_POST['nuevo_telefono']),
            trim($_POST['nuevo_email']),
            trim($_POST['nuevo_direccion']),
            trim($_POST['nuevo_ocupacion']),
            $_POST['nuevo_estado_civil'],
        ]);
        $adoptante_id = $pdo->lastInsertId();
    }

    $pdo->prepare("INSERT INTO adopciones (animal_id,adoptante_id,fecha_solicitud,estado,observaciones,responsable_id)
        VALUES (?,?,?,?,?,?)")->execute([
        (int)$_POST['animal_id'],
        $adoptante_id,
        $_POST['fecha_solicitud'],
        $_POST['estado'],
        trim($_POST['observaciones']),
        $_SESSION['usuario_id'],
    ]);
    setFlash('success', 'Adopción registrada correctamente.');
    header("Location: adopciones.php");
    exit;
}

// CAMBIAR ESTADO
if ($accion === 'estado' && $id && isset($_GET['nuevo_estado'])) {
    $nuevo = $_GET['nuevo_estado'];
    $pdo->prepare("UPDATE adopciones SET estado=? WHERE id=?")->execute([$nuevo, $id]);
    // Si se aprueba/entrega, marcar animal como adoptado
    if (in_array($nuevo, ['aprobada','entregada'])) {
        $adopcion = $pdo->prepare("SELECT animal_id FROM adopciones WHERE id=?");
        $adopcion->execute([$id]);
        $anim_id = $adopcion->fetchColumn();
        $pdo->prepare("UPDATE animales SET estado='adoptado' WHERE id=?")->execute([$anim_id]);
    }
    setFlash('success', 'Estado actualizado.');
    header("Location: adopciones.php");
    exit;
}

include '../includes/header.php';

if ($accion === 'nueva'):
    $animales_disp = $pdo->query("SELECT id,nombre,especie FROM animales WHERE estado='disponible' ORDER BY nombre")->fetchAll();
    $adoptantes    = $pdo->query("SELECT id,nombre_completo,dni FROM adoptantes ORDER BY nombre_completo")->fetchAll();
?>
<div class="form-card">
    <h2 style="margin-bottom:20px;">➕ Nueva Adopción</h2>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Animal *</label>
                <select name="animal_id" required>
                    <option value="">-- Seleccionar Animal --</option>
                    <?php foreach ($animales_disp as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= e($a['nombre']) ?> (<?= e($a['especie']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Adoptante existente</label>
                <select name="adoptante_id">
                    <option value="">-- Nuevo adoptante abajo --</option>
                    <?php foreach ($adoptantes as $ad): ?>
                    <option value="<?= $ad['id'] ?>"><?= e($ad['nombre_completo']) ?> (<?= e($ad['dni']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha Solicitud *</label>
                <input type="date" name="fecha_solicitud" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado">
                    <option value="pendiente">Pendiente</option>
                    <option value="aprobada">Aprobada</option>
                    <option value="rechazada">Rechazada</option>
                </select>
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="observaciones" placeholder="Notas sobre la solicitud..."></textarea>
            </div>
        </div>

        <hr style="margin:20px 0; border-color:#e5e7eb;">
        <h3 style="margin-bottom:14px; font-size:.95rem; color:#6b7280;">Datos nuevo adoptante (si no existe)</h3>
        <div class="form-grid">
            <div class="form-group"><label>Nombre completo</label><input type="text" name="nuevo_nombre"></div>
            <div class="form-group"><label>CI/DNI</label><input type="text" name="nuevo_dni"></div>
            <div class="form-group"><label>Teléfono</label><input type="text" name="nuevo_telefono"></div>
            <div class="form-group"><label>Email</label><input type="email" name="nuevo_email"></div>
            <div class="form-group"><label>Dirección</label><input type="text" name="nuevo_direccion"></div>
            <div class="form-group"><label>Ocupación</label><input type="text" name="nuevo_ocupacion"></div>
            <div class="form-group">
                <label>Estado Civil</label>
                <select name="nuevo_estado_civil">
                    <?php foreach(['soltero','casado','divorciado','viudo','union_libre'] as $ec): ?>
                    <option value="<?= $ec ?>"><?= ucfirst(str_replace('_',' ',$ec)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="guardar" class="btn btn-primary">➕ Registrar Adopción</button>
            <a href="adopciones.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php else: // LISTADO
$stmt = $pdo->query("
    SELECT a.*, an.nombre as animal_nombre, an.especie,
           ad.nombre_completo as adoptante_nombre, ad.telefono as adoptante_tel,
           u.nombre as responsable_nombre
    FROM adopciones a
    JOIN animales an ON a.animal_id = an.id
    JOIN adoptantes ad ON a.adoptante_id = ad.id
    LEFT JOIN usuarios u ON a.responsable_id = u.id
    ORDER BY a.creado_en DESC
");
$adopciones = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>🏠 Adopciones (<?= count($adopciones) ?>)</h2>
        <a href="adopciones.php?accion=nueva" class="btn btn-primary btn-sm">➕ Nueva Adopción</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Animal</th><th>Adoptante</th><th>Fecha</th><th>Estado</th><th>Responsable</th><th>Cambiar Estado</th></tr>
            </thead>
            <tbody>
            <?php foreach ($adopciones as $a):
                $bm = ['pendiente'=>'badge-amarillo','aprobada'=>'badge-verde','rechazada'=>'badge-rojo','entregada'=>'badge-azul'];
            ?>
            <tr>
                <td><?= $a['id'] ?></td>
                <td><strong><?= e($a['animal_nombre']) ?></strong><br><small style="color:#9ca3af;"><?= e($a['especie']) ?></small></td>
                <td><?= e($a['adoptante_nombre']) ?><br><small style="color:#9ca3af;"><?= e($a['adoptante_tel']) ?></small></td>
                <td><?= date('d/m/Y', strtotime($a['fecha_solicitud'])) ?></td>
                <td><span class="badge <?= $bm[$a['estado']] ?? 'badge-gris' ?>"><?= ucfirst($a['estado']) ?></span></td>
                <td><?= e($a['responsable_nombre'] ?? '-') ?></td>
                <td>
                    <?php if ($a['estado'] === 'pendiente'): ?>
                    <a href="adopciones.php?accion=estado&id=<?= $a['id'] ?>&nuevo_estado=aprobada"
                       class="btn btn-primary btn-sm"
                       onclick="return confirm('¿Aprobar esta adopción?')">✅ Aprobar</a>
                    <a href="adopciones.php?accion=estado&id=<?= $a['id'] ?>&nuevo_estado=rechazada"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('¿Rechazar esta adopción?')">❌ Rechazar</a>
                    <?php elseif ($a['estado'] === 'aprobada'): ?>
                    <a href="adopciones.php?accion=estado&id=<?= $a['id'] ?>&nuevo_estado=entregada"
                       class="btn btn-warning btn-sm"
                       onclick="return confirm('¿Marcar como entregada?')">📦 Entregada</a>
                    <?php else: ?>
                    <span style="color:#9ca3af; font-size:.8rem;">Sin acciones</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($adopciones)): ?>
            <tr><td colspan="7" style="text-align:center; padding:30px; color:#9ca3af;">Sin registros de adopciones.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
<?php include '../includes/footer.php'; ?>
