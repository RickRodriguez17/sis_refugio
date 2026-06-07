<?php
require_once '../includes/config.php';
$titulo = 'Gestión de Voluntarios';
$pdo = conectar();

$accion = $_GET['accion'] ?? 'listar';
$id     = (int)($_GET['id'] ?? 0);

// GUARDAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $datos = [
        trim($_POST['nombre_completo']),
        trim($_POST['dni']),
        trim($_POST['telefono']),
        trim($_POST['email']),
        trim($_POST['direccion']),
        $_POST['fecha_ingreso'],
        trim($_POST['disponibilidad']),
        trim($_POST['habilidades']),
        $_POST['estado'],
    ];
    if ($id) {
        $datos[] = $id;
        $pdo->prepare("UPDATE voluntarios SET nombre_completo=?,dni=?,telefono=?,email=?,direccion=?,
            fecha_ingreso=?,disponibilidad=?,habilidades=?,estado=? WHERE id=?")->execute($datos);
        setFlash('success', 'Voluntario actualizado.');
    } else {
        $pdo->prepare("INSERT INTO voluntarios (nombre_completo,dni,telefono,email,direccion,
            fecha_ingreso,disponibilidad,habilidades,estado) VALUES (?,?,?,?,?,?,?,?,?)")->execute($datos);
        setFlash('success', 'Voluntario registrado.');
    }
    header("Location: voluntarios.php");
    exit;
}

// ELIMINAR
if ($accion === 'eliminar' && $id) {
    $pdo->prepare("DELETE FROM voluntarios WHERE id=?")->execute([$id]);
    setFlash('success', 'Voluntario eliminado.');
    header("Location: voluntarios.php");
    exit;
}

$voluntario = [];
if (($accion === 'editar') && $id) {
    $s = $pdo->prepare("SELECT * FROM voluntarios WHERE id=?");
    $s->execute([$id]);
    $voluntario = $s->fetch();
}

include '../includes/header.php';

if ($accion === 'nuevo' || $accion === 'editar'):
$es_editar = $accion === 'editar';
?>
<div class="form-card">
    <h2 style="margin-bottom:20px;"><?= $es_editar ? '✏️ Editar Voluntario' : '➕ Registrar Voluntario' ?></h2>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" name="nombre_completo" value="<?= e($voluntario['nombre_completo'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>CI/DNI *</label>
                <input type="text" name="dni" value="<?= e($voluntario['dni'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" value="<?= e($voluntario['telefono'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= e($voluntario['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="direccion" value="<?= e($voluntario['direccion'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Fecha de Ingreso *</label>
                <input type="date" name="fecha_ingreso" value="<?= e($voluntario['fecha_ingreso'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="form-group">
                <label>Disponibilidad</label>
                <input type="text" name="disponibilidad" placeholder="Ej: Fines de semana" value="<?= e($voluntario['disponibilidad'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado">
                    <option value="activo"   <?= ($voluntario['estado']??'activo')==='activo'  ?'selected':'' ?>>Activo</option>
                    <option value="inactivo" <?= ($voluntario['estado']??'')==='inactivo'?'selected':'' ?>>Inactivo</option>
                </select>
            </div>
            <div class="form-group full">
                <label>Habilidades</label>
                <textarea name="habilidades" placeholder="Ej: Cuidado animal, primeros auxilios, transporte..."><?= e($voluntario['habilidades'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" name="guardar" class="btn btn-primary">
                <?= $es_editar ? '💾 Guardar Cambios' : '➕ Registrar' ?>
            </button>
            <a href="voluntarios.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php else:
$voluntarios = $pdo->query("SELECT * FROM voluntarios ORDER BY nombre_completo")->fetchAll();
$activos     = count(array_filter($voluntarios, fn($v) => $v['estado'] === 'activo'));
?>

<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon">🙋</div>
        <div><div class="stat-label">Total</div><div class="stat-value"><?= count($voluntarios) ?></div></div>
    </div>
    <div class="stat-card naranja">
        <div class="stat-icon">✅</div>
        <div><div class="stat-label">Activos</div><div class="stat-value"><?= $activos ?></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>🙋 Voluntarios</h2>
        <a href="voluntarios.php?accion=nuevo" class="btn btn-primary btn-sm">➕ Registrar</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Nombre</th><th>DNI</th><th>Teléfono</th><th>Email</th><th>Disponibilidad</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($voluntarios as $v): ?>
            <tr>
                <td><?= $v['id'] ?></td>
                <td><strong><?= e($v['nombre_completo']) ?></strong></td>
                <td><?= e($v['dni']) ?></td>
                <td><?= e($v['telefono']) ?></td>
                <td><?= e($v['email']) ?></td>
                <td><?= e($v['disponibilidad']) ?: '-' ?></td>
                <td>
                    <span class="badge <?= $v['estado']==='activo'?'badge-verde':'badge-gris' ?>">
                        <?= ucfirst($v['estado']) ?>
                    </span>
                </td>
                <td>
                    <a href="voluntarios.php?accion=editar&id=<?= $v['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
                    <a href="voluntarios.php?accion=eliminar&id=<?= $v['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('¿Eliminar voluntario?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($voluntarios)): ?>
            <tr><td colspan="8" style="text-align:center; padding:30px; color:#9ca3af;">Sin voluntarios registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
<?php include '../includes/footer.php'; ?>
