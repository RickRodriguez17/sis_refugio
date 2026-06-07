<?php
require_once '../includes/config.php';
$titulo = 'Reportes';
$pdo = conectar();

$tipo = $_GET['tipo'] ?? 'mascotas';

// Reportes imprimibles: el navegador permite guardar cada vista como PDF.
$reportes = [
    'mascotas' => 'Mascotas registradas',
    'disponibles' => 'Mascotas disponibles',
    'adoptadas' => 'Mascotas adoptadas',
    'donaciones' => 'Ingresos por donaciones',
    'gastos' => 'Gastos del refugio',
    'inventario' => 'Inventario',
    'adopciones' => 'Historial de adopciones',
];

switch ($tipo) {
    case 'disponibles':
        $tituloReporte = $reportes[$tipo];
        $datos = $pdo->query("SELECT nombre, especie, raza, sexo, estado, COALESCE(fecha_ingreso, fecha_rescate) fecha FROM animales WHERE estado='disponible' ORDER BY nombre")->fetchAll();
        break;
    case 'adoptadas':
        $tituloReporte = $reportes[$tipo];
        $datos = $pdo->query("SELECT nombre, especie, raza, sexo, estado, COALESCE(fecha_ingreso, fecha_rescate) fecha FROM animales WHERE estado='adoptado' ORDER BY nombre")->fetchAll();
        break;
    case 'donaciones':
        $tituloReporte = $reportes[$tipo];
        $datos = $pdo->query("SELECT d.fecha, dn.nombre, d.tipo, d.monto, d.cantidad, d.unidad, d.descripcion FROM donaciones d JOIN donantes dn ON d.donante_id=dn.id ORDER BY d.fecha DESC")->fetchAll();
        break;
    case 'gastos':
        $tituloReporte = $reportes[$tipo];
        $datos = $pdo->query("SELECT fecha, categoria, descripcion, proveedor, monto FROM gastos ORDER BY fecha DESC")->fetchAll();
        break;
    case 'inventario':
        $tituloReporte = $reportes[$tipo];
        $datos = $pdo->query("SELECT nombre, categoria, cantidad, unidad, stock_minimo, fecha_vencimiento FROM inventario ORDER BY categoria, nombre")->fetchAll();
        break;
    case 'adopciones':
        $tituloReporte = $reportes[$tipo];
        $datos = $pdo->query("SELECT an.nombre mascota, ad.nombre_completo adoptante, ad.dni, a.fecha_adopcion, a.estado, a.seguimiento_estado FROM adopciones a JOIN animales an ON a.animal_id=an.id JOIN adoptantes ad ON a.adoptante_id=ad.id ORDER BY COALESCE(a.fecha_adopcion, a.fecha_solicitud) DESC")->fetchAll();
        break;
    default:
        $tipo = 'mascotas';
        $tituloReporte = $reportes[$tipo];
        $datos = $pdo->query("SELECT nombre, especie, raza, sexo, estado, COALESCE(fecha_ingreso, fecha_rescate) fecha FROM animales ORDER BY nombre")->fetchAll();
}

include '../includes/header.php';
?>

<div class="card filters-card no-print">
    <form method="GET" class="filter-grid">
        <div class="form-group"><label>Tipo de reporte</label><select name="tipo"><?php foreach($reportes as $k=>$v): ?><option value="<?= $k ?>" <?= $tipo===$k?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
        <div class="form-actions inline"><button class="btn btn-primary">Generar</button><button type="button" onclick="window.print()" class="btn btn-secondary">🖨️ Imprimir / PDF</button><button type="button" onclick="exportTableToCSV('reporte_refugio.csv')" class="btn btn-secondary">⬇️ Excel CSV</button></div>
    </form>
</div>

<div class="card printable">
    <div class="report-title">
        <h2><?= e($tituloReporte) ?></h2>
        <p><?= SITE_NAME ?> · Generado el <?= date('d/m/Y H:i') ?> · Moneda: Bolivianos (Bs.)</p>
    </div>
    <div class="table-wrap">
        <table id="report-table">
            <thead>
                <?php if ($tipo === 'donaciones'): ?>
                <tr><th>Fecha</th><th>Donante</th><th>Tipo</th><th>Monto/Cantidad</th><th>Descripción</th></tr>
                <?php elseif ($tipo === 'gastos'): ?>
                <tr><th>Fecha</th><th>Categoría</th><th>Descripción</th><th>Proveedor</th><th>Monto</th></tr>
                <?php elseif ($tipo === 'inventario'): ?>
                <tr><th>Producto</th><th>Categoría</th><th>Cantidad</th><th>Mínimo</th><th>Vence</th></tr>
                <?php elseif ($tipo === 'adopciones'): ?>
                <tr><th>Mascota</th><th>Adoptante</th><th>CI/DNI</th><th>Fecha</th><th>Estado</th><th>Seguimiento</th></tr>
                <?php else: ?>
                <tr><th>Nombre</th><th>Especie</th><th>Raza</th><th>Sexo</th><th>Estado</th><th>Fecha ingreso</th></tr>
                <?php endif; ?>
            </thead>
            <tbody>
                <?php foreach($datos as $d): ?>
                    <?php if ($tipo === 'donaciones'): ?>
                    <tr><td><?= fechaLatina($d['fecha']) ?></td><td><?= e($d['nombre']) ?></td><td><?= e($d['tipo']) ?></td><td><?= in_array($d['tipo'], ['dinero','monetaria'], true) ? moneyBs($d['monto']) : e($d['cantidad'].' '.$d['unidad']) ?></td><td><?= e($d['descripcion']) ?></td></tr>
                    <?php elseif ($tipo === 'gastos'): ?>
                    <tr><td><?= fechaLatina($d['fecha']) ?></td><td><?= e($d['categoria']) ?></td><td><?= e($d['descripcion']) ?></td><td><?= e($d['proveedor']) ?></td><td><?= moneyBs($d['monto']) ?></td></tr>
                    <?php elseif ($tipo === 'inventario'): ?>
                    <tr><td><?= e($d['nombre']) ?></td><td><?= e($d['categoria']) ?></td><td><?= e($d['cantidad'].' '.$d['unidad']) ?></td><td><?= e($d['stock_minimo'].' '.$d['unidad']) ?></td><td><?= fechaLatina($d['fecha_vencimiento']) ?></td></tr>
                    <?php elseif ($tipo === 'adopciones'): ?>
                    <tr><td><?= e($d['mascota']) ?></td><td><?= e($d['adoptante']) ?></td><td><?= e($d['dni']) ?></td><td><?= fechaLatina($d['fecha_adopcion']) ?></td><td><?= e($d['estado']) ?></td><td><?= e($d['seguimiento_estado']) ?></td></tr>
                    <?php else: ?>
                    <tr><td><?= e($d['nombre']) ?></td><td><?= e($d['especie']) ?></td><td><?= e($d['raza']) ?></td><td><?= e($d['sexo']) ?></td><td><?= e($d['estado']) ?></td><td><?= fechaLatina($d['fecha']) ?></td></tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (!$datos): ?><tr><td colspan="8" class="empty-state">Sin datos para este reporte.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Exportación simple a CSV compatible con Excel sin depender de librerías externas.
function exportTableToCSV(filename) {
    const rows = [...document.querySelectorAll('#report-table tr')].map(row =>
        [...row.children].map(cell => `"${cell.innerText.replaceAll('"', '""')}"`).join(',')
    ).join('\n');
    const blob = new Blob([rows], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}
</script>

<?php include '../includes/footer.php'; ?>
