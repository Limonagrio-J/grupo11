<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/seguridad.php';
require_role(['administrador', 'profesor', 'estudiante']);

require_once __DIR__ . '/../models/TutoriaModel.php';

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);
    exit('La dependencia de PDF no está instalada. Reconstruye el contenedor con Docker.');
}

require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;

$model = new TutoriaModel($pdo);
$rol = user_role();
$idUsuario = (int) $_SESSION['id_usuario'];
$tipo = $_GET['tipo'] ?? 'lista';

function pdfEsc($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function estadoClasePdf(string $estado): string
{
    return match ($estado) {
        'confirmada' => 'confirmada',
        'realizada' => 'realizada',
        'cancelada' => 'cancelada',
        default => 'pendiente',
    };
}

$html = '
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 35px 36px 45px; }
    body {
        font-family: DejaVu Sans, sans-serif;
        color: #16303b;
        font-size: 10px;
    }
    .header {
        border-bottom: 3px solid #018abd;
        padding-bottom: 12px;
        margin-bottom: 18px;
    }
    .title {
        color: #018abd;
        font-size: 19px;
        font-weight: bold;
        margin: 0 0 4px;
    }
    .subtitle {
        color: #6c7a80;
        font-size: 9px;
    }
    .meta {
        margin: 0 0 15px;
        padding: 10px;
        background: #f0f8fb;
        border: 1px solid #d5e9ef;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }
    th {
        background: #018abd;
        color: #ffffff;
        text-align: left;
        padding: 7px;
        font-size: 8px;
    }
    td {
        border-bottom: 1px solid #dfeaf0;
        padding: 7px;
        vertical-align: top;
    }
    .badge {
        display: inline-block;
        padding: 3px 6px;
        border-radius: 8px;
        font-weight: bold;
    }
    .pendiente { background: #fff4cf; color: #7a5a00; }
    .confirmada { background: #e8f7fc; color: #01698f; }
    .realizada { background: #eaf8f0; color: #176f40; }
    .cancelada { background: #ffecee; color: #a92d3b; }
    .box {
        border: 1px solid #dfeaf0;
        padding: 12px;
        margin: 10px 0;
    }
    .label {
        color: #6c7a80;
        font-size: 8px;
        text-transform: uppercase;
    }
    .value {
        font-size: 11px;
        margin: 2px 0 8px;
    }
    .footer {
        position: fixed;
        bottom: -25px;
        left: 0;
        right: 0;
        text-align: center;
        color: #8a9aa0;
        font-size: 8px;
    }
</style>
</head>
<body>
<div class="header">
    <div class="title">Sistema Web de Apoyo Académico para Tutorías</div>
    <div class="subtitle">Grupo11 · Constancia y reportes de tutorías</div>
</div>
<div class="meta">Generado el ' . pdfEsc(date('d/m/Y H:i')) . '</div>
';

if ($tipo === 'detalle') {
    $id = (int) ($_GET['id'] ?? 0);
    $detalle = $model->obtenerDetalle($id);

    if (!$detalle || !$model->puedeEditar($id, $rol, $idUsuario)) {
        http_response_code(403);
        exit('No tienes permiso para generar este documento.');
    }

    $html .= '
        <h2>Constancia de tutoría #' . $id . '</h2>
        <div class="box">
            <div class="label">Estudiante</div>
            <div class="value">' . pdfEsc($detalle['estudiante']) . '</div>
            <div class="label">Tutor</div>
            <div class="value">' . pdfEsc($detalle['profesor']) . '</div>
            <div class="label">Materia</div>
            <div class="value">' . pdfEsc($detalle['nombre_materia']) . '</div>
            <div class="label">Fecha y horario</div>
            <div class="value">' . pdfEsc($detalle['fecha']) . ' · ' .
                pdfEsc(substr($detalle['hora_inicio'], 0, 5)) . ' - ' .
                pdfEsc(substr($detalle['hora_fin'], 0, 5)) . '</div>
            <div class="label">Modalidad</div>
            <div class="value">' . pdfEsc(ucfirst($detalle['modalidad'])) . '</div>
            <div class="label">Lugar o enlace</div>
            <div class="value">' . pdfEsc($detalle['lugar_o_enlace'] ?: '—') . '</div>
            <div class="label">Estado</div>
            <div class="value">
                <span class="badge ' . estadoClasePdf($detalle['estado']) . '">' .
                pdfEsc(ucfirst($detalle['estado'])) . '</span>
            </div>
            <div class="label">Observaciones</div>
            <div class="value">' . nl2br(pdfEsc($detalle['observaciones'] ?: '—')) . '</div>
        </div>
    ';
} else {
    $filtros = [
        'q' => trim($_GET['q'] ?? ''),
        'estado' => $_GET['estado'] ?? '',
        'id_tutor' => (int) ($_GET['id_tutor'] ?? 0),
        'id_materia' => (int) ($_GET['id_materia'] ?? 0),
        'modalidad' => $_GET['modalidad'] ?? '',
        'fecha_desde' => $_GET['fecha_desde'] ?? '',
        'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    ];

    $resultado = $model->obtenerListado(
        $filtros,
        1,
        5000,
        $rol,
        $idUsuario
    );

    $html .= '
        <h2>Listado de tutorías</h2>
        <div class="meta">Resultados encontrados: ' . (int) $resultado['total'] . '</div>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Estudiante</th>
                    <th>Tutor</th>
                    <th>Materia</th>
                    <th>Modalidad</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
    ';

    foreach ($resultado['items'] as $item) {
        $html .= '
            <tr>
                <td>' . pdfEsc($item['fecha']) . '<br>' .
                    pdfEsc(substr($item['hora_inicio'], 0, 5)) . ' - ' .
                    pdfEsc(substr($item['hora_fin'], 0, 5)) . '</td>
                <td>' . pdfEsc($item['estudiante']) . '</td>
                <td>' . pdfEsc($item['profesor']) . '</td>
                <td>' . pdfEsc($item['nombre_materia']) . '</td>
                <td>' . pdfEsc(ucfirst($item['modalidad'])) . '</td>
                <td><span class="badge ' . estadoClasePdf($item['estado']) . '">' .
                    pdfEsc(ucfirst($item['estado'])) . '</span></td>
            </tr>
        ';
    }

    $html .= '</tbody></table>';
}

$html .= '
<div class="footer">Sistema Web de Apoyo Académico para Tutorías · Grupo11 © ' . date('Y') . ' UPDS</div>
</body>
</html>
';

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$nombreArchivo = $tipo === 'detalle'
    ? 'constancia-tutoria-' . (int) ($_GET['id'] ?? 0) . '.pdf'
    : 'listado-tutorias-' . date('Ymd-His') . '.pdf';

$dompdf->stream($nombreArchivo, [
    'Attachment' => true,
]);
