
<?php
require_once __DIR__ . '/../controlador/auth.php';
requerirLogin();
$admin = esAdmin();
$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
$aviso = $_GET['error'] ?? '';

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../modelo/Inventario.php';
require_once __DIR__ . '/../modelo/Venta.php';
require_once __DIR__ . '/../modelo/Compra.php';
$resInv = (new Inventario($pdo))->resumen();
$ventasHoy = (new Venta($pdo))->resumenHoy($admin ? 0 : (int)$_SESSION['usuario_id']);
$comprasMes = $admin ? (new Compra($pdo))->totalMes() : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - Agencia Lagunitas</title>

    <link rel="stylesheet" href="css/estilo.css?v=20261116c">
</head>

<body>

    <?php include __DIR__ . '/../vista/sidebar.php';
 ?>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">

        <!-- ENCABEZADO -->
        <header class="topbar">

            <div>
                <h1><?= $admin ? 'Panel de Administración' : 'Panel de Ventas' ?></h1>
                <p>Bienvenido, <?= $nombreUsuario ?> (<?= $admin ? 'administrador' : 'vendedor' ?>)</p>
            </div>

            <a href="../controlador/logout.php" class="btn-logout">
                ⇥ Cerrar sesión
            </a>

        </header>

        <?php if ($aviso): ?>
        <div style="background:#ffeded;color:#9c2222;padding:12px 15px;border-radius:7px;margin:0 0 16px">
            <?= htmlspecialchars($aviso) ?>
        </div>
        <?php endif;
 ?>

        <!-- BIENVENIDA -->
        <section class="welcome-banner">

            <div class="welcome-text">
                <span class="welcome-label">SISTEMA DE GESTIÓN</span>

                <h2>¡Bienvenido a Agencia Lagunitas!</h2>

                <p>
                    Administra tus productos, controla tu inventario
                    y organiza las operaciones de tu negocio desde un solo lugar.
                </p>
            </div>

            <div class="welcome-icon">
                🏪
            </div>

        </section>

        <!-- TÍTULO DE RESUMEN -->
        <section class="section-heading">
            <div>
                <h2>Resumen general</h2>
                <p>Acceso rápido a las principales funciones del sistema.</p>
            </div>
        </section>

        <!-- TARJETAS DE RESUMEN -->
        <section class="stats-grid">

            <a href="inventario.php" class="stat-card stat-blue" style="text-decoration:none;color:inherit">
                <div class="stat-icon">📦</div>
                <div>
                    <p>Productos activos</p>
                    <h3><?= $resInv['total'] ?></h3>
                    <span><?= number_format($resInv['unidades']) ?> unidades en stock</span>
                </div>
            </a>

            <a href="inventario.php?estado=alerta" class="stat-card stat-green" style="text-decoration:none;color:inherit">
                <div class="stat-icon">⚠️</div>
                <div>
                    <p>Requieren atención</p>
                    <h3><?= $resInv['bajos'] + $resInv['agotados'] ?></h3>
                    <span><?= $resInv['bajos'] ?> con stock bajo · <?= $resInv['agotados'] ?> agotados</span>
                </div>
            </a>

            <a href="ventas.php" class="stat-card stat-orange" style="text-decoration:none;color:inherit">
                <div class="stat-icon">🛒</div>
                <div>
                    <p><?= $admin ? 'Ventas de hoy' : 'Mis ventas de hoy' ?></p>
                    <h3>Bs <?= number_format((float)$ventasHoy['total'], 2) ?></h3>
                    <span><?= (int)$ventasHoy['cantidad'] ?> venta(s) registradas</span>
                </div>
            </a>

            <?php if ($admin): ?>
            <a href="compras.php" class="stat-card stat-purple" style="text-decoration:none;color:inherit">
                <div class="stat-icon">🚚</div>
                <div>
                    <p>Compras del mes</p>
                    <h3>Bs <?= number_format($comprasMes, 2) ?></h3>
                    <span>Registro de adquisiciones</span>
                </div>
            </a>
            <?php else: ?>
            <a href="asistente.php" class="stat-card stat-purple" style="text-decoration:none;color:inherit">
                <div class="stat-icon">🤖</div>
                <div>
                    <p>Asistente</p>
                    <h3>Reposición</h3>
                    <span>Qué productos conviene pedir</span>
                </div>
            </a>
            <?php endif;
 ?>

        </section>

        <!-- MÓDULOS DEL SISTEMA -->
        <section class="section-heading modules-heading">
            <div>
                <h2>Módulos del sistema</h2>
                <p>Selecciona el módulo que deseas administrar.</p>
            </div>
        </section>

        <section class="modules-grid">

            <a href="ventas.php" class="module-card">
                <div class="module-icon icon-orange">🛒</div>
                <h3>Ventas</h3>
                <p>Registra las ventas por unidad o por paquete.</p>
                <span class="module-link">Registrar →</span>
            </a>

            <?php if ($admin): ?>
            <a href="productos.php" class="module-card">
                <div class="module-icon icon-blue">📦</div>
                <h3>Productos</h3>
                <p>Registra, modifica y consulta los productos y sus precios.</p>
                <span class="module-link">Administrar →</span>
            </a>

            <a href="presentaciones.php" class="module-card">
                <div class="module-icon icon-cyan">📦</div>
                <h3>Presentaciones</h3>
                <p>Define las formas de venta: unidad, paquete o caja.</p>
                <span class="module-link">Administrar →</span>
            </a>

            <a href="categorias.php" class="module-card">
                <div class="module-icon icon-green">🏷️</div>
                <h3>Categorías</h3>
                <p>Organiza los productos por tipo de bebida.</p>
                <span class="module-link">Administrar →</span>
            </a>

            <a href="proveedores.php" class="module-card">
                <div class="module-icon icon-red">🏭</div>
                <h3>Proveedores</h3>
                <p>Registra y administra los proveedores de productos.</p>
                <span class="module-link">Administrar →</span>
            </a>

            <a href="compras.php" class="module-card">
                <div class="module-icon icon-purple">🚚</div>
                <h3>Compras</h3>
                <p>Registra las compras y entradas de productos al negocio.</p>
                <span class="module-link">Gestionar →</span>
            </a>
            <?php endif;
 ?>

            <a href="inventario.php" class="module-card">
                <div class="module-icon icon-green">📊</div>
                <h3>Inventario</h3>
                <p>Controla las existencias y los niveles de stock de productos.</p>
                <span class="module-link">Consultar →</span>
            </a>

            <a href="movimientos.php" class="module-card">
                <div class="module-icon icon-cyan">📋</div>
                <h3>Movimientos</h3>
                <p>Consulta las entradas y salidas de productos.</p>
                <span class="module-link">Ver movimientos →</span>
            </a>

            <a href="asistente.php" class="module-card">
                <div class="module-icon icon-dark">🤖</div>
                <h3>Asistente Lagunitas</h3>
                <p>Apoyo para identificar productos que necesitan reposición.</p>
                <span class="module-link">Consultar asistente →</span>
            </a>

        </section>

        <!-- PIE DE PÁGINA -->
        <footer class="footer">
            <p>© 2026 Agencia Lagunitas | Sistema de gestión de inventario y ventas</p>
        </footer>

    </main>

</body>
</html>
