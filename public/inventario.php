<?php
require_once __DIR__ . '/../controlador/auth.php';
requerirLogin();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelo/Inventario.php';

$modelo = new Inventario($pdo);
$admin = esAdmin();
$buscar = trim($_GET['buscar'] ?? '');
$categoria = (int)($_GET['categoria'] ?? 0);
$estado = in_array($_GET['estado'] ?? '', ['agotado', 'bajo', 'alerta', 'normal'], true) ? $_GET['estado'] : '';
$items = $modelo->listar($buscar, $categoria, $estado);
$res = $modelo->resumen();
$categorias = $pdo->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();
$mensaje = ['ajustado' => 'Stock ajustado correctamente. El cambio quedó registrado en Movimientos.'][$_GET['mensaje'] ?? ''] ?? '';
$error = $_GET['error'] ?? '';
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventario - Agencia Lagunitas</title>
<link rel="stylesheet" href="css/estilo.css?v=20261116c">
</head>
<body>
<?php require __DIR__ . '/../vista/inicio.php';
 ?>
<div class="i-wrap">
 <div class="m-top">
<div>
<h1>Inventario</h1>
<p>Existencias actuales en unidades y alertas de stock.</p>
</div>
</div>
 <?php if($mensaje): ?><div class="m-alerta m-ok"><?= $h($mensaje) ?></div><?php endif;
 ?>
 <?php if($error): ?><div class="m-alerta m-error"><?= $h($error) ?></div><?php endif;
 ?>

 <div class="m-chips">
  <div class="m-chip">Productos activos<b><?= $res['total'] ?></b>
</div>
  <div class="m-chip">Unidades en stock<b><?= number_format($res['unidades']) ?></b>
</div>
  <div class="m-chip <?= $res['bajos'] ? 'alerta' : '' ?>">Stock bajo<b><?= $res['bajos'] ?></b>
</div>
  <div class="m-chip <?= $res['agotados'] ? 'alerta' : '' ?>">Agotados<b><?= $res['agotados'] ?></b>
</div>
 </div>

 <?php if($admin): ?>
 <section class="m-panel" id="ajuste" hidden>
  <h2>Ajustar stock</h2>
  <p class="m-nota">Úsalo cuando el conteo físico no coincide con el sistema (merma, rotura, error). Se guarda como movimiento de tipo «ajuste».</p>
  <form class="m-form" method="POST" action="../controlador/inventarioControlador.php" style="margin-top:14px"><?= csrfCampo() ?>
   <input type="hidden" name="accion" value="ajustar">
   <input type="hidden" name="producto_id" id="aj-id">
   <div>
<label>Producto</label>
<input id="aj-nombre" disabled>
</div>
   <div>
<label>Stock actual</label>
<input id="aj-actual" disabled>
</div>
   <div>
<label for="aj-nuevo">Stock real contado *</label>
<input type="number" min="0" step="1" name="nuevo_stock" id="aj-nuevo" required>
</div>
   <div>
<label for="aj-motivo">Motivo *</label>
<input name="motivo" id="aj-motivo" maxlength="150" required placeholder="Ej. Botellas rotas">
</div>
   <div style="grid-column:1/-1;display:flex;gap:9px">
<button class="m-btn m-primario" type="submit">Guardar ajuste</button>
<button type="button" class="m-btn m-sec" id="aj-cancel">Cancelar</button>
</div>
  </form>
 </section>
 <?php endif;
 ?>

 <section class="m-panel">
  <form class="m-filtros" method="GET">
   <div>
<label>Buscar</label>
<input type="search" name="buscar" placeholder="Nombre o código" value="<?= $h($buscar) ?>">
</div>
   <div>
<label>Categoría</label>
<select name="categoria">
<option value="0">Todas</option><?php foreach($categorias as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $categoria===(int)$c['id']?'selected':'' ?>><?= $h($c['nombre']) ?></option><?php endforeach;
 ?></select>
</div>
   <div>
<label>Estado</label>
<select name="estado">
<option value="">Todos</option>
<option value="alerta" <?= $estado==='alerta'?'selected':'' ?>>Requieren atención</option>
<option value="bajo" <?= $estado==='bajo'?'selected':'' ?>>Stock bajo</option>
<option value="agotado" <?= $estado==='agotado'?'selected':'' ?>>Agotados</option>
<option value="normal" <?= $estado==='normal'?'selected':'' ?>>Normal</option>
</select>
</div>
   <button class="m-btn m-primario">Filtrar</button>
<a class="m-btn m-sec" href="inventario.php">Limpiar</a>
  </form>
  <div class="m-scroll">
<table class="m-tabla">
<thead>
<tr>
<th>Código</th>
<th>Producto</th>
<th>Categoría</th>
<th>Proveedor</th>
<th class="num">Stock</th>
<th class="num">Seguridad</th>
<th>Estado</th><?php if($admin): ?><th>
</th><?php endif;
 ?></tr>
</thead>
<tbody>
  <?php if(!$items): ?><tr>
<td colspan="8" class="m-vacio">No se encontraron productos.</td>
</tr><?php else: foreach($items as $p):
    $s = (int)$p['stock_actual'];
 $seg = (int)$p['stock_seguridad'];
    if ($s <= 0) { $cls = 'm-b-no';
 $txt = 'Agotado';
 } elseif ($s <= $seg) { $cls = 'm-b-warn';
 $txt = 'Stock bajo';
 } else { $cls = 'm-b-ok';
 $txt = 'Normal';
 } ?>
   <tr>
<td><?= $h($p['codigo']) ?></td>
<td><?= $h($p['nombre']) ?></td>
<td><?= $h($p['categoria']) ?></td>
<td><?= $h($p['proveedor'] ?? 'Sin proveedor') ?></td>
<td class="num">
<strong><?= $s ?></strong>
</td>
<td class="num"><?= $seg ?></td>
<td>
<span class="m-badge <?= $cls ?>"><?= $txt ?></span>
</td>
   <?php if($admin): ?><td>
<button type="button" class="m-btn m-edit js-ajustar" data-id="<?= (int)$p['id'] ?>" data-nombre="<?= $h($p['nombre']) ?>" data-stock="<?= $s ?>">Ajustar</button>
</td><?php endif;
 ?></tr>
  <?php endforeach;
 endif;
 ?>
  </tbody>
</table>
</div>
  <p class="m-nota">El stock se mide en unidades sueltas. Una venta de «Paquete x12» descuenta 12 unidades.</p>
 </section>
</div>
<?php if($admin): ?>
<script>
(function(){
  var panel=document.getElementById('ajuste'); if(!panel) return;
  document.querySelectorAll('.js-ajustar').forEach(function(b){
    b.addEventListener('click',function(){
      document.getElementById('aj-id').value=b.dataset.id;
      document.getElementById('aj-nombre').value=b.dataset.nombre;
      document.getElementById('aj-actual').value=b.dataset.stock;
      document.getElementById('aj-nuevo').value=b.dataset.stock;
      document.getElementById('aj-motivo').value='';
      panel.hidden=false; panel.scrollIntoView({behavior:'smooth',block:'start'});
      document.getElementById('aj-nuevo').focus();
    });
  });
  document.getElementById('aj-cancel').addEventListener('click',function(){panel.hidden=true;});
})();
</script>
<?php endif;
 ?>
<?php require __DIR__ . '/../vista/fin.php';
 ?>
</body>
</html>
