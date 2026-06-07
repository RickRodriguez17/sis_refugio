<?php
require_once '../includes/config.php';
$titulo = 'Gestión de Adopciones';
$pdo = conectar();

$accion = $_GET['accion'] ?? 'listar';
$id     = (int)($_GET['id'] ?? 0);

// Guarda una adopción completa: mascota, adoptante, vivienda, motivación y seguimiento inicial.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $adoptante_id = (int)$_POST['adoptante_id'];

    if (!$adoptante_id && !empty($_POST['nuevo_nombre'])) {
        $pdo->prepare("INSERT INTO adoptantes (nombre_completo,dni,fecha_nacimiento,telefono,email,direccion,ciudad,ocupacion,profesion,referencia_personal,motivo_adopcion,tipo_vivienda,tiene_patio,posee_otras_mascotas,cantidad_personas_hogar,observaciones,estado_civil)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
            trim($_POST['nuevo_nombre']),
            trim($_POST['nuevo_dni']),
            $_POST['nuevo_fecha_nacimiento'] ?: null,
            trim($_POST['nuevo_telefono']),
            trim($_POST['nuevo_email']),
            trim($_POST['nuevo_direccion']),
            trim($_POST['nuevo_ciudad']),
            trim($_POST['nuevo_ocupacion']),
            trim($_POST['nuevo_profesion']),
            trim($_POST['nuevo_referencia_personal']),
            trim($_POST['nuevo_motivo_adopcion']),
            $_POST['nuevo_tipo_vivienda'],
            isset($_POST['nuevo_tiene_patio']) ? 1 : 0,
            isset($_POST['nuevo_posee_otras_mascotas']) ? 1 : 0,
            (int)($_POST['nuevo_cantidad_personas_hogar'] ?: 1),
            trim($_POST['nuevo_observaciones']),
            $_POST['nuevo_estado_civil'],
        ]);
        $adoptante_id = $pdo->lastInsertId();
    }

    $pdo->prepare("INSERT INTO adopciones (animal_id,adoptante_id,fecha_solicitud,fecha_adopcion,estado,seguimiento_estado,observaciones,responsable_id)
        VALUES (?,?,?,?,?,?,?,?)")->execute([
        (int)$_POST['animal_id'],
        $adoptante_id,
        $_POST['fecha_solicitud'],
        $_POST['fecha_adopcion'] ?: null,
        $_POST['estado'],
        $_POST['seguimiento_estado'],
        trim($_POST['observaciones']),
        $_SESSION['usuario_id'],
    ]);
    $adopcionId = (int)$pdo->lastInsertId();
    if (in_array($_POST['estado'], ['aprobada','entregada'], true)) {
        $pdo->prepare("UPDATE animales SET estado='adoptado' WHERE id=?")->execute([(int)$_POST['animal_id']]);
    }
    logAudit($pdo, 'crear', 'adopciones', $adopcionId, 'Formulario completo de adopción registrado');
    setFlash('success', 'Adopción registrada correctamente.');
    header("Location: adopciones.php");
    exit;
}

// Cambia el estado de la solicitud y sincroniza la disponibilidad de la mascota.
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
    logAudit($pdo, 'estado', 'adopciones', $id, "Estado cambiado a $nuevo");
    setFlash('success', 'Estado actualizado.');
    header("Location: adopciones.php");
    exit;
}

include '../includes/header.php';

