<?php
class Presentacion {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo;
 }
    public function listar(string $buscar = '', int $productoId = 0, bool $todos = false): array {
        $sql = "SELECT pp.*, p.nombre AS producto, p.codigo FROM presentaciones_producto pp INNER JOIN productos p ON p.id=pp.producto_id WHERE 1=1";
        $params = [];
        if (!$todos) $sql .= " AND pp.activo=1 AND p.activo=1";
        if ($buscar !== '') { $sql .= " AND (p.nombre LIKE :producto OR pp.nombre LIKE :presentacion OR p.codigo LIKE :codigo)";
 $params['producto']="%$buscar%";
 $params['presentacion']="%$buscar%";
 $params['codigo']="%$buscar%";
 }
        if ($productoId > 0) { $sql .= " AND pp.producto_id=:producto_id";
 $params['producto_id']=$productoId;
 }
        $sql .= " ORDER BY p.nombre, pp.cantidad_unidades DESC";
        $st=$this->pdo->prepare($sql);
 $st->execute($params);
 return $st->fetchAll();
    }
    public function obtener(int $id): ?array { $st=$this->pdo->prepare("SELECT * FROM presentaciones_producto WHERE id=?");
 $st->execute([$id]);
 return $st->fetch() ?: null;
 }
    public function existe(int $productoId, string $nombre, ?int $excluir=null): bool {
        $sql="SELECT COUNT(*) FROM presentaciones_producto WHERE producto_id=:producto AND LOWER(nombre)=LOWER(:nombre)";
 $p=['producto'=>$productoId,'nombre'=>$nombre];
        if($excluir){$sql.=" AND id<>:id";
$p['id']=$excluir;
} $st=$this->pdo->prepare($sql);
$st->execute($p);
return (int)$st->fetchColumn()>0;
    }
    public function guardar(array $d): void { $st=$this->pdo->prepare("INSERT INTO presentaciones_producto(producto_id,nombre,cantidad_unidades,precio_venta,activo) VALUES(:producto,:nombre,:cantidad,:precio,1)");
$st->execute($d);
 }
    public function actualizar(int $id,array $d): void { $d['id']=$id;
$st=$this->pdo->prepare("UPDATE presentaciones_producto SET producto_id=:producto,nombre=:nombre,cantidad_unidades=:cantidad,precio_venta=:precio WHERE id=:id");
$st->execute($d);
 }
    public function cambiarEstado(int $id,int $estado): void { $st=$this->pdo->prepare("UPDATE presentaciones_producto SET activo=:estado WHERE id=:id");
$st->execute(['estado'=>$estado,'id'=>$id]);
 }
}
