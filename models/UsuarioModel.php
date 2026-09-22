<?php
class UsuarioModel {
 private PDO $pdo; public function __construct(PDO $pdo){$this->pdo=$pdo;}
 public function obtenerTodas(){return $this->pdo->query("SELECT u.*,r.nombre_rol FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol ORDER BY u.id_usuario DESC")->fetchAll();}
 public function obtenerPorId($id){$s=$this->pdo->prepare('SELECT * FROM usuarios WHERE id_usuario=?');$s->execute([$id]);return $s->fetch();}
 public function obtenerPorUsuario($login){$s=$this->pdo->prepare('SELECT u.*,r.nombre_rol FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE u.usuario=:usuario OR u.correo=:correo LIMIT 1');$s->execute(['usuario'=>$login,'correo'=>$login]);return $s->fetch();}
 public function crear(array $d){$s=$this->pdo->prepare('INSERT INTO usuarios(id_rol,nombre,apellido,correo,usuario,contrasena_hash,telefono,estado) VALUES(:rol,:nom,:ape,:correo,:usuario,:hash,:tel,:estado)');return $s->execute(['rol'=>$d['id_rol'],'nom'=>trim($d['nombre']),'ape'=>trim($d['apellido']),'correo'=>trim($d['correo']),'usuario'=>trim($d['usuario']),'hash'=>password_hash($d['clave'],PASSWORD_DEFAULT),'tel'=>trim($d['telefono']??''),'estado'=>$d['estado']??'activo']);}
 public function actualizar($id,array $d){$sql='UPDATE usuarios SET id_rol=:rol,nombre=:nom,apellido=:ape,correo=:correo,usuario=:usuario,telefono=:tel,estado=:estado';$p=['rol'=>$d['id_rol'],'nom'=>$d['nombre'],'ape'=>$d['apellido'],'correo'=>$d['correo'],'usuario'=>$d['usuario'],'tel'=>$d['telefono']??'','estado'=>$d['estado'],'id'=>$id];if(!empty($d['clave'])){$sql.=',contrasena_hash=:hash';$p['hash']=password_hash($d['clave'],PASSWORD_DEFAULT);} $sql.=' WHERE id_usuario=:id';$s=$this->pdo->prepare($sql);return $s->execute($p);}
 public function eliminar($id){$s=$this->pdo->prepare('DELETE FROM usuarios WHERE id_usuario=?');return $s->execute([$id]);}
}
