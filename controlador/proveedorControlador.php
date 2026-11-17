<?php
require_once __DIR__ . '/auth.php';
requerirAdmin('../public/index.php', '../public/dashboard.php');
require_once '../conexion.php';
require_once '../modelo/Proveedor.php';
$modelo = new Proveedor($pdo);
function volverProveedor(string $parametro, string $valor): void {
    header('Location: ../public/proveedores.php?' . $parametro . '=' . urlencode($valor));
    exit();
}
csrfExigir('../public/proveedores.php');
$accion = $_POST['accion'] ?? '';
try {
    if ($accion === 'guardar' || $accion === 'actualizar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        if ($nombre === '') volverProveedor('error', 'El nombre del proveedor es obligatorio.');
        if ($accion === 'guardar') {
            $modelo->guardar($nombre, $telefono, $direccion);
            volverProveedor('mensaje', 'registrado');
        }
        if ($id < 1) volverProveedor('error', 'Identificador de proveedor inválido.');
        $modelo->actualizar($id, $nombre, $telefono, $direccion);
        volverProveedor('mensaje', 'actualizado');
    }
    if ($accion === 'desactivar' || $accion === 'reactivar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) volverProveedor('error', 'Identificador de proveedor inválido.');
        $modelo->cambiarEstado($id, $accion === 'reactivar' ? 1 : 0);
        volverProveedor('mensaje', $accion === 'reactivar' ? 'reactivado' : 'desactivado');
    }
    volverProveedor('error', 'Acción no válida.');
} catch (PDOException $e) {
    volverProveedor('error', 'No se pudo completar la operación. Verifica los datos y la base de datos.');
}
