<?php
require_once '../includes/config.php';
$titulo = 'Inventario';
$pdo = conectar();

$accion = $_GET['accion'] ?? 'listar';
$id = (int)($_GET['id'] ?? 0);

// El inventario controla stock disponible y resalta productos por debajo del mínimo.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $datos = [
        trim($_POST['nombre']),
        $_POST['categoria'],
        trim($_POST['unidad']),
        (float)$_POST['cantidad'],
        (float)$_POST['stock_minimo'],
        $_POST['fecha_vencimiento'] ?: null,
        trim($_POST['ubicacion']),
        trim($_POST['observaciones']),
    ];
    if ($id) {
        $datos[] = $id;
        $pdo->prepare("UPDATE inventario SET nombre=?, categoria=?, unidad=?, cantidad=?, stock_minimo=?, fecha_vencimiento=?, ubicacion=?, observaciones=? WHERE id=?")->execute($datos);
        logAudit($pdo, 'actualizar', 'inventario', $id, 'Item de inventario actualizado');
        setFlash('success', 'Producto actualizado.');
    } else {
        $pdo->prepare("INSERT INTO inventario (nombre,categoria,unidad,cantidad,stock_minimo,fecha_vencimiento,ubicacion,observaciones) VALUES (?,?,?,?,?,?,?,?)")->execute($datos);
        logAudit($pdo, 'crear', 'inventario', (int)$pdo->lastInsertId(), 'Item de inventario creado');
        setFlash('success', 'Producto registrado.');
    }
    header("Location: inventario.php");
    exit;
}

if ($accion === 'eliminar' && $id) {
    $pdo->prepare("DELETE FROM inventario WHERE id=?")->execute([$id]);
    logAudit($pdo, 'eliminar', 'inventario', $id, 'Item de inventario eliminado');
    setFlash('success', 'Producto eliminado.');
    header("Location: inventario.php");
    exit;
}

$item = [];
if ($accion === 'editar' && $id) {
    $s = $pdo->prepare("SELECT * FROM inventario WHERE id=?");
    $s->execute([$id]);
    $item = $s->fetch();
}

include '../includes/header.php';

if ($accion === 'nuevo' || $accion === 'editar'):
?>
<div class="form-card">
    <h2><?= $accion==='editar' ? '✏️ Editar producto' : '➕ Registrar producto' ?></h2>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group"><label>Nombre</label><input type="text" name="nombre" value="<?= e($item['nombre'] ?? '') ?>" required></div>
            <div class="form-group"><label>Categoría</label><select name="categoria"><?php foreach(['alimentos','medicamentos','accesorios','limpieza'] as $c): ?><option value="<?= $c ?>" <?= ($item['categoria']??'')===$c?'selected':'' ?>><?= ucfirst($c) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Unidad</label><input type="text" name="unidad" value="<?= e($item['unidad'] ?? 'unidad') ?>"></div>
            <div class="form-group"><label>Cantidad</label><input type="number" step="0.01" name="cantidad" value="<?= e($item['cantidad'] ?? 0) ?>"></div>
            <div class="form-group"><label>Stock mínimo</label><input type="number" step="0.01" name="stock_minimo" value="<?= e($item['stock_minimo'] ?? 0) ?>"></div>
            <div class="form-group"><label>Fecha vencimiento</label><input type="date" name="fecha_vencimiento" value="<?= e($item['fecha_vencimiento'] ?? '') ?>"></div>
            <div class="form-group"><label>Ubicación</label><input type="text" name="ubicacion" value="<?= e($item['ubicacion'] ?? '') ?>"></div>
            <div class="form-group full"><label>Observaciones</label><textarea name="observaciones"><?= e($item['observaciones'] ?? '') ?></textarea></div>
        </div>
        <div class="form-actions"><button name="guardar" class="btn btn-primary">Guardar</button><a href="inventario.php" class="btn btn-secondary">Cancelar</a></div>
    </form>
</div>
<?php else:
$categoria = $_GET['categoria'] ?? '';
$where = $categoria ? "WHERE categoria=?" : "";
$stmt = $pdo->prepare("SELECT * FROM inventario $where ORDER BY categoria, nombre");
$stmt->execute($categoria ? [$categoria] : []);
$items = $stmt->fetchAll();
$bajos = array_filter($items, fn($i) => (float)$i['cantidad'] <= (float)$i['stock_minimo']);
?>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon">📦</div><div><div class="stat-label">Productos</div><div class="stat-value"><?= count($items) ?></div></div></div>
    <div class="stat-card rojo"><div class="stat-icon">⚠️</div><div><div class="stat-label">Stock bajo</div><div class="stat-value"><?= count($bajos) ?></div></div></div>
</div>
<div class="card filters-card no-print">
    <form method="GET" class="filter-grid">
        <div class="form-group"><label>Categoría</label><select name="categoria"><option value="">Todas</option><?php foreach(['alimentos','medicamentos','accesorios','limpieza'] as $c): ?><option value="<?= $c ?>" <?= $categoria===$c?'selected':'' ?>><?= ucfirst($c) ?></option><?php endforeach; ?></select></div>
        <div class="form-actions inline"><button class="btn btn-primary">Filtrar</button><button type="button" class="btn btn-secondary" onclick="window.print()">🖨️ Imprimir</button><a href="inventario.php?accion=nuevo" class="btn btn-primary">➕ Nuevo</a></div>
    </form>
</div>
<div class="card printable">
    <div class="card-header"><h2>📦 Inventario de alimentos, medicamentos y accesorios</h2></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Producto</th><th>Categoría</th><th>Stock</th><th>Mínimo</th><th>Vence</th><th>Ubicación</th><th class="no-print">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($items as $i): $bajo = (float)$i['cantidad'] <= (float)$i['stock_minimo']; ?>
            <tr class="<?= $bajo ? 'row-alert' : '' ?>">
                <td><strong><?= e($i['nombre']) ?></strong><br><small><?= e($i['observaciones']) ?></small></td>
                <td><?= ucfirst(e($i['categoria'])) ?></td>
                <td><?= number_format((float)$i['cantidad'], 2, ',', '.') ?> <?= e($i['unidad']) ?> <?= $bajo ? '<span class="badge badge-rojo">Bajo</span>' : '' ?></td>
                <td><?= number_format((float)$i['stock_minimo'], 2, ',', '.') ?> <?= e($i['unidad']) ?></td>
                <td><?= fechaLatina($i['fecha_vencimiento']) ?></td>
                <td><?= e($i['ubicacion'] ?: '-') ?></td>
                <td class="no-print"><a class="btn btn-warning btn-sm" href="inventario.php?accion=editar&id=<?= $i['id'] ?>">✏️</a><a class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar producto?')" href="inventario.php?accion=eliminar&id=<?= $i['id'] ?>">🗑️</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="7" class="empty-state">Sin productos registrados.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
