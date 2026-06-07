<?php
require_once '../includes/config.php';
$titulo = 'Gestión de Donaciones';
$pdo = conectar();

$accion = $_GET['accion'] ?? 'listar';

// Registra donaciones monetarias o en especie; las monetarias se expresan en Bolivianos.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $donante_id = (int)$_POST['donante_id'];
    if (!$donante_id && !empty($_POST['nuevo_nombre'])) {
        $pdo->prepare("INSERT INTO donantes (nombre,telefono,email,direccion) VALUES (?,?,?,?)")->execute([
            trim($_POST['nuevo_nombre']),
            trim($_POST['nuevo_telefono']),
            trim($_POST['nuevo_email']),
            trim($_POST['nuevo_direccion']),
        ]);
        $donante_id = $pdo->lastInsertId();
    }
    $pdo->prepare("INSERT INTO donaciones (donante_id,fecha,tipo,monto,cantidad,unidad,descripcion,observaciones,metodo_pago,responsable_id)
        VALUES (?,?,?,?,?,?,?,?,?,?)")->execute([
        $donante_id,
        $_POST['fecha'],
        $_POST['tipo'],
        (float)$_POST['monto'],
        (float)($_POST['cantidad'] ?: 0),
        trim($_POST['unidad']),
        trim($_POST['descripcion']),
        trim($_POST['observaciones']),
        $_POST['metodo_pago'],
        $_SESSION['usuario_id'],
    ]);
    logAudit($pdo, 'crear', 'donaciones', (int)$pdo->lastInsertId(), 'Donación registrada');
    setFlash('success', 'Donación registrada correctamente.');
    header("Location: donaciones.php");
    exit;
}

include '../includes/header.php';

if ($accion === 'nueva'):
    $donantes = $pdo->query("SELECT id,nombre FROM donantes ORDER BY nombre")->fetchAll();
?>
<div class="form-card">
    <h2 style="margin-bottom:20px;">➕ Registrar Donación</h2>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Donante existente</label>
                <select name="donante_id">
                    <option value="">-- Nuevo donante abajo --</option>
                    <?php foreach ($donantes as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= e($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha *</label>
                <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Tipo de donación *</label>
                <select name="tipo" required>
                    <option value="dinero">Dinero</option>
                    <option value="alimento">Alimento</option>
                    <option value="medicina">Medicina</option>
                    <option value="accesorios">Accesorios</option>
                    <option value="especie">Otros en especie</option>
                </select>
            </div>
            <div class="form-group">
                <label>Monto (Bs.)</label>
                <input type="number" step="0.01" name="monto" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Cantidad</label>
                <input type="number" step="0.01" name="cantidad" placeholder="Ej: 20">
            </div>
            <div class="form-group">
                <label>Unidad</label>
                <input type="text" name="unidad" placeholder="kg, unidad, caja">
            </div>
            <div class="form-group">
                <label>Método de Pago</label>
                <select name="metodo_pago">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="form-group full">
                <label>Descripción</label>
                <input type="text" name="descripcion" placeholder="Descripción de la donación...">
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="observaciones" placeholder="Notas adicionales del donante o entrega..."></textarea>
            </div>
        </div>

        <hr style="margin:20px 0; border-color:#e5e7eb;">
        <h3 style="margin-bottom:14px; font-size:.95rem; color:#6b7280;">Datos nuevo donante (si no existe)</h3>
        <div class="form-grid">
            <div class="form-group"><label>Nombre</label><input type="text" name="nuevo_nombre"></div>
            <div class="form-group"><label>Teléfono</label><input type="text" name="nuevo_telefono"></div>
            <div class="form-group"><label>Email</label><input type="email" name="nuevo_email"></div>
            <div class="form-group"><label>Dirección</label><input type="text" name="nuevo_direccion"></div>
        </div>

        <div class="form-actions">
            <button type="submit" name="guardar" class="btn btn-primary">💝 Registrar Donación</button>
            <a href="donaciones.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php else: // LISTADO
$donaciones = $pdo->query("
    SELECT d.*, dn.nombre as donante_nombre, u.nombre as responsable_nombre
    FROM donaciones d
    JOIN donantes dn ON d.donante_id = dn.id
    LEFT JOIN usuarios u ON d.responsable_id = u.id
    ORDER BY d.fecha DESC, d.id DESC
")->fetchAll();

// Totales
$total_mon   = array_sum(array_column(array_filter($donaciones, fn($d)=>in_array($d['tipo'], ['dinero','monetaria'], true)), 'monto'));
$total_esp   = count(array_filter($donaciones, fn($d)=>!in_array($d['tipo'], ['dinero','monetaria'], true)));
?>

<!-- Resumen -->
<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon">💵</div>
        <div>
            <div class="stat-label">Total Monetario</div>
            <div class="stat-value small"><?= moneyBs($total_mon) ?></div>
        </div>
    </div>
    <div class="stat-card naranja">
        <div class="stat-icon">📦</div>
        <div>
            <div class="stat-label">Donaciones en Especie</div>
            <div class="stat-value"><?= $total_esp ?></div>
        </div>
    </div>
    <div class="stat-card azul">
        <div class="stat-icon">🤝</div>
        <div>
            <div class="stat-label">Total Donaciones</div>
            <div class="stat-value"><?= count($donaciones) ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>💝 Donaciones</h2>
        <a href="donaciones.php?accion=nueva" class="btn btn-primary btn-sm">➕ Registrar</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Donante</th><th>Fecha</th><th>Tipo</th><th>Monto/Cantidad</th><th>Descripción</th><th>Método</th><th>Responsable</th></tr>
            </thead>
            <tbody>
            <?php foreach ($donaciones as $d): ?>
            <tr>
                <td><?= $d['id'] ?></td>
                <td><strong><?= e($d['donante_nombre']) ?></strong></td>
                <td><?= date('d/m/Y', strtotime($d['fecha'])) ?></td>
                <td>
                    <span class="badge <?= in_array($d['tipo'], ['dinero','monetaria'], true)?'badge-verde':'badge-azul' ?>">
                        <?= ucfirst($d['tipo']) ?>
                    </span>
                </td>
                <td><?= in_array($d['tipo'], ['dinero','monetaria'], true) ? moneyBs($d['monto']) : e($d['cantidad'] . ' ' . $d['unidad']) ?></td>
                <td><?= e($d['descripcion']) ?: '-' ?><br><small><?= e($d['observaciones'] ?? '') ?></small></td>
                <td><?= ucfirst(e($d['metodo_pago'])) ?></td>
                <td><?= e($d['responsable_nombre'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($donaciones)): ?>
            <tr><td colspan="8" style="text-align:center; padding:30px; color:#9ca3af;">Sin donaciones registradas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
<?php include '../includes/footer.php'; ?>
