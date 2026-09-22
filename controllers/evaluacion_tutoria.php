<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/seguridad.php';
require_role(['administrador', 'estudiante']);

require_once __DIR__ . '/../models/TutoriaModel.php';

$model = new TutoriaModel($pdo);
$idTutoria = (int) ($_POST['id_tutoria'] ?? $_GET['id_tutoria'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $accion = $_POST['accion'] ?? '';
        $detalle = $model->obtenerDetalle($idTutoria);

        if (!$detalle) {
            throw new TutoriaValidationException('La tutoría no existe.');
        }

        if (user_role() === 'administrador' && $accion === 'eliminar') {
            $model->eliminarEvaluacion($idTutoria);
            flash('success', 'Reseña eliminada correctamente.');
            redir('/controllers/tutorias.php?accion=detalle&id=' . $idTutoria);
        }

        if (user_role() !== 'estudiante' || $accion !== 'guardar') {
            throw new TutoriaValidationException('No tienes permiso para realizar esta acción.');
        }

        if ((int) $detalle['estudiante_usuario'] !== (int) $_SESSION['id_usuario']) {
            throw new TutoriaValidationException('Solo puedes evaluar tus propias tutorías.');
        }

        $model->crearEvaluacion(
            $idTutoria,
            (int) ($_POST['calificacion'] ?? 0),
            $_POST['comentario'] ?? ''
        );

        flash('success', 'Gracias por calificar la tutoría.');
        redir('/controllers/tutorias.php?accion=detalle&id=' . $idTutoria);
    } catch (TutoriaValidationException $e) {
        flash('error', $e->getMessage());
        redir('/controllers/tutorias.php?accion=detalle&id=' . $idTutoria);
    } catch (Throwable $e) {
        flash('error', 'No se pudo guardar la reseña.');
        redir('/controllers/tutorias.php?accion=detalle&id=' . $idTutoria);
    }
}

redir('/controllers/tutorias.php?accion=detalle&id=' . $idTutoria);
