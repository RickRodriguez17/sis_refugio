<?php
require_once '../includes/config.php';
$titulo = 'Gastos del Refugio';
$pdo = conectar();

$accion = $_GET['accion'] ?? 'listar';
$id = (int)($_GET['id'] ?? 0);

// Cada gasto se clasifica para reportes mensuales/anuales y cálculo de balance.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $datos = [
        $_POST['categoria'],
        $_POST['fecha'],
        (float)$_POST['monto'],
        trim($_POST['descripcion']),
        trim($_POST['proveedor']),
        trim($_POST['comprobante']),
        $_SESSION['usuario_id'],
    ];
    if ($id) {
        $datos[] = $id;
        $pdo->prepare("UPDATE gastos SET categoria=?, fecha=?, monto=?, descripcion=?, proveedor=?, comprobante=?, responsable_id=? WHERE id=?")->execute($datos);
        logAudit($pdo, 'actualizar', 'gastos', $id, 'Gasto actualizado');
        setFlash('success', 'Gasto actualizado.');
    } else {
        $pdo->prepare("INSERT INTO gastos (categoria, fecha, monto, descripcion, proveedor, comprobante, responsable_id) VALUES (?,?,?,?,?,?,?)")->execute($datos);
        logAudit($pdo, 'crear', 'gastos', (int)$pdo->lastInsertId(), 'Gasto registrado');
        setFlash('success', 'Gasto registrado.');
    }
    header("Location: gastos.php");
    exit;
}

if ($accion === 'eliminar' && $id) {
    $pdo->prepare("DELETE FROM gastos WHERE id=?")->execute([$id]);
    logAudit($pdo, 'eliminar', 'gastos', $id, 'Gasto eliminado');
    setFlash('success', 'Gasto eliminado.');
    header("Location: gastos.php");
    exit;
}

$gasto = [];
if ($accion === 'editar' && $id) {
    $s = $pdo->prepare("SELECT * FROM gastos WHERE id=?");
    $s->execute([$id]);
    $gasto = $s->fetch();
}

include '../includes/header.php';

if ($accion === 'nuevo' || $accion === 'editar'):
?>
<div class="form-card">
    <h2><?= $accion==='editar' ? '✏️ Editar gasto' : '➕ Registrar gasto' ?></h2>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group"><label>Categoría</label><select name="categoria" required><?php foreach(['alimento','medicamentos','veterinario','limpieza','transporte','servicios','otros'] as $c): ?><option value="<?= $c ?>" <?= ($gasto['categoria']??'')===$c?'selected':'' ?>><?= ucfirst($c) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Fecha</label><input type="date" name="fecha" value="<?= e($gasto['fecha'] ?? date('Y-m-d')) ?>" required></div>
            <div class="form-group"><label>Monto (Bs.)</label><input type="number" step="0.01" name="monto" value="<?= e($gasto['monto'] ?? '') ?>" required></div>
            <div class="form-group"><label>Proveedor</label><input type="text" name="proveedor" value="<?= e($gasto['proveedor'] ?? '') ?>"></div>
            <div class="form-group"><label>Comprobante</label><input type="text" name="comprobante" value="<?= e($gasto['comprobante'] ?? '') ?>"></div>
            <div class="form-group full"><label>Descripción</label><textarea name="descripcion" required><?= e($gasto['descripcion'] ?? '') ?></textarea></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary" name="guardar">Guardar</button><a href="gastos.php" class="btn btn-secondary">Cancelar</a></div>
    </form>
</div>
<?php else:
$mes = $_GET['mes'] ?? date('Y-m');
$stmt = $pdo->prepare("SELECT g.*, u.nombre responsable FROM gastos g LEFT JOIN usuarios u ON g.responsable_id=u.id WHERE DATE_FORMAT(g.fecha, '%Y-%m')=? ORDER BY g.fecha DESC");
$stmt->execute([$mes]);
$gastos = $stmt->fetchAll();
$total = array_sum(array_column($gastos, 'monto'));
?>
<div class="stats-grid">
    <div class="stat-card rojo"><div class="stat-icon">💸</div><div><div class="stat-label">Gastos del mes</div><div class="stat-value small"><?= moneyBs($total) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon">🧾</div><div><div class="stat-label">Registros</div><div class="stat-value"><?= count($gastos) ?></div></div></div>
</div>
<div class="card filters-card no-print">
    <form method="GET" class="filter-grid">
        <div class="form-group"><label>Mes</label><input type="month" name="mes" value="<?= e($mes) ?>"></div>
        <div class="form-actions inline"><button class="btn btn-primary">Filtrar</button><button type="button" class="btn btn-secondary" onclick="window.print()">🖨️ Reporte</button><a href="gastos.php?accion=nuevo" class="btn btn-primary">➕ Nuevo</a></div>
    </form>
</div>
<div class="card printable">
    <div class="card-header"><h2>💸 Gastos del refugio</h2></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Fecha</th><th>Categoría</th><th>Descripción</th><th>Proveedor</th><th>Monto</th><th class="no-print">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($gastos as $g): ?>
            <tr>
                <td><?= fechaLatina($g['fecha']) ?></td>
                <td><span class="badge badge-rojo"><?= ucfirst(e($g['categoria'])) ?></span></td>
                <td><?= e($g['descripcion']) ?><br><small><?= e($g['comprobante']) ?></small></td>
                <td><?= e($g['proveedor'] ?: '-') ?></td>
                <td><?= moneyBs($g['monto']) ?></td>
                <td class="no-print"><a class="btn btn-warning btn-sm" href="gastos.php?accion=editar&id=<?= $g['id'] ?>">✏️</a><a class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar gasto?')" href="gastos.php?accion=eliminar&id=<?= $g['id'] ?>">🗑️</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$gastos): ?><tr><td colspan="6" class="empty-state">Sin gastos en el periodo.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
