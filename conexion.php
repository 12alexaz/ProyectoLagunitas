<?php
// Configuración de conexión a la Base de Datos
// Los datos de conexión viven en config.php (no se sube a GitHub).
// Si no existe, se usan los valores por defecto de XAMPP local.
$cfg = is_file(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
$host    = $cfg['host']    ?? 'localhost';
$db      = $cfg['db']      ?? 'agensia_lagunitas_prueva';
$user    = $cfg['user']    ?? 'root';
$pass    = $cfg['pass']    ?? '';
$charset = $cfg['charset'] ?? 'utf8mb4';

// En producción no se muestran errores de PHP al público.
if (empty($cfg['debug'])) {
    ini_set('display_errors', '0');
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

date_default_timezone_set('America/La_Paz');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);
    $pdo->exec("SET time_zone = '-04:00'"); // hora de Bolivia
} catch (PDOException $e) {
    // El detalle técnico va al log del servidor; el usuario solo ve un aviso claro.
    error_log('Lagunitas - error de conexión: ' . $e->getMessage());
    http_response_code(500);
    exit('<!DOCTYPE html><meta charset="UTF-8"><title>Sin conexión</title>'
       . '<div style="font-family:sans-serif;max-width:480px;margin:80px auto;text-align:center">'
       . '<h2>No se pudo conectar con la base de datos</h2>'
       . '<p>Verifica que MySQL/MariaDB esté encendido (XAMPP) y que los datos de <code>config.php</code> sean correctos.</p></div>');
}
