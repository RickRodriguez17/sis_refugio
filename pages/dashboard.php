<?php
require_once '../includes/config.php';
$titulo = 'Dashboard';
include '../includes/header.php';

$pdo = conectar();

// Estadísticas en tiempo real calculadas desde la base de datos.
$total_animales    = $pdo->query("SELECT COUNT(*) FROM animales")->fetchColumn();
$disponibles       = $pdo->query("SELECT COUNT(*) FROM animales WHERE estado='disponible'")->fetchColumn();
$adoptados         = $pdo->query("SELECT COUNT(*) FROM animales WHERE estado='adoptado'")->fetchColumn();
$tratamiento       = $pdo->query("SELECT COUNT(*) FROM animales WHERE estado='en_tratamiento'")->fetchColumn();
$adopciones_mes    = $pdo->query("SELECT COUNT(*) FROM adopciones WHERE MONTH(COALESCE(fecha_adopcion, fecha_solicitud)) = MONTH(CURDATE()) AND YEAR(COALESCE(fecha_adopcion, fecha_solicitud)) = YEAR(CURDATE())")->fetchColumn();
$nuevos_ingresos   = $pdo->query("SELECT COUNT(*) FROM animales WHERE MONTH(COALESCE(fecha_ingreso, fecha_rescate)) = MONTH(CURDATE()) AND YEAR(COALESCE(fecha_ingreso, fecha_rescate)) = YEAR(CURDATE())")->fetchColumn();
$total_donaciones  = $pdo->query("SELECT SUM(monto) FROM donaciones WHERE tipo IN ('dinero','monetaria')")->fetchColumn();
$total_gastos      = tableExists($pdo, 'gastos') ? $pdo->query("SELECT SUM(monto) FROM gastos")->fetchColumn() : 0;
$stock_bajo        = tableExists($pdo, 'inventario') ? $pdo->query("SELECT COUNT(*) FROM inventario WHERE cantidad <= stock_minimo")->fetchColumn() : 0;

// Últimos animales registrados
$animales_recientes = $pdo->query("SELECT * FROM animales ORDER BY creado_en DESC LIMIT 5")->fetchAll();

