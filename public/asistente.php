<?php
require_once __DIR__ . '/../controlador/auth.php';
requerirLogin();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelo/Asistente.php';

$admin = esAdmin();
$ventana = (int)($_GET['ventana'] ?? 30);
if (!in_array($ventana, [7, 15, 30, 60, 90], true)) $ventana = 30;
$cobertura = (int)($_GET['cobertura'] ?? 15);
if (!in_array($cobertura, [7, 15, 30], true)) $cobertura = 15;
$modeloAsistente = new Asistente($pdo);
$todos = $modeloAsistente->sugerencias($ventana, $cobertura);
$vencimientos = $modeloAsistente->alertasVencimiento();
$vencidos = count(array_filter($vencimientos, fn($l) => (int)$l['dias_restantes'] < 0));
$proximos = count($vencimientos) - $vencidos;

$atencion = array_values(array_filter($todos, fn($p) => $p['nivel'] !== 'ok'));
$criticos = count(array_filter($atencion, fn($p) => $p['nivel'] === 'critico'));
$ok = count($todos) - count($atencion);

// Agrupar por proveedor para armar pedidos
$porProv = [];
foreach ($atencion as $p) $porProv[$p['proveedor_id']][] = $p;

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$niveles = ['critico' => ['Crítico', 'm-b-no'], 'reponer' => ['Reponer pronto', 'm-b-warn'], 'ok' => ['Suficiente', 'm-b-ok']];
$cob = fn($d) => $d === null ? 'Sin ventas' : (round($d) . ' días');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Asistente Lagunitas - Agencia Lagunitas</title>
<link rel="stylesheet" href="css/estilo.css?v=20261116c">
</head>
<body>
<?php require __DIR__ . '/../vista/inicio.php';
 ?>
<div class="a-wrap">
 <div class="m-top">
<div>
<h1>🤖 Asistente Lagunitas</h1>
<p>Te dice qué productos conviene reponer, según lo que realmente vendes y lo que tarda cada proveedor.</p>
</div>
</div>

 <div class="m-chips">
  <div class="m-chip <?= $criticos ? 'alerta' : '' ?>">Críticos<b><?= $criticos ?></b>
</div>
  <div class="m-chip">Reponer pronto<b><?= count($atencion) - $criticos ?></b>
</div>
  <div class="m-chip">Con stock suficiente<b><?= $ok ?></b>
</div>
  <div class="m-chip <?= $vencidos ? 'alerta' : '' ?>">Lotes vencidos<b><?= $vencidos ?></b>
</div>
  <div class="m-chip">Por vencer (30 días)<b><?= $proximos ?></b>
</div>
 </div>

 <section class="m-panel">
  <form class="m-filtros" method="GET">
   <div>
<label>Calcular consumo con las ventas de los últimos</label>
<select name="ventana"><?php foreach([7,15,30,60,90] as $d): ?><option value="<?= $d ?>" <?= $ventana===$d?'selected':'' ?>><?= $d ?> días</option><?php endforeach;
 ?></select>
</div>
   <div>
<label>Cubrir después de la entrega</label>
<select name="cobertura"><?php foreach([7,15,30] as $d): ?><option value="<?= $d ?>" <?= $cobertura===$d?'selected':'' ?>><?= $d ?> días</option><?php endforeach;
 ?></select>
</div>
   <button class="m-btn m-primario">Recalcular</button>
  </form>
  <p class="m-nota">
<b>Cómo calcula:</b> consumo diario = unidades vendidas ÷ días. Conviene pedir cuando el stock llega al <i>punto de pedido</i> (consumo × días de entrega + stock de seguridad). La cantidad sugerida alcanza para cubrir la entrega y los días elegidos, redondeada a paquetes completos. Si aún no hay ventas, solo se compara con el stock de seguridad.</p>
 </section>

 <section class="m-panel" id="panel-vencimientos">
  <div class="m-top">
<div>
<h2>📅 Control de fechas de vencimiento</h2>
<p>Solo se muestran lotes que tienen fecha registrada y unidades disponibles.</p>
</div>
   <?php if($vencimientos): ?><button type="button" class="m-btn m-primario" id="hablar-vencimientos">🔊 Escuchar alertas</button><?php endif;
 ?>
  </div>
  <?php if(!$vencimientos): ?>
   <p class="m-vacio">✅ No hay lotes vencidos ni próximos a vencer en los siguientes 30 días.</p>
  <?php else: ?>
   <div class="m-scroll">
<table class="m-tabla">
<thead>
<tr>
<th>Producto</th>
<th>Lote</th>
<th>Vencimiento</th>
<th class="num">Unidades disponibles</th>
<th>Estado</th>
</tr>
</thead>
<tbody>
   <?php foreach($vencimientos as $l): $dias=(int)$l['dias_restantes'];
 ?>
    <tr>
