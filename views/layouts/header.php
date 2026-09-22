<?php
require_once __DIR__ . '/../../includes/seguridad.php';

$tituloPagina = $tituloPagina ?? 'Sistema de Tutorías';
$flash = mostrarFlash();
$rol = user_role();
$path = $_SERVER['REQUEST_URI'] ?? '';

function activo(string $needle): string
{
    global $path;

    return str_contains($path, $needle) ? 'active' : '';
}

$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
$inicialUsuario = strtoupper(substr($nombreUsuario, 0, 1));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema Web de Apoyo Académico para Tutorías">
    <title><?= e($tituloPagina) ?> | Sistema de Tutorías</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="app">
    <aside class="sidebar" aria-label="Navegación principal">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">ST</span>
            <span>Sistema de Tutorías</span>
        </div>

        <div class="nav-title">Principal</div>

        <a class="nav-link <?= e(activo('/dashboard')) ?>" href="/controllers/dashboard.php">
            <i class="bi bi-speedometer2" aria-hidden="true"></i>
            <span>Dashboard</span>
        </a>

        <?php if ($rol === 'administrador'): ?>
            <div class="nav-title">Académico</div>

            <a class="nav-link <?= e(activo('/estudiantes')) ?>" href="/controllers/estudiantes.php">
                <i class="bi bi-mortarboard" aria-hidden="true"></i>
                <span>Estudiantes</span>
            </a>
            <a class="nav-link <?= e(activo('/profesores')) ?>" href="/controllers/profesores.php">
                <i class="bi bi-person-workspace" aria-hidden="true"></i>
                <span>Profesores</span>
            </a>
            <a class="nav-link <?= e(activo('/cursos')) ?>" href="/controllers/cursos.php">
                <i class="bi bi-book" aria-hidden="true"></i>
                <span>Cursos</span>
            </a>
            <a class="nav-link <?= e(activo('/inscripciones')) ?>" href="/controllers/inscripciones.php">
                <i class="bi bi-journal-check" aria-hidden="true"></i>
                <span>Inscripciones</span>
            </a>
            <a class="nav-link <?= e(activo('/calificaciones')) ?>" href="/controllers/calificaciones.php">
                <i class="bi bi-award" aria-hidden="true"></i>
                <span>Calificaciones</span>
            </a>
            <a class="nav-link <?= e(activo('/asistencia')) ?>" href="/controllers/asistencia.php">
                <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                <span>Asistencia</span>
            </a>

            <div class="nav-title">Administración</div>

            <a class="nav-link <?= e(activo('/usuarios')) ?>" href="/controllers/usuarios.php">
                <i class="bi bi-people" aria-hidden="true"></i>
                <span>Usuarios</span>
            </a>
            <a class="nav-link <?= e(activo('/roles')) ?>" href="/controllers/roles.php">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <span>Roles</span>
            </a>
            <a class="nav-link <?= e(activo('/carreras')) ?>" href="/controllers/carreras.php">
                <i class="bi bi-building" aria-hidden="true"></i>
                <span>Carreras</span>
            </a>

            <div class="nav-title">Tutorías</div>

            <a class="nav-link <?= e(activo('/tutorias')) ?>" href="/controllers/tutorias.php">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                <span>Tutorías</span>
            </a>

        <?php elseif ($rol === 'profesor'): ?>
            <div class="nav-title">Mi gestión</div>

            <a class="nav-link <?= e(activo('/cursos')) ?>" href="/controllers/cursos.php">
                <i class="bi bi-book" aria-hidden="true"></i>
                <span>Mis cursos</span>
            </a>
            <a class="nav-link <?= e(activo('/calificaciones')) ?>" href="/controllers/calificaciones.php">
                <i class="bi bi-award" aria-hidden="true"></i>
                <span>Calificaciones</span>
            </a>
            <a class="nav-link <?= e(activo('/asistencia')) ?>" href="/controllers/asistencia.php">
                <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                <span>Asistencia</span>
            </a>
            <a class="nav-link <?= e(activo('/tutorias')) ?>" href="/controllers/tutorias.php">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                <span>Tutorías</span>
            </a>

        <?php else: ?>
            <div class="nav-title">Mi portal</div>

            <a class="nav-link <?= e(activo('/calificaciones')) ?>" href="/controllers/calificaciones.php">
                <i class="bi bi-award" aria-hidden="true"></i>
                <span>Mis notas</span>
            </a>
            <a class="nav-link <?= e(activo('/asistencia')) ?>" href="/controllers/asistencia.php">
                <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                <span>Mi asistencia</span>
            </a>
            <a class="nav-link <?= e(activo('/tutorias')) ?>" href="/controllers/tutorias.php">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                <span>Mis tutorías</span>
            </a>
        <?php endif; ?>

        <div class="sidebar-logout">
            <a class="nav-link" href="/controllers/logout.php">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button
                    class="mobile-toggle"
                    type="button"
                    data-menu-toggle
                    aria-label="Abrir menú"
                    aria-controls="sidebar"
                >
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>

                <div>
                    <strong><?= e($tituloPagina) ?></strong>
                    <div class="topbar-subtitle">Sistema Web de Apoyo Académico para Tutorías</div>
                </div>
            </div>

            <div class="user-chip">
                <div class="user-info">
                    <strong><?= e($nombreUsuario) ?></strong>
                    <div class="user-role"><?= e($rol) ?></div>
                </div>
                <div class="avatar" aria-hidden="true"><?= e($inicialUsuario) ?></div>
            </div>
        </header>

        <section class="content">
            <?php if ($flash): ?>
                <div
                    class="alert alert-<?= e($flash['tipo'] === 'success' ? 'success' : 'error') ?>"
                    role="alert"
                >
                    <?= e($flash['mensaje']) ?>
                </div>
            <?php endif; ?>