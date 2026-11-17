<?php
// Barra lateral compartida. Requiere controlador/auth.php ya cargado.
$__admin = esAdmin();
$__actual = basename($_SERVER['SCRIPT_NAME'] ?? '');
$__nombre = htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
$__item = function (string $archivo, string $icono, string $texto, bool $soloAdmin = false) use ($__actual, $__admin) {
    if ($soloAdmin && !$__admin) return;
    $activo = $__actual === $archivo ? ' active' : '';
    echo '<a href="' . $archivo . '" class="menu-item' . $activo . '"><span class="menu-icon">' . $icono . '</span>' . $texto . '</a>' . "\n";
};
?>
<aside class="sidebar">
    <div class="logo">
        <div class="logo-icon">L</div>
        <div>
            <h2>LAGUNITAS</h2>
            <span>Agencia de bebidas</span>
        </div>
    </div>

    <div class="menu-title">MENÚ PRINCIPAL</div>

    <nav class="menu">
        <?php
        $__item('dashboard.php', '▦', 'Dashboard');
        $__item('ventas.php', '🛒', 'Ventas');
        $__item('productos.php', '📦', 'Productos', true);
        $__item('presentaciones.php', '🧾', 'Presentaciones', true);
        $__item('categorias.php', '🏷️', 'Categorías', true);
        $__item('proveedores.php', '🏭', 'Proveedores', true);
        $__item('compras.php', '🚚', 'Compras', true);
        $__item('inventario.php', '📊', 'Inventario');
        $__item('movimientos.php', '📋', 'Movimientos');
        $__item('asistente.php', '🤖', 'Asistente Lagunitas');
        ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <strong><?= $__nombre ?></strong>
            <small><?= $__admin ? 'Administrador' : 'Vendedor' ?></small>
        </div>
        <a href="../controlador/logout.php" class="sidebar-logout">⇥ Cerrar sesión</a>
    </div>
</aside>
