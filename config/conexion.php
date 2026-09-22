<?php
date_default_timezone_set('America/La_Paz');

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db = getenv('DB_NAME') ?: 'student_portal_db';
$user = getenv('DB_USER') ?: 'student_portal_user';
$pass = getenv('DB_PASS') ?: '12345';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET time_zone = '-04:00'");
} catch (PDOException $e) {
    http_response_code(500);
    exit('No se pudo conectar con MySQL. Verifica que los servicios estén levantados.');
}
