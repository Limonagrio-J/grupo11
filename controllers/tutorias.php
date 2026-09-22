<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/seguridad.php';
require_role(['administrador', 'profesor', 'estudiante']);

require_once __DIR__ . '/../models/TutoriaModel.php';

$model = new TutoriaModel($pdo);
$rol = user_role();
$idUsuario = (int) $_SESSION['id_usuario'];
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$accion = $_GET['accion'] ?? 'listar';
$formData = [];
$formError = null;

$estudianteActual = $rol === 'estudiante'
    ? $model->obtenerEstudiantePorUsuario($idUsuario)
    : null;

$tutorActual = $rol === 'profesor'
    ? $model->obtenerTutorPorUsuario($idUsuario)
    : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $accionPost = $_POST['accion'] ?? '';

    try {
        if ($accionPost === 'eliminar') {
            $registro = $model->obtenerDetalle((int) $_POST['id']);

            if (!$registro) {
                throw new TutoriaValidationException('La tutoría indicada no existe.');
            }

            if (
                $rol === 'estudiante'
                && ((int) $registro['estudiante_usuario'] !== $idUsuario || $registro['estado'] !== 'pendiente')
            ) {
                throw new TutoriaValidationException(
                    'Solo puedes eliminar tus solicitudes que todavía están pendientes.'
                );
            }

            if (
                $rol === 'profesor'
                && (
                    (int) $registro['profesor_usuario'] !== $idUsuario
                    || !in_array($registro['estado'], ['pendiente', 'confirmada'], true)
                )
            ) {
                throw new TutoriaValidationException(
                    'Solo puedes eliminar tus tutorías pendientes o confirmadas.'
                );
            }

            if (!$model->eliminarSiPertenece((int) $_POST['id'], $rol, $idUsuario)) {
                throw new TutoriaValidationException('No tienes permiso para eliminar esta tutoría.');
            }

            flash('success', 'Tutoría eliminada correctamente.');
            redir('/controllers/tutorias.php');
        }

        if ($accionPost === 'guardar') {
            $idPost = (int) ($_POST['id'] ?? 0);
            $formData = $_POST;
            $esEdicion = $idPost > 0;

            if ($rol === 'estudiante') {
                if (!$estudianteActual) {
                    throw new TutoriaValidationException('No se encontró tu registro de estudiante.');
                }

                $_POST['id_estudiante'] = $estudianteActual['id_estudiante'];
                $formData['id_estudiante'] = $estudianteActual['id_estudiante'];
                $_POST['estado'] = 'pendiente';
                $formData['estado'] = 'pendiente';

                if ($esEdicion) {
                    $registro = $model->obtenerDetalle($idPost);

                    if (
                        !$registro
                        || (int) $registro['estudiante_usuario'] !== $idUsuario
                    ) {
                        throw new TutoriaValidationException(
                            'No tienes permiso para editar esta tutoría.'
                        );
                    }

                    if ($registro['estado'] !== 'pendiente') {
                        throw new TutoriaValidationException(
                            'Solo puedes editar tutorías que todavía están pendientes.'
                        );
                    }
                }
            }

            if ($rol === 'profesor') {
                if (!$tutorActual) {
                    throw new TutoriaValidationException('No se encontró tu perfil de tutor.');
                }

                if ($esEdicion) {
                    $registro = $model->obtenerDetalle($idPost);

                    if (
                        !$registro
                        || (int) $registro['profesor_usuario'] !== $idUsuario
                    ) {
                        throw new TutoriaValidationException(
                            'Solo puedes editar las tutorías que tienes asignadas.'
                        );
                    }

                    $_POST['id_tutor'] = $tutorActual['id_tutor'];
                    $formData['id_tutor'] = $tutorActual['id_tutor'];
                } else {
                    $_POST['id_tutor'] = $tutorActual['id_tutor'];
                    $formData['id_tutor'] = $tutorActual['id_tutor'];
                    $_POST['estado'] = 'confirmada';
                    $formData['estado'] = 'confirmada';
                }
            }

            $datos = [
                'id_estudiante' => (int) ($_POST['id_estudiante'] ?? 0),
                'id_tutor' => (int) ($_POST['id_tutor'] ?? 0),
                'id_materia' => (int) ($_POST['id_materia'] ?? 0),
                'fecha' => trim($_POST['fecha'] ?? ''),
                'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
                'hora_fin' => trim($_POST['hora_fin'] ?? ''),
                'modalidad' => $_POST['modalidad'] ?? '',
                'lugar_o_enlace' => $_POST['lugar_o_enlace'] ?? '',
                'estado' => $_POST['estado'] ?? 'pendiente',
                'observaciones' => $_POST['observaciones'] ?? '',
            ];

            if ($esEdicion) {
                $model->editar($idPost, $datos);
                flash('success', 'Tutoría actualizada correctamente.');
            } else {
                $model->crear($datos);
                flash('success', 'Tutoría registrada correctamente.');
            }

            redir('/controllers/tutorias.php');
        }

        throw new TutoriaValidationException('Acción no reconocida.');
    } catch (TutoriaValidationException $e) {
        $formError = $e->getMessage();
        $accion = $id > 0 ? 'editar' : 'crear';
    } catch (Throwable $e) {
        $formError = 'No se pudo completar la operación. Revisa los datos e inténtalo nuevamente.';
        $accion = $id > 0 ? 'editar' : 'crear';
    }
}

