<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__.'/../conexion.php';
require_once __DIR__.'/../modelo/Presentacion.php';
requerirAdmin('../public/index.php', '../public/dashboard.php');
csrfExigir('../public/presentaciones.php');
$modelo=new Presentacion($pdo);
 $accion=$_POST['accion'] ?? '';
try {
 if($accion==='guardar' || $accion==='actualizar') {
  $id=(int)($_POST['id']??0);
 $producto=(int)($_POST['producto_id']??0);
 $nombre=trim($_POST['nombre']??'');
  $cantidad=filter_var($_POST['cantidad_unidades']??null,FILTER_VALIDATE_INT);
 $precio=filter_var($_POST['precio_venta']??null,FILTER_VALIDATE_FLOAT);
  if($producto<=0 || $nombre==='' || $cantidad===false || $cantidad<1 || $precio===false || $precio<0) throw new Exception('Completa correctamente los campos. La cantidad debe ser mayor que cero y el precio no puede ser negativo.');
  $st=$pdo->prepare('SELECT COUNT(*) FROM productos WHERE id=? AND activo=1');
$st->execute([$producto]);
if(!(int)$st->fetchColumn())throw new Exception('Selecciona un producto activo.');
  if($modelo->existe($producto,$nombre,$accion==='actualizar'?$id:null)) throw new Exception('Ese producto ya tiene una presentación con ese nombre.');
  $d=['producto'=>$producto,'nombre'=>$nombre,'cantidad'=>$cantidad,'precio'=>$precio];
  if($accion==='guardar'){$modelo->guardar($d);
$m='registrado';
}else{if($id<=0)throw new Exception('Presentación no válida.');
$modelo->actualizar($id,$d);
$m='actualizado';
}
  header('Location: ../public/presentaciones.php?mensaje='.$m);
exit();
 }
 if($accion==='desactivar'||$accion==='reactivar'){$id=(int)($_POST['id']??0);
if($id<=0)throw new Exception('Presentación no válida.');
$modelo->cambiarEstado($id,$accion==='reactivar'?1:0);
header('Location: ../public/presentaciones.php?mensaje='.($accion==='reactivar'?'reactivado':'desactivado'));
exit();
}
 throw new Exception('Acción no válida.');
} catch(Throwable $e){$msg=($e instanceof PDOException)?'No se pudo completar la operación. Verifica los datos y la base de datos.':$e->getMessage();
$url='../public/presentaciones.php?error='.urlencode($msg);
if(!empty($_POST['id']))$url.='&editar='.(int)$_POST['id'];
header('Location: '.$url);
exit();
}
