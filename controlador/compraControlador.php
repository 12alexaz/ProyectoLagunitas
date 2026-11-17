<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelo/Compra.php';
requerirAdmin('../public/index.php', '../public/dashboard.php');

csrfExigir('../public/compras.php');

$modelo = new Compra($pdo);
try {
    if (($_POST['accion'] ?? '') !== 'registrar') throw new Exception('Acción no válida.');
    $items = [];
    foreach ((array)($_POST['items'] ?? []) as $it) {
        if (!is_array($it)) continue;
        $items[] = [
            'producto_id' => $it['producto_id'] ?? 0,
            'unidades_por_paquete' => $it['unidades_por_paquete'] ?? 0,
            'cantidad' => $it['cantidad'] ?? 0,
            'costo_unitario' => $it['costo_unitario'] ?? -1,
            'fecha_vencimiento' => $it['fecha_vencimiento'] ?? '',
        ];
    }
    $id = $modelo->registrar((int)$_SESSION['usuario_id'], (int)($_POST['proveedor_id'] ?? 0), $items, isset($_POST['actualizar_precio']));
    header('Location: ../public/compras.php?mensaje=registrada&ver=' . $id);
} catch (Throwable $e) {
    $msg = ($e instanceof PDOException) ? 'No se pudo completar la operación. Verifica la base de datos.' : $e->getMessage();
    header('Location: ../public/compras.php?error=' . urlencode($msg));
}
exit();
