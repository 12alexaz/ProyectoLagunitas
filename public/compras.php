<?php
require_once __DIR__ . '/../controlador/auth.php';
requerirAdmin();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelo/Compra.php';

$modelo = new Compra($pdo);
$desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '') ? $_GET['desde'] : '';
$hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'] ?? '') ? $_GET['hasta'] : '';
$provFiltro = (int)($_GET['proveedor'] ?? 0);

$compras = $modelo->listar($desde, $hasta, $provFiltro);
$productos = $modelo->productosParaComprar();
$proveedores = $pdo->query("SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre")->fetchAll();
$detalle = isset($_GET['ver']) ? $modelo->obtener((int)$_GET['ver']) : null;
$provPre = (int)($_GET['proveedor_nuevo'] ?? 0);
   // viene del Asistente

$mensaje = ['registrada' => 'Compra registrada. El stock fue actualizado y se creó el movimiento de entrada.'][$_GET['mensaje'] ?? ''] ?? '';
$error = $_GET['error'] ?? '';
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fecha = fn($f) => date('d/m/Y H:i', strtotime($f));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Compras - Agencia Lagunitas</title>
<link rel="stylesheet" href="css/estilo.css?v=20261116c">
</head>
<body>
<?php require __DIR__ . '/../vista/inicio.php';
 ?>
<div class="c-wrap">
 <div class="m-top">
<div>
<h1>Compras</h1>
<p>Registra los pedidos a proveedores. El stock sube automáticamente al confirmar.</p>
</div>
</div>

 <?php if($mensaje): ?><div class="m-alerta m-ok"><?= $h($mensaje) ?></div><?php endif;
 ?>
 <?php if($error): ?><div class="m-alerta m-error"><?= $h($error) ?></div><?php endif;
 ?>

 <div class="m-chips">
<div class="m-chip">Comprado este mes<b>Bs <?= number_format($modelo->totalMes(), 2) ?></b>
</div>
</div>

 <?php if($detalle): ?>
 <section class="m-panel">
  <div class="m-top">
<h2>Compra #<?= (int)$detalle['id'] ?></h2>
<a class="m-btn m-sec" href="compras.php">Cerrar detalle</a>
</div>
  <p>Fecha: <?= $fecha($detalle['fecha']) ?> · Proveedor: <?= $h($detalle['proveedor']) ?> · Registró: <?= $h($detalle['usuario']) ?></p>
  <div class="m-scroll">
<table class="m-tabla">
<thead>
<tr>
<th>Código</th>
<th>Producto</th>
<th class="num">Paquetes</th>
<th class="num">Unid. x paquete</th>
<th class="num">Unidades</th>
<th class="num">Costo paquete</th>
<th>Vencimiento</th>
<th class="num">Subtotal</th>
</tr>
</thead>
<tbody>
  <?php foreach($detalle['detalle'] as $d): ?><tr>
<td><?= $h($d['codigo']) ?></td>
<td><?= $h($d['producto']) ?></td>
<td class="num"><?= (int)$d['cantidad'] ?></td>
<td class="num"><?= (int)$d['unidades_por_paquete'] ?></td>
<td class="num"><?= (int)$d['cantidad'] * (int)$d['unidades_por_paquete'] ?></td>
<td class="num">Bs <?= number_format((float)$d['costo_unitario'],2) ?></td>
<td><?= !empty($d['fecha_vencimiento']) ? $h(date('d/m/Y', strtotime($d['fecha_vencimiento']))) : 'Sin vencimiento' ?></td>
<td class="num">Bs <?= number_format((float)$d['subtotal'],2) ?></td>
</tr><?php endforeach;
 ?>
  </tbody>
</table>
</div>
  <div class="m-total">
<strong>Total: Bs <?= number_format((float)$detalle['total'],2) ?></strong>
</div>
 </section>
 <?php endif;
 ?>

 <section class="m-panel">
  <h2>Nueva compra</h2>
  <?php if(!$proveedores || !$productos): ?>
   <p class="m-vacio">Necesitas al menos un proveedor activo y productos con proveedor asignado.</p>
  <?php else: ?>
  <div class="m-form">
   <div>
