<?php
include __DIR__ . '/../layouts/header.php';

$estadoClase = static function (string $estado): string {
    return match ($estado) {
        'confirmada', 'realizada' => 'badge-success',
        'cancelada' => 'badge-danger',
        default => 'badge-warning',
    };
};

$estados = [
    'pendiente' => 'Pendiente',
    'confirmada' => 'Confirmada',
    'realizada' => 'Realizada',
    'cancelada' => 'Cancelada',
];

$puedeCrear = in_array($rol, ['administrador', 'profesor', 'estudiante'], true);
$puedeEditarDetalle = $registro
    ? $model->puedeEditar((int) $registro['id_tutoria'], $rol, (int) $_SESSION['id_usuario'])
    : false;

$puedeEliminarDetalle = false;
if ($registro) {
    $puedeEliminarDetalle = $rol === 'administrador'
        || (
            $rol === 'profesor'
            && (int) $registro['profesor_usuario'] === (int) $_SESSION['id_usuario']
            && in_array($registro['estado'], ['pendiente', 'confirmada'], true)
        )
        || (
            $rol === 'estudiante'
            && (int) $registro['estudiante_usuario'] === (int) $_SESSION['id_usuario']
            && $registro['estado'] === 'pendiente'
        );
}

$filtrosActivos = array_filter(
    $filtros,
    static fn ($valor) => $valor !== '' && $valor !== 0 && $valor !== null
);

$crearUrl = '/controllers/tutorias.php?accion=crear';
if (!empty($_GET['id_tutor'])) {
    $crearUrl .= '&id_tutor=' . (int) $_GET['id_tutor'];
}

$urlPagina = static function (int $numero) use ($filtros): string {
    $params = array_filter(
        array_merge($filtros, ['pagina' => $numero]),
        static fn ($valor) => $valor !== '' && $valor !== 0 && $valor !== null
    );

    return '/controllers/tutorias.php?' . http_build_query($params);
};

$detalleUrl = static function (int $id, string $accion = 'detalle'): string {
    return '/controllers/tutorias.php?accion=' . rawurlencode($accion) . '&id=' . $id;
};

$pdfListaParams = array_filter(
    array_merge(['tipo' => 'lista'], $filtros),
    static fn ($valor) => $valor !== '' && $valor !== 0 && $valor !== null
);
$pdfListaUrl = '/controllers/tutoria_pdf.php?' . http_build_query($pdfListaParams);
?>

