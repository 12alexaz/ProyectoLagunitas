<?php
/**
 * Asistente de reposición: sugiere qué comprar según el consumo real (ventas),
 * el stock de seguridad y los días de entrega del proveedor.
 *
 *   consumo diario   = unidades vendidas en la ventana / días de la ventana
 *   punto de pedido  = consumo diario × días de entrega + stock de seguridad
 *   stock objetivo   = consumo diario × (días de entrega + días de cobertura) + stock de seguridad
 *                      (como mínimo el doble del stock de seguridad)
 *   sugerido         = (objetivo − stock actual) redondeado hacia arriba a paquetes completos
 */
class Asistente {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo;
 }

    public function sugerencias(int $ventanaDias = 30, int $coberturaDias = 15): array {
        $ventanaDias = max(1, $ventanaDias);
        $coberturaDias = max(0, $coberturaDias);

        $productos = $this->pdo->query(
            "SELECT p.id, p.codigo, p.nombre, p.proveedor_id, p.stock_actual, p.stock_seguridad, p.dias_entrega_proveedor,
                    pr.nombre AS proveedor,
                    COALESCE((SELECT MAX(pp.cantidad_unidades) FROM presentaciones_producto pp
                              WHERE pp.producto_id = p.id AND pp.activo = 1), 1) AS unidades_paquete
             FROM productos p LEFT JOIN proveedores pr ON pr.id = p.proveedor_id
             WHERE p.activo = 1 ORDER BY p.nombre")->fetchAll();

        // Unidades vendidas por producto (ventas completadas dentro de la ventana)
        $st = $this->pdo->prepare(
            "SELECT dv.producto_id,
                    SUM(CASE WHEN dv.unidades > 0 THEN dv.unidades ELSE dv.cantidad * COALESCE(pp.cantidad_unidades, 1) END) AS vendidas
             FROM detalle_ventas dv
             INNER JOIN ventas v ON v.id = dv.venta_id
             LEFT JOIN presentaciones_producto pp ON pp.id = dv.presentacion_id
             WHERE v.estado = 'completada' AND v.fecha >= :desde
             GROUP BY dv.producto_id");
        $st->execute(['desde' => date('Y-m-d H:i:s', strtotime("-{$ventanaDias} days"))]);
        $vendidas = [];
        foreach ($st->fetchAll() as $r) $vendidas[(int)$r['producto_id']] = (int)$r['vendidas'];

        $orden = ['critico' => 0, 'reponer' => 1, 'ok' => 2];
        $out = [];
        foreach ($productos as $p) {
            $stock = (int)$p['stock_actual'];
            $seg = (int)$p['stock_seguridad'];
            $dias = (int)$p['dias_entrega_proveedor'];
            $upp = max(1, (int)$p['unidades_paquete']);
            $v = $vendidas[(int)$p['id']] ?? 0;
            $consumo = $v / $ventanaDias;

            $puntoPedido = (int)ceil($consumo * $dias) + $seg;
            $objetivo = max((int)ceil($consumo * ($dias + $coberturaDias)) + $seg, 2 * $seg);

            if ($stock <= $seg) $nivel = 'critico';
            elseif ($stock <= $puntoPedido) $nivel = 'reponer';
            else $nivel = 'ok';

            $faltante = max(0, $objetivo - $stock);
            $paquetes = ($nivel !== 'ok' && $faltante > 0) ? (int)ceil($faltante / $upp) : 0;

            $out[] = [
                'id' => (int)$p['id'], 'codigo' => $p['codigo'], 'nombre' => $p['nombre'],
                'proveedor_id' => $p['proveedor_id'] !== null ? (int)$p['proveedor_id'] : 0,
                'proveedor' => $p['proveedor'] ?? 'Sin proveedor',
                'stock' => $stock, 'seguridad' => $seg, 'dias_entrega' => $dias,
                'vendidas' => $v, 'consumo_diario' => $consumo,
                'cobertura_dias' => $consumo > 0 ? $stock / $consumo : null,
                'punto_pedido' => $puntoPedido, 'objetivo' => $objetivo,
                'unidades_paquete' => $upp, 'paquetes' => $paquetes, 'unidades_sugeridas' => $paquetes * $upp,
                'nivel' => $nivel,
            ];
        }
        usort($out, function ($a, $b) use ($orden) {
            return [$orden[$a['nivel']], $a['cobertura_dias'] ?? PHP_INT_MAX, $a['nombre']]
               <=> [$orden[$b['nivel']], $b['cobertura_dias'] ?? PHP_INT_MAX, $b['nombre']];
        });
        return $out;
    }

    /** Lotes vencidos o próximos a vencer. Los lotes sin fecha se excluyen. */
    public function alertasVencimiento(): array {
        $sql = "SELECT lp.id AS lote_id, lp.producto_id, p.codigo, p.nombre AS producto,
                       lp.cantidad_actual, lp.fecha_ingreso, lp.fecha_vencimiento,
                       DATEDIFF(lp.fecha_vencimiento, CURDATE()) AS dias_restantes,
                       pr.nombre AS proveedor
                FROM lotes_productos lp
                INNER JOIN productos p ON p.id = lp.producto_id
                LEFT JOIN compras c ON c.id = lp.compra_id
                LEFT JOIN proveedores pr ON pr.id = c.proveedor_id
                WHERE lp.cantidad_actual > 0 AND lp.fecha_vencimiento IS NOT NULL
                  AND lp.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ORDER BY lp.fecha_vencimiento ASC, p.nombre ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

}
