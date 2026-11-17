<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelo/Producto.php';

requerirAdmin('../public/index.php', '../public/dashboard.php');
csrfExigir('../public/productos.php');

$modelo = new Producto($pdo);
$accion = $_POST['accion'] ?? '';

try {
    if ($accion === 'guardar' || $accion === 'actualizar') {
        $id = (int)($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $categoria = (int)($_POST['categoria_id'] ?? 0);
        $proveedor = ($_POST['proveedor_id'] ?? '') !== '' ? (int)$_POST['proveedor_id'] : null;
        $stock = filter_var($_POST['stock_actual'] ?? null, FILTER_VALIDATE_INT);
        $seguridad = filter_var($_POST['stock_seguridad'] ?? null, FILTER_VALIDATE_INT);
        $compra = filter_var($_POST['precio_compra'] ?? null, FILTER_VALIDATE_FLOAT);
        $venta = filter_var($_POST['precio_venta'] ?? null, FILTER_VALIDATE_FLOAT);
        $dias = filter_var($_POST['dias_entrega'] ?? null, FILTER_VALIDATE_INT);

        if ($codigo === '' || $nombre === '' || $categoria <= 0 || $stock === false || $seguridad === false ||
            $compra === false || $venta === false || $dias === false || $stock < 0 || $seguridad < 0 ||
            $compra < 0 || $venta < 0 || $dias < 0) {
            throw new Exception('Completa correctamente todos los campos obligatorios. Los valores numéricos no pueden ser negativos.');
        }
        if ($modelo->codigoExiste($codigo, $accion === 'actualizar' ? $id : null)) {
            throw new Exception('Ese código de producto ya está registrado.');
        }

        $datos = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'categoria_id' => $categoria,
            'proveedor_id' => $proveedor,
            'stock_actual' => $stock,
            'stock_seguridad' => $seguridad,
            'precio_compra' => $compra,
            'precio_venta' => $venta,
            'dias_entrega' => $dias
        ];

        if ($accion === 'guardar') {
            $modelo->crear($datos);
            header('Location: ../public/productos.php?mensaje=registrado');
        } else {
            if ($id <= 0) throw new Exception('Producto no válido.');
            $modelo->actualizar($id, $datos);
            header('Location: ../public/productos.php?mensaje=actualizado');
        }
        exit();
    }

    if ($accion === 'desactivar' || $accion === 'reactivar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Producto no válido.');
        if ($accion === 'desactivar') {
            $modelo->desactivar($id);
            header('Location: ../public/productos.php?mensaje=desactivado');
        } else {
            $modelo->reactivar($id);
            header('Location: ../public/productos.php?mensaje=reactivado');
        }
        exit();
    }
    throw new Exception('Acción no válida.');
} catch (Throwable $e) {
    $msg = ($e instanceof PDOException) ? 'No se pudo completar la operación. Verifica los datos y la base de datos.' : $e->getMessage();
    $destino = '../public/productos.php?error=' . urlencode($msg);
    if (!empty($_POST['id'])) $destino .= '&editar=' . (int)$_POST['id'];
    header('Location: ' . $destino);
    exit();
}
