<?php
class Categoria {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo;
 }
    public function listar(string $buscar = '', bool $todos = false): array {
        $sql = "SELECT c.id,c.nombre,c.descripcion,c.activo,
                (SELECT COUNT(*) FROM productos p WHERE p.categoria_id=c.id) AS total_productos
                FROM categorias c WHERE (:todos=1 OR c.activo=1)
                AND (c.nombre LIKE :nombre OR c.descripcion LIKE :descripcion)
                ORDER BY c.nombre";
        $st = $this->pdo->prepare($sql);
        $like = "%$buscar%";
        $st->execute(['todos'=>$todos?1:0,'nombre'=>$like,'descripcion'=>$like]);
        return $st->fetchAll();
    }
    public function obtener(int $id): array|false {
        $st=$this->pdo->prepare('SELECT * FROM categorias WHERE id=?');
 $st->execute([$id]);
 return $st->fetch();
    }
    public function guardar(string $nombre, string $descripcion): void {
        $st=$this->pdo->prepare('INSERT INTO categorias(nombre,descripcion,activo) VALUES(?,?,1)');
        $st->execute([$nombre,$descripcion !== '' ? $descripcion : null]);
    }
    public function actualizar(int $id,string $nombre,string $descripcion): void {
        $st=$this->pdo->prepare('UPDATE categorias SET nombre=?,descripcion=? WHERE id=?');
        $st->execute([$nombre,$descripcion !== '' ? $descripcion : null,$id]);
    }
    public function cambiarEstado(int $id,int $activo): void {
        $st=$this->pdo->prepare('UPDATE categorias SET activo=? WHERE id=?');
 $st->execute([$activo,$id]);
    }
}
