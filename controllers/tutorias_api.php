<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/seguridad.php';
require_role(['administrador', 'profesor', 'estudiante']);

require_once __DIR__ . '/../models/TutoriaModel.php';

$model = new TutoriaModel($pdo);
$inicio = substr($_GET['start'] ?? date('Y-m-01'), 0, 10);
$fin = substr($_GET['end'] ?? date('Y-m-t'), 0, 10);

$eventos = [];

try {
    $registros = $model->obtenerEventosCalendario(
        $inicio,
        $fin,
        user_role(),
        (int) $_SESSION['id_usuario']
    );

    $colores = [
        'pendiente' => '#d99b00',
        'confirmada' => '#018abd',
        'realizada' => '#18824a',
        'cancelada' => '#bd3c49',
    ];

    foreach ($registros as $registro) {
        $eventos[] = [
            'id' => (string) $registro['id_tutoria'],
            'title' => $registro['nombre_materia'] . ' · ' . $registro['estudiante'],
            'start' => $registro['fecha'] . 'T' . $registro['hora_inicio'],
            'end' => $registro['fecha'] . 'T' . $registro['hora_fin'],
            'url' => '/controllers/tutorias.php?accion=detalle&id=' . (int) $registro['id_tutoria'],
            'backgroundColor' => $colores[$registro['estado']] ?? '#018abd',
            'borderColor' => $colores[$registro['estado']] ?? '#018abd',
            'extendedProps' => [
                'estado' => $registro['estado'],
                'tutor' => $registro['profesor'],
                'estudiante' => $registro['estudiante'],
                'materia' => $registro['nombre_materia'],
                'modalidad' => $registro['modalidad'],
            ],
        ];
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        $eventos,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'No se pudo cargar el calendario.',
    ], JSON_UNESCAPED_UNICODE);
}