$perPagina = 8;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$filtros = [
    'q' => trim($_GET['q'] ?? ''),
    'estado' => $_GET['estado'] ?? '',
    'id_tutor' => (int) ($_GET['id_tutor'] ?? 0),
    'id_materia' => (int) ($_GET['id_materia'] ?? 0),
    'modalidad' => $_GET['modalidad'] ?? '',
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
];

$permitidosEstado = ['pendiente', 'confirmada', 'realizada', 'cancelada'];
if (!in_array($filtros['estado'], $permitidosEstado, true)) {
    $filtros['estado'] = '';
}

if (!in_array($filtros['modalidad'], ['presencial', 'virtual'], true)) {
    $filtros['modalidad'] = '';
}

$resultado = $model->obtenerListado(
    $filtros,
    $pagina,
    $perPagina,
    $rol,
    $idUsuario
);

$registro = null;
if ($accion === 'detalle' || $accion === 'editar') {
    if ($id <= 0) {
        flash('error', 'No se indicó una tutoría válida.');
        redir('/controllers/tutorias.php');
    }

    $registro = $model->obtenerDetalle($id);

    if (!$registro) {
        flash('error', 'La tutoría no existe.');
        redir('/controllers/tutorias.php');
    }

    if (!$model->puedeEditar($id, $rol, $idUsuario)) {
        flash('error', 'No tienes permiso para acceder a esta tutoría.');
        redir('/controllers/tutorias.php');
    }

    if ($accion === 'editar' && $rol === 'estudiante' && $registro['estado'] !== 'pendiente') {
        $formError = 'Solo puedes editar tutorías que todavía están pendientes.';
        $accion = 'detalle';
    }

    if ($formError && !$formData) {
        $formData = $registro;
    }
}

if ($accion === 'crear') {
    if ($rol === 'estudiante' && !$estudianteActual) {
        $formError = 'No se encontró tu registro de estudiante.';
    }

    if ($rol === 'profesor' && !$tutorActual) {
        $formError = 'No se encontró tu perfil de tutor.';
    }
}

if (!$formData) {
    $formData = [
        'id_estudiante' => $rol === 'estudiante' ? ($estudianteActual['id_estudiante'] ?? '') : '',
        'id_tutor' => $rol === 'profesor'
            ? ($tutorActual['id_tutor'] ?? '')
            : (int) ($_GET['id_tutor'] ?? 0),
        'id_materia' => (int) ($_GET['id_materia'] ?? 0),
        'fecha' => $_GET['fecha'] ?? date('Y-m-d'),
        'hora_inicio' => $_GET['hora_inicio'] ?? '',
        'hora_fin' => $_GET['hora_fin'] ?? '',
        'modalidad' => 'presencial',
        'lugar_o_enlace' => '',
        'estado' => $rol === 'profesor' ? 'confirmada' : 'pendiente',
        'observaciones' => '',
    ];
}

$tutores = $model->obtenerTutores();
$estudiantes = $rol === 'estudiante' ? [] : $model->obtenerEstudiantes();
$materias = $model->obtenerMaterias();

$materiasPorTutor = [];
foreach ($tutores as $tutor) {
    $materiasPorTutor[$tutor['id_tutor']] = $model->obtenerMateriasPorTutor((int) $tutor['id_tutor']);
}

$tituloPagina = 'Tutorías académicas';

require __DIR__ . '/../views/tutorias/index.php';
