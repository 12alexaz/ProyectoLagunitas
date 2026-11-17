<?php
class Proveedor {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo;
 }

    public function listar(string $buscar = '', bool $todos = false): array {
        $sql = "SELECT pr.id, pr.nombre, pr.telefono, pr.direccion, pr.activo,
                (SELECT COUNT(*) FROM productos p WHERE p.proveedor_id = pr.id) AS total_productos
                FROM proveedores pr
                WHERE (:todos = 1 OR pr.activo = 1)
                AND (pr.nombre LIKE :nombre OR pr.telefono LIKE :telefono OR pr.direccion LIKE :direccion)
                ORDER BY pr.nombre";
        $st = $this->pdo->prepare($sql);
        $like = "%$buscar%";
        $st->execute(['todos' => $todos ? 1 : 0, 'nombre' => $like, 'telefono' => $like, 'direccion' => $like]);
        return $st->fetchAll();
    }

    public function obtener(int $id): array|false {
        $st = $this->pdo->prepare('SELECT * FROM proveedores WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    public function guardar(string $nombre, string $telefono, string $direccion): void {
        $st = $this->pdo->prepare('INSERT INTO proveedores (nombre, telefono, direccion, activo) VALUES (?, ?, ?, 1)');
        $st->execute([$nombre, $telefono !== '' ? $telefono : null, $direccion !== '' ? $direccion : null]);
    }

    public function actualizar(int $id, string $nombre, string $telefono, string $direccion): void {
        $st = $this->pdo->prepare('UPDATE proveedores SET nombre = ?, telefono = ?, direccion = ? WHERE id = ?');
        $st->execute([$nombre, $telefono !== '' ? $telefono : null, $direccion !== '' ? $direccion : null, $id]);
    }

    public function cambiarEstado(int $id, int $activo): void {
        $st = $this->pdo->prepare('UPDATE proveedores SET activo = ? WHERE id = ?');
        $st->execute([$activo, $id]);
    }
}
