<?php
require_once '../includes/config.php';
$titulo = 'Historial de Adopciones';
$pdo = conectar();

// Historial consolidado para consultar e imprimir adopciones ya registradas.
$buscar = trim($_GET['buscar'] ?? '');
$estado = $_GET['estado'] ?? '';
$where = "WHERE 1=1";
$params = [];
if ($buscar) {
    $where .= " AND (an.nombre LIKE ? OR ad.nombre_completo LIKE ? OR ad.dni LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}
if ($estado) {
    $where .= " AND a.seguimiento_estado=?";
    $params[] = $estado;
}

$stmt = $pdo->prepare("
    SELECT a.*, an.nombre animal_nombre, an.especie, ad.nombre_completo, ad.dni, ad.telefono, ad.email, ad.direccion, ad.ciudad
    FROM adopciones a
    JOIN animales an ON a.animal_id = an.id
    JOIN adoptantes ad ON a.adoptante_id = ad.id
    $where
    ORDER BY COALESCE(a.fecha_adopcion, a.fecha_solicitud) DESC
");
$stmt->execute($params);
$historial = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="card filters-card no-print">
    <form method="GET" class="filter-grid">
        <div class="form-group"><label>Buscar</label><input type="text" name="buscar" value="<?= e($buscar) ?>" placeholder="Mascota, adoptante o CI"></div>
        <div class="form-group"><label>Seguimiento</label><select name="estado"><option value="">Todos</option><?php foreach(['pendiente','programado','en_seguimiento','completado','alerta'] as $v): ?><option value="<?= $v ?>" <?= $estado===$v?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$v)) ?></option><?php endforeach; ?></select></div>
        <div class="form-actions inline"><button class="btn btn-primary">Filtrar</button><button type="button" onclick="window.print()" class="btn btn-secondary">🖨️ Imprimir</button></div>
    </form>
</div>

<div class="card printable">
    <div class="card-header"><h2>📜 Historial de adopciones (<?= count($historial) ?>)</h2></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Mascota</th><th>Adoptante</th><th>Fecha</th><th>Estado</th><th>Seguimiento</th><th class="no-print">Ficha</th></tr></thead>
            <tbody>
            <?php foreach ($historial as $h): ?>
            <tr>
                <td><strong><?= e($h['animal_nombre']) ?></strong><br><small><?= ucfirst(e($h['especie'])) ?></small></td>
                <td><?= e($h['nombre_completo']) ?><br><small><?= e($h['dni']) ?> · <?= e($h['telefono']) ?></small></td>
                <td><?= fechaLatina($h['fecha_adopcion'] ?: $h['fecha_solicitud']) ?></td>
                <td><span class="badge badge-azul"><?= ucfirst(e($h['estado'])) ?></span></td>
                <td><span class="badge badge-gris"><?= ucfirst(str_replace('_',' ', e($h['seguimiento_estado'] ?? 'pendiente'))) ?></span></td>
                <td class="no-print"><a class="btn btn-secondary btn-sm" href="adopciones.php?accion=ver&id=<?= $h['id'] ?>">Ver / PDF</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$historial): ?>
            <tr><td colspan="6" class="empty-state">Sin adopciones registradas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
