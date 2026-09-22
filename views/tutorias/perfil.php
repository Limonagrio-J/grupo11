<?php
include __DIR__ . '/../layouts/header.php';

$iniciales = strtoupper(
    substr($perfil['nombre'], 0, 1) . substr($perfil['apellido'], 0, 1)
);
$promedio = (float) $perfil['promedio_estrellas'];
?>

<div class="toolbar">
    <div>
        <a class="back-link" href="/controllers/tutorias.php">← Volver a tutorías</a>
        <h1 class="page-title">Perfil del tutor</h1>
        <p class="subtitle">Información académica, disponibilidad y reseñas.</p>
    </div>

    <a
        class="btn btn-primary"
        href="/controllers/tutorias.php?accion=crear&id_tutor=<?= (int) $perfil['id_tutor'] ?>"
    >
        + Solicitar tutoría
    </a>
</div>

<?php if ($mensajeError): ?>
    <div class="alert alert-error"><?= e($mensajeError) ?></div>
<?php endif; ?>

<div class="profile-hero card">
    <div class="card-body">
        <div class="profile-avatar"><?= e($iniciales) ?></div>

        <div class="profile-main">
            <span class="badge badge-primary">Tutor académico</span>
            <h2><?= e($perfil['nombre'] . ' ' . $perfil['apellido']) ?></h2>
            <p class="profile-specialty"><?= e($perfil['especialidad'] ?: 'Especialidad no registrada') ?></p>
            <p class="profile-bio">
                <?= nl2br(e($perfil['biografia'] ?: 'Este tutor todavía no tiene una biografía registrada.')) ?>
            </p>
        </div>

        <div class="profile-stats">
            <div>
                <strong><?= number_format($promedio, 1) ?></strong>
                <span class="stars-readonly large"><?= e(str_repeat('★', (int) round($promedio)) . str_repeat('☆', 5 - (int) round($promedio))) ?></span>
                <small><?= (int) $perfil['total_resenas'] ?> reseña<?= (int) $perfil['total_resenas'] === 1 ? '' : 's' ?></small>
            </div>
            <div>
                <strong><?= (int) $perfil['tutorias_realizadas'] ?></strong>
                <small>Tutorías realizadas</small>
            </div>
        </div>
    </div>
</div>

<div class="profile-grid">
    <div class="card">
        <div class="card-body">
            <div class="section-heading">
                <div>
                    <h2>Materias que dicta</h2>
                    <p>Materias vinculadas mediante tutor_materia.</p>
                </div>
            </div>

            <?php if (!$materias): ?>
                <div class="empty compact">Todavía no tiene materias asignadas.</div>
            <?php else: ?>
                <div class="tag-list">
                    <?php foreach ($materias as $materia): ?>
                        <span class="tag"><?= e($materia['nombre_materia']) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($puedeGestionar): ?>
                <div class="management-block">
                    <h3>Gestionar materias</h3>

                    <?php if ($materiasDisponibles): ?>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="accion" value="asignar_materia">
                            <input type="hidden" name="id_tutor" value="<?= (int) $idTutor ?>">

                            <select required name="id_materia" aria-label="Materia para asignar">
                                <option value="">Selecciona una materia</option>
                                <?php foreach ($materiasDisponibles as $materia): ?>
                                    <option value="<?= (int) $materia['id_materia'] ?>">
                                        <?= e($materia['nombre_materia']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button class="btn btn-primary" type="submit">Asignar</button>
                        </form>
                    <?php else: ?>
                        <p class="muted-text">Todas las materias disponibles ya están asignadas.</p>
                    <?php endif; ?>

                    <?php if ($materias): ?>
                        <div class="management-list">
                            <?php foreach ($materias as $materia): ?>
                                <div class="management-row">
                                    <span><?= e($materia['nombre_materia']) ?></span>
                                    <form method="post">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="accion" value="quitar_materia">
                                        <input type="hidden" name="id_tutor" value="<?= (int) $idTutor ?>">
                                        <input type="hidden" name="id_materia" value="<?= (int) $materia['id_materia'] ?>">
                                        <button
                                            class="btn btn-danger btn-sm"
                                            type="submit"
                                            data-confirm="¿Retirar esta materia del tutor?"
                                        >
                                            Retirar
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="section-heading">
                <div>
                    <h2>Disponibilidad semanal</h2>
                    <p>Los horarios se usan para validar nuevas tutorías.</p>
                </div>
            </div>

            <?php if (!$disponibilidad): ?>
                <div class="empty compact">No hay horarios de disponibilidad registrados.</div>
            <?php else: ?>
                <div class="availability-list">
                    <?php foreach ($disponibilidad as $horario): ?>
                        <div class="availability-row">
                            <div>
                                <strong><?= e($horario['dia_semana']) ?></strong>
                                <span>
                                    <?= e(substr($horario['hora_inicio'], 0, 5)) ?>
                                    -
                                    <?= e(substr($horario['hora_fin'], 0, 5)) ?>
                                </span>
                            </div>

                            <?php if ($puedeGestionar): ?>
                                <form method="post">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="accion" value="eliminar_disponibilidad">
                                    <input type="hidden" name="id_tutor" value="<?= (int) $idTutor ?>">
                                    <input type="hidden" name="id_disponibilidad" value="<?= (int) $horario['id_disponibilidad'] ?>">
                                    <button
                                        class="btn btn-danger btn-sm"
                                        type="submit"
                                        data-confirm="¿Eliminar este horario de disponibilidad?"
                                    >
                                        Eliminar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($puedeGestionar): ?>
                <div class="management-block">
                    <h3>Agregar disponibilidad</h3>

                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="accion" value="guardar_disponibilidad">
                        <input type="hidden" name="id_tutor" value="<?= (int) $idTutor ?>">

                        <div class="grid grid-3">
                            <div class="form-group">
                                <label class="form-label" for="dia_semana">Día</label>
                                <select required id="dia_semana" name="dia_semana">
                                    <?php foreach (['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'] as $dia): ?>
                                        <option value="<?= e($dia) ?>"><?= e($dia) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="hora_inicio">Desde</label>
                                <input required id="hora_inicio" type="time" name="hora_inicio">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="hora_fin">Hasta</label>
                                <input required id="hora_fin" type="time" name="hora_fin">
                            </div>
                        </div>

                        <button class="btn btn-primary" type="submit">Agregar horario</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="section-heading">
            <div>
                <h2>Reseñas recientes</h2>
                <p>Comentarios registrados después de tutorías realizadas.</p>
            </div>
        </div>

        <?php if (!$resenas): ?>
            <div class="empty compact">Todavía no hay reseñas para este tutor.</div>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($resenas as $resena): ?>
                    <article class="review-item">
                        <div class="review-head">
                            <div>
                                <strong><?= e($resena['estudiante']) ?></strong>
                                <div class="stars-readonly" aria-label="Calificación <?= (int) $resena['calificacion'] ?> de 5">
                                    <?= str_repeat('★', (int) $resena['calificacion']) ?>
                                </div>
                            </div>
                            <small class="muted-text"><?= e($resena['fecha_evaluacion']) ?></small>
                        </div>
                        <p><?= nl2br(e($resena['comentario'] ?: 'Sin comentario.')) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
