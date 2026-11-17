<?php
require_once __DIR__ . '/../controlador/auth.php';
requerirAdmin();
require_once '../conexion.php';
 require_once '../modelo/Categoria.php';
$modelo=new Categoria($pdo);
$buscar=trim($_GET['buscar'] ?? '');
 $todos=($_GET['estado'] ?? 'activos')==='todos';
$categorias=$modelo->listar($buscar,$todos);
$editar=isset($_GET['editar']) ? $modelo->obtener((int)$_GET['editar']) : null;
$valor=fn($campo)=>htmlspecialchars((string)($editar[$campo] ?? ''),ENT_QUOTES,'UTF-8');
$mensajes=['registrado'=>'Categoría registrada correctamente.','actualizado'=>'Categoría actualizada correctamente.','desactivado'=>'Categoría desactivada correctamente.','reactivado'=>'Categoría reactivada correctamente.'];
$mensaje=$mensajes[$_GET['mensaje'] ?? ''] ?? '';
 $error=$_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Categorías - Agencia Lagunitas</title>
<link rel="stylesheet" href="css/estilo.css?v=20261116c">
<style>
.cat-wrap{max-width:1100px;
    margin:28px auto;
    padding:0 22px}.cat-top{display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap}.cat-panel{background:#fff;
    border-radius:12px;
    padding:22px;
    margin:20px 0;
    box-shadow:0 3px 14px #0000000d}.cat-form{display:grid;
    grid-template-columns:1fr 2fr auto;
    gap:14px;
    align-items:end}.cat-form label{display:block;
    font-weight:600;
    margin-bottom:6px}.cat-form input{width:100%;
    padding:10px;
    border:1px solid #d5dbe3;
    border-radius:7px}.cat-btn{border:0;
    border-radius:7px;
    padding:10px 15px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block}.cat-primary{background:#173f67;
    color:white}.cat-light{background:#e9eef4;
    color:#173f67}.cat-edit{background:#e7f1ff;
    color:#174d83}.cat-danger{background:#ffe8e8;
    color:#a32626}.cat-filter{display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:15px}.cat-filter input,.cat-filter select{padding:10px;
    border:1px solid #d5dbe3;
    border-radius:7px}.cat-table{width:100%;
    border-collapse:collapse;
    font-size:20px}.cat-table th,.cat-table td{padding:11px 9px;
    border-bottom:1px solid #e8edf2;
    text-align:left}.cat-table th{background:#f3f6f9}.cat-scroll{overflow-x:auto}.cat-actions{display:flex;
    gap:7px;
    align-items:center}.cat-alert{padding:12px 15px;
    border-radius:7px;
    margin:12px 0}.cat-ok{background:#e7f7ec;
    color:#176b35}.cat-error{background:#ffeded;
    color:#9c2222}@media(max-width:700px){.cat-form{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php require __DIR__ . '/../vista/inicio.php';
 ?>
<div class="cat-wrap">
<div class="cat-top">
<div>
<h1>Gestión de categorías</h1>
<p>Organiza los productos de Agencia Lagunitas.</p>
</div>
<a class="cat-btn cat-light" href="dashboard.php">← Volver al panel</a>
</div>
<?php if($mensaje): ?><div class="cat-alert cat-ok"><?= htmlspecialchars($mensaje) ?></div><?php endif;
 ?><?php if($error): ?><div class="cat-alert cat-error"><?= htmlspecialchars($error) ?></div><?php endif;
 ?>
<section class="cat-panel">
<h2><?= $editar?'Editar categoría':'Registrar categoría' ?></h2>
<form class="cat-form" method="POST" action="../controlador/categoriaControlador.php"><?= csrfCampo() ?>
<input type="hidden" name="accion" value="<?= $editar?'actualizar':'guardar' ?>"><?php if($editar): ?><input type="hidden" name="id" value="<?= (int)$editar['id'] ?>"><?php endif;
 ?><div>
<label>Nombre *</label>
<input name="nombre" maxlength="80" required value="<?= $valor('nombre') ?>" placeholder="Ej. Cerveza">
</div>
<div>
<label>Descripción</label>
<input name="descripcion" maxlength="255" value="<?= $valor('descripcion') ?>" placeholder="Descripción de la categoría">
</div>
<div>
<button class="cat-btn cat-primary" type="submit"><?= $editar?'Guardar cambios':'Registrar' ?></button><?php if($editar): ?> <a class="cat-btn cat-light" href="categorias.php">Cancelar</a><?php endif;
 ?></div>
</form>
</section>
<section class="cat-panel">
<h2>Categorías registradas</h2>
<form class="cat-filter" method="GET">
<input type="search" name="buscar" placeholder="Buscar categoría" value="<?= htmlspecialchars($buscar) ?>">
<select name="estado">
<option value="activos" <?= !$todos?'selected':'' ?>>Solo activas</option>
<option value="todos" <?= $todos?'selected':'' ?>>Activas e inactivas</option>
</select>
<button class="cat-btn cat-primary">Buscar</button>
<a class="cat-btn cat-light" href="categorias.php">Limpiar</a>
</form>
<div class="cat-scroll">
<table class="cat-table">
<thead>
<tr>
<th>Nombre</th>
<th>Descripción</th>
<th>Productos relacionados</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody><?php if(!$categorias): ?><tr>
<td colspan="5">No se encontraron categorías.</td>
</tr><?php else: foreach($categorias as $c): ?><tr>
<td><?= htmlspecialchars($c['nombre']) ?></td>
<td><?= htmlspecialchars($c['descripcion'] ?? '') ?></td>
<td><?= (int)$c['total_productos'] ?></td>
<td><?= (int)$c['activo']===1?'Activa':'Inactiva' ?></td>
<td>
<div class="cat-actions"><?php if((int)$c['activo']===1): ?><a class="cat-btn cat-edit" href="?editar=<?= (int)$c['id'] ?>">Editar</a>
<form method="POST" action="../controlador/categoriaControlador.php" data-confirm="¿Desactivar esta categoría?"><?= csrfCampo() ?>
<input type="hidden" name="accion" value="desactivar">
<input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
<button class="cat-btn cat-danger">Desactivar</button>
</form><?php else: ?><form method="POST" action="../controlador/categoriaControlador.php"><?= csrfCampo() ?>
<input type="hidden" name="accion" value="reactivar">
<input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
<button class="cat-btn cat-primary">Reactivar</button>
</form><?php endif;
 ?></div>
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
