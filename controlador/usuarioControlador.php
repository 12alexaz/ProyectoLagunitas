<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../conexion.php';

const MAX_INTENTOS = 5;      // intentos fallidos permitidos
const VENTANA_MIN  = 10;     // en esta cantidad de minutos

function volverLogin(string $mensaje): void {
    // El mensaje se muestra en un cuadro de diálogo propio en la página de login.
    $_SESSION['login_aviso'] = $mensaje;
    header('Location: ../public/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit();
}

$usuario = trim($_POST['usuario'] ?? '');
$clave   = trim($_POST['clave'] ?? '');
$rol     = trim($_POST['rol'] ?? '');
$ip      = $_SERVER['REMOTE_ADDR'] ?? '';

if ($usuario === '' || $clave === '' || !in_array($rol, ['admin', 'vendedor'], true)) {
    volverLogin('Completa todos los campos.');
}

try {
    // Tabla de intentos (se crea sola la primera vez)
    $pdo->exec("CREATE TABLE IF NOT EXISTS intentos_login (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario VARCHAR(50) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_intentos (usuario, ip, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->prepare("DELETE FROM intentos_login WHERE fecha < (NOW() - INTERVAL 1 DAY)")->execute();
    $cont = $pdo->prepare("SELECT COUNT(*) FROM intentos_login WHERE usuario = ? AND ip = ? AND fecha > (NOW() - INTERVAL " . VENTANA_MIN . " MINUTE)");
    $cont->execute([$usuario, $ip]);
    if ((int)$cont->fetchColumn() >= MAX_INTENTOS) {
        volverLogin('Demasiados intentos fallidos. Espera ' . VENTANA_MIN . ' minutos e inténtalo de nuevo.');
    }

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND rol = :rol AND activo = 1");
    $stmt->execute(['usuario' => $usuario, 'rol' => $rol]);
    $user = $stmt->fetch();

    if ($user && password_verify($clave, $user['clave'])) {
        $pdo->prepare("DELETE FROM intentos_login WHERE usuario = ? AND ip = ?")->execute([$usuario, $ip]);
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        header('Location: ../public/dashboard.php');
        exit();
    }

    $pdo->prepare("INSERT INTO intentos_login (usuario, ip) VALUES (?, ?)")->execute([$usuario, $ip]);
    // Mismo mensaje para usuario inexistente o clave errónea (no revela qué usuarios existen)
    volverLogin('Usuario, contraseña o tipo de usuario incorrectos.');
} catch (PDOException $e) {
    error_log('Lagunitas - login: ' . $e->getMessage());
    volverLogin('Error en el servidor. Inténtalo más tarde.');
}
