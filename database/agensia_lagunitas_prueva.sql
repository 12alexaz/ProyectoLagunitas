-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-11-2026 a las 00:11:11
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `agensia_lagunitas_prueva`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'Cerveza', 'Cervezas nacionales e importadas', 1),
(2, 'Rones', 'Diferentes mascas de ron', 1),
(3, 'Vodkas', 'Diferentes marcas de Vodkas', 1),
(4, 'Bebidas sin alcohol', 'Gaseosas, aguas y otras bebidas', 1),
(5, 'Vinos', 'Vinos nacionales e importados', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `compras`
--

INSERT INTO `compras` (`id`, `proveedor_id`, `usuario_id`, `fecha`, `total`) VALUES
(1, 1, 1, '2026-10-28 00:58:14', 600.00),
(2, 1, 1, '2026-11-12 21:14:50', 1880.20),
(3, 3, 1, '2026-11-12 21:16:46', 151.20),
(4, 2, 1, '2026-11-12 21:18:36', 272.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_compras`
--

CREATE TABLE `detalle_compras` (
  `id` int(11) NOT NULL,
  `compra_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `unidades_por_paquete` int(11) NOT NULL DEFAULT 1,
  `cantidad` int(11) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_compras`
--

INSERT INTO `detalle_compras` (`id`, `compra_id`, `producto_id`, `unidades_por_paquete`, `cantidad`, `costo_unitario`, `subtotal`) VALUES
(2, 1, 1, 12, 5, 121.51, 607.55),
(3, 2, 9, 12, 20, 94.01, 1880.20),
(4, 3, 18, 6, 2, 75.60, 151.20),
(5, 4, 27, 6, 1, 272.00, 272.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `presentacion_id` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `unidades` int(11) NOT NULL DEFAULT 0,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id`, `venta_id`, `producto_id`, `presentacion_id`, `cantidad`, `unidades`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 1, 1, 10, 120, 130.00, 1300.00),
(2, 2, 27, 47, 2, 12, 285.00, 570.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta_lotes`
--

CREATE TABLE `detalle_venta_lotes` (
  `id` int(11) NOT NULL,
  `detalle_venta_id` int(11) NOT NULL,
  `lote_id` int(11) DEFAULT NULL,
  `unidades` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_venta_lotes`
--

INSERT INTO `detalle_venta_lotes` (`id`, `detalle_venta_id`, `lote_id`, `unidades`) VALUES
(1, 2, 3, 6),
(2, 2, NULL, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lotes_productos`
--

CREATE TABLE `lotes_productos` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `compra_id` int(11) DEFAULT NULL,
  `detalle_compra_id` int(11) DEFAULT NULL,
  `cantidad_inicial` int(11) NOT NULL,
  `cantidad_actual` int(11) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `lotes_productos`
--

INSERT INTO `lotes_productos` (`id`, `producto_id`, `compra_id`, `detalle_compra_id`, `cantidad_inicial`, `cantidad_actual`, `fecha_ingreso`, `fecha_vencimiento`, `creado_en`) VALUES
(1, 9, 2, 3, 240, 240, '2026-11-12', '2026-12-31', '2026-11-12 21:14:50'),
(2, 18, 3, 4, 12, 12, '2026-11-12', NULL, '2026-11-12 21:16:46'),
(3, 27, 4, 5, 6, 0, '2026-11-12', '2026-11-19', '2026-11-12 21:18:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('entrada','salida','ajuste') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` varchar(200) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `usuario_id`, `tipo`, `cantidad`, `motivo`, `fecha`) VALUES
(1, 1, 1, 'salida', 5, 'Ninguno', '2026-10-28 01:11:32'),
(2, 1, 2, 'salida', 120, 'Venta #1', '2026-11-12 20:01:27'),
(3, 9, 1, 'entrada', 240, 'Compra #2', '2026-11-12 21:14:50'),
(4, 18, 1, 'entrada', 12, 'Compra #3', '2026-11-12 21:16:46'),
(5, 27, 1, 'entrada', 6, 'Compra #4', '2026-11-12 21:18:36'),
(6, 27, 1, 'salida', 12, 'Venta #2', '2026-11-14 01:50:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `presentaciones_producto`
--

CREATE TABLE `presentaciones_producto` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `cantidad_unidades` int(11) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `presentaciones_producto`
--

INSERT INTO `presentaciones_producto` (`id`, `producto_id`, `nombre`, `cantidad_unidades`, `precio_venta`, `activo`) VALUES
(1, 1, 'Paquete x12', 12, 130.00, 1),
(2, 1, 'unidad', 1, 12.00, 1),
(3, 6, 'Caja x12', 12, 180.00, 1),
(4, 6, 'unidad', 1, 17.00, 1),
(6, 7, 'Paquete x12', 12, 110.00, 1),
(7, 7, 'unidad', 1, 10.00, 1),
(8, 8, 'Paquete x12', 12, 89.00, 1),
(9, 8, 'unidad', 1, 8.50, 1),
(12, 9, 'Paquete x12', 12, 100.00, 1),
(13, 9, 'unidad', 1, 9.00, 1),
(14, 10, 'Paquete x12', 12, 155.00, 1),
(15, 10, 'unidad', 1, 14.00, 1),
(16, 11, 'Caja x24', 24, 275.00, 1),
(17, 11, 'unidad', 1, 15.00, 1),
(18, 12, 'Paquete  x24', 24, 255.00, 1),
(19, 12, 'unidad', 1, 12.00, 1),
(20, 13, 'Paquete x6', 6, 80.00, 1),
(21, 13, 'unidad', 1, 22.00, 1),
(22, 14, 'Paquete x6', 6, 75.00, 1),
(23, 14, 'unidad', 1, 13.00, 1),
(24, 15, 'Paquete x12', 12, 80.00, 1),
(25, 15, 'unidad', 1, 7.00, 1),
(26, 16, 'Paquete x12', 12, 80.00, 1),
(27, 16, 'unidad', 1, 7.00, 1),
(28, 17, 'Paquete x6', 6, 127.00, 1),
(29, 17, 'unidad', 1, 22.00, 1),
(31, 18, 'Paquete x6', 6, 80.00, 1),
(32, 18, 'unidad', 1, 14.00, 1),
(33, 19, 'Paquete x6', 6, 127.00, 1),
(34, 19, 'unidad', 1, 22.00, 1),
(35, 21, 'Paquete x6', 6, 127.00, 1),
(36, 21, 'unidad', 1, 22.00, 1),
(37, 22, 'Paquete x12', 12, 150.00, 1),
(38, 22, 'unidad', 1, 13.00, 1),
(39, 23, 'Paquete x12', 12, 120.00, 1),
(40, 23, 'unidad', 1, 11.00, 1),
(41, 24, 'Paquete x12', 12, 85.00, 1),
(42, 24, 'unidad', 1, 8.00, 1),
(43, 25, 'Paquete x12', 12, 65.00, 1),
(44, 25, 'unidad', 1, 6.00, 1),
(45, 26, 'Paquete x6', 6, 495.00, 1),
(46, 26, 'unidad', 1, 90.00, 1),
(47, 27, 'Paquete x6', 6, 285.00, 1),
(48, 27, 'unidad', 1, 55.00, 1),
(49, 29, 'Paquete x4', 4, 230.00, 1),
(50, 29, 'Unidad', 1, 60.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `stock_actual` int(11) NOT NULL DEFAULT 0,
  `stock_seguridad` int(11) NOT NULL DEFAULT 0,
  `precio_compra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dias_entrega_proveedor` int(11) NOT NULL DEFAULT 2,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `nombre`, `categoria_id`, `proveedor_id`, `stock_actual`, `stock_seguridad`, `precio_compra`, `precio_venta`, `dias_entrega_proveedor`, `activo`, `fecha_creacion`) VALUES
(1, 'crvp440', 'Cerveza Paceña 440 ml (Lata grande)', 1, 1, 480, 12, 121.51, 130.00, 4, 1, '2026-10-23 17:48:07'),
(6, 'cr2', 'Cerveza Paceña Retornable 710 ml (Macanuda)', 1, 1, 840, 56, 171.50, 180.00, 4, 1, '2026-10-23 12:01:44'),
(7, 'cvg1', 'Cerveza Golden 440 ml (Lata grande)', 1, 1, 360, 56, 102.51, 110.00, 4, 1, '2026-10-23 12:10:56'),
(8, 'crt1', 'Cerveza Chicha Taquiña 440 ml (Lata grande)', 1, 1, 360, 56, 89.00, 95.00, 4, 1, '2026-10-27 12:13:05'),
(9, 'crvb1', 'Cerveza Baltica 440 ml (Lata grande)', 1, 1, 480, 48, 94.01, 100.00, 4, 1, '2026-10-27 12:15:48'),
(10, 'crvh1', 'Cerveza Huari Tradicional 440 ml (Lata grande)', 1, 1, 240, 48, 140.00, 155.00, 4, 1, '2026-10-27 12:19:10'),
(11, 'crvc1', 'Cerveza Corona Botellin 330 ml', 1, 1, 240, 48, 264.00, 275.00, 4, 1, '2026-10-27 12:22:16'),
(12, 'crvq1', 'Cerveza Quilmes 473 ml (Lata clasica)', 1, 1, 240, 48, 246.00, 255.00, 4, 1, '2026-10-27 12:47:13'),
(13, 'gsp1', 'Gaseosa Pepsi 3 l', 4, 1, 120, 30, 75.00, 80.00, 4, 1, '2026-10-27 12:49:38'),
(14, 'gso1', 'Gaseosa Oriental 3 l', 4, 1, 90, 30, 70.00, 75.00, 4, 1, '2026-10-27 12:51:26'),
(15, 'gsp2', 'Gaseosa Pepsi 1 l', 4, 1, 120, 48, 72.00, 80.00, 4, 1, '2026-10-27 12:52:51'),
(16, 'gss1', 'Gaseosa Seven Up 1 l', 4, 1, 240, 48, 72.00, 80.00, 4, 1, '2026-10-27 12:54:41'),
(17, 'gsc1', 'Coca-cola Original 3 l', 4, 3, 240, 48, 121.20, 127.00, 2, 1, '2026-10-26 12:56:20'),
(18, 'gsc2', 'Coca-cola Original 2 l', 4, 3, 132, 48, 75.60, 80.00, 2, 1, '2026-10-27 14:55:05'),
(19, 'gsf1', 'Fanta 3l', 4, 3, 240, 48, 121.20, 127.00, 2, 1, '2026-10-27 14:57:17'),
(21, 'gssp1', 'Sprite Original 3 l', 4, 3, 240, 48, 121.20, 127.00, 2, 1, '2026-10-27 14:59:02'),
(22, 'cvb1', 'Cerveza Burguesa 473 ml', 1, 2, 360, 48, 143.00, 150.00, 3, 1, '2026-10-27 15:01:47'),
(23, 'crvz1', 'Cerveza Zero Grau 473 ml', 1, 2, 240, 48, 113.00, 120.00, 3, 1, '2026-10-27 15:43:18'),
(24, 'crvz2', 'Cerveza Zero Grau 269 ml', 1, 2, 480, 48, 77.00, 85.00, 3, 1, '2026-10-27 15:45:17'),
(25, 'gsc3', 'Conti cola 350 ml', 4, 2, 240, 48, 58.00, 65.00, 3, 1, '2026-10-27 15:46:41'),
(26, 'vk1', 'Vodka Polara 970 ml', 3, 2, 60, 24, 480.00, 495.00, 3, 1, '2026-10-27 16:02:16'),
(27, 'vkb1', 'Barkov Frutos Rojos 1 lt', 3, 2, 54, 24, 272.00, 285.00, 3, 1, '2026-10-27 16:06:18'),
(28, 'prev', 'prueva', 4, 3, 11, 11, 123.00, 123.00, 2, 0, '2026-11-12 18:41:29'),
(29, 'vnt', 'Toro Viejo', 5, NULL, 15, 8, 210.00, 230.00, 0, 1, '2026-11-16 11:52:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `telefono`, `direccion`, `activo`) VALUES
(1, 'Cerveza Boliviana Nacional CBN', '800171007', 'Cochabamba: Cerveceria Taquiña (Av. Centenario Final s/n)', 1),
(2, 'NUR S.R.L', '+591 71010099', 'Santa Cruz de la Sierra ( Calle 1, Zona Sur )', 1),
(3, 'Embotelladoras Bolivianas Unidas S.A (EMBOL S.A)', '+591 79000019', 'Av. Blanco Galindo Km 10 (Colcapirhua)', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `rol` enum('admin','vendedor') NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `clave`, `rol`, `activo`, `fecha_creacion`) VALUES
(1, 'Alexander Andrade Zarate', 'alex12345', '$2y$10$Xv86v.AMjOxkjk9xyUjl1O2mp7c1BIZgOX/3o3cjT5npPRy3IjFhi', 'admin', 1, '2026-10-28 00:56:24'),
(2, 'juan', 'jaun12', '$2y$10$VOVJsOYajKTj9TrPhuDXMuvBH2uzJZn9CUBJLUviEw/fmpLq/p7lS', 'vendedor', 1, '2026-10-30 23:22:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('completada','anulada') NOT NULL DEFAULT 'completada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `usuario_id`, `fecha`, `total`, `estado`) VALUES
(1, 2, '2026-11-12 20:01:27', 1300.00, 'completada'),
(2, 1, '2026-11-14 01:50:21', 570.00, 'completada');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proveedor_id` (`proveedor_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `compra_id` (`compra_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `presentacion_id` (`presentacion_id`);

--
-- Indices de la tabla `detalle_venta_lotes`
--
ALTER TABLE `detalle_venta_lotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dvl_detalle` (`detalle_venta_id`),
  ADD KEY `idx_dvl_lote` (`lote_id`);

--
-- Indices de la tabla `lotes_productos`
--
ALTER TABLE `lotes_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lotes_producto_vencimiento` (`producto_id`,`fecha_vencimiento`),
  ADD KEY `idx_lotes_compra` (`compra_id`),
  ADD KEY `idx_lotes_detalle_compra` (`detalle_compra_id`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `presentaciones_producto`
--
ALTER TABLE `presentaciones_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detalle_venta_lotes`
--
ALTER TABLE `detalle_venta_lotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `lotes_productos`
--
ALTER TABLE `lotes_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `presentaciones_producto`
--
ALTER TABLE `presentaciones_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`),
  ADD CONSTRAINT `compras_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  ADD CONSTRAINT `detalle_compras_ibfk_1` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`),
  ADD CONSTRAINT `detalle_compras_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `detalle_ventas_presentacion_fk` FOREIGN KEY (`presentacion_id`) REFERENCES `presentaciones_producto` (`id`);

--
-- Filtros para la tabla `detalle_venta_lotes`
--
ALTER TABLE `detalle_venta_lotes`
  ADD CONSTRAINT `dvl_detalle_fk` FOREIGN KEY (`detalle_venta_id`) REFERENCES `detalle_ventas` (`id`),
  ADD CONSTRAINT `dvl_lote_fk` FOREIGN KEY (`lote_id`) REFERENCES `lotes_productos` (`id`);

--
-- Filtros para la tabla `lotes_productos`
--
ALTER TABLE `lotes_productos`
  ADD CONSTRAINT `lotes_compra_fk` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`),
  ADD CONSTRAINT `lotes_detalle_compra_fk` FOREIGN KEY (`detalle_compra_id`) REFERENCES `detalle_compras` (`id`),
  ADD CONSTRAINT `lotes_producto_fk` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `presentaciones_producto`
--
ALTER TABLE `presentaciones_producto`
  ADD CONSTRAINT `presentaciones_producto_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
--
-- Estructura de tabla para la tabla `intentos_login` (límite de intentos de inicio de sesión)
--

CREATE TABLE IF NOT EXISTS `intentos_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_intentos` (`usuario`,`ip`,`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
