<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/seguridad.php';
require_role(['administrador', 'profesor', 'estudiante']);

require_once __DIR__ . '/../models/TutorModel.php';
require_once __DIR__ . '/../models/TutoriaModel.php';

$tutorModel = new TutorModel($pdo);
$tutoriaModel = new TutoriaModel($pdo);

$rol = user_role();
$idUsuario = (int) $_SESSION['id_usuario'];
$idTutor = (int) ($_GET['id'] ?? $_POST['id_tutor'] ?? 0);
$mensajeError = null;

$tutorPropio = $rol === 'profesor'
    ? $tutoriaModel->obtenerTutorPorUsuario($idUsuario)
    : null;

if ($rol === 'profesor') {
    if (!$tutorPropio) {
        http_response_code(403);
        exit('No se encontró tu perfil de tutor.');
    }

    if ($idTutor !== (int) $tutorPropio['id_tutor']) {
        redir('/controllers/tutor_perfil.php?id=' . (int) $tutorPropio['id_tutor']);
    }
}

if ($idTutor <= 0) {
    $primerTutor = $tutorModel->obtenerPerfil(1);
    if ($primerTutor) {
        $idTutor = (int) $primerTutor['id_tutor'];
    }
}

$perfil = $tutorModel->obtenerPerfil($idTutor);

if (!$perfil) {
    flash('error', 'El tutor solicitado no existe.');
    redir('/controllers/tutorias.php');
}

$puedeGestionar = $rol === 'administrador'
    || ($rol === 'profesor' && (int) $perfil['id_usuario'] === $idUsuario);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    if (!$puedeGestionar) {
        http_response_code(403);
        exit('No tienes permisos para gestionar este tutor.');
    }

    try {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'asignar_materia') {
            $tutorModel->agregarMateria(
                $idTutor,
                (int) ($_POST['id_materia'] ?? 0)
            );
            flash('success', 'Materia asignada al tutor.');
        } elseif ($accion === 'quitar_materia') {
            $tutorModel->quitarMateria(
                $idTutor,
                (int) ($_POST['id_materia'] ?? 0)
            );
            flash('success', 'Materia retirada del tutor.');
        } elseif ($accion === 'guardar_disponibilidad') {
            $tutorModel->agregarDisponibilidad(
                $idTutor,
                $_POST['dia_semana'] ?? '',
                $_POST['hora_inicio'] ?? '',
                $_POST['hora_fin'] ?? ''
            );
            flash('success', 'Disponibilidad agregada correctamente.');
        } elseif ($accion === 'eliminar_disponibilidad') {
            $tutorModel->eliminarDisponibilidad(
                (int) ($_POST['id_disponibilidad'] ?? 0),
                $idTutor
            );
            flash('success', 'Disponibilidad eliminada.');
        } else {
            throw new RuntimeException('La acción solicitada no es válida.');
        }

        redir('/controllers/tutor_perfil.php?id=' . $idTutor);
    } catch (Throwable $e) {
        $mensajeError = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'No se pudo completar la operación.';
    }
}

$materias = $tutorModel->obtenerMaterias($idTutor);
$materiasDisponibles = $tutorModel->obtenerMateriasDisponibles($idTutor);
$disponibilidad = $tutorModel->obtenerDisponibilidad($idTutor);
$resenas = $tutorModel->obtenerResenas($idTutor);

$tituloPagina = 'Perfil del tutor';

require __DIR__ . '/../views/tutorias/perfil.php';
