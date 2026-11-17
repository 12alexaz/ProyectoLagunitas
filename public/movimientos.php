<?php
require_once __DIR__ . '/../controlador/auth.php';
requerirLogin();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelo/Movimiento.php';

$modelo = new Movimiento($pdo);
$desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '') ? $_GET['desde'] : '';
$hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'] ?? '') ? $_GET['hasta'] : '';
$tipo = $_GET['tipo'] ?? '';
$producto = (int)($_GET['producto'] ?? 0);
$movs = $modelo->listar($desde, $hasta, $tipo, $producto);
$productos = $pdo->query("SELECT id, nombre FROM productos ORDER BY nombre")->fetchAll();
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$tipos = ['entrada' => ['Entrada', 'm-b-ok'], 'salida' => ['Salida', 'm-b-no'], 'ajuste' => ['Ajuste', 'm-b-info']];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Movimientos - Agencia Lagunitas</title>
<link rel="stylesheet" href="css/estilo.css?v=20261116c">
</head>
<body>
<?php require __DIR__ . '/../vista/inicio.php';
 ?>
<div class="mv-wrap">
 <div class="m-top">
<div>
<h1>Movimientos de inventario</h1>
<p>Historial de entradas, salidas y ajustes de stock (últimos 300).</p>
</div>
</div>
 <section class="m-panel">
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
<label>Tipo</label>
<select name="tipo">
<option value="">Todos</option><?php foreach($tipos as $k => $t): ?><option value="<?= $k ?>" <?= $tipo===$k?'selected':'' ?>><?= $t[0] ?></option><?php endforeach;
 ?></select>
</div>
   <div>
<label>Producto</label>
<select name="producto">
<option value="0">Todos</option><?php foreach($productos as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $producto===(int)$p['id']?'selected':'' ?>><?= $h($p['nombre']) ?></option><?php endforeach;
 ?></select>
</div>
   <button class="m-btn m-primario">Filtrar</button>
<a class="m-btn m-sec" href="movimientos.php">Limpiar</a>
  </form>
  <div class="m-scroll">
<table class="m-tabla">
<thead>
<tr>
<th>Fecha</th>
<th>Producto</th>
<th>Tipo</th>
<th class="num">Unidades</th>
<th>Motivo</th>
<th>Usuario</th>
</tr>
</thead>
<tbody>
  <?php if(!$movs): ?><tr>
<td colspan="6" class="m-vacio">No hay movimientos con esos filtros.</td>
</tr><?php else: foreach($movs as $m):
    $t = $tipos[$m['tipo']] ?? [$m['tipo'], 'm-b-info'];
    $c = (int)$m['cantidad'];
    $signo = $m['tipo'] === 'entrada' ? '+' . $c : ($m['tipo'] === 'salida' ? '-' . $c : ($c > 0 ? '+' . $c : (string)$c));
 ?>
   <tr>
<td><?= date('d/m/Y H:i', strtotime($m['fecha'])) ?></td>
<td><?= $h($m['producto']) ?> <small style="color:#7a8794">(<?= $h($m['codigo']) ?>)</small>
</td>
<td>
<span class="m-badge <?= $t[1] ?>"><?= $h($t[0]) ?></span>
</td>
<td class="num">
<strong><?= $signo ?></strong>
</td>
<td><?= $h($m['motivo'] ?? '') ?></td>
<td><?= $h($m['usuario']) ?></td>
</tr>
  <?php endforeach;
 endif;
 ?>
  </tbody>
</table>
</div>
 </section>
</div>
<?php require __DIR__ . '/../vista/fin.php';
 ?>
</body>
</html>
