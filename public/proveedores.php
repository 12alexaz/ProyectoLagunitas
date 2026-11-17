<?php
require_once __DIR__ . '/../controlador/auth.php';
requerirAdmin();
require_once '../conexion.php';
require_once '../modelo/Proveedor.php';
$modelo = new Proveedor($pdo);
$buscar = trim($_GET['buscar'] ?? '');
$todos = ($_GET['estado'] ?? 'activos') === 'todos';
$proveedores = $modelo->listar($buscar, $todos);
$editar = isset($_GET['editar']) ? $modelo->obtener((int)$_GET['editar']) : null;
$valor = fn($campo) => htmlspecialchars((string)($editar[$campo] ?? ''), ENT_QUOTES, 'UTF-8');
$mensajes = ['registrado'=>'Proveedor registrado correctamente.','actualizado'=>'Proveedor actualizado correctamente.','desactivado'=>'Proveedor desactivado correctamente.','reactivado'=>'Proveedor reactivado correctamente.'];
$mensaje = $mensajes[$_GET['mensaje'] ?? ''] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Proveedores - Agencia Lagunitas</title>
<link rel="stylesheet" href="css/estilo.css?v=20261116c">
<style>
.prov-wrap{max-width:1150px;
    margin:28px auto;
    padding:0 22px}.prov-top{display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap}.prov-panel{background:#fff;
    border-radius:12px;
    padding:22px;
    margin:20px 0;
    box-shadow:0 3px 14px #0000000d}.prov-form{display:grid;
    grid-template-columns:1.2fr 1fr 1.5fr auto;
    gap:14px;
    align-items:end}.prov-form label{display:block;
    font-weight:600;
    margin-bottom:6px}.prov-form input{width:100%;
    padding:10px;
    border:1px solid #d5dbe3;
    border-radius:7px;
    box-sizing:border-box}.prov-btn{border:0;
    border-radius:7px;
    padding:10px 15px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block}.prov-primary{background:#173f67;
    color:white}.prov-light{background:#e9eef4;
    color:#173f67}.prov-edit{background:#e7f1ff;
    color:#174d83}.prov-danger{background:#ffe8e8;
    color:#a32626}.prov-filter{display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:15px}.prov-filter input,.prov-filter select{padding:10px;
    border:1px solid #d5dbe3;
    border-radius:7px}.prov-table{width:100%;
    border-collapse:collapse;
    font-size:19px}.prov-table th,.prov-table td{padding:11px 9px;
    border-bottom:1px solid #e8edf2;
    text-align:left}.prov-table th{background:#f3f6f9}.prov-scroll{overflow-x:auto}.prov-actions{display:flex;
    gap:7px;
    align-items:center;
    flex-wrap:wrap}.prov-alert{padding:12px 15px;
    border-radius:7px;
    margin:12px 0}.prov-ok{background:#e7f7ec;
    color:#176b35}.prov-error{background:#ffeded;
    color:#9c2222}@media(max-width:850px){.prov-form{grid-template-columns:1fr 1fr}}@media(max-width:550px){.prov-form{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php require __DIR__ . '/../vista/inicio.php';
 ?>
<div class="prov-wrap">
<div class="prov-top">
<div>
<h1>Gestión de proveedores</h1>
<p>Administra las empresas y personas que suministran productos a Agencia Lagunitas.</p>
</div>
<a class="prov-btn prov-light" href="dashboard.php">← Volver al panel</a>
</div>
<?php if ($mensaje): ?><div class="prov-alert prov-ok"><?= htmlspecialchars($mensaje) ?></div><?php endif;
 ?>
<?php if ($error): ?><div class="prov-alert prov-error"><?= htmlspecialchars($error) ?></div><?php endif;
 ?>
<section class="prov-panel">
<h2><?= $editar ? 'Editar proveedor' : 'Registrar proveedor' ?></h2>
<form class="prov-form" method="POST" action="../controlador/proveedorControlador.php"><?= csrfCampo() ?>
<input type="hidden" name="accion" value="<?= $editar ? 'actualizar' : 'guardar' ?>">
<?php if ($editar): ?><input type="hidden" name="id" value="<?= (int)$editar['id'] ?>"><?php endif;
 ?>
<div>
<label>Nombre del proveedor *</label>
<input name="nombre" maxlength="120" required value="<?= $valor('nombre') ?>" placeholder="Ej. CBN">
</div>
<div>
<label>Teléfono</label>
<input name="telefono" maxlength="30" value="<?= $valor('telefono') ?>" placeholder="Número de contacto">
</div>
<div>
<label>Dirección</label>
<input name="direccion" maxlength="200" value="<?= $valor('direccion') ?>" placeholder="Dirección del proveedor">
</div>
<div>
<button class="prov-btn prov-primary" type="submit"><?= $editar ? 'Guardar cambios' : 'Registrar' ?></button><?php if ($editar): ?> <a class="prov-btn prov-light" href="proveedores.php">Cancelar</a><?php endif;
 ?></div>
</form>
</section>
<section class="prov-panel">
<h2>Proveedores registrados</h2>
<form class="prov-filter" method="GET">
<input type="search" name="buscar" placeholder="Buscar por nombre, teléfono o dirección" value="<?= htmlspecialchars($buscar) ?>">
<select name="estado">
<option value="activos" <?= !$todos ? 'selected' : '' ?>>Solo activos</option>
<option value="todos" <?= $todos ? 'selected' : '' ?>>Activos e inactivos</option>
</select>
<button class="prov-btn prov-primary" type="submit">Buscar</button>
<a class="prov-btn prov-light" href="proveedores.php">Limpiar</a>
</form>
<div class="prov-scroll">
<table class="prov-table">
<thead>
<tr>
<th>Nombre</th>
<th>Teléfono</th>
<th>Dirección</th>
<th>Productos relacionados</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if (!$proveedores): ?><tr>
<td colspan="6">No se encontraron proveedores.</td>
</tr><?php else: foreach ($proveedores as $p): ?><tr>
<td><?= htmlspecialchars($p['nombre']) ?></td>
<td><?= htmlspecialchars($p['telefono'] ?? '') ?></td>
<td><?= htmlspecialchars($p['direccion'] ?? '') ?></td>
<td><?= (int)$p['total_productos'] ?></td>
<td><?= (int)$p['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
<td>
<div class="prov-actions">
<?php if ((int)$p['activo'] === 1): ?><a class="prov-btn prov-edit" href="?editar=<?= (int)$p['id'] ?>">Editar</a>
<form method="POST" action="../controlador/proveedorControlador.php" data-confirm="¿Desactivar este proveedor?"><?= csrfCampo() ?>
<input type="hidden" name="accion" value="desactivar">
<input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
<button class="prov-btn prov-danger" type="submit">Desactivar</button>
</form>
<?php else: ?><form method="POST" action="../controlador/proveedorControlador.php"><?= csrfCampo() ?>
<input type="hidden" name="accion" value="reactivar">
<input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
<button class="prov-btn prov-primary" type="submit">Reactivar</button>
</form><?php endif;
 ?>
</div>
</td>
</tr><?php endforeach;
 endif;
 ?></tbody>
</table>
</div>
</section>
</div>
<?php require __DIR__ . '/../vista/fin.php';
 ?>
</body>
</html>
