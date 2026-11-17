<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelo/Venta.php';
requerirLogin('../public/index.php');

csrfExigir('../public/ventas.php');

$modelo = new Venta($pdo);
$accion = $_POST['accion'] ?? '';

try {
    if ($accion === 'registrar') {
        $items = [];
        foreach ((array)($_POST['items'] ?? []) as $it) {
            if (is_array($it)) {
                $items[] = ['presentacion_id' => $it['presentacion_id'] ?? 0, 'cantidad' => $it['cantidad'] ?? 0];
            }
        }
        $id = $modelo->registrar((int)$_SESSION['usuario_id'], $items);
        header('Location: ../public/ventas.php?mensaje=registrada&ver=' . $id);
        exit();
    }

    if ($accion === 'anular') {
        if (!esAdmin()) throw new Exception('Solo un administrador puede anular ventas.');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Venta no válida.');
        $modelo->anular($id, (int)$_SESSION['usuario_id']);
        header('Location: ../public/ventas.php?mensaje=anulada&ver=' . $id);
        exit();
    }

    throw new Exception('Acción no válida.');
} catch (Throwable $e) {
    // Solo se muestran los mensajes propios; los errores de BD se ocultan
    $msg = ($e instanceof PDOException) ? 'No se pudo completar la operación. Verifica la base de datos.' : $e->getMessage();
    header('Location: ../public/ventas.php?error=' . urlencode($msg));
    exit();
}
