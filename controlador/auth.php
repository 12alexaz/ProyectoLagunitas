<?php
// Utilidades de sesión y roles (admin / vendedor)
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function estaLogueado(): bool {
    return isset($_SESSION['usuario_id']);
}

function esAdmin(): bool {
    return ($_SESSION['rol'] ?? '') === 'admin';
}

// Exige sesión iniciada; si no, redirige al login.
function requerirLogin(string $login = 'index.php'): void {
    if (!estaLogueado()) {
        header('Location: ' . $login);
        exit();
    }
}

// Exige rol administrador; un vendedor vuelve al dashboard con aviso.
function requerirAdmin(string $login = 'index.php', string $dashboard = 'dashboard.php'): void {
    requerirLogin($login);
    if (!esAdmin()) {
        header('Location: ' . $dashboard . '?error=' . urlencode('No tienes permiso para acceder a ese módulo.'));
        exit();
    }
}

// ---------- Protección CSRF ----------
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// Campo oculto para los formularios POST.
function csrfCampo(): string {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

// Exige POST con token válido; si no, vuelve a la página indicada con un aviso.
function csrfExigir(string $volver): void {
    $ok = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
    if (!$ok) {
        header('Location: ' . $volver . '?error=' . urlencode('Solicitud no válida o sesión expirada. Intenta de nuevo.'));
        exit();
    }
}
