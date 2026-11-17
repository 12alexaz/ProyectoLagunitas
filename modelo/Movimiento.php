<?php
class Movimiento {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo;
 }

    public function listar(string $desde = '', string $hasta = '', string $tipo = '', int $productoId = 0, int $limite = 300): array {
        $sql = "SELECT m.id, m.fecha, m.tipo, m.cantidad, m.motivo,
                       p.codigo, p.nombre AS producto, u.nombre AS usuario
                FROM movimientos_inventario m
                INNER JOIN productos p ON p.id = m.producto_id
                INNER JOIN usuarios u ON u.id = m.usuario_id WHERE 1=1";
        $params = [];
        if ($desde !== '') { $sql .= " AND m.fecha >= :desde";
 $params['desde'] = $desde . ' 00:00:00';
 }
        if ($hasta !== '') { $sql .= " AND m.fecha <= :hasta";
 $params['hasta'] = $hasta . ' 23:59:59';
 }
        if (in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) { $sql .= " AND m.tipo = :tipo";
 $params['tipo'] = $tipo;
 }
        if ($productoId > 0) { $sql .= " AND m.producto_id = :pid";
 $params['pid'] = $productoId;
 }
        $sql .= " ORDER BY m.id DESC LIMIT " . (int)$limite;
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}
