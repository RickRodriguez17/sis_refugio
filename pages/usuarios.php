<?php
require_once '../includes/config.php';
$titulo = 'Gestión de Usuarios';
$pdo = conectar();

// Solo administradores
if ($_SESSION['rol'] !== 'administrador') {
    setFlash('error', 'No tienes permisos para acceder a esta sección.');
    header("Location: dashboard.php");
    exit;
}

$accion = $_GET['accion'] ?? 'listar';
$id     = (int)($_GET['id'] ?? 0);

// GUARDAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $nombre = trim($_POST['nombre']);
    $email  = trim($_POST['email']);
    $rol    = $_POST['rol'];
    $estado = (int)$_POST['estado'];

    if ($id) {
        // Editar (cambiar contraseña solo si se ingresó una)
        if (!empty($_POST['password'])) {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE usuarios SET nombre=?,email=?,rol=?,estado=?,password=? WHERE id=?")->execute([$nombre,$email,$rol,$estado,$hash,$id]);
        } else {
            $pdo->prepare("UPDATE usuarios SET nombre=?,email=?,rol=?,estado=? WHERE id=?")->execute([$nombre,$email,$rol,$estado,$id]);
        }
        setFlash('success', 'Usuario actualizado.');
    } else {
        if (empty($_POST['password'])) { setFlash('error', 'La contraseña es obligatoria.'); header("Location: usuarios.php?accion=nuevo"); exit; }
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO usuarios (nombre,email,password,rol,estado) VALUES (?,?,?,?,?)")->execute([$nombre,$email,$hash,$rol,$estado]);
        setFlash('success', 'Usuario creado.');
    }
    header("Location: usuarios.php");
    exit;
}

// ELIMINAR (no puede eliminarse a sí mismo)
if ($accion === 'eliminar' && $id && $id !== (int)$_SESSION['usuario_id']) {
    $pdo->prepare("DELETE FROM usuarios WHERE id=?")->execute([$id]);
    setFlash('success', 'Usuario eliminado.');
    header("Location: usuarios.php");
    exit;
}

$usuario = [];
if (($accion === 'editar') && $id) {
    $s = $pdo->prepare("SELECT * FROM usuarios WHERE id=?");
    $s->execute([$id]);
    $usuario = $s->fetch();
}

include '../includes/header.php';

if ($accion === 'nuevo' || $accion === 'editar'):
$es_editar = $accion === 'editar';
$roles = ['administrador','veterinario','adopciones','donaciones','voluntarios','consulta'];
?>
<div class="form-card">
    <h2 style="margin-bottom:20px;"><?= $es_editar ? '✏️ Editar Usuario' : '➕ Nuevo Usuario' ?></h2>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" value="<?= e($usuario['nombre'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?= e($usuario['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label><?= $es_editar ? 'Nueva Contraseña (dejar vacío para mantener)' : 'Contraseña *' ?></label>
                <input type="password" name="password" <?= $es_editar ? '' : 'required' ?> placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Rol *</label>
                <select name="rol" required>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= ($usuario['rol'] ?? '')===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado">
                    <option value="1" <?= ($usuario['estado'] ?? 1)==1?'selected':'' ?>>Activo</option>
                    <option value="0" <?= ($usuario['estado'] ?? 1)==0?'selected':'' ?>>Inactivo</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" name="guardar" class="btn btn-primary">
                <?= $es_editar ? '💾 Guardar' : '➕ Crear Usuario' ?>
            </button>
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php else:
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nombre")->fetchAll();
?>
<div class="card">
    <div class="card-header">
        <h2>👤 Usuarios del Sistema (<?= count($usuarios) ?>)</h2>
        <a href="usuarios.php?accion=nuevo" class="btn btn-primary btn-sm">➕ Nuevo Usuario</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Creado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><strong><?= e($u['nombre']) ?></strong> <?= $u['id']==$_SESSION['usuario_id']?'<small style="color:#9ca3af;">(tú)</small>':'' ?></td>
                <td><?= e($u['email']) ?></td>
                <td><span class="badge badge-azul"><?= ucfirst(e($u['rol'])) ?></span></td>
                <td><span class="badge <?= $u['estado']?'badge-verde':'badge-rojo' ?>"><?= $u['estado']?'Activo':'Inactivo' ?></span></td>
                <td><?= date('d/m/Y', strtotime($u['creado_en'])) ?></td>
                <td>
                    <a href="usuarios.php?accion=editar&id=<?= $u['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
                    <?php if ($u['id'] !== (int)$_SESSION['usuario_id']): ?>
                    <a href="usuarios.php?accion=eliminar&id=<?= $u['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('¿Eliminar este usuario?')">🗑️</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
<?php include '../includes/footer.php'; ?>
