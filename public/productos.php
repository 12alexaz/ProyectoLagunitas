<?php
require_once __DIR__ . '/../controlador/auth.php';
requerirAdmin();
require_once '../conexion.php';
require_once '../modelo/Producto.php';
$modelo = new Producto($pdo);
$busqueda = trim($_GET['buscar'] ?? '');
$categoriaFiltro = (int)($_GET['categoria'] ?? 0);
$mostrarInactivos = ($_GET['estado'] ?? 'activos') === 'todos';
$productos = $modelo->listar($busqueda, $categoriaFiltro, $mostrarInactivos);
$categorias = $pdo->query("SELECT id, nombre FROM categorias WHERE activo=1 ORDER BY nombre")->fetchAll();
$proveedores = $pdo->query("SELECT id, nombre FROM proveedores WHERE activo=1 ORDER BY nombre")->fetchAll();
$editar = isset($_GET['editar']) ? $modelo->obtener((int)$_GET['editar']) : null;
$valor = fn($campo, $defecto = '') => htmlspecialchars((string)($editar[$campo] ?? $defecto), ENT_QUOTES, 'UTF-8');
$mensaje = ['registrado'=>'Producto registrado correctamente.','actualizado'=>'Producto actualizado correctamente.','desactivado'=>'Producto desactivado correctamente.','reactivado'=>'Producto reactivado correctamente.'][$_GET['mensaje'] ?? ''] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Productos - Agencia Lagunitas</title>
<link rel="stylesheet" href="css/estilo.css?v=20261116c">
<style>
.productos-wrap{max-width:1200px;
    margin:28px auto;
    padding:0 22px}.productos-top{display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    flex-wrap:wrap}.productos-panel{background:#fff;
    border-radius:12px;
    padding:22px;
    margin:20px 0;
    box-shadow:0 3px 14px #0000000d}.productos-form{display:grid;
    grid-template-columns:repeat(3,minmax(180px,1fr));
    gap:14px}.productos-form label{display:block;
    font-weight:600;
    font-size:19px;
    margin-bottom:6px}.productos-form input,.productos-form select,.filtros input,.filtros select{width:100%;
    padding:10px;
    border:1px solid #d5dbe3;
    border-radius:7px}.productos-form button,.btn-accion{border:0;
    border-radius:7px;
    padding:10px 16px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block}.btn-primario{background:#173f67;
    color:white}.btn-secundario{background:#e9eef4;
    color:#173f67}.filtros{display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:15px}.filtros input{max-width:300px}.tabla-productos{width:100%;
    border-collapse:collapse;
    font-size:19px}.tabla-productos th,.tabla-productos td{padding:11px 9px;
    border-bottom:1px solid #e8edf2;
    text-align:left}.tabla-productos th{background:#f3f6f9}.tabla-scroll{overflow-x:auto}.acciones{display:flex;
    gap:7px;
    align-items:center}.btn-editar{background:#e7f1ff;
    color:#174d83}.btn-borrar{background:#ffe8e8;
    color:#a32626}.alerta{padding:12px 15px;
    border-radius:7px;
    margin:12px 0}.ok{background:#e7f7ec;
    color:#176b35}.error{background:#ffeded;
    color:#9c2222}.estado-bajo{color:#b42318;
    font-weight:700}.estado-ok{color:#16743a;
    font-weight:700}@media(max-width:760px){.productos-form{grid-template-columns:1fr 1fr}}@media(max-width:480px){.productos-form{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php require __DIR__ . '/../vista/inicio.php';
 ?>
<div class="productos-wrap">
 <div class="productos-top">
<div>
<h1>Gestión de productos</h1>
<p>Registro y consulta de bebidas y existencias.</p>
</div>
<a class="btn-accion btn-secundario" href="dashboard.php">← Volver al panel</a>
</div>
 <?php if($mensaje): ?><div class="alerta ok"><?= htmlspecialchars($mensaje) ?></div><?php endif;
 ?>
 <?php if($error): ?><div class="alerta error"><?= htmlspecialchars($error) ?></div><?php endif;
 ?>
 <section class="productos-panel">
  <h2><?= $editar ? 'Editar producto' : 'Registrar nuevo producto' ?></h2>
  <form class="productos-form" action="../controlador/productoControlador.php" method="POST"><?= csrfCampo() ?>
   <input type="hidden" name="accion" value="<?= $editar ? 'actualizar' : 'guardar' ?>">
   <?php if($editar): ?><input type="hidden" name="id" value="<?= (int)$editar['id'] ?>"><?php endif;
 ?>
   <div>
<label>Código *</label>
<input name="codigo" maxlength="40" required value="<?= $valor('codigo') ?>">
</div>
   <div>
<label>Nombre del producto *</label>
<input name="nombre" maxlength="120" required value="<?= $valor('nombre') ?>">
</div>
   <div>
<label>Categoría *</label>
<select name="categoria_id" required>
<option value="">Seleccione</option><?php foreach($categorias as $c): ?><option value="<?= $c['id'] ?>" <?= (string)($editar['categoria_id'] ?? '')===(string)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option><?php endforeach;
 ?></select>
</div>
   <div>
<label>Proveedor</label>
<select name="proveedor_id">
<option value="">Sin proveedor</option><?php foreach($proveedores as $p): ?><option value="<?= $p['id'] ?>" <?= (string)($editar['proveedor_id'] ?? '')===(string)$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nombre']) ?></option><?php endforeach;
 ?></select>
</div>
   <div>
<label>Stock actual (unidades) *</label>
<input type="number" min="0" name="stock_actual" required value="<?= $valor('stock_actual','0') ?>">
</div>
   <div>
<label>Stock de seguridad *</label>
<input type="number" min="0" name="stock_seguridad" required value="<?= $valor('stock_seguridad','0') ?>">
</div>
   <div>
<label>Precio de compra (Bs) *</label>
<input type="number" min="0" step="0.01" name="precio_compra" required value="<?= $valor('precio_compra','0.00') ?>">
</div>
   <div>
<label>Precio de venta (Bs) *</label>
<input type="number" min="0" step="0.01" name="precio_venta" required value="<?= $valor('precio_venta','0.00') ?>">
</div>
   <div>
<label>Días de entrega del proveedor *</label>
<input type="number" min="0" name="dias_entrega" required value="<?= $valor('dias_entrega_proveedor','2') ?>">
</div>
   <div style="grid-column:1/-1;display:flex;gap:9px;flex-wrap:wrap">
<button class="btn-primario" type="submit"><?= $editar ? 'Guardar cambios' : 'Registrar producto' ?></button><?php if($editar): ?><a class="btn-accion btn-secundario" href="productos.php">Cancelar edición</a><?php endif;
 ?></div>
  </form>
 </section>
 <section class="productos-panel">
  <h2>Productos registrados</h2>
  <form class="filtros" method="GET">
<input type="search" name="buscar" placeholder="Buscar por nombre o código" value="<?= htmlspecialchars($busqueda) ?>">
<select name="estado">
<option value="activos" <?= !$mostrarInactivos?'selected':'' ?>>Solo activos</option>
<option value="todos" <?= $mostrarInactivos?'selected':'' ?>>Activos e inactivos</option>
</select>
<select name="categoria">
<option value="0">Todas las categorías</option><?php foreach($categorias as $c): ?><option value="<?= $c['id'] ?>" <?= $categoriaFiltro===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option><?php endforeach;
 ?></select>
<button class="btn-accion btn-primario">Buscar</button>
<a class="btn-accion btn-secundario" href="productos.php">Limpiar</a>
</form>
  <div class="tabla-scroll">
<table class="tabla-productos">
<thead>
<tr>
<th>Código</th>
<th>Producto</th>
<th>Categoría</th>
<th>Proveedor</th>
<th>Stock</th>
<th>Precio compra</th>
<th>Precio venta</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
  <?php if(!$productos): ?><tr>
<td colspan="9">No se encontraron productos.</td>
</tr><?php else: foreach($productos as $p): ?><tr>
<td><?= htmlspecialchars($p['codigo']) ?></td>
<td><?= htmlspecialchars($p['nombre']) ?></td>
<td><?= htmlspecialchars($p['categoria']) ?></td>
<td><?= htmlspecialchars($p['proveedor'] ?? 'Sin proveedor') ?></td>
<td>
<span class="<?= (int)$p['stock_actual'] <= (int)$p['stock_seguridad'] ? 'estado-bajo':'estado-ok' ?>"><?= (int)$p['stock_actual'] ?></span>
</td>
<td>Bs <?= number_format((float)$p['precio_compra'],2) ?></td>
<td>Bs <?= number_format((float)$p['precio_venta'],2) ?></td>
<td><?= (int)$p['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
<td>
<div class="acciones"><?php if ((int)$p['activo'] === 1): ?><a class="btn-accion btn-editar" href="?editar=<?= $p['id'] ?>">Editar</a>
<form method="POST" action="../controlador/productoControlador.php" data-confirm="¿Deseas desactivar este producto?"><?= csrfCampo() ?>
<input type="hidden" name="accion" value="desactivar">
<input type="hidden" name="id" value="<?= $p['id'] ?>">
<button class="btn-accion btn-borrar" type="submit">Desactivar</button>
</form><?php else: ?><form method="POST" action="../controlador/productoControlador.php" data-confirm="¿Deseas volver a activar este producto?"><?= csrfCampo() ?>
<input type="hidden" name="accion" value="reactivar">
<input type="hidden" name="id" value="<?= $p['id'] ?>">
<button class="btn-accion btn-primario" type="submit">Reactivar</button>
</form><?php endif;
 ?></div>
</td>
</tr><?php endforeach;
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
