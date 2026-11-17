<?php
class Producto {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listar(string $busqueda = '', int $categoria = 0, bool $incluirInactivos = false): array {
        $sql = "SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor
                FROM productos p
                INNER JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN proveedores pr ON pr.id = p.proveedor_id
                WHERE 1=1";
        if (!$incluirInactivos) {
            $sql .= " AND p.activo = 1";
        }
        $params = [];
        if ($busqueda !== '') {
            $sql .= " AND (p.nombre LIKE :busqueda_nombre OR p.codigo LIKE :busqueda_codigo)";
            $params['busqueda_nombre'] = "%{$busqueda}%";
            $params['busqueda_codigo'] = "%{$busqueda}%";
        }
        if ($categoria > 0) {
            $sql .= " AND p.categoria_id = :categoria";
            $params['categoria'] = $categoria;
        }
        $sql .= " ORDER BY p.nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtener(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM productos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function crear(array $datos): void {
        $sql = "INSERT INTO productos
                (codigo, nombre, categoria_id, proveedor_id, stock_actual, stock_seguridad,
                 precio_compra, precio_venta, dias_entrega_proveedor, activo)
                VALUES (:codigo, :nombre, :categoria_id, :proveedor_id, :stock_actual,
                 :stock_seguridad, :precio_compra, :precio_venta, :dias_entrega, 1)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($datos);
    }

    public function actualizar(int $id, array $datos): void {
        $sql = "UPDATE productos SET codigo=:codigo, nombre=:nombre, categoria_id=:categoria_id,
                proveedor_id=:proveedor_id, stock_actual=:stock_actual,
                stock_seguridad=:stock_seguridad, precio_compra=:precio_compra,
                precio_venta=:precio_venta, dias_entrega_proveedor=:dias_entrega
                WHERE id=:id";
        $datos['id'] = $id;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($datos);
    }

    public function desactivar(int $id): void {
        $stmt = $this->pdo->prepare("UPDATE productos SET activo = 0 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function reactivar(int $id): void {
        $stmt = $this->pdo->prepare("UPDATE productos SET activo = 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function codigoExiste(string $codigo, ?int $excluir = null): bool {
        $sql = "SELECT COUNT(*) FROM productos WHERE codigo = :codigo";
        $params = ['codigo' => $codigo];
        if ($excluir !== null) {
            $sql .= " AND id <> :id";
            $params['id'] = $excluir;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }
}