<?php if ($accion === 'listar'): ?>
    <div class="toolbar">
        <div>
            <h1 class="page-title">Tutorías académicas</h1>
            <p class="subtitle">
                Calendario, solicitudes, filtros, validaciones y seguimiento de tutorías.
            </p>
        </div>

        <div class="actions">
            <a class="btn btn-light" href="<?= e($pdfListaUrl) ?>">PDF del listado</a>
            <?php if ($puedeCrear): ?>
                <a class="btn btn-primary" href="<?= e($crearUrl) ?>">+ Nueva tutoría</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card calendar-card">
        <div class="card-body">
            <div class="section-heading">
                <div>
                    <h2>Calendario de tutorías</h2>
                    <p>Vista mensual y semanal. Los colores representan el estado.</p>
                </div>
                <div class="calendar-legend">
                    <?php foreach ($estados as $estado => $texto): ?>
                        <span>
                            <i class="legend-dot legend-<?= e($estado) ?>"></i>
                            <?= e($texto) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div
                id="calendar"
                data-calendar-url="/controllers/tutorias_api.php"
                data-can-create="<?= $puedeCrear ? '1' : '0' ?>"
            ></div>
        </div>
    </div>

    <div class="card filter-card">
        <div class="card-body">
            <div class="section-heading">
                <div>
                    <h2>Filtros</h2>
                    <p>Los filtros se mantienen en la URL y también se aplican al PDF.</p>
                </div>
                <strong class="result-count">
                    <?= number_format($resultado['total']) ?> resultado<?= $resultado['total'] === 1 ? '' : 's' ?>
                </strong>
            </div>

            <form method="get" class="filter-form">
                <div class="filter-grid">
                    <div class="form-group">
                        <label class="form-label" for="q">Buscar</label>
                        <input
                            id="q"
                            name="q"
                            value="<?= e($filtros['q']) ?>"
                            placeholder="Estudiante, tutor, materia..."
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="">Todos</option>
                            <?php foreach ($estados as $valor => $texto): ?>
                                <option
                                    value="<?= e($valor) ?>"
                                    <?= $filtros['estado'] === $valor ? 'selected' : '' ?>
                                >
                                    <?= e($texto) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="id_tutor">Tutor</label>
                        <select id="id_tutor" name="id_tutor">
                            <option value="">Todos</option>
                            <?php foreach ($tutores as $tutor): ?>
                                <option
                                    value="<?= (int) $tutor['id_tutor'] ?>"
                                    <?= (int) $filtros['id_tutor'] === (int) $tutor['id_tutor'] ? 'selected' : '' ?>
                                >
                                    <?= e($tutor['nombre_completo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="id_materia">Materia</label>
                        <select id="id_materia" name="id_materia">
                            <option value="">Todas</option>
                            <?php foreach ($materias as $materia): ?>
                                <option
                                    value="<?= (int) $materia['id_materia'] ?>"
                                    <?= (int) $filtros['id_materia'] === (int) $materia['id_materia'] ? 'selected' : '' ?>
                                >
                                    <?= e($materia['nombre_materia']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modalidad">Modalidad</label>
                        <select id="modalidad" name="modalidad">
                            <option value="">Todas</option>
                            <option value="presencial" <?= $filtros['modalidad'] === 'presencial' ? 'selected' : '' ?>>
                                Presencial
                            </option>
                            <option value="virtual" <?= $filtros['modalidad'] === 'virtual' ? 'selected' : '' ?>>
                                Virtual
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fecha_desde">Desde</label>
                        <input id="fecha_desde" type="date" name="fecha_desde" value="<?= e($filtros['fecha_desde']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fecha_hasta">Hasta</label>
                        <input id="fecha_hasta" type="date" name="fecha_hasta" value="<?= e($filtros['fecha_hasta']) ?>">
                    </div>

                    <div class="filter-actions">
                        <button class="btn btn-primary" type="submit">Aplicar filtros</button>
                        <a class="btn btn-light" href="/controllers/tutorias.php">Limpiar filtros</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table" id="tabla-tutorias">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Estudiante</th>
                            <th>Tutor</th>
                            <th>Materia</th>
                            <th>Modalidad</th>
                            <th>Estado</th>
                            <th>Reseña</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$resultado['items']): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty">No hay tutorías que coincidan con los filtros.</div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($resultado['items'] as $item): ?>
                        <tr>
                            <td>
                                <strong><?= e($item['fecha']) ?></strong>
                                <br>
                                <small>
                                    <?= e(substr($item['hora_inicio'], 0, 5)) ?>
                                    -
                                    <?= e(substr($item['hora_fin'], 0, 5)) ?>
                                </small>
                            </td>
                            <td><?= e($item['estudiante']) ?></td>
                            <td>
                                <a
                                    class="link-primary"
                                    href="/controllers/tutor_perfil.php?id=<?= (int) $item['id_tutor'] ?>"
                                >
                                    <?= e($item['profesor']) ?>
                                </a>
                            </td>
                            <td><?= e($item['nombre_materia']) ?></td>
                            <td><?= e(ucfirst($item['modalidad'])) ?></td>
                            <td>
                                <span class="badge <?= e($estadoClase($item['estado'])) ?>">
                                    <?= e(ucfirst($item['estado'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($item['evaluacion_calificacion']): ?>
                                    <span class="stars-readonly" aria-label="Calificación <?= (int) $item['evaluacion_calificacion'] ?> de 5">
                                        <?= str_repeat('★', (int) $item['evaluacion_calificacion']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="muted-text">Sin reseña</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-light btn-sm" href="<?= e($detalleUrl((int) $item['id_tutoria'])) ?>">
                                        Ver
                                    </a>

                                    <?php
                                    $puedeEditarFila = $rol === 'administrador'
                                        || (
                                            $rol === 'profesor'
                                            && (int) $item['profesor_usuario'] === (int) $_SESSION['id_usuario']
                                        )
                                        || (
                                            $rol === 'estudiante'
                                            && (int) $item['estudiante_usuario'] === (int) $_SESSION['id_usuario']
                                        );

                                    $puedeEliminarFila = $rol === 'administrador'
                                        || (
                                            $rol === 'profesor'
                                            && (int) $item['profesor_usuario'] === (int) $_SESSION['id_usuario']
                                            && in_array($item['estado'], ['pendiente', 'confirmada'], true)
                                        )
                                        || (
                                            $rol === 'estudiante'
                                            && (int) $item['estudiante_usuario'] === (int) $_SESSION['id_usuario']
                                            && $item['estado'] === 'pendiente'
                                        );
                                    ?>

                                    <?php if ($puedeEditarFila && ($rol !== 'estudiante' || $item['estado'] === 'pendiente')): ?>
                                        <a class="btn btn-light btn-sm" href="<?= e($detalleUrl((int) $item['id_tutoria'], 'editar')) ?>">
                                            Editar
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($puedeEliminarFila): ?>
                                        <form method="post">
                                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?= (int) $item['id_tutoria'] ?>">
                                            <button
                                                class="btn btn-danger btn-sm"
                                                type="submit"
                                                data-confirm="¿Eliminar esta tutoría?"
                                            >
                                                Eliminar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($resultado['paginas'] > 1): ?>
                <nav class="pagination" aria-label="Paginación de tutorías">
                    <?php if ($resultado['pagina'] > 1): ?>
                        <a class="btn btn-light btn-sm" href="<?= e($urlPagina($resultado['pagina'] - 1)) ?>">
                            ← Anterior
                        </a>
                    <?php endif; ?>

                    <?php
                    $inicioPagina = max(1, $resultado['pagina'] - 2);
                    $finPagina = min($resultado['paginas'], $resultado['pagina'] + 2);
                    ?>

                    <?php for ($numero = $inicioPagina; $numero <= $finPagina; $numero++): ?>
                        <a
                            class="btn <?= $numero === $resultado['pagina'] ? 'btn-primary' : 'btn-light' ?> btn-sm"
                            href="<?= e($urlPagina($numero)) ?>"
                            aria-current="<?= $numero === $resultado['pagina'] ? 'page' : 'false' ?>"
                        >
                            <?= $numero ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($resultado['pagina'] < $resultado['paginas']): ?>
                        <a class="btn btn-light btn-sm" href="<?= e($urlPagina($resultado['pagina'] + 1)) ?>">
                            Siguiente →
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($accion === 'detalle' && $registro): ?>
    <div class="toolbar">
        <div>
            <a class="back-link" href="/controllers/tutorias.php">← Volver a tutorías</a>
            <h1 class="page-title">Detalle de tutoría #<?= (int) $registro['id_tutoria'] ?></h1>
            <p class="subtitle">Consulta completa de la sesión y su evaluación.</p>
        </div>
        <div class="actions">
            <a
                class="btn btn-light"
                href="/controllers/tutoria_pdf.php?tipo=detalle&id=<?= (int) $registro['id_tutoria'] ?>"
            >
                Exportar PDF
            </a>
            <?php if ($puedeEditarDetalle && ($rol !== 'estudiante' || $registro['estado'] === 'pendiente')): ?>
                <a class="btn btn-primary" href="<?= e($detalleUrl((int) $registro['id_tutoria'], 'editar')) ?>">
                    Editar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="detail-grid">
        <div class="card">
            <div class="card-body">
                <div class="detail-status">
                    <span class="badge <?= e($estadoClase($registro['estado'])) ?>">
                        <?= e(ucfirst($registro['estado'])) ?>
                    </span>
                    <span class="muted-text">
                        <?= e(ucfirst($registro['modalidad'])) ?>
                    </span>
                </div>

                <h2 class="detail-title"><?= e($registro['nombre_materia']) ?></h2>

                <div class="detail-list">
                    <div>
                        <span>Estudiante</span>
                        <strong><?= e($registro['estudiante']) ?></strong>
                    </div>
                    <div>
                        <span>Tutor</span>
                        <strong>
                            <a class="link-primary" href="/controllers/tutor_perfil.php?id=<?= (int) $registro['id_tutor'] ?>">
                                <?= e($registro['profesor']) ?>
                            </a>
                        </strong>
                    </div>
                    <div>
                        <span>Fecha</span>
                        <strong><?= e($registro['fecha']) ?></strong>
                    </div>
                    <div>
                        <span>Horario</span>
                        <strong>
                            <?= e(substr($registro['hora_inicio'], 0, 5)) ?>
                            -
                            <?= e(substr($registro['hora_fin'], 0, 5)) ?>
                        </strong>
                    </div>
                    <div>
                        <span>Lugar o enlace</span>
                        <strong><?= e($registro['lugar_o_enlace'] ?: 'No especificado') ?></strong>
                    </div>
                </div>

                <div class="detail-observations">
                    <span>Observaciones</span>
                    <p><?= nl2br(e($registro['observaciones'] ?: 'Sin observaciones.')) ?></p>
                </div>

                <div class="actions">
                    <?php if ($puedeEliminarDetalle): ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?= (int) $registro['id_tutoria'] ?>">
                            <button
                                class="btn btn-danger"
                                type="submit"
                                data-confirm="¿Eliminar esta tutoría?"
                            >
                                Eliminar tutoría
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h2>Evaluación</h2>

                <?php if ($registro['evaluacion_calificacion']): ?>
                    <div class="review-card">
                        <div class="stars-readonly large" aria-label="Calificación <?= (int) $registro['evaluacion_calificacion'] ?> de 5">
                            <?= str_repeat('★', (int) $registro['evaluacion_calificacion']) ?>
                        </div>
                        <p><?= nl2br(e($registro['evaluacion_comentario'] ?: 'Sin comentario.')) ?></p>
                        <small class="muted-text">
                            <?= e($registro['fecha_evaluacion']) ?>
                        </small>
                    </div>

                    <?php if ($rol === 'administrador'): ?>
                        <form method="post" action="/controllers/evaluacion_tutoria.php" class="review-delete-form">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_tutoria" value="<?= (int) $registro['id_tutoria'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit" data-confirm="¿Eliminar esta reseña?">
                                Eliminar reseña
                            </button>
                        </form>
                    <?php endif; ?>
                <?php elseif ($rol === 'estudiante' && $registro['estado'] === 'realizada' && (int) $registro['estudiante_usuario'] === (int) $_SESSION['id_usuario']): ?>
                    <form method="post" action="/controllers/evaluacion_tutoria.php" class="review-form">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="accion" value="guardar">
                        <input type="hidden" name="id_tutoria" value="<?= (int) $registro['id_tutoria'] ?>">

                        <label class="form-label">Tu calificación</label>
                        <div class="star-input" data-star-input>
                            <?php for ($estrella = 5; $estrella >= 1; $estrella--): ?>
                                <input
                                    id="star-<?= $estrella ?>"
                                    type="radio"
                                    name="calificacion"
                                    value="<?= $estrella ?>"
                                    required
                                >
                                <label for="star-<?= $estrella ?>" title="<?= $estrella ?> estrella<?= $estrella === 1 ? '' : 's' ?>">★</label>
                            <?php endfor; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="comentario">Comentario</label>
                            <textarea
                                id="comentario"
                                name="comentario"
                                placeholder="Cuéntanos cómo fue la tutoría..."
                            ></textarea>
                        </div>

                        <button class="btn btn-primary" type="submit">Guardar reseña</button>
                    </form>
                <?php else: ?>
                    <div class="empty compact">
                        <?= $registro['estado'] === 'realizada'
                            ? 'Esta tutoría todavía no tiene una reseña.'
                            : 'La reseña estará disponible cuando la tutoría pase a realizada.' ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php elseif (in_array($accion, ['crear', 'editar'], true)): ?>
    <?php
    $esEdicion = $accion === 'editar' && $registro;
    $idFormulario = $esEdicion ? (int) $registro['id_tutoria'] : 0;
    $tutorSeleccionado = (int) ($formData['id_tutor'] ?? 0);
    $materiaSeleccionada = (int) ($formData['id_materia'] ?? 0);
    ?>

    <div class="toolbar">
        <div>
            <a class="back-link" href="/controllers/tutorias.php">← Volver a tutorías</a>
            <h1 class="page-title"><?= $esEdicion ? 'Editar tutoría' : 'Nueva tutoría' ?></h1>
            <p class="subtitle">
                El servidor valida fecha, disponibilidad y choques de horario antes de guardar.
            </p>
        </div>
    </div>

    <?php if ($formError): ?>
        <div class="alert alert-error"><?= e($formError) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" id="form-tutoria" novalidate>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" value="<?= $idFormulario ?>">

                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="id_estudiante">Estudiante</label>

                        <?php if ($rol === 'estudiante'): ?>
                            <input type="hidden" name="id_estudiante" value="<?= (int) ($formData['id_estudiante'] ?? 0) ?>">
                            <input
                                id="id_estudiante"
                                value="<?= e($estudianteActual['nombre'] . ' ' . $estudianteActual['apellido']) ?>"
                                readonly
                            >
                        <?php else: ?>
                            <select required id="id_estudiante" name="id_estudiante">
                                <option value="">Selecciona un estudiante</option>
                                <?php foreach ($estudiantes as $estudiante): ?>
                                    <option
                                        value="<?= (int) $estudiante['id_estudiante'] ?>"
                                        <?= (int) ($formData['id_estudiante'] ?? 0) === (int) $estudiante['id_estudiante'] ? 'selected' : '' ?>
                                    >
                                        <?= e($estudiante['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="id_tutor">Tutor</label>

                        <?php if ($rol === 'profesor'): ?>
                            <input type="hidden" name="id_tutor" value="<?= $tutorSeleccionado ?>">
                            <input
                                id="id_tutor"
                                value="<?= e($tutorActual['nombre'] . ' ' . $tutorActual['apellido']) ?>"
                                readonly
                            >
                        <?php else: ?>
                            <select required id="id_tutor" name="id_tutor" data-tutor-selector>
                                <option value="">Selecciona un tutor</option>
                                <?php foreach ($tutores as $tutor): ?>
                                    <option
                                        value="<?= (int) $tutor['id_tutor'] ?>"
                                        <?= $tutorSeleccionado === (int) $tutor['id_tutor'] ? 'selected' : '' ?>
                                    >
                                        <?= e($tutor['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="id_materia">Materia</label>
                        <select required id="id_materia" name="id_materia" data-materia-selector>
                            <option value="">Selecciona una materia</option>
                            <?php foreach ($materiasPorTutor as $idTutorOption => $materiasTutor): ?>
                                <?php foreach ($materiasTutor as $materia): ?>
                                    <option
                                        value="<?= (int) $materia['id_materia'] ?>"
                                        data-tutor="<?= (int) $idTutorOption ?>"
                                        <?= $materiaSeleccionada === (int) $materia['id_materia'] ? 'selected' : '' ?>
                                    >
                                        <?= e($materia['nombre_materia']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-help">Solo se aceptarán materias asignadas al tutor.</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fecha">Fecha</label>
                        <input
                            required
                            id="fecha"
                            type="date"
                            name="fecha"
                            min="<?= e(date('Y-m-d')) ?>"
                            value="<?= e($formData['fecha'] ?? date('Y-m-d')) ?>"
                            data-tutoria-date
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="hora_inicio">Hora de inicio</label>
                        <input
                            required
                            id="hora_inicio"
                            type="time"
                            name="hora_inicio"
                            value="<?= e($formData['hora_inicio'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="hora_fin">Hora de fin</label>
                        <input
                            required
                            id="hora_fin"
                            type="time"
                            name="hora_fin"
                            value="<?= e($formData['hora_fin'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modalidad">Modalidad</label>
                        <select id="modalidad" name="modalidad">
                            <option value="presencial" <?= ($formData['modalidad'] ?? '') === 'presencial' ? 'selected' : '' ?>>
                                Presencial
                            </option>
                            <option value="virtual" <?= ($formData['modalidad'] ?? '') === 'virtual' ? 'selected' : '' ?>>
                                Virtual
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="lugar_o_enlace">Lugar o enlace</label>
                        <input
                            id="lugar_o_enlace"
                            name="lugar_o_enlace"
                            value="<?= e($formData['lugar_o_enlace'] ?? '') ?>"
                            placeholder="Aula 204 o enlace de Meet"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones"><?= e($formData['observaciones'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="estado">Estado</label>

                    <?php if ($rol === 'estudiante'): ?>
                        <input type="hidden" name="estado" value="pendiente">
                        <select id="estado" disabled>
                            <option selected>Pendiente</option>
                        </select>
                        <div class="form-help">Las solicitudes de estudiante se crean y mantienen como pendientes.</div>
                    <?php else: ?>
                        <select id="estado" name="estado">
                            <?php foreach ($estados as $valor => $texto): ?>
                                <option
                                    value="<?= e($valor) ?>"
                                    <?= ($formData['estado'] ?? 'pendiente') === $valor ? 'selected' : '' ?>
                                >
                                    <?= e($texto) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="form-note">
                    <strong>Validación automática:</strong>
                    el horario debe estar dentro de la disponibilidad del tutor y no puede
                    solaparse con otra tutoría pendiente o confirmada del estudiante o del tutor.
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">
                        <?= $esEdicion ? 'Guardar cambios' : 'Guardar tutoría' ?>
                    </button>
                    <a class="btn btn-light" href="/controllers/tutorias.php">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($accion === 'listar'): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.js"></script>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
