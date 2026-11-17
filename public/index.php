<?php
require_once __DIR__ . '/../controlador/auth.php';
$avisoLogin = $_SESSION['login_aviso'] ?? '';
unset($_SESSION['login_aviso']);
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agencia Lagunitas | Iniciar sesión</title>

    <link rel="stylesheet" href="css/estilo.css?v=20261116c">
</head>

<body class="login-page">

    <main class="login-layout">

        <!-- PANEL IZQUIERDO -->
        <section class="login-presentation">

            <div class="presentation-logo">
                <div class="presentation-logo-icon">L</div>

                <div>
                    <h2>AGENCIA LAGUNITAS</h2>
                    <span>Gestión inteligente de bebidas</span>
                </div>
            </div>

            <div class="presentation-content">

                <span class="presentation-tag">
                    SISTEMA DE GESTIÓN EMPRESARIAL
                </span>

                <h1>
                    Administra tu negocio
                    <span>de manera inteligente.</span>
                </h1>

                <p>
                    Controla tus productos, inventario, compras y ventas
                    desde una sola plataforma.
                </p>

                <div class="presentation-features">

                    <div class="feature-item">
                        <span>✓</span>
                        Control de inventario
                    </div>

                    <div class="feature-item">
                        <span>✓</span>
                        Gestión de productos
                    </div>

                    <div class="feature-item">
                        <span>✓</span>
                        Administración de ventas y compras
                    </div>

                </div>

            </div>

            <div class="presentation-footer">
                © 2026 Agencia Lagunitas
            </div>

            <div class="decoration-circle circle-one">
</div>
            <div class="decoration-circle circle-two">
</div>

        </section>

        <!-- PANEL DERECHO: FORMULARIO -->
        <section class="login-form-section">

            <div class="login-container">

                <div class="login-icon">
                    🔐
                </div>

                <span class="login-subtitle">
                    BIENVENIDO DE NUEVO
                </span>

                <h2>Iniciar sesión</h2>

                <p class="login-description">
                    Ingresa tus datos para acceder al sistema.
                </p>

                <form action="../controlador/usuarioControlador.php"
                      method="POST">

                    <div class="form-group">

                        <label for="rol">
                            Tipo de usuario
                        </label>

                        <select name="rol" id="rol" required>
                            <option value="admin">
                                Administrador
                            </option>

                            <option value="vendedor" selected>
                                Vendedor / Usuario
                            </option>
                        </select>

                    </div>

                    <div class="form-group">

                        <label for="usuario">
                            Nombre de usuario
                        </label>

                        <input
                            type="text"
                            name="usuario"
                            id="usuario"
                            placeholder="Ingresa tu usuario"
                            required
                            autocomplete="username"
                        >

                    </div>

                    <div class="form-group">

                        <label for="clave">
                            Contraseña
                        </label>

                        <input
                            type="password"
                            name="clave"
                            id="clave"
                            placeholder="Ingresa tu contraseña"
                            required
                            autocomplete="current-password"
                        >

                    </div>

                    <button type="submit" class="btn-primary">
                        Ingresar al sistema
                        <span>→</span>
                    </button>

                </form>

                <div class="login-security">
                    <span>🔒</span>
                    Acceso seguro al sistema de Agencia Lagunitas
                </div>

            </div>

        </section>

    </main>

<script src="js/dialogo.js"></script>
<?php if ($avisoLogin !== ''): ?>
<script>lagDialog.error(<?= json_encode($avisoLogin, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, 'No se pudo iniciar sesión');</script>
<?php endif; ?>
</body>
</html>
