<?php
class TutorModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function obtenerPerfil(int $idTutor): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                t.id_tutor,
                p.id_profesor,
                p.id_usuario,
                u.nombre,
                u.apellido,
                u.correo,
                t.especialidad,
                t.biografia,
                COUNT(DISTINCT CASE WHEN tu.estado = 'realizada' THEN tu.id_tutoria END) AS tutorias_realizadas,
                COALESCE(AVG(ev.calificacion), 0) AS promedio_estrellas,
                COUNT(DISTINCT ev.id_evaluacion) AS total_resenas
            FROM tutores t
            JOIN profesores p ON p.id_profesor = t.id_profesor
            JOIN usuarios u ON u.id_usuario = p.id_usuario
            LEFT JOIN tutorias tu ON tu.id_tutor = t.id_tutor
            LEFT JOIN evaluaciones_tutoria ev ON ev.id_tutoria = tu.id_tutoria
            WHERE t.id_tutor = ?
            GROUP BY
                t.id_tutor,
                p.id_profesor,
                p.id_usuario,
                u.nombre,
                u.apellido,
                u.correo,
                t.especialidad,
                t.biografia
        ");
        $stmt->execute([$idTutor]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function obtenerMaterias(int $idTutor): array
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

    public function obtenerMateriasDisponibles(int $idTutor): array
    {
        $stmt = $this->pdo->prepare("
            SELECT m.id_materia, m.nombre_materia
            FROM materias m
            WHERE NOT EXISTS (
                SELECT 1
                FROM tutor_materia tm
                WHERE tm.id_tutor = ? AND tm.id_materia = m.id_materia
            )
            ORDER BY m.nombre_materia
        ");
        $stmt->execute([$idTutor]);

        return $stmt->fetchAll();
    }

    public function obtenerDisponibilidad(int $idTutor): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM disponibilidad_tutor
            WHERE id_tutor = ?
            ORDER BY FIELD(
                dia_semana,
                'Lunes',
                'Martes',
                'Miercoles',
                'Jueves',
                'Viernes',
                'Sabado'
            ), hora_inicio
        ");
        $stmt->execute([$idTutor]);

        return $stmt->fetchAll();
    }

    public function obtenerResenas(int $idTutor, int $limite = 6): array
    {
        $limite = max(1, min(20, $limite));

        $stmt = $this->pdo->prepare("
            SELECT
                ev.id_evaluacion,
                ev.id_tutoria,
                ev.calificacion,
                ev.comentario,
                ev.fecha_evaluacion,
                CONCAT(u.nombre, ' ', u.apellido) AS estudiante
            FROM evaluaciones_tutoria ev
            JOIN tutorias t ON t.id_tutoria = ev.id_tutoria
            JOIN estudiantes e ON e.id_estudiante = t.id_estudiante
            JOIN usuarios u ON u.id_usuario = e.id_usuario
            WHERE t.id_tutor = ?
            ORDER BY ev.fecha_evaluacion DESC
            LIMIT {$limite}
        ");
        $stmt->execute([$idTutor]);

        return $stmt->fetchAll();
    }

    public function agregarMateria(int $idTutor, int $idMateria): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO tutor_materia (id_tutor, id_materia)
            VALUES (?, ?)
        ");

        try {
            return $stmt->execute([$idTutor, $idMateria]);
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                throw new RuntimeException('La materia ya está asignada a este tutor.');
            }

            throw $e;
        }
    }

    public function quitarMateria(int $idTutor, int $idMateria): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM tutor_materia
            WHERE id_tutor = ? AND id_materia = ?
        ");

        return $stmt->execute([$idTutor, $idMateria]);
    }

    public function agregarDisponibilidad(
        int $idTutor,
        string $dia,
        string $horaInicio,
        string $horaFin
    ): bool {
        $diasValidos = [
            'Lunes',
            'Martes',
            'Miercoles',
            'Jueves',
            'Viernes',
            'Sabado',
        ];

        if (!in_array($dia, $diasValidos, true)) {
            throw new RuntimeException('El día seleccionado no es válido.');
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $horaInicio) || !preg_match('/^\d{2}:\d{2}$/', $horaFin)) {
            throw new RuntimeException('Indica un horario válido.');
        }

        if ($horaFin <= $horaInicio) {
            throw new RuntimeException('La hora de fin debe ser posterior a la hora de inicio.');
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM disponibilidad_tutor
            WHERE id_tutor = ?
              AND dia_semana = ?
              AND hora_inicio < ?
              AND hora_fin > ?
        ");
        $stmt->execute([$idTutor, $dia, $horaFin, $horaInicio]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException('La disponibilidad se solapa con otro horario del tutor.');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO disponibilidad_tutor (id_tutor, dia_semana, hora_inicio, hora_fin)
            VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([$idTutor, $dia, $horaInicio, $horaFin]);
    }

    public function eliminarDisponibilidad(int $idDisponibilidad, int $idTutor): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM disponibilidad_tutor
            WHERE id_disponibilidad = ? AND id_tutor = ?
        ");

        return $stmt->execute([$idDisponibilidad, $idTutor]);
    }
}