<label for="c-prov">Proveedor *</label>
<select id="c-prov">
<option value="">Seleccione</option><?php foreach($proveedores as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $provPre===(int)$p['id']?'selected':'' ?>><?= $h($p['nombre']) ?></option><?php endforeach;
 ?></select>
</div>
   <div style="grid-column:span 3">
<label for="c-prod">Producto</label>
<select id="c-prod">
</select>
</div>
   <div>
<label for="c-cant">Paquetes / cajas</label>
<input type="number" id="c-cant" min="1" step="1" value="1">
</div>
   <div>
<label for="c-upp">Unidades por paquete</label>
<input type="number" id="c-upp" min="1" step="1" value="1">
</div>
   <div>
<label for="c-costo">Costo por paquete (Bs)</label>
<input type="number" id="c-costo" min="0" step="0.01" value="0.00">
</div>
   <div>
<label for="c-vencimiento">Fecha de vencimiento (opcional)</label>
<input type="date" id="c-vencimiento">
<small class="m-nota">Déjalo vacío si el producto no tiene vencimiento.</small>
</div>
   <div>
<button type="button" class="m-btn m-primario" id="c-agregar">Agregar</button>
</div>
  </div>
  <div class="m-info" id="c-info">
</div>

  <form method="POST" action="../controlador/compraControlador.php" id="c-form"><?= csrfCampo() ?>
   <input type="hidden" name="accion" value="registrar">
   <input type="hidden" name="proveedor_id" id="c-prov-h" value="">
   <div class="m-scroll">
<table class="m-tabla">
<thead>
<tr>
<th>Producto</th>
<th class="num">Paquetes</th>
<th class="num">Unid. x paq.</th>
<th class="num">Unidades</th>
<th class="num">Costo paq.</th>
<th>Vencimiento</th>
<th class="num">Subtotal</th>
<th>
</th>
</tr>
</thead>
<tbody id="c-lineas">
</tbody>
</table>
</div>
   <div id="c-hidden">
</div>
   <label style="display:block;margin-top:12px;font-size:20px">
<input type="checkbox" name="actualizar_precio" value="1" checked> Actualizar el precio de compra del producto con el costo de esta compra</label>
   <div class="m-total">
<strong id="c-total">Total: Bs 0.00</strong>
<div>
<button type="button" class="m-btn m-sec" id="c-vaciar">Vaciar</button>
<button type="submit" class="m-btn m-verde" id="c-confirmar" disabled>Confirmar compra</button>
</div>
</div>
  </form>
  <?php endif;
 ?>
 </section>

 <section class="m-panel">
  <h2>Historial de compras</h2>
  <form class="m-filtros" method="GET">
   <div>
<label>Desde</label>
<input type="date" name="desde" value="<?= $h($desde) ?>">
</div>
   <div>
<label>Hasta</label>
<input type="date" name="hasta" value="<?= $h($hasta) ?>">
</div>
   <div>
<label>Proveedor</label>
<select name="proveedor">
<option value="0">Todos</option><?php foreach($proveedores as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $provFiltro===(int)$p['id']?'selected':'' ?>><?= $h($p['nombre']) ?></option><?php endforeach;
 ?></select>
</div>
   <button class="m-btn m-primario">Filtrar</button>
<a class="m-btn m-sec" href="compras.php">Limpiar</a>
  </form>
  <div class="m-scroll">
<table class="m-tabla">
<thead>
<tr>
<th>N.º</th>
<th>Fecha</th>
<th>Proveedor</th>
<th>Registró</th>
<th class="num">Líneas</th>
<th class="num">Total</th>
<th>
</th>
</tr>
</thead>
<tbody>
  <?php if(!$compras): ?><tr>
<td colspan="8" class="m-vacio">No se encontraron compras.</td>
</tr><?php else: foreach($compras as $c): ?>
   <tr>
<td>#<?= (int)$c['id'] ?></td>
<td><?= $fecha($c['fecha']) ?></td>
<td><?= $h($c['proveedor']) ?></td>
<td><?= $h($c['usuario']) ?></td>
<td class="num"><?= (int)$c['lineas'] ?></td>
<td class="num">Bs <?= number_format((float)$c['total'],2) ?></td>
<td>
<a class="m-btn m-edit" href="?ver=<?= (int)$c['id'] ?>">Ver detalle</a>
</td>
</tr>
  <?php endforeach;
 endif;
 ?>
  </tbody>
</table>
</div>
 </section>
</div>
<script>window.PRODUCTOS = <?= json_encode($productos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.PREFILL = <?= json_encode(array_values(array_filter(array_map(function ($x) { $a = explode(':', $x); return ['id' => (int)($a[0] ?? 0), 'paq' => max(1, (int)($a[1] ?? 1))]; }, explode(',', $_GET['producto_nuevo'] ?? '')), fn($r) => $r['id'] > 0))) ?>;</script>
<script src="js/compras.js">
</script>
<?php require __DIR__ . '/../vista/fin.php';
 ?>
</body>
</html>