if ($accion === 'ver' && $id):
$stmt = $pdo->prepare("
    SELECT a.*, a.observaciones adopcion_observaciones, an.nombre animal_nombre, an.especie, an.foto,
           ad.nombre_completo, ad.dni, ad.fecha_nacimiento, ad.telefono, ad.email, ad.direccion, ad.ciudad,
           ad.ocupacion, ad.profesion, ad.referencia_personal, ad.motivo_adopcion, ad.tipo_vivienda,
           ad.tiene_patio, ad.posee_otras_mascotas, ad.cantidad_personas_hogar, ad.observaciones adoptante_observaciones
    FROM adopciones a
    JOIN animales an ON a.animal_id = an.id
    JOIN adoptantes ad ON a.adoptante_id = ad.id
    WHERE a.id=?
");
$stmt->execute([$id]);
$detalle = $stmt->fetch();
?>
<div class="print-actions">
    <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    <a href="adopciones.php" class="btn btn-secondary">Volver</a>
</div>
<div class="card printable">
    <div class="card-header"><h2>📄 Ficha de adopción #<?= $id ?></h2></div>
    <?php if ($detalle): ?>
    <div class="detail-grid padded">
        <div><strong>Mascota:</strong> <?= e($detalle['animal_nombre']) ?> (<?= e($detalle['especie']) ?>)</div>
        <div><strong>Fecha adopción:</strong> <?= fechaLatina($detalle['fecha_adopcion'] ?? $detalle['fecha_solicitud']) ?></div>
        <div><strong>Estado:</strong> <?= ucfirst(e($detalle['estado'])) ?></div>
        <div><strong>Seguimiento:</strong> <?= ucfirst(str_replace('_',' ', e($detalle['seguimiento_estado'] ?? 'pendiente'))) ?></div>
        <div><strong>Adoptante:</strong> <?= e($detalle['nombre_completo']) ?></div>
        <div><strong>CI/DNI:</strong> <?= e($detalle['dni']) ?></div>
        <div><strong>Nacimiento:</strong> <?= fechaLatina($detalle['fecha_nacimiento']) ?></div>
        <div><strong>Teléfono:</strong> <?= e($detalle['telefono']) ?></div>
        <div><strong>Email:</strong> <?= e($detalle['email']) ?></div>
        <div><strong>Dirección:</strong> <?= e($detalle['direccion']) ?></div>
        <div><strong>Ciudad:</strong> <?= e($detalle['ciudad']) ?></div>
        <div><strong>Profesión:</strong> <?= e($detalle['profesion'] ?: $detalle['ocupacion']) ?></div>
        <div><strong>Referencia:</strong> <?= e($detalle['referencia_personal']) ?></div>
        <div><strong>Vivienda:</strong> <?= ucfirst(e($detalle['tipo_vivienda'])) ?> · Patio: <?= !empty($detalle['tiene_patio'])?'Sí':'No' ?></div>
        <div><strong>Otras mascotas:</strong> <?= !empty($detalle['posee_otras_mascotas'])?'Sí':'No' ?></div>
        <div><strong>Personas en hogar:</strong> <?= (int)$detalle['cantidad_personas_hogar'] ?></div>
    </div>
    <div class="padded">
        <h3>Motivo de adopción</h3>
        <p><?= nl2br(e($detalle['motivo_adopcion'])) ?></p>
        <h3>Observaciones</h3>
        <p><?= nl2br(e($detalle['adopcion_observaciones'])) ?></p>
    </div>
    <?php else: ?>
    <div class="empty-state">No se encontró la adopción solicitada.</div>
    <?php endif; ?>
</div>

<?php
elseif ($accion === 'nueva'):
    $animales_disp = $pdo->query("SELECT id,nombre,especie FROM animales WHERE estado='disponible' ORDER BY nombre")->fetchAll();
    $adoptantes    = $pdo->query("SELECT id,nombre_completo,dni FROM adoptantes ORDER BY nombre_completo")->fetchAll();
    $animal_preseleccionado = (int)($_GET['animal_id'] ?? 0);
?>
<div class="form-card">
    <h2 style="margin-bottom:20px;">➕ Formulario Completo de Adopción</h2>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Animal *</label>
                <select name="animal_id" required>
                    <option value="">-- Seleccionar Animal --</option>
                    <?php foreach ($animales_disp as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $animal_preseleccionado===(int)$a['id']?'selected':'' ?>><?= e($a['nombre']) ?> (<?= e($a['especie']) ?>)</option>
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
                <label>Fecha de adopción</label>
                <input type="date" name="fecha_adopcion" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado">
                    <option value="pendiente">Pendiente</option>
                    <option value="aprobada">Aprobada</option>
                    <option value="rechazada">Rechazada</option>
                </select>
            </div>
            <div class="form-group">
                <label>Estado de seguimiento</label>
                <select name="seguimiento_estado">
                    <?php foreach(['pendiente'=>'Pendiente','programado'=>'Programado','en_seguimiento'=>'En seguimiento','completado'=>'Completado','alerta'=>'Alerta'] as $k=>$v): ?>
                    <option value="<?= $k ?>"><?= $v ?></option>
                    <?php endforeach; ?>
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
            <div class="form-group"><label>Fecha de nacimiento</label><input type="date" name="nuevo_fecha_nacimiento"></div>
            <div class="form-group"><label>Teléfono</label><input type="text" name="nuevo_telefono"></div>
            <div class="form-group"><label>Email</label><input type="email" name="nuevo_email"></div>
            <div class="form-group"><label>Dirección</label><input type="text" name="nuevo_direccion"></div>
            <div class="form-group"><label>Ciudad</label><input type="text" name="nuevo_ciudad" value="Bolivia"></div>
            <div class="form-group"><label>Ocupación</label><input type="text" name="nuevo_ocupacion"></div>
            <div class="form-group"><label>Profesión</label><input type="text" name="nuevo_profesion"></div>
            <div class="form-group"><label>Referencia personal</label><input type="text" name="nuevo_referencia_personal" placeholder="Nombre y teléfono"></div>
            <div class="form-group">
                <label>Tipo de vivienda</label>
                <select name="nuevo_tipo_vivienda">
                    <?php foreach(['casa','departamento','habitacion','quinta','otro'] as $tv): ?>
                    <option value="<?= $tv ?>"><?= ucfirst($tv) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Cantidad de personas en el hogar</label><input type="number" name="nuevo_cantidad_personas_hogar" min="1" value="1"></div>
            <div class="form-group">
                <label>Estado Civil</label>
                <select name="nuevo_estado_civil">
                    <?php foreach(['soltero','casado','divorciado','viudo','union_libre'] as $ec): ?>
                    <option value="<?= $ec ?>"><?= ucfirst(str_replace('_',' ',$ec)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="check-row"><input type="checkbox" name="nuevo_tiene_patio" value="1"> Tiene patio</label></div>
            <div class="form-group"><label class="check-row"><input type="checkbox" name="nuevo_posee_otras_mascotas" value="1"> Posee otras mascotas</label></div>
            <div class="form-group full"><label>Motivo por el cual desea adoptar</label><textarea name="nuevo_motivo_adopcion"></textarea></div>
            <div class="form-group full"><label>Observaciones del adoptante</label><textarea name="nuevo_observaciones"></textarea></div>
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
                <tr><th>#</th><th>Animal</th><th>Adoptante</th><th>Fecha adopción</th><th>Estado</th><th>Seguimiento</th><th>Responsable</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($adopciones as $a):
                $bm = ['pendiente'=>'badge-amarillo','aprobada'=>'badge-verde','rechazada'=>'badge-rojo','entregada'=>'badge-azul'];
            ?>
            <tr>
                <td><?= $a['id'] ?></td>
                <td><strong><?= e($a['animal_nombre']) ?></strong><br><small style="color:#9ca3af;"><?= e($a['especie']) ?></small></td>
                <td><?= e($a['adoptante_nombre']) ?><br><small style="color:#9ca3af;"><?= e($a['adoptante_tel']) ?></small></td>
                <td><?= fechaLatina($a['fecha_adopcion'] ?: $a['fecha_solicitud']) ?></td>
                <td><span class="badge <?= $bm[$a['estado']] ?? 'badge-gris' ?>"><?= ucfirst($a['estado']) ?></span></td>
                <td><span class="badge badge-gris"><?= ucfirst(str_replace('_',' ', e($a['seguimiento_estado'] ?? 'pendiente'))) ?></span></td>
                <td><?= e($a['responsable_nombre'] ?? '-') ?></td>
                <td>
                    <a href="adopciones.php?accion=ver&id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm">📄 Ficha</a>
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
            <tr><td colspan="8" style="text-align:center; padding:30px; color:#9ca3af;">Sin registros de adopciones.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
<?php include '../includes/footer.php'; ?>
