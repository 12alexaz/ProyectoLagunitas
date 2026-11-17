<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelo/Inventario.php';
requerirAdmin('../public/index.php', '../public/dashboard.php');

csrfExigir('../public/inventario.php');
try {
    if (($_POST['accion'] ?? '') !== 'ajustar') throw new Exception('Acción no válida.');
    $nuevo = filter_var($_POST['nuevo_stock'] ?? null, FILTER_VALIDATE_INT);
    if ($nuevo === false) throw new Exception('Indica un stock válido (número entero).');
    (new Inventario($pdo))->ajustar((int)($_POST['producto_id'] ?? 0), $nuevo, (string)($_POST['motivo'] ?? ''), (int)$_SESSION['usuario_id']);
    header('Location: ../public/inventario.php?mensaje=ajustado');
} catch (Throwable $e) {
    $msg = ($e instanceof PDOException) ? 'No se pudo completar la operación. Verifica la base de datos.' : $e->getMessage();
    header('Location: ../public/inventario.php?error=' . urlencode($msg));
}
exit();
