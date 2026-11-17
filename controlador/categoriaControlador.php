<?php
require_once __DIR__ . '/auth.php';
requerirAdmin('../public/index.php', '../public/dashboard.php');
require_once '../conexion.php';
require_once '../modelo/Categoria.php';
$modelo = new Categoria($pdo);
function volver(string $parametro, string $valor): void {
    header('Location: ../public/categorias.php?'.$parametro.'='.urlencode($valor));
 exit();
}
csrfExigir('../public/categorias.php');
$accion = $_POST['accion'] ?? '';
try {
    if ($accion === 'guardar' || $accion === 'actualizar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        if ($nombre === '') volver('error','El nombre de la categoría es obligatorio.');
        if ($accion === 'guardar') { $modelo->guardar($nombre,$descripcion);
 volver('mensaje','registrado');
 }
        if ($id < 1) volver('error','Identificador de categoría inválido.');
        $modelo->actualizar($id,$nombre,$descripcion);
 volver('mensaje','actualizado');
    }
    if ($accion === 'desactivar' || $accion === 'reactivar') {
        $id=(int)($_POST['id'] ?? 0);
        if ($id < 1) volver('error','Identificador de categoría inválido.');
        $modelo->cambiarEstado($id,$accion === 'reactivar' ? 1 : 0);
        volver('mensaje',$accion === 'reactivar' ? 'reactivado' : 'desactivado');
    }
    volver('error','Acción no válida.');
} catch (PDOException $e) {
    if ($e->getCode() === '23000') volver('error','Ya existe una categoría con esos datos o está relacionada con productos.');
    volver('error','No se pudo completar la operación. Verifica la base de datos.');
}
