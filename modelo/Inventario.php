<?php
class Inventario {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo;
 }

    public function listar(string $buscar = '', int $categoria = 0, string $estado = ''): array {
        $sql = "SELECT p.id, p.codigo, p.nombre, p.stock_actual, p.stock_seguridad, p.dias_entrega_proveedor,
                       c.nombre AS categoria, pr.nombre AS proveedor
                FROM productos p
                INNER JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN proveedores pr ON pr.id = p.proveedor_id
                WHERE p.activo = 1";
        $params = [];
        if ($buscar !== '') {
            $sql .= " AND (p.nombre LIKE :b1 OR p.codigo LIKE :b2)";
            $params['b1'] = "%$buscar%";
 $params['b2'] = "%$buscar%";
        }
        if ($categoria > 0) { $sql .= " AND p.categoria_id = :cat";
 $params['cat'] = $categoria;
 }
        if ($estado === 'agotado') $sql .= " AND p.stock_actual <= 0";
        if ($estado === 'bajo')    $sql .= " AND p.stock_actual > 0 AND p.stock_actual <= p.stock_seguridad";
        if ($estado === 'alerta')  $sql .= " AND p.stock_actual <= p.stock_seguridad";
        if ($estado === 'normal')  $sql .= " AND p.stock_actual > p.stock_seguridad";
        $sql .= " ORDER BY p.nombre";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function resumen(): array {
        $r = $this->pdo->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN stock_actual <= 0 THEN 1 ELSE 0 END),0) AS agotados,
                    COALESCE(SUM(CASE WHEN stock_actual > 0 AND stock_actual <= stock_seguridad THEN 1 ELSE 0 END),0) AS bajos,
                    COALESCE(SUM(stock_actual),0) AS unidades
             FROM productos WHERE activo = 1")->fetch();
        return array_map('intval', $r);
    }

    /**
     * Fija el stock real contado y deja un movimiento 'ajuste' con la diferencia (con signo).
     */
    public function ajustar(int $productoId, int $nuevoStock, string $motivo, int $usuarioId): void {
        if ($nuevoStock < 0) throw new Exception('El stock no puede ser negativo.');
        if (trim($motivo) === '') throw new Exception('Indica el motivo del ajuste.');
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare("SELECT nombre, stock_actual FROM productos WHERE id = ? AND activo = 1");
            $st->execute([$productoId]);
            $p = $st->fetch();
            if (!$p) throw new Exception('Producto no válido.');
            $anterior = (int)$p['stock_actual'];
            $dif = $nuevoStock - $anterior;
            if ($dif === 0) throw new Exception('El stock indicado es igual al actual; no hay nada que ajustar.');
            $this->pdo->prepare("UPDATE productos SET stock_actual = ? WHERE id = ?")->execute([$nuevoStock, $productoId]);
            $texto = mb_substr("Ajuste {$anterior} → {$nuevoStock}: " . trim($motivo), 0, 200);
            $this->pdo->prepare("INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, motivo) VALUES (?,?,'ajuste',?,?)")
                      ->execute([$productoId, $usuarioId, $dif, $texto]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
