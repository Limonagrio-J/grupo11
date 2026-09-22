<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redir(string $url): never { header('Location: '.$url); exit; }
function flash(string $tipo, string $mensaje): void { $_SESSION['flash']=['tipo'=>$tipo,'mensaje'=>$mensaje]; }
function mostrarFlash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function validar_csrf(): void { if(!hash_equals($_SESSION['csrf']??'', $_POST['csrf']??'')) { http_response_code(419); exit('Token CSRF inválido. Recarga la página e inténtalo nuevamente.'); } }
function require_login(): void { if(empty($_SESSION['id_usuario'])) redir('/views/login/login.php'); }
function require_role(array $roles): void { require_login(); if(!in_array($_SESSION['rol']??'', $roles, true)) redir('/views/errores/acceso_denegado.php'); }
function user_role(): string { return $_SESSION['rol']??''; }
function es_estudiante(): bool { return user_role()==='estudiante'; }
function puede_gestionar_academico(): bool { return in_array(user_role(), ['administrador','profesor'], true); }
function home_by_role(): string { return user_role()==='administrador' ? '/controllers/dashboard.php' : (user_role()==='profesor' ? '/controllers/dashboard.php' : '/controllers/dashboard.php'); }
