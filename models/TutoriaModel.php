<?php
class TutoriaValidationException extends RuntimeException
{
}

class TutoriaModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function obtenerListado(
        array $filtros,
        int $pagina,
        int $porPagina,
        string $rol,
        int $idUsuario
    ): array {
        $where = ['1=1'];
        $params = [];

        $this->agregarAlcance($where, $params, $rol, $idUsuario);

        if (($filtros['estado'] ?? '') !== '') {
            $where[] = 't.estado = ?';
            $params[] = $filtros['estado'];
        }

        if ((int) ($filtros['id_tutor'] ?? 0) > 0) {
            $where[] = 't.id_tutor = ?';
            $params[] = (int) $filtros['id_tutor'];
        }

        if ((int) ($filtros['id_materia'] ?? 0) > 0) {
            $where[] = 't.id_materia = ?';
            $params[] = (int) $filtros['id_materia'];
        }

        if (($filtros['modalidad'] ?? '') !== '') {
            $where[] = 't.modalidad = ?';
            $params[] = $filtros['modalidad'];
        }

        if (($filtros['fecha_desde'] ?? '') !== '') {
            $where[] = 't.fecha >= ?';
            $params[] = $filtros['fecha_desde'];
        }

        if (($filtros['fecha_hasta'] ?? '') !== '') {
            $where[] = 't.fecha <= ?';
            $params[] = $filtros['fecha_hasta'];
        }

        if (($filtros['q'] ?? '') !== '') {
            $like = '%' . $filtros['q'] . '%';
            $where[] = "(CONCAT(ue.nombre, ' ', ue.apellido) LIKE ?
                OR CONCAT(up.nombre, ' ', up.apellido) LIKE ?
                OR m.nombre_materia LIKE ?
                OR COALESCE(t.observaciones, '') LIKE ?)";
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "
            SELECT COUNT(*)
            FROM tutorias t
            JOIN estudiantes e ON e.id_estudiante = t.id_estudiante
            JOIN usuarios ue ON ue.id_usuario = e.id_usuario
            JOIN tutores tr ON tr.id_tutor = t.id_tutor
            JOIN profesores p ON p.id_profesor = tr.id_profesor
            JOIN usuarios up ON up.id_usuario = p.id_usuario
            JOIN materias m ON m.id_materia = t.id_materia
            WHERE {$whereSql}
        ";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $offset = max(0, ($pagina - 1) * $porPagina);
        $limit = max(1, min(100, $porPagina));

        $sql = "
            SELECT
                t.*,
                CONCAT(ue.nombre, ' ', ue.apellido) AS estudiante,
                ue.id_usuario AS estudiante_usuario,
                CONCAT(up.nombre, ' ', up.apellido) AS profesor,
                up.id_usuario AS profesor_usuario,
                m.nombre_materia,
                ev.calificacion AS evaluacion_calificacion,
                ev.comentario AS evaluacion_comentario
            FROM tutorias t
            JOIN estudiantes e ON e.id_estudiante = t.id_estudiante
            JOIN usuarios ue ON ue.id_usuario = e.id_usuario
            JOIN tutores tr ON tr.id_tutor = t.id_tutor
            JOIN profesores p ON p.id_profesor = tr.id_profesor
            JOIN usuarios up ON up.id_usuario = p.id_usuario
            JOIN materias m ON m.id_materia = t.id_materia
            LEFT JOIN evaluaciones_tutoria ev ON ev.id_tutoria = t.id_tutoria
            WHERE {$whereSql}
            ORDER BY t.fecha DESC, t.hora_inicio DESC, t.id_tutoria DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'paginas' => max(1, (int) ceil($total / $porPagina)),
        ];
    }

    public function obtenerDetalle(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                t.*,
                CONCAT(ue.nombre, ' ', ue.apellido) AS estudiante,
                ue.id_usuario AS estudiante_usuario,
                CONCAT(up.nombre, ' ', up.apellido) AS profesor,
                up.id_usuario AS profesor_usuario,
                tr.id_tutor,
                tr.especialidad AS tutor_especialidad,
                tr.biografia AS tutor_biografia,
                m.nombre_materia,
                ev.id_evaluacion,
                ev.calificacion AS evaluacion_calificacion,
                ev.comentario AS evaluacion_comentario,
                ev.fecha_evaluacion
            FROM tutorias t
            JOIN estudiantes e ON e.id_estudiante = t.id_estudiante
            JOIN usuarios ue ON ue.id_usuario = e.id_usuario
            JOIN tutores tr ON tr.id_tutor = t.id_tutor
            JOIN profesores p ON p.id_profesor = tr.id_profesor
            JOIN usuarios up ON up.id_usuario = p.id_usuario
            JOIN materias m ON m.id_materia = t.id_materia
            LEFT JOIN evaluaciones_tutoria ev ON ev.id_tutoria = t.id_tutoria
            WHERE t.id_tutoria = ?
        ");
        $stmt->execute([$id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tutorias WHERE id_tutoria = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function obtenerEventosCalendario(
        string $inicio,
        string $fin,
        string $rol,
        int $idUsuario
    ): array {
        $where = ['t.fecha >= ?', 't.fecha <= ?'];
        $params = [$inicio, $fin];

        $this->agregarAlcance($where, $params, $rol, $idUsuario);

        $sql = "
            SELECT
                t.id_tutoria,
                t.id_estudiante,
                t.id_tutor,
                t.id_materia,
                t.fecha,
                t.hora_inicio,
                t.hora_fin,
                t.estado,
                t.modalidad,
                t.observaciones,
                CONCAT(ue.nombre, ' ', ue.apellido) AS estudiante,
                CONCAT(up.nombre, ' ', up.apellido) AS profesor,
                m.nombre_materia
            FROM tutorias t
            JOIN estudiantes e ON e.id_estudiante = t.id_estudiante
            JOIN usuarios ue ON ue.id_usuario = e.id_usuario
            JOIN tutores tr ON tr.id_tutor = t.id_tutor
            JOIN profesores p ON p.id_profesor = tr.id_profesor
            JOIN usuarios up ON up.id_usuario = p.id_usuario
            JOIN materias m ON m.id_materia = t.id_materia
            WHERE " . implode(' AND ', $where) . "
            ORDER BY t.fecha, t.hora_inicio
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function obtenerTutores(): array
    {
        return $this->pdo->query("
            SELECT
                t.id_tutor,
                p.id_profesor,
                u.nombre,
                u.apellido,
                CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo,
                t.especialidad
            FROM tutores t
            JOIN profesores p ON p.id_profesor = t.id_profesor
            JOIN usuarios u ON u.id_usuario = p.id_usuario
            WHERE u.estado = 'activo'
            ORDER BY u.apellido, u.nombre
        ")->fetchAll();
    }

    public function obtenerEstudiantes(): array
    {
        return $this->pdo->query("
            SELECT
                e.id_estudiante,
                u.nombre,
                u.apellido,
                CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo
            FROM estudiantes e
            JOIN usuarios u ON u.id_usuario = e.id_usuario
            WHERE u.estado = 'activo'
            ORDER BY u.apellido, u.nombre
        ")->fetchAll();
    }

    public function obtenerMaterias(): array
    {
        return $this->pdo->query("
            SELECT id_materia, nombre_materia
            FROM materias
            ORDER BY nombre_materia
        ")->fetchAll();
    }

    public function obtenerMateriasPorTutor(int $idTutor): array
    {
        $stmt = $this->pdo->prepare("
            SELECT m.id_materia, m.nombre_materia
            FROM tutor_materia tm
            JOIN materias m ON m.id_materia = tm.id_materia
            WHERE tm.id_tutor = ?
            ORDER BY m.nombre_materia
        ");
        $stmt->execute([$idTutor]);

        return $stmt->fetchAll();
    }

    public function obtenerEstudiantePorUsuario(int $idUsuario): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT e.*, u.nombre, u.apellido
            FROM estudiantes e
            JOIN usuarios u ON u.id_usuario = e.id_usuario
            WHERE e.id_usuario = ?
        ");
        $stmt->execute([$idUsuario]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function obtenerTutorPorUsuario(int $idUsuario): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*, p.id_profesor
            FROM tutores t
            JOIN profesores p ON p.id_profesor = t.id_profesor
            WHERE p.id_usuario = ?
        ");
        $stmt->execute([$idUsuario]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function crear(array $datos): int
    {
        $this->pdo->beginTransaction();

        try {
            $this->validarDatos($datos);

            $stmt = $this->pdo->prepare("
                INSERT INTO tutorias (
                    id_estudiante,
                    id_tutor,
                    id_materia,
                    fecha,
                    hora_inicio,
                    hora_fin,
                    modalidad,
                    lugar_o_enlace,
                    estado,
                    observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                (int) $datos['id_estudiante'],
                (int) $datos['id_tutor'],
                (int) $datos['id_materia'],
                $datos['fecha'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                $datos['modalidad'],
                trim($datos['lugar_o_enlace'] ?? ''),
                $datos['estado'],
                trim($datos['observaciones'] ?? ''),
            ]);

            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();

            return $id;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function editar(int $id, array $datos): bool
    {
        $this->pdo->beginTransaction();

        try {
            $this->validarDatos($datos, $id);

            $stmt = $this->pdo->prepare("
                UPDATE tutorias
                SET
                    id_estudiante = ?,
                    id_tutor = ?,
                    id_materia = ?,
                    fecha = ?,
                    hora_inicio = ?,
                    hora_fin = ?,
                    modalidad = ?,
                    lugar_o_enlace = ?,
                    estado = ?,
                    observaciones = ?
                WHERE id_tutoria = ?
            ");

            $resultado = $stmt->execute([
                (int) $datos['id_estudiante'],
                (int) $datos['id_tutor'],
                (int) $datos['id_materia'],
                $datos['fecha'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                $datos['modalidad'],
                trim($datos['lugar_o_enlace'] ?? ''),
                $datos['estado'],
                trim($datos['observaciones'] ?? ''),
                $id,
            ]);

            $this->pdo->commit();

            return $resultado;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM tutorias WHERE id_tutoria = ?');

        return $stmt->execute([$id]);
    }

    public function eliminarSiPertenece(int $id, string $rol, int $idUsuario): bool
    {
        if ($rol === 'administrador') {
            return $this->eliminar($id);
        }

        if ($rol === 'profesor') {
            $stmt = $this->pdo->prepare("
                DELETE t
                FROM tutorias t
                JOIN tutores tr ON tr.id_tutor = t.id_tutor
                JOIN profesores p ON p.id_profesor = tr.id_profesor
                WHERE t.id_tutoria = ? AND p.id_usuario = ?
            ");
            $stmt->execute([$id, $idUsuario]);

            return $stmt->rowCount() > 0;
        }

        $stmt = $this->pdo->prepare("
            DELETE t
            FROM tutorias t
            JOIN estudiantes e ON e.id_estudiante = t.id_estudiante
            WHERE t.id_tutoria = ? AND e.id_usuario = ?
        ");
        $stmt->execute([$id, $idUsuario]);

        return $stmt->rowCount() > 0;
    }

    public function puedeEditar(int $id, string $rol, int $idUsuario): bool
    {
        if ($rol === 'administrador') {
            return true;
        }

        if ($rol === 'profesor') {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM tutorias t
                JOIN tutores tr ON tr.id_tutor = t.id_tutor
                JOIN profesores p ON p.id_profesor = tr.id_profesor
                WHERE t.id_tutoria = ? AND p.id_usuario = ?
            ");
            $stmt->execute([$id, $idUsuario]);

            return (int) $stmt->fetchColumn() > 0;
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM tutorias t
            JOIN estudiantes e ON e.id_estudiante = t.id_estudiante
            WHERE t.id_tutoria = ? AND e.id_usuario = ?
        ");
        $stmt->execute([$id, $idUsuario]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function obtenerEvaluacion(int $idTutoria): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM evaluaciones_tutoria WHERE id_tutoria = ?');
        $stmt->execute([$idTutoria]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function crearEvaluacion(int $idTutoria, int $calificacion, string $comentario): bool
    {
        if ($calificacion < 1 || $calificacion > 5) {
            throw new TutoriaValidationException('La calificación debe estar entre 1 y 5 estrellas.');
        }

        $detalle = $this->obtenerDetalle($idTutoria);

        if (!$detalle || $detalle['estado'] !== 'realizada') {
            throw new TutoriaValidationException('Solo puedes evaluar tutorías que ya fueron realizadas.');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO evaluaciones_tutoria (id_tutoria, calificacion, comentario)
            VALUES (?, ?, ?)
        ");

        try {
            return $stmt->execute([
                $idTutoria,
                $calificacion,
                trim($comentario),
            ]);
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                throw new TutoriaValidationException('Esta tutoría ya tiene una evaluación.');
            }

            throw $e;
        }
    }

    public function eliminarEvaluacion(int $idTutoria): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM evaluaciones_tutoria WHERE id_tutoria = ?');

        return $stmt->execute([$idTutoria]);
    }

    private function validarDatos(array $datos, int $idExcluir = 0): void
    {
        $idEstudiante = (int) ($datos['id_estudiante'] ?? 0);
        $idTutor = (int) ($datos['id_tutor'] ?? 0);
        $idMateria = (int) ($datos['id_materia'] ?? 0);
        $fecha = trim($datos['fecha'] ?? '');
        $horaInicio = trim($datos['hora_inicio'] ?? '');
        $horaFin = trim($datos['hora_fin'] ?? '');
        $modalidad = $datos['modalidad'] ?? '';
        $estado = $datos['estado'] ?? 'pendiente';

        if ($idEstudiante <= 0 || $idTutor <= 0 || $idMateria <= 0) {
            throw new TutoriaValidationException('Selecciona estudiante, tutor y materia.');
        }

        if (!in_array($modalidad, ['presencial', 'virtual'], true)) {
            throw new TutoriaValidationException('La modalidad seleccionada no es válida.');
        }

        if (!in_array($estado, ['pendiente', 'confirmada', 'realizada', 'cancelada'], true)) {
            throw new TutoriaValidationException('El estado seleccionado no es válido.');
        }

        $fechaObjeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        $erroresFecha = DateTimeImmutable::getLastErrors();

        if (
            !$fechaObjeto
            || ($erroresFecha !== false && ($erroresFecha['warning_count'] > 0 || $erroresFecha['error_count'] > 0))
        ) {
            throw new TutoriaValidationException('La fecha indicada no es válida.');
        }

        $hoy = new DateTimeImmutable('today');

        if ($fechaObjeto < $hoy) {
            throw new TutoriaValidationException('La fecha de la tutoría no puede ser pasada.');
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $horaInicio) || !preg_match('/^\d{2}:\d{2}$/', $horaFin)) {
            throw new TutoriaValidationException('Indica una hora de inicio y una hora de fin válidas.');
        }

        if ($horaFin <= $horaInicio) {
            throw new TutoriaValidationException('La hora de fin debe ser posterior a la hora de inicio.');
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM estudiantes WHERE id_estudiante = ?');
        $stmt->execute([$idEstudiante]);

        if ((int) $stmt->fetchColumn() === 0) {
            throw new TutoriaValidationException('El estudiante seleccionado no existe.');
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM tutores WHERE id_tutor = ?');
        $stmt->execute([$idTutor]);

        if ((int) $stmt->fetchColumn() === 0) {
            throw new TutoriaValidationException('El tutor seleccionado no existe.');
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM tutor_materia
            WHERE id_tutor = ? AND id_materia = ?
        ");
        $stmt->execute([$idTutor, $idMateria]);

        if ((int) $stmt->fetchColumn() === 0) {
            throw new TutoriaValidationException('El tutor seleccionado no tiene asignada esa materia.');
        }

        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miercoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sabado',
            7 => 'Domingo',
        ];
        $diaSemana = $dias[(int) $fechaObjeto->format('N')];

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM disponibilidad_tutor
            WHERE id_tutor = ?
              AND dia_semana = ?
              AND hora_inicio <= ?
              AND hora_fin >= ?
        ");
        $stmt->execute([$idTutor, $diaSemana, $horaInicio, $horaFin]);

        if ((int) $stmt->fetchColumn() === 0) {
            throw new TutoriaValidationException(
                "El horario está fuera de la disponibilidad del tutor para {$diaSemana}."
            );
        }

        if ($estado !== 'cancelada') {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM tutorias
                WHERE id_tutor = ?
                  AND fecha = ?
                  AND estado IN ('pendiente', 'confirmada')
                  AND id_tutoria <> ?
                  AND hora_inicio < ?
                  AND hora_fin > ?
            ");
            $stmt->execute([
                $idTutor,
                $fecha,
                $idExcluir,
                $horaFin,
                $horaInicio,
            ]);

            if ((int) $stmt->fetchColumn() > 0) {
                throw new TutoriaValidationException(
                    'El tutor ya tiene otra tutoría pendiente o confirmada que se solapa con ese horario.'
                );
            }

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM tutorias
                WHERE id_estudiante = ?
                  AND fecha = ?
                  AND estado IN ('pendiente', 'confirmada')
                  AND id_tutoria <> ?
                  AND hora_inicio < ?
                  AND hora_fin > ?
            ");
            $stmt->execute([
                $idEstudiante,
                $fecha,
                $idExcluir,
                $horaFin,
                $horaInicio,
            ]);

            if ((int) $stmt->fetchColumn() > 0) {
                throw new TutoriaValidationException(
                    'El estudiante ya tiene otra tutoría pendiente o confirmada que se solapa con ese horario.'
                );
            }
        }
    }

    private function agregarAlcance(
        array &$where,
        array &$params,
        string $rol,
        int $idUsuario
    ): void {
        if ($rol === 'profesor') {
            $where[] = 'p.id_usuario = ?';
            $params[] = $idUsuario;
        } elseif ($rol === 'estudiante') {
            $where[] = 'e.id_usuario = ?';
            $params[] = $idUsuario;
        }
    }
}
