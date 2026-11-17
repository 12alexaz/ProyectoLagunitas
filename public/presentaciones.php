<?php
require_once __DIR__ . '/../controlador/auth.php';
requerirAdmin();
require_once '../conexion.php';
 require_once '../modelo/Presentacion.php';
$modelo=new Presentacion($pdo);
$buscar=trim($_GET['buscar']??'');
$productoFiltro=(int)($_GET['producto']??0);
$todos=($_GET['estado']??'activos')==='todos';
$presentaciones=$modelo->listar($buscar,$productoFiltro,$todos);
$productos=$pdo->query("SELECT id,nombre,codigo FROM productos WHERE activo=1 ORDER BY nombre")->fetchAll();
$editar=isset($_GET['editar'])?$modelo->obtener((int)$_GET['editar']):null;
$v=fn($c,$d='')=>htmlspecialchars((string)($editar[$c]??$d),ENT_QUOTES,'UTF-8');
$mensajes=['registrado'=>'Presentación registrada correctamente.','actualizado'=>'Presentación actualizada correctamente.','desactivado'=>'Presentación desactivada.','reactivado'=>'Presentación reactivada correctamente.'];
$mensaje=$mensajes[$_GET['mensaje']??'']??'';
$error=$_GET['error']??'';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Presentaciones - Agencia Lagunitas</title>
<link rel="stylesheet" href="css/estilo.css?v=20261116c">
<style>
.wrap{max-width:1200px;
    margin:28px auto;
    padding:0 22px}.top{display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    flex-wrap:wrap}.panel{background:#fff;
    border-radius:12px;
    padding:22px;
    margin:20px 0;
    box-shadow:0 3px 14px #0000000d}.form-grid{display:grid;
    grid-template-columns:repeat(4,minmax(160px,1fr));
    gap:14px}.form-grid label{display:block;
    font-weight:600;
    font-size:19px;
    margin-bottom:6px}.form-grid input,.form-grid select,.filtros input,.filtros select{width:100%;
    padding:10px;
    border:1px solid #d5dbe3;
    border-radius:7px;
    box-sizing:border-box}.btn{border:0;
    border-radius:7px;
    padding:10px 15px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block}.primario{background:#173f67;
    color:#fff}.secundario{background:#e9eef4;
    color:#173f67}.editar{background:#e7f1ff;
    color:#174d83}.borrar{background:#ffe8e8;
    color:#a32626}.filtros{display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:15px}.filtros input{max-width:300px}.tabla{width:100%;
    border-collapse:collapse;
    font-size:19px}.tabla th,.tabla td{padding:11px 9px;
    border-bottom:1px solid #e8edf2;
    text-align:left}.tabla th{background:#f3f6f9}.scroll{overflow-x:auto}.alerta{padding:12px 15px;
    border-radius:7px;
    margin:12px 0}.ok{background:#e7f7ec;
    color:#176b35}.error{background:#ffeded;
    color:#9c2222}.acciones{display:flex;
    gap:7px;
    align-items:center}@media(max-width:800px){.form-grid{grid-template-columns:1fr 1fr}}@media(max-width:500px){.form-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php require __DIR__ . '/../vista/inicio.php';
 ?>
<div class="wrap">
<div class="top">
<div>
<h1>Presentaciones de productos</h1>
<p>Administra las formas de venta por unidad, paquete o caja.</p>
</div>
<a class="btn secundario" href="dashboard.php">← Volver al panel</a>
</div>
<?php if($mensaje):?><div class="alerta ok"><?=htmlspecialchars($mensaje)?></div><?php endif;
?><?php if($error):?><div class="alerta error"><?=htmlspecialchars($error)?></div><?php endif;
?>
<section class="panel">
<h2><?=$editar?'Editar presentación':'Registrar presentación'?></h2>
<form class="form-grid" method="POST" action="../controlador/presentacionControlador.php"><?= csrfCampo() ?>
<input type="hidden" name="accion" value="<?=$editar?'actualizar':'guardar'?>"><?php if($editar):?><input type="hidden" name="id" value="<?=(int)$editar['id']?>"><?php endif;
?>
<div>
<label>Producto *</label>
<select name="producto_id" required>
<option value="">Seleccione un producto</option><?php foreach($productos as $p):?><option value="<?=$p['id']?>" <?=(string)($editar['producto_id']??'')===(string)$p['id']?'selected':''?>><?=htmlspecialchars($p['nombre'].' ('.$p['codigo'].')')?></option><?php endforeach;
?></select>
</div>
<div>
<label>Nombre de presentación *</label>
<input name="nombre" maxlength="50" placeholder="Ej. Unidad, Paquete x12" required value="<?=$v('nombre')?>">
</div>
<div>
<label>Unidades que contiene *</label>
<input type="number" name="cantidad_unidades" min="1" step="1" required value="<?=$v('cantidad_unidades','1')?>">
</div>
<div>
<label>Precio de venta (Bs) *</label>
<input type="number" name="precio_venta" min="0" step="0.01" required value="<?=$v('precio_venta','0.00')?>">
</div>
<div style="grid-column:1/-1;display:flex;gap:9px;flex-wrap:wrap">
<button class="btn primario" type="submit"><?=$editar?'Guardar cambios':'Registrar presentación'?></button><?php if($editar):?><a class="btn secundario" href="presentaciones.php">Cancelar</a><?php endif;
?></div>
</form>
</section>
<section class="panel">
<h2>Presentaciones registradas</h2>
<form class="filtros" method="GET">
<input type="search" name="buscar" placeholder="Buscar producto o presentación" value="<?=htmlspecialchars($buscar)?>">
<select name="producto">
<option value="0">Todos los productos</option><?php foreach($productos as $p):?><option value="<?=$p['id']?>" <?=(int)$productoFiltro===(int)$p['id']?'selected':''?>><?=htmlspecialchars($p['nombre'])?></option><?php endforeach;
?></select>
<select name="estado">
<option value="activos" <?=!$todos?'selected':''?>>Solo activos</option>
<option value="todos" <?=$todos?'selected':''?>>Activos e inactivos</option>
</select>
<button class="btn primario">Buscar</button>
<a class="btn secundario" href="presentaciones.php">Limpiar</a>
</form>
<div class="scroll">
<table class="tabla">
<thead>
<tr>
<th>Producto</th>
<th>Código</th>
<th>Presentación</th>
<th>Unidades</th>
<th>Precio</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if(!$presentaciones):?><tr>
<td colspan="7">No se encontraron presentaciones.</td>
</tr><?php else:foreach($presentaciones as $p):?><tr>
<td><?=htmlspecialchars($p['producto'])?></td>
<td><?=htmlspecialchars($p['codigo'])?></td>
<td><?=htmlspecialchars($p['nombre'])?></td>
<td><?=(int)$p['cantidad_unidades']?></td>
<td>Bs <?=number_format((float)$p['precio_venta'],2)?></td>
<td><?=(int)$p['activo']?'Activo':'Inactivo'?></td>
<td>
<div class="acciones"><?php if((int)$p['activo']):?><a class="btn editar" href="?editar=<?=$p['id']?>">Editar</a>
<form method="POST" action="../controlador/presentacionControlador.php" data-confirm="¿Desactivar esta presentación?"><?= csrfCampo() ?>
<input type="hidden" name="accion" value="desactivar">
<input type="hidden" name="id" value="<?=$p['id']?>">
<button class="btn borrar">Desactivar</button>
</form><?php else:?><form method="POST" action="../controlador/presentacionControlador.php" data-confirm="¿Reactivar esta presentación?"><?= csrfCampo() ?>
<input type="hidden" name="accion" value="reactivar">
<input type="hidden" name="id" value="<?=$p['id']?>">
<button class="btn primario">Reactivar</button>
</form><?php endif;
?></div>
</td>
</tr><?php endforeach;
endif;
?></tbody>
</table>
</div>
</section>
<p>
<strong>Nota:</strong> la cantidad de unidades indica cuánto stock individual se descontará al vender esa presentación. Este módulo administra las presentaciones; todavía no realiza ventas ni modifica el inventario.</p>
</div>
<?php require __DIR__ . '/../vista/fin.php';
 ?>
</body>
</html>
