<?php
class Venta {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo;
 }

    /**
     * Presentaciones disponibles para vender (activas, de productos activos).
     */
    public function presentacionesVendibles(): array {
        $sql = "SELECT pp.id, pp.nombre AS presentacion, pp.cantidad_unidades, pp.precio_venta,
                       p.id AS producto_id, p.codigo, p.nombre AS producto, p.stock_actual
                FROM presentaciones_producto pp
                INNER JOIN productos p ON p.id = pp.producto_id
                WHERE pp.activo = 1 AND p.activo = 1
                ORDER BY p.nombre, pp.cantidad_unidades DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Registra una venta completa en una sola transacción.
     * $items: lista de ['presentacion_id' => int, 'cantidad' => int]
     * Los precios SIEMPRE se toman de la base de datos, nunca del navegador.
     * @return int id de la venta creada
     */
    public function registrar(int $usuarioId, array $items): int {
        // Unir líneas repetidas de la misma presentación
        $lineas = [];
        foreach ($items as $it) {
            $pid = (int)($it['presentacion_id'] ?? 0);
            $cant = filter_var($it['cantidad'] ?? null, FILTER_VALIDATE_INT);
            if ($pid <= 0 || $cant === false || $cant < 1) {
                throw new Exception('Hay una línea de venta con datos no válidos.');
            }
            $lineas[$pid] = ($lineas[$pid] ?? 0) + $cant;
        }
        if (!$lineas) throw new Exception('Agrega al menos un producto a la venta.');

        $this->pdo->beginTransaction();
        try {
            // Se valida TODO (presentaciones y stock) antes de crear la venta: así una venta
            // rechazada no consume un número de venta y la numeración no se salta.
            $pre = $this->pdo->prepare(
                "SELECT pp.cantidad_unidades, p.id AS producto_id, p.nombre AS producto, p.stock_actual
                 FROM presentaciones_producto pp
                 INNER JOIN productos p ON p.id = pp.producto_id
                 WHERE pp.id = ? AND pp.activo = 1 AND p.activo = 1
                 FOR UPDATE");
            $pedido = [];  // unidades requeridas por producto
            $infoProd = [];
            foreach ($lineas as $presId => $cant) {
                $pre->execute([$presId]);
                $x = $pre->fetch();
                if (!$x) throw new Exception('Una presentación seleccionada ya no está disponible.');
                $pedido[$x['producto_id']] = ($pedido[$x['producto_id']] ?? 0) + (int)$x['cantidad_unidades'] * $cant;
                $infoProd[$x['producto_id']] = $x;
            }
            foreach ($pedido as $prodId => $requeridas) {
                $disp = (int)$infoProd[$prodId]['stock_actual'];
                if ($requeridas > $disp) {
                    throw new Exception("Stock insuficiente para {$infoProd[$prodId]['producto']}: disponible {$disp} unidades, se requieren {$requeridas}.");
                }
            }

            $this->pdo->prepare("INSERT INTO ventas (usuario_id, total, estado) VALUES (?, 0, 'completada')")
                      ->execute([$usuarioId]);
            $ventaId = (int)$this->pdo->lastInsertId();

            $selPres = $this->pdo->prepare(
                "SELECT pp.id, pp.nombre AS presentacion, pp.cantidad_unidades, pp.precio_venta,
                        p.id AS producto_id, p.nombre AS producto, p.stock_actual
                 FROM presentaciones_producto pp
                 INNER JOIN productos p ON p.id = pp.producto_id
                 WHERE pp.id = ? AND pp.activo = 1 AND p.activo = 1");
            $descuento = $this->pdo->prepare(
                "UPDATE productos SET stock_actual = stock_actual - :u
                 WHERE id = :id AND stock_actual >= :u2");
            $insDet = $this->pdo->prepare(
                "INSERT INTO detalle_ventas (venta_id, producto_id, presentacion_id, cantidad, unidades, precio_unitario, subtotal)
                 VALUES (?,?,?,?,?,?,?)");
            $insMov = $this->pdo->prepare(
                "INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, motivo)
                 VALUES (?,?,'salida',?,?)");
            $lotes = $this->pdo->prepare(
                "SELECT id, cantidad_actual FROM lotes_productos
                 WHERE producto_id = ? AND cantidad_actual > 0
                   AND (fecha_vencimiento IS NULL OR fecha_vencimiento >= CURDATE())
                 ORDER BY (fecha_vencimiento IS NULL), fecha_vencimiento, fecha_ingreso, id
                 FOR UPDATE");
            $sumLotes = $this->pdo->prepare("SELECT COALESCE(SUM(cantidad_actual),0) FROM lotes_productos WHERE producto_id = ?");
            $actualizarLote = $this->pdo->prepare("UPDATE lotes_productos SET cantidad_actual = cantidad_actual - ? WHERE id = ? AND cantidad_actual >= ?");
            $guardarAsignacion = $this->pdo->prepare("INSERT INTO detalle_venta_lotes (detalle_venta_id, lote_id, unidades) VALUES (?,?,?)");

            $total = 0.0;
            foreach ($lineas as $presId => $cant) {
                $selPres->execute([$presId]);
                $pr = $selPres->fetch();
                if (!$pr) throw new Exception('Una presentación seleccionada ya no está disponible.');

                $unidades = (int)$pr['cantidad_unidades'] * $cant;
                // Descuento atómico: solo se aplica si hay stock suficiente
                $descuento->execute(['u' => $unidades, 'id' => $pr['producto_id'], 'u2' => $unidades]);
                if ($descuento->rowCount() !== 1) {
                    $s = $this->pdo->prepare("SELECT stock_actual FROM productos WHERE id = ?");
                    $s->execute([$pr['producto_id']]);
                    throw new Exception("Stock insuficiente para {$pr['producto']}: disponible {$s->fetchColumn()} unidades, se requieren {$unidades}.");
                }

                $precio = (float)$pr['precio_venta'];
                $subtotal = round($precio * $cant, 2);
                $total += $subtotal;
                $insDet->execute([$ventaId, $pr['producto_id'], $presId, $cant, $unidades, $precio, $subtotal]);
                $detalleVentaId = (int)$this->pdo->lastInsertId();

                // FEFO: primero se descuentan los lotes con vencimiento más cercano.
                $lotes->execute([$pr['producto_id']]);
                $lotesDisponibles = $lotes->fetchAll();
                $sumLotes->execute([$pr['producto_id']]);
                $unidadesEnLotes = (int)$sumLotes->fetchColumn();
                $stockSinLote = max(0, (int)$pr['stock_actual'] - $unidadesEnLotes);
                $restante = $unidades;
                foreach ($lotesDisponibles as $lote) {
                    if ($restante <= 0) break;
                    $tomar = min($restante, (int)$lote['cantidad_actual']);
                    if ($tomar > 0) {
                        $actualizarLote->execute([$tomar, $lote['id'], $tomar]);
                        if ($actualizarLote->rowCount() !== 1) throw new Exception('No se pudo actualizar un lote del inventario. Intenta nuevamente.');
                        $guardarAsignacion->execute([$detalleVentaId, $lote['id'], $tomar]);
                        $restante -= $tomar;
                    }
                }
                if ($restante > $stockSinLote) {
                    throw new Exception("No hay suficientes unidades vigentes de {$pr['producto']}. Revisa los lotes vencidos o el inventario sin lote.");
                }
                // Las unidades de inventario antiguo, aún no asociadas a lotes, se mantienen vendibles.
                if ($restante > 0) $guardarAsignacion->execute([$detalleVentaId, null, $restante]);
                $insMov->execute([$pr['producto_id'], $usuarioId, $unidades, "Venta #{$ventaId}"]);
            }

            $this->pdo->prepare("UPDATE ventas SET total = ? WHERE id = ?")->execute([round($total, 2), $ventaId]);
            $this->pdo->commit();
            return $ventaId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Anula una venta: devuelve el stock y deja registro en movimientos.
     */
    public function anular(int $ventaId, int $usuarioId): void {
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare("SELECT id, estado FROM ventas WHERE id = ?");
            $st->execute([$ventaId]);
            $v = $st->fetch();
            if (!$v) throw new Exception('La venta no existe.');
            if ($v['estado'] === 'anulada') throw new Exception('La venta ya estaba anulada.');

            // Marca primero (condicional) para evitar doble anulación simultánea
            $up = $this->pdo->prepare("UPDATE ventas SET estado = 'anulada' WHERE id = ? AND estado = 'completada'");
            $up->execute([$ventaId]);
            if ($up->rowCount() !== 1) throw new Exception('La venta ya estaba anulada.');

            $det = $this->pdo->prepare(
                "SELECT dv.id, dv.producto_id, dv.cantidad, dv.unidades, pp.cantidad_unidades
                 FROM detalle_ventas dv
                 LEFT JOIN presentaciones_producto pp ON pp.id = dv.presentacion_id
                 WHERE dv.venta_id = ?");
            $det->execute([$ventaId]);
            $suma = $this->pdo->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?");
            $asignaciones = $this->pdo->prepare("SELECT lote_id, unidades FROM detalle_venta_lotes WHERE detalle_venta_id = ?");
            $restaurarLote = $this->pdo->prepare("UPDATE lotes_productos SET cantidad_actual = cantidad_actual + ? WHERE id = ?");
            $mov = $this->pdo->prepare(
                "INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, motivo)
                 VALUES (?,?,'entrada',?,?)");
            foreach ($det->fetchAll() as $d) {
                // Se usan las unidades guardadas al vender; si es una venta antigua sin ese dato, se estima
                $unidades = (int)$d['unidades'] > 0 ? (int)$d['unidades'] : (int)$d['cantidad'] * (int)($d['cantidad_unidades'] ?? 1);
                $suma->execute([$unidades, $d['producto_id']]);
                $asignaciones->execute([$d['id']]);
                foreach ($asignaciones->fetchAll() as $asig) {
                    if ($asig['lote_id'] !== null) $restaurarLote->execute([(int)$asig['unidades'], (int)$asig['lote_id']]);
                }
                $mov->execute([$d['producto_id'], $usuarioId, $unidades, "Anulación venta #{$ventaId}"]);
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Lista de ventas. $usuarioId > 0 limita a las ventas de ese usuario (vendedor).
     */
    public function listar(string $desde = '', string $hasta = '', string $estado = '', int $usuarioId = 0): array {
        $sql = "SELECT v.id, v.fecha, v.total, v.estado, u.nombre AS vendedor,
                       (SELECT COUNT(*) FROM detalle_ventas d WHERE d.venta_id = v.id) AS lineas
                FROM ventas v INNER JOIN usuarios u ON u.id = v.usuario_id WHERE 1=1";
        $p = [];
        if ($desde !== '') { $sql .= " AND v.fecha >= :desde";
 $p['desde'] = $desde . ' 00:00:00';
 }
        if ($hasta !== '') { $sql .= " AND v.fecha <= :hasta";
 $p['hasta'] = $hasta . ' 23:59:59';
 }
        if (in_array($estado, ['completada', 'anulada'], true)) { $sql .= " AND v.estado = :estado";
 $p['estado'] = $estado;
 }
        if ($usuarioId > 0) { $sql .= " AND v.usuario_id = :uid";
 $p['uid'] = $usuarioId;
 }
        $sql .= " ORDER BY v.id DESC LIMIT 200";
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }

    public function obtener(int $id): ?array {
        $st = $this->pdo->prepare("SELECT v.*, u.nombre AS vendedor FROM ventas v INNER JOIN usuarios u ON u.id = v.usuario_id WHERE v.id = ?");
        $st->execute([$id]);
        $v = $st->fetch();
        if (!$v) return null;
        $d = $this->pdo->prepare(
            "SELECT dv.cantidad, dv.unidades, dv.precio_unitario, dv.subtotal,
                    p.codigo, p.nombre AS producto, pp.nombre AS presentacion
             FROM detalle_ventas dv
             INNER JOIN productos p ON p.id = dv.producto_id
             LEFT JOIN presentaciones_producto pp ON pp.id = dv.presentacion_id
             WHERE dv.venta_id = ? ORDER BY dv.id");
        $d->execute([$id]);
        $v['detalle'] = $d->fetchAll();
        return $v;
    }

    /** Totales del día (solo ventas completadas). */
    public function resumenHoy(int $usuarioId = 0): array {
        $sql = "SELECT COUNT(*) AS cantidad, COALESCE(SUM(total),0) AS total
                FROM ventas WHERE estado='completada' AND DATE(fecha) = :hoy";
        $p = ['hoy' => date('Y-m-d')];
        if ($usuarioId > 0) { $sql .= " AND usuario_id = :uid";
 $p['uid'] = $usuarioId;
 }
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetch();
    }
}
