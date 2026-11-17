<?php
class Compra {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo;
 }

    /**
     * Productos activos con su proveedor, costo actual y tamaño de paquete sugerido
     * (el mayor contenido entre sus presentaciones activas).
     */
    public function productosParaComprar(): array {
        $sql = "SELECT p.id, p.codigo, p.nombre, p.proveedor_id, p.precio_compra, p.stock_actual,
                       COALESCE((SELECT MAX(pp.cantidad_unidades) FROM presentaciones_producto pp
                                 WHERE pp.producto_id = p.id AND pp.activo = 1), 1) AS unidades_paquete
                FROM productos p
                WHERE p.activo = 1 AND p.proveedor_id IS NOT NULL
                ORDER BY p.nombre";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Registra una compra: suma stock y deja movimientos de entrada.
     * $items: ['producto_id','unidades_por_paquete','cantidad'(paquetes),'costo_unitario'(por paquete)]
     */
    public function registrar(int $usuarioId, int $proveedorId, array $items, bool $actualizarPrecio = true): int {
        if ($proveedorId <= 0) throw new Exception('Selecciona un proveedor.');
        $lineas = [];
        foreach ($items as $it) {
            $pid = (int)($it['producto_id'] ?? 0);
            $upp = filter_var($it['unidades_por_paquete'] ?? null, FILTER_VALIDATE_INT);
            $cant = filter_var($it['cantidad'] ?? null, FILTER_VALIDATE_INT);
            $costo = filter_var($it['costo_unitario'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($pid <= 0 || $upp === false || $upp < 1 || $cant === false || $cant < 1 || $costo === false || $costo < 0) {
                throw new Exception('Hay una línea de compra con datos no válidos.');
            }
            $vencimiento = trim((string)($it['fecha_vencimiento'] ?? ''));
            if ($vencimiento !== '') {
                $dt = DateTime::createFromFormat('Y-m-d', $vencimiento);
                if (!$dt || $dt->format('Y-m-d') !== $vencimiento) throw new Exception('La fecha de vencimiento no es válida.');
            } else {
                $vencimiento = null;
            }
            $lineas[] = ['producto_id' => $pid, 'upp' => $upp, 'cant' => $cant, 'costo' => round($costo, 2), 'vencimiento' => $vencimiento];
        }
        if (!$lineas) throw new Exception('Agrega al menos un producto a la compra.');

        $this->pdo->beginTransaction();
        try {
            $pv = $this->pdo->prepare("SELECT COUNT(*) FROM proveedores WHERE id = ? AND activo = 1");
            $pv->execute([$proveedorId]);
            if (!(int)$pv->fetchColumn()) throw new Exception('El proveedor seleccionado no está activo.');

            // Validar productos antes de crear la compra (evita saltos en la numeración).
            $chk = $this->pdo->prepare("SELECT nombre, proveedor_id FROM productos WHERE id = ? AND activo = 1");
            foreach ($lineas as $l) {
                $chk->execute([$l['producto_id']]);
                $pr0 = $chk->fetch();
                if (!$pr0) throw new Exception('Un producto de la compra ya no está disponible.');
                if ((int)$pr0['proveedor_id'] !== $proveedorId) {
                    throw new Exception("El producto {$pr0['nombre']} no pertenece al proveedor seleccionado.");
                }
            }

            $this->pdo->prepare("INSERT INTO compras (proveedor_id, usuario_id, total) VALUES (?, ?, 0)")
                      ->execute([$proveedorId, $usuarioId]);
            $compraId = (int)$this->pdo->lastInsertId();

            $selProd = $this->pdo->prepare("SELECT id, nombre, proveedor_id FROM productos WHERE id = ? AND activo = 1");
            $insDet = $this->pdo->prepare(
                "INSERT INTO detalle_compras (compra_id, producto_id, unidades_por_paquete, cantidad, costo_unitario, subtotal)
                 VALUES (?,?,?,?,?,?)");
            $insLote = $this->pdo->prepare(
                "INSERT INTO lotes_productos (producto_id, compra_id, detalle_compra_id, cantidad_inicial, cantidad_actual, fecha_ingreso, fecha_vencimiento)
                 VALUES (?,?,?,?,?,CURDATE(),?)");
            $sumar = $this->pdo->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?");
            $mov = $this->pdo->prepare(
                "INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, motivo)
                 VALUES (?,?,'entrada',?,?)");
            $precio = $this->pdo->prepare("UPDATE productos SET precio_compra = ? WHERE id = ?");

            $total = 0.0;
            foreach ($lineas as $l) {
                $selProd->execute([$l['producto_id']]);
                $pr = $selProd->fetch();
                if (!$pr) throw new Exception('Un producto de la compra ya no está disponible.');
                if ((int)$pr['proveedor_id'] !== $proveedorId) {
                    throw new Exception("El producto {$pr['nombre']} no pertenece al proveedor seleccionado.");
                }
                $unidades = $l['upp'] * $l['cant'];
                $subtotal = round($l['costo'] * $l['cant'], 2);
                $total += $subtotal;
                $insDet->execute([$compraId, $l['producto_id'], $l['upp'], $l['cant'], $l['costo'], $subtotal]);
                $detalleCompraId = (int)$this->pdo->lastInsertId();
                $insLote->execute([$l['producto_id'], $compraId, $detalleCompraId, $unidades, $unidades, $l['vencimiento']]);
                $sumar->execute([$unidades, $l['producto_id']]);
                $mov->execute([$l['producto_id'], $usuarioId, $unidades, "Compra #{$compraId}"]);
                if ($actualizarPrecio) $precio->execute([$l['costo'], $l['producto_id']]);
            }
            $this->pdo->prepare("UPDATE compras SET total = ? WHERE id = ?")->execute([round($total, 2), $compraId]);
            $this->pdo->commit();
            return $compraId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listar(string $desde = '', string $hasta = '', int $proveedorId = 0): array {
        $sql = "SELECT c.id, c.fecha, c.total, pr.nombre AS proveedor, u.nombre AS usuario,
                       (SELECT COUNT(*) FROM detalle_compras d WHERE d.compra_id = c.id) AS lineas
                FROM compras c
                INNER JOIN proveedores pr ON pr.id = c.proveedor_id
                INNER JOIN usuarios u ON u.id = c.usuario_id WHERE 1=1";
        $p = [];
        if ($desde !== '') { $sql .= " AND c.fecha >= :desde";
 $p['desde'] = $desde . ' 00:00:00';
 }
        if ($hasta !== '') { $sql .= " AND c.fecha <= :hasta";
 $p['hasta'] = $hasta . ' 23:59:59';
 }
        if ($proveedorId > 0) { $sql .= " AND c.proveedor_id = :prov";
 $p['prov'] = $proveedorId;
 }
        $sql .= " ORDER BY c.id DESC LIMIT 200";
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }

    public function obtener(int $id): ?array {
        $st = $this->pdo->prepare(
            "SELECT c.*, pr.nombre AS proveedor, u.nombre AS usuario
             FROM compras c INNER JOIN proveedores pr ON pr.id = c.proveedor_id
             INNER JOIN usuarios u ON u.id = c.usuario_id WHERE c.id = ?");
        $st->execute([$id]);
        $c = $st->fetch();
        if (!$c) return null;
        $d = $this->pdo->prepare(
            "SELECT dc.unidades_por_paquete, dc.cantidad, dc.costo_unitario, dc.subtotal, p.codigo, p.nombre AS producto,
                    lp.fecha_vencimiento, lp.cantidad_actual
             FROM detalle_compras dc INNER JOIN productos p ON p.id = dc.producto_id
             LEFT JOIN lotes_productos lp ON lp.detalle_compra_id = dc.id
             WHERE dc.compra_id = ? ORDER BY dc.id");
        $d->execute([$id]);
        $c['detalle'] = $d->fetchAll();
        return $c;
    }

    public function totalMes(): float {
        $st = $this->pdo->prepare("SELECT COALESCE(SUM(total),0) FROM compras WHERE fecha >= ?");
        $st->execute([date('Y-m-01') . ' 00:00:00']);
        return (float)$st->fetchColumn();
    }
}
