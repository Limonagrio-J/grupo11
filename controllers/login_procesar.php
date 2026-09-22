<?php
require_once __DIR__.'/../config/conexion.php'; require_once __DIR__.'/../includes/seguridad.php'; require_once __DIR__.'/../models/UsuarioModel.php';
if($_SERVER['REQUEST_METHOD']!=='POST') redir('/views/login/login.php'); validar_csrf();
$login=trim($_POST['login']??'');$clave=$_POST['clave']??'';$model=new UsuarioModel($pdo);$u=$model->obtenerPorUsuario($login);
if($u && $u['estado']==='activo' && password_verify($clave,$u['contrasena_hash'])){session_regenerate_id(true);$_SESSION['id_usuario']=$u['id_usuario'];$_SESSION['nombre']=$u['nombre'].' '.$u['apellido'];$_SESSION['rol']=$u['nombre_rol'];$s=$pdo->prepare("INSERT INTO registro_accesos(id_usuario,ip_origen,resultado) VALUES(?,?, 'exitoso')");$s->execute([$u['id_usuario'],$_SERVER['REMOTE_ADDR']??'']);redir('/controllers/dashboard.php');}
$s=$pdo->prepare("INSERT INTO registro_accesos(id_usuario,ip_origen,resultado) VALUES(?,?, 'fallido')");$s->execute([$u['id_usuario']??null,$_SERVER['REMOTE_ADDR']??'']);flash('error','Usuario o contraseña incorrectos, o la cuenta está inactiva.');redir('/views/login/login.php');