// Últimas adopciones
$adopciones_recientes = $pdo->query("
    SELECT a.*, an.nombre as animal_nombre, ad.nombre_completo as adoptante_nombre
    FROM adopciones a
    JOIN animales an ON a.animal_id = an.id
    JOIN adoptantes ad ON a.adoptante_id = ad.id
    ORDER BY a.creado_en DESC LIMIT 5
")->fetchAll();

$estados = $pdo->query("SELECT estado, COUNT(*) total FROM animales GROUP BY estado")->fetchAll();
$maxEstado = max(1, ...array_map(fn($e) => (int)$e['total'], $estados ?: [['total'=>1]]));
?>

<div class="hero-panel">
    <div>
        <span class="hero-badge">🐾 Panel vivo</span>
        <h2>Resumen operativo del refugio</h2>
        <p>Indicadores, adopciones, alertas de inventario y flujo económico en Bolivianos.</p>
    </div>
    <a href="galeria.php" class="btn btn-primary">Ver galería de adopción</a>
</div>

<!-- Tarjetas KPI: cada cifra se actualiza desde consultas SQL simples. -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">🐾</div>
        <div>
            <div class="stat-label">Total Mascotas</div>
            <div class="stat-value"><?= $total_animales ?></div>
        </div>
    </div>
    <div class="stat-card naranja">
        <div class="stat-icon">✅</div>
        <div>
            <div class="stat-label">Disponibles</div>
            <div class="stat-value"><?= $disponibles ?></div>
        </div>
    </div>
    <div class="stat-card azul">
        <div class="stat-icon">🏠</div>
        <div>
            <div class="stat-label">Adoptadas</div>
            <div class="stat-value"><?= $adoptados ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💝</div>
        <div>
            <div class="stat-label">En tratamiento</div>
            <div class="stat-value"><?= $tratamiento ?></div>
        </div>
    </div>
    <div class="stat-card rojo">
        <div class="stat-icon">📅</div>
        <div>
            <div class="stat-label">Adopciones del mes</div>
            <div class="stat-value"><?= $adopciones_mes ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🆕</div>
        <div>
            <div class="stat-label">Nuevos ingresos</div>
            <div class="stat-value"><?= $nuevos_ingresos ?></div>
        </div>
    </div>
    <div class="stat-card azul">
        <div class="stat-icon">💝</div>
        <div>
            <div class="stat-label">Donaciones</div>
            <div class="stat-value small"><?= moneyBs($total_donaciones ?? 0) ?></div>
        </div>
    </div>
    <div class="stat-card rojo">
        <div class="stat-icon">⚠️</div>
        <div>
            <div class="stat-label">Stock bajo</div>
            <div class="stat-value"><?= $stock_bajo ?></div>
        </div>
    </div>
</div>

<div class="charts-grid">
    <div class="card padded">
        <h2>📈 Estado de mascotas</h2>
        <?php foreach ($estados as $estado): $w = ((int)$estado['total'] / $maxEstado) * 100; ?>
        <div class="bar-row">
            <span><?= ucfirst(str_replace('_',' ', e($estado['estado']))) ?></span>
            <div class="bar-track"><div class="bar-fill" style="width:<?= $w ?>%"></div></div>
            <strong><?= (int)$estado['total'] ?></strong>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="card padded">
        <h2>💰 Flujo económico</h2>
        <div class="money-row"><span>Ingresos por donaciones</span><strong><?= moneyBs($total_donaciones ?? 0) ?></strong></div>
        <div class="money-row"><span>Gastos registrados</span><strong><?= moneyBs($total_gastos ?? 0) ?></strong></div>
        <div class="money-row total"><span>Balance estimado</span><strong><?= moneyBs(($total_donaciones ?? 0) - ($total_gastos ?? 0)) ?></strong></div>
    </div>
</div>

<!-- Tablas resumen -->
<div class="two-column">

    <!-- Animales recientes -->
    <div class="card">
        <div class="card-header">
            <h2>🐶 Últimos Animales Registrados</h2>
            <a href="animales.php" class="btn btn-secondary btn-sm">Ver todos</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Nombre</th><th>Especie</th><th>Estado</th></tr>
                </thead>
                <tbody>
                <?php foreach ($animales_recientes as $a): ?>
                <tr>
                    <td><?= e($a['nombre']) ?></td>
                    <td><?= ucfirst(e($a['especie'])) ?></td>
                    <td>
                        <?php
                        $badgeMap = ['disponible'=>'badge-verde','adoptado'=>'badge-azul','en_tratamiento'=>'badge-amarillo','reservado'=>'badge-gris'];
                        $badge = $badgeMap[$a['estado']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $badge ?>"><?= ucfirst(str_replace('_',' ',$a['estado'])) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Adopciones recientes -->
    <div class="card">
        <div class="card-header">
            <h2>🏠 Últimas Adopciones</h2>
            <a href="adopciones.php" class="btn btn-secondary btn-sm">Ver todas</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Animal</th><th>Adoptante</th><th>Estado</th></tr>
                </thead>
                <tbody>
                <?php foreach ($adopciones_recientes as $ad): ?>
                <tr>
                    <td><?= e($ad['animal_nombre']) ?></td>
                    <td><?= e($ad['adoptante_nombre']) ?></td>
                    <td>
                        <?php
                        $bm = ['pendiente'=>'badge-amarillo','aprobada'=>'badge-verde','rechazada'=>'badge-rojo','entregada'=>'badge-azul'];
                        $b  = $bm[$ad['estado']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $b ?>"><?= ucfirst($ad['estado']) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($adopciones_recientes)): ?>
                <tr><td colspan="3" style="text-align:center;color:#9ca3af;">Sin registros</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