<td><?= $h($l['producto']) ?></td>
<td>#<?= (int)$l['lote_id'] ?></td>
<td><?= $h(date('d/m/Y', strtotime($l['fecha_vencimiento']))) ?></td>
<td class="num"><?= (int)$l['cantidad_actual'] ?></td>
<td>
     <?php if($dias < 0): ?><span class="m-badge m-b-no">Vencido hace <?= abs($dias) ?> días</span>
     <?php elseif($dias === 0): ?><span class="m-badge m-b-no">Vence hoy</span>
     <?php elseif($dias <= 7): ?><span class="m-badge m-b-no">Vence en <?= $dias ?> días</span>
     <?php elseif($dias <= 15): ?><span class="m-badge m-b-warn">Vence en <?= $dias ?> días</span>
     <?php else: ?><span class="m-badge m-b-ok">Vence en <?= $dias ?> días</span><?php endif;
 ?>
    </td>
</tr>
   <?php endforeach;
 ?>
   </tbody>
</table>
</div>
  <?php endif;
 ?>
 </section>

 <?php if(!$atencion): ?>
  <section class="m-panel">
<p class="m-vacio">✅ Todo en orden: ningún producto necesita reposición ahora.</p>
</section>
 <?php else: foreach($porProv as $provId => $lista): $nombreProv = $lista[0]['proveedor'];
    $param = implode(',', array_map(fn($p) => $p['id'] . ':' . max(1, $p['paquetes']), $lista));
 ?>
  <section class="m-panel">
   <div class="m-top">
<h2>🚚 <?= $h($nombreProv) ?> <small style="font-weight:400;color:#7a8794">· entrega en <?= (int)$lista[0]['dias_entrega'] ?> días</small>
</h2>
    <?php if($admin && $provId): ?><a class="m-btn m-verde" href="compras.php?proveedor_nuevo=<?= (int)$provId ?>&producto_nuevo=<?= $h($param) ?>#c-prov">Crear compra con estos productos</a><?php endif;
 ?></div>
   <div class="m-scroll">
<table class="m-tabla">
<thead>
<tr>
<th>Producto</th>
<th>Nivel</th>
<th class="num">Stock</th>
<th class="num">Seguridad</th>
<th class="num">Vendidas (<?= $ventana ?> d)</th>
<th class="num">Cobertura</th>
<th class="num">Punto de pedido</th>
<th class="num">Sugerido</th>
</tr>
</thead>
<tbody>
   <?php foreach($lista as $p): [$t, $c] = $niveles[$p['nivel']];
 ?>
    <tr>
<td><?= $h($p['nombre']) ?></td>
<td>
<span class="m-badge <?= $c ?>"><?= $t ?></span>
</td>
<td class="num">
<strong><?= $p['stock'] ?></strong>
</td>
<td class="num"><?= $p['seguridad'] ?></td>
<td class="num"><?= $p['vendidas'] ?></td>
<td class="num"><?= $cob($p['cobertura_dias']) ?></td>
<td class="num"><?= $p['punto_pedido'] ?></td>
<td class="num"><?= $p['paquetes'] ? '<strong>' . $p['paquetes'] . ' paq.</strong> <small>(' . $p['unidades_sugeridas'] . ' u.)</small>' : '—' ?></td>
</tr>
   <?php endforeach;
 ?>
   </tbody>
</table>
</div>
   <?php if(!$provId): ?><p class="m-nota">Estos productos no tienen proveedor asignado; asígnalo en Productos para poder armar la compra.</p><?php endif;
 ?>
  </section>
 <?php endforeach;
 endif;
 ?>
</div>
<script>
(function(){
 const boton=document.getElementById('hablar-vencimientos');
 if(!boton) return;
 const mensajes=<?= json_encode(array_map(function($l){
   $d=(int)$l['dias_restantes'];
   if($d<0) $estado='está vencido hace '.abs($d).' días';
   elseif($d===0) $estado='vence hoy';
   else $estado='vence en '.$d.' días';
   return $l['producto'].', '.$estado.', quedan '.(int)$l['cantidad_actual'].' unidades';
 }, $vencimientos), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
 boton.addEventListener('click',function(){
   if(!('speechSynthesis' in window)){ lagDialog.error('Este navegador no permite reproducir voz.'); return; }
   window.speechSynthesis.cancel();
   const texto=mensajes.length ? 'Atención, administrador. Alertas de vencimiento. '+mensajes.join('. ') : 'No hay alertas de vencimiento.';
   const voz=new SpeechSynthesisUtterance(texto); voz.lang='es-ES'; voz.rate=0.95;
   window.speechSynthesis.speak(voz);
 });
})();
</script>
<?php require __DIR__ . '/../vista/fin.php';
 ?>
</body>
</html>
