<?php
require_once __DIR__ . '/../controlador/auth.php';
requerirLogin();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelo/Venta.php';

$modelo = new Venta($pdo);
$admin = esAdmin();
$uid = (int)$_SESSION['usuario_id'];
$filtroUsuario = $admin ? 0 : $uid;
   // el vendedor solo ve sus propias ventas

$desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '') ? $_GET['desde'] : '';
$hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'] ?? '') ? $_GET['hasta'] : '';
$estado = $_GET['estado'] ?? '';

$ventas = $modelo->listar($desde, $hasta, $estado, $filtroUsuario);
$catalogo = $modelo->presentacionesVendibles();
$hoy = $modelo->resumenHoy($filtroUsuario);

$detalle = null;
if (isset($_GET['ver'])) {
    $detalle = $modelo->obtener((int)$_GET['ver']);
    if ($detalle && !$admin && (int)$detalle['usuario_id'] !== $uid) $detalle = null;
}

$mensajes = ['registrada' => 'Venta registrada correctamente. El stock fue actualizado.', 'anulada' => 'Venta anulada. El stock fue devuelto al inventario.'];
$mensaje = $mensajes[$_GET['mensaje'] ?? ''] ?? '';
$error = $_GET['error'] ?? '';
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fecha = fn($f) => date('d/m/Y H:i', strtotime($f));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ventas - Agencia Lagunitas</title>
<link rel="stylesheet" href="css/estilo.css?v=20261116c">
<style>
.v-wrap{max-width:1200px;
    margin:28px auto;
    padding:0 22px}.v-top{display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    flex-wrap:wrap}.v-panel{background:#fff;
    border-radius:12px;
    padding:22px;
    margin:20px 0;
    box-shadow:0 3px 14px #0000000d}.v-add{display:grid;
    grid-template-columns:1fr 2fr 120px auto;
    gap:14px;
    align-items:end}.v-add label{display:block;
    font-weight:600;
    font-size:19px;
    margin-bottom:6px}.v-add input,.v-add select,.v-filtros input,.v-filtros select{width:100%;
    padding:10px;
    border:1px solid #d5dbe3;
    border-radius:7px;
    box-sizing:border-box}.v-btn{border:0;
    border-radius:7px;
    padding:10px 16px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
    font-size:19px}.v-primario{background:#173f67;
    color:#fff}.v-sec{background:#e9eef4;
    color:#173f67}.v-edit{background:#e7f1ff;
    color:#174d83}.v-borrar{background:#ffe8e8;
    color:#a32626}.v-ok-btn{background:#16743a;
    color:#fff}.v-btn:disabled{opacity:.5;
    cursor:not-allowed}.v-tabla{width:100%;
    border-collapse:collapse;
    font-size:19px}.v-tabla th,.v-tabla td{padding:11px 9px;
    border-bottom:1px solid #e8edf2;
    text-align:left}.v-tabla th{background:#f3f6f9}.v-tabla .num{text-align:right}.v-scroll{overflow-x:auto}.v-alerta{padding:12px 15px;
    border-radius:7px;
    margin:12px 0}.v-ok{background:#e7f7ec;
    color:#176b35}.v-error{background:#ffeded;
    color:#9c2222}.v-filtros{display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:15px;
    align-items:center}.v-filtros input,.v-filtros select{width:auto}.v-total{display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-top:16px}.v-total strong{font-size:24px;
    color:#173f67}.v-chips{display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-top:14px}.v-chip{background:#f3f6f9;
    border-radius:9px;
    padding:10px 16px;
    font-size:16px}.v-chip b{display:block;
    font-size:22px;
    color:#173f67}.v-badge{padding:3px 9px;
    border-radius:99px;
    font-size:19px;
    font-weight:700}.v-b-ok{background:#e7f7ec;
    color:#176b35}.v-b-no{background:#ffeded;
    color:#9c2222}.v-info{font-size:19px;
    color:#5b6b7c;
    margin-top:6px;
    min-height:18px}.v-vacio{color:#7a8794;
    text-align:center;
    padding:18px}@media(max-width:800px){.v-add{grid-template-columns:1fr 1fr}}@media(max-width:520px){.v-add{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php require __DIR__ . '/../vista/inicio.php';
 ?>
<div class="v-wrap">
 <div class="v-top">
<div>
<h1>Ventas</h1>
<p>Registra ventas por unidad, paquete o caja. El stock se descuenta automáticamente.</p>
</div>
<a class="v-btn v-sec" href="dashboard.php">← Volver al panel</a>
</div>

 <?php if($mensaje): ?><div class="v-alerta v-ok"><?= $h($mensaje) ?></div><?php endif;
 ?>
 <?php if($error): ?><div class="v-alerta v-error"><?= $h($error) ?></div><?php endif;
 ?>

 <div class="v-chips">
  <div class="v-chip">Ventas de hoy<b><?= (int)$hoy['cantidad'] ?></b>
</div>
  <div class="v-chip">Total vendido hoy<b>Bs <?= number_format((float)$hoy['total'], 2) ?></b>
</div>
 </div>

 <?php if($detalle): ?>
 <section class="v-panel">
  <div class="v-top">
   <h2>Venta #<?= (int)$detalle['id'] ?> <span class="v-badge <?= $detalle['estado']==='completada'?'v-b-ok':'v-b-no' ?>"><?= $detalle['estado']==='completada'?'Completada':'Anulada' ?></span>
</h2>
   <a class="v-btn v-sec" href="ventas.php">Cerrar detalle</a>
  </div>
  <p>Fecha: <?= $fecha($detalle['fecha']) ?> · Vendedor: <?= $h($detalle['vendedor']) ?></p>
  <div class="v-scroll">
<table class="v-tabla">
<thead>
<tr>
<th>Código</th>
<th>Producto</th>
<th>Presentación</th>
<th class="num">Cantidad</th>
<th class="num">Unidades</th>
<th class="num">Precio</th>
<th class="num">Subtotal</th>
</tr>
</thead>
<tbody>
  <?php foreach($detalle['detalle'] as $d): ?><tr>
<td><?= $h($d['codigo']) ?></td>
<td><?= $h($d['producto']) ?></td>
<td><?= $h($d['presentacion'] ?? '-') ?></td>
<td class="num"><?= (int)$d['cantidad'] ?></td>
<td class="num"><?= (int)$d['unidades'] ?: '-' ?></td>
<td class="num">Bs <?= number_format((float)$d['precio_unitario'],2) ?></td>
<td class="num">Bs <?= number_format((float)$d['subtotal'],2) ?></td>
</tr><?php endforeach;
 ?>
  </tbody>
</table>
</div>
  <div class="v-total">
<strong>Total: Bs <?= number_format((float)$detalle['total'],2) ?></strong>
   <?php if($admin && $detalle['estado']==='completada'): ?>
   <form method="POST" action="../controlador/ventaControlador.php" data-confirm="¿Anular esta venta? El stock será devuelto al inventario."><?= csrfCampo() ?>
<input type="hidden" name="accion" value="anular">
<input type="hidden" name="id" value="<?= (int)$detalle['id'] ?>">
<button class="v-btn v-borrar" type="submit">Anular venta</button>
</form>
   <?php endif;
 ?>
  </div>
 </section>
 <?php endif;
 ?>

 <section class="v-panel">
  <h2>Nueva venta</h2>
  <?php if(!$catalogo): ?>
   <p class="v-vacio">No hay presentaciones activas para vender. Registra presentaciones en el módulo de Presentaciones.</p>
  <?php else: ?>
  <div class="v-add">
   <div>
<label for="v-buscar">Buscar producto</label>
<input type="search" id="v-buscar" placeholder="Nombre o código">
</div>
   <div>
<label for="v-pres">Producto y presentación</label>
<select id="v-pres">
</select>
</div>
   <div>
<label for="v-cant">Cantidad</label>
<input type="number" id="v-cant" min="1" step="1" value="1">
</div>
   <div>
<button type="button" class="v-btn v-primario" id="v-agregar">Agregar</button>
</div>
  </div>
  <div class="v-info" id="v-info">
</div>

  <form method="POST" action="../controlador/ventaControlador.php" id="v-form"><?= csrfCampo() ?>
   <input type="hidden" name="accion" value="registrar">
   <div class="v-scroll">
<table class="v-tabla">
<thead>
<tr>
<th>Producto</th>
<th>Presentación</th>
<th class="num">Cantidad</th>
<th class="num">Precio</th>
<th class="num">Subtotal</th>
<th>
</th>
</tr>
</thead>
<tbody id="v-lineas">
<tr>
<td colspan="6" class="v-vacio">Aún no agregaste productos.</td>
</tr>
</tbody>
</table>
</div>
   <div id="v-hidden">
</div>
   <div class="v-total">
<strong id="v-total">Total: Bs 0.00</strong>
<div>
<button type="button" class="v-btn v-sec" id="v-vaciar">Vaciar</button>
<button type="submit" class="v-btn v-ok-btn" id="v-confirmar" disabled>Confirmar venta</button>
</div>
</div>
  </form>
  <?php endif;
 ?>
 </section>

 <section class="v-panel">
  <h2><?= $admin ? 'Historial de ventas' : 'Mis ventas' ?></h2>
  <form class="v-filtros" method="GET">
   <label>Desde <input type="date" name="desde" value="<?= $h($desde) ?>">
</label>
   <label>Hasta <input type="date" name="hasta" value="<?= $h($hasta) ?>">
</label>
   <select name="estado">
<option value="">Todas</option>
<option value="completada" <?= $estado==='completada'?'selected':'' ?>>Completadas</option>
<option value="anulada" <?= $estado==='anulada'?'selected':'' ?>>Anuladas</option>
</select>
   <button class="v-btn v-primario">Filtrar</button>
<a class="v-btn v-sec" href="ventas.php">Limpiar</a>
  </form>
  <div class="v-scroll">
<table class="v-tabla">
<thead>
<tr>
<th>N.º</th>
<th>Fecha</th>
<th>Vendedor</th>
<th class="num">Líneas</th>
<th class="num">Total</th>
<th>Estado</th>
<th>
</th>
</tr>
</thead>
<tbody>
  <?php if(!$ventas): ?><tr>
<td colspan="7" class="v-vacio">No se encontraron ventas.</td>
</tr><?php else: foreach($ventas as $v): ?>
   <tr>
<td>#<?= (int)$v['id'] ?></td>
<td><?= $fecha($v['fecha']) ?></td>
<td><?= $h($v['vendedor']) ?></td>
<td class="num"><?= (int)$v['lineas'] ?></td>
<td class="num">Bs <?= number_format((float)$v['total'],2) ?></td>
<td>
<span class="v-badge <?= $v['estado']==='completada'?'v-b-ok':'v-b-no' ?>"><?= $v['estado']==='completada'?'Completada':'Anulada' ?></span>
</td>
<td>
<a class="v-btn v-edit" href="?ver=<?= (int)$v['id'] ?>">Ver detalle</a>
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
<script>window.CATALOGO = <?= json_encode($catalogo, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="js/ventas.js">
</script>
<?php require __DIR__ . '/../vista/fin.php';
 ?>
</body>
</html>
