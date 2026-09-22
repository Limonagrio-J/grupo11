<?php
require_once __DIR__.'/includes/seguridad.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!empty($_SESSION['id_usuario'])) {
    header('Location: /controllers/dashboard.php');
} else {
    header('Location: /views/login/login.php');
}
exit;
