<?php
require_once '../includes/config.php';
$titulo = 'Dashboard';
include '../includes/header.php';

$pdo = conectar();

// Estadísticas
$total_animales    = $pdo->query("SELECT COUNT(*) FROM animales")->fetchColumn();
$disponibles       = $pdo->query("SELECT COUNT(*) FROM animales WHERE estado='disponible'")->fetchColumn();
$total_adopciones  = $pdo->query("SELECT COUNT(*) FROM adopciones")->fetchColumn();
$total_donaciones  = $pdo->query("SELECT SUM(monto) FROM donaciones WHERE tipo='monetaria'")->fetchColumn();
$total_voluntarios = $pdo->query("SELECT COUNT(*) FROM voluntarios WHERE estado='activo'")->fetchColumn();

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
?>

<!-- Estadísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">🐾</div>
        <div>
            <div class="stat-label">Total Animales</div>
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
            <div class="stat-label">Adopciones</div>
            <div class="stat-value"><?= $total_adopciones ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💝</div>
        <div>
            <div class="stat-label">Donaciones ($)</div>
            <div class="stat-value">$<?= number_format($total_donaciones ?? 0, 0) ?></div>
        </div>
    </div>
    <div class="stat-card rojo">
        <div class="stat-icon">🙋</div>
        <div>
            <div class="stat-label">Voluntarios</div>
            <div class="stat-value"><?= $total_voluntarios ?></div>
        </div>
    </div>
</div>

<!-- Tablas resumen -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

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
