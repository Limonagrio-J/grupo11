USE student_portal_db;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER DATABASE student_portal_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

ALTER TABLE roles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE carreras CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE estudiantes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE profesores CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tutores CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE cursos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE materias CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE inscripciones CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE calificaciones CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE asistencia CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tutor_materia CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE disponibilidad_tutor CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tutorias CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE evaluaciones_tutoria CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE registro_accesos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @tutor1 := (
    SELECT t.id_tutor
    FROM tutores t
    JOIN profesores p ON p.id_profesor = t.id_profesor
    JOIN usuarios u ON u.id_usuario = p.id_usuario
    WHERE u.usuario = 'profesor1'
    LIMIT 1
);

SET @tutor2 := (
    SELECT t.id_tutor
    FROM tutores t
    JOIN profesores p ON p.id_profesor = t.id_profesor
    JOIN usuarios u ON u.id_usuario = p.id_usuario
    WHERE u.usuario = 'profesor2'
    LIMIT 1
);

SET @estudiante1 := (
    SELECT e.id_estudiante
    FROM estudiantes e
    JOIN usuarios u ON u.id_usuario = e.id_usuario
    WHERE u.usuario = 'estudiante1'
    LIMIT 1
);

SET @estudiante2 := (
    SELECT e.id_estudiante
    FROM estudiantes e
    JOIN usuarios u ON u.id_usuario = e.id_usuario
    WHERE u.usuario = 'estudiante2'
    LIMIT 1
);

SET @materia1 := (
    SELECT id_materia
    FROM materias
    WHERE nombre_materia = 'Base de Datos I'
    LIMIT 1
);

SET @materia3 := (
    SELECT id_materia
    FROM materias
    WHERE nombre_materia = 'Tecnología Web I'
    LIMIT 1
);

SET @materia4 := (
    SELECT id_materia
    FROM materias
    WHERE nombre_materia = 'Matemática Discreta'
    LIMIT 1
);

INSERT INTO tutor_materia (id_tutor, id_materia)
SELECT @tutor1, @materia1
WHERE @tutor1 IS NOT NULL
  AND @materia1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM tutor_materia
      WHERE id_tutor = @tutor1
        AND id_materia = @materia1
  );

INSERT INTO tutor_materia (id_tutor, id_materia)
SELECT @tutor1, @materia3
WHERE @tutor1 IS NOT NULL
  AND @materia3 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM tutor_materia
      WHERE id_tutor = @tutor1
        AND id_materia = @materia3
  );

INSERT INTO tutor_materia (id_tutor, id_materia)
SELECT @tutor2, @materia4
WHERE @tutor2 IS NOT NULL
  AND @materia4 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM tutor_materia
      WHERE id_tutor = @tutor2
        AND id_materia = @materia4
  );

INSERT INTO disponibilidad_tutor (id_tutor, dia_semana, hora_inicio, hora_fin)
SELECT @tutor1, 'Lunes', '14:00', '18:00'
WHERE @tutor1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM disponibilidad_tutor
      WHERE id_tutor = @tutor1
        AND dia_semana = 'Lunes'
        AND hora_inicio = '14:00'
        AND hora_fin = '18:00'
  );

INSERT INTO disponibilidad_tutor (id_tutor, dia_semana, hora_inicio, hora_fin)
SELECT @tutor1, 'Miercoles', '14:00', '18:00'
WHERE @tutor1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM disponibilidad_tutor
      WHERE id_tutor = @tutor1
        AND dia_semana = 'Miercoles'
        AND hora_inicio = '14:00'
        AND hora_fin = '18:00'
  );

INSERT INTO disponibilidad_tutor (id_tutor, dia_semana, hora_inicio, hora_fin)
SELECT @tutor2, 'Martes', '09:00', '12:00'
WHERE @tutor2 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM disponibilidad_tutor
      WHERE id_tutor = @tutor2
        AND dia_semana = 'Martes'
        AND hora_inicio = '09:00'
        AND hora_fin = '12:00'
  );

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
)
SELECT @estudiante1, @tutor1, @materia1, '2026-09-07', '14:00', '15:00', 'virtual',
       'https://meet.google.com/portal-demo', 'realizada', 'Repaso de consultas JOIN.'
WHERE @estudiante1 IS NOT NULL AND @tutor1 IS NOT NULL AND @materia1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante1 AND id_tutor = @tutor1
        AND fecha = '2026-09-07' AND hora_inicio = '14:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante2, @tutor2, @materia4, '2026-09-08', '09:00', '10:00',
       'presencial', 'Aula 204', 'realizada', 'Preparación para evaluación.'
WHERE @estudiante2 IS NOT NULL AND @tutor2 IS NOT NULL AND @materia4 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante2 AND id_tutor = @tutor2
        AND fecha = '2026-09-08' AND hora_inicio = '09:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante2, @tutor1, @materia3, '2026-09-09', '14:00', '15:00',
       'virtual', 'https://meet.google.com/web-demo', 'realizada', 'Revisión de formulario PHP.'
WHERE @estudiante2 IS NOT NULL AND @tutor1 IS NOT NULL AND @materia3 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante2 AND id_tutor = @tutor1
        AND fecha = '2026-09-09' AND hora_inicio = '14:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante1, @tutor1, @materia3, '2026-09-14', '15:00', '16:00',
       'presencial', 'Laboratorio 3', 'realizada', 'Repaso de MVC y sesiones.'
WHERE @estudiante1 IS NOT NULL AND @tutor1 IS NOT NULL AND @materia3 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante1 AND id_tutor = @tutor1
        AND fecha = '2026-09-14' AND hora_inicio = '15:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante1, @tutor2, @materia4, '2026-09-15', '10:00', '11:00',
       'presencial', 'Aula 204', 'cancelada', 'Se canceló por actividad institucional.'
WHERE @estudiante1 IS NOT NULL AND @tutor2 IS NOT NULL AND @materia4 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante1 AND id_tutor = @tutor2
        AND fecha = '2026-09-15' AND hora_inicio = '10:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante2, @tutor1, @materia1, '2026-09-16', '16:00', '17:00',
       'virtual', 'https://meet.google.com/bd-demo', 'realizada', 'Práctica de subconsultas.'
WHERE @estudiante2 IS NOT NULL AND @tutor1 IS NOT NULL AND @materia1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante2 AND id_tutor = @tutor1
        AND fecha = '2026-09-16' AND hora_inicio = '16:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante2, @tutor1, @materia1, '2026-09-21', '14:00', '15:00',
       'presencial', 'Laboratorio 2', 'confirmada', 'Preparación para parcial.'
WHERE @estudiante2 IS NOT NULL AND @tutor1 IS NOT NULL AND @materia1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante2 AND id_tutor = @tutor1
        AND fecha = '2026-09-21' AND hora_inicio = '14:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante1, @tutor2, @materia4, '2026-09-22', '09:00', '10:00',
       'virtual', 'https://meet.google.com/mat-demo', 'pendiente', 'Ejercicios de lógica proposicional.'
WHERE @estudiante1 IS NOT NULL AND @tutor2 IS NOT NULL AND @materia4 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante1 AND id_tutor = @tutor2
        AND fecha = '2026-09-22' AND hora_inicio = '09:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante1, @tutor1, @materia3, '2026-09-23', '15:00', '16:00',
       'virtual', 'https://meet.google.com/web-demo-2', 'confirmada', 'Revisión del proyecto web.'
WHERE @estudiante1 IS NOT NULL AND @tutor1 IS NOT NULL AND @materia3 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante1 AND id_tutor = @tutor1
        AND fecha = '2026-09-23' AND hora_inicio = '15:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante2, @tutor1, @materia3, '2026-09-28', '16:00', '17:00',
       'presencial', 'Laboratorio 3', 'pendiente', 'Práctica de JavaScript.'
WHERE @estudiante2 IS NOT NULL AND @tutor1 IS NOT NULL AND @materia3 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante2 AND id_tutor = @tutor1
        AND fecha = '2026-09-28' AND hora_inicio = '16:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante2, @tutor2, @materia4, '2026-09-29', '10:00', '11:00',
       'presencial', 'Aula 204', 'confirmada', 'Repaso de relaciones y conjuntos.'
WHERE @estudiante2 IS NOT NULL AND @tutor2 IS NOT NULL AND @materia4 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante2 AND id_tutor = @tutor2
        AND fecha = '2026-09-29' AND hora_inicio = '10:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante1, @tutor1, @materia1, '2026-09-30', '14:00', '15:00',
       'virtual', 'https://meet.google.com/sql-demo', 'pendiente', 'Preparación para consultas SQL.'
WHERE @estudiante1 IS NOT NULL AND @tutor1 IS NOT NULL AND @materia1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante1 AND id_tutor = @tutor1
        AND fecha = '2026-09-30' AND hora_inicio = '14:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante2, @tutor1, @materia1, '2026-10-05', '15:00', '16:00',
       'presencial', 'Laboratorio 2', 'confirmada', 'Modelado relacional.'
WHERE @estudiante2 IS NOT NULL AND @tutor1 IS NOT NULL AND @materia1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante2 AND id_tutor = @tutor1
        AND fecha = '2026-10-05' AND hora_inicio = '15:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante1, @tutor2, @materia4, '2026-10-06', '11:00', '12:00',
       'virtual', 'https://meet.google.com/mat-demo-2', 'pendiente', 'Ejercicios de matrices.'
WHERE @estudiante1 IS NOT NULL AND @tutor2 IS NOT NULL AND @materia4 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante1 AND id_tutor = @tutor2
        AND fecha = '2026-10-06' AND hora_inicio = '11:00'
  );

INSERT INTO tutorias (
    id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
    modalidad, lugar_o_enlace, estado, observaciones
)
SELECT @estudiante1, @tutor1, @materia3, '2026-10-07', '16:00', '17:00',
       'presencial', 'Laboratorio 3', 'confirmada', 'Revisión final del proyecto.'
WHERE @estudiante1 IS NOT NULL AND @tutor1 IS NOT NULL AND @materia3 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tutorias
      WHERE id_estudiante = @estudiante1 AND id_tutor = @tutor1
        AND fecha = '2026-10-07' AND hora_inicio = '16:00'
  );

INSERT INTO evaluaciones_tutoria (id_tutoria, calificacion, comentario)
SELECT t.id_tutoria, 5, 'Excelente explicación y seguimiento.'
FROM tutorias t
WHERE t.id_tutor = @tutor1
  AND t.id_estudiante = @estudiante1
  AND t.fecha = '2026-09-07'
  AND t.hora_inicio = '14:00'
  AND NOT EXISTS (
      SELECT 1
      FROM evaluaciones_tutoria ev
      WHERE ev.id_tutoria = t.id_tutoria
  );

INSERT INTO evaluaciones_tutoria (id_tutoria, calificacion, comentario)
SELECT t.id_tutoria, 4, 'Explicó los ejercicios con claridad.'
FROM tutorias t
WHERE t.id_tutor = @tutor2
  AND t.id_estudiante = @estudiante2
  AND t.fecha = '2026-09-08'
  AND t.hora_inicio = '09:00'
  AND NOT EXISTS (
      SELECT 1
      FROM evaluaciones_tutoria ev
      WHERE ev.id_tutoria = t.id_tutoria
  );

INSERT INTO evaluaciones_tutoria (id_tutoria, calificacion, comentario)
SELECT t.id_tutoria, 5, 'Me ayudó a resolver el problema y entender el código.'
FROM tutorias t
WHERE t.id_tutor = @tutor1
  AND t.id_estudiante = @estudiante2
  AND t.fecha = '2026-09-09'
  AND t.hora_inicio = '14:00'
  AND NOT EXISTS (
      SELECT 1
      FROM evaluaciones_tutoria ev
      WHERE ev.id_tutoria = t.id_tutoria
  );

INSERT INTO evaluaciones_tutoria (id_tutoria, calificacion, comentario)
SELECT t.id_tutoria, 4, 'Buena tutoría, especialmente en sesiones y MVC.'
FROM tutorias t
WHERE t.id_tutor = @tutor1
  AND t.id_estudiante = @estudiante1
  AND t.fecha = '2026-09-14'
  AND t.hora_inicio = '15:00'
  AND NOT EXISTS (
      SELECT 1
      FROM evaluaciones_tutoria ev
      WHERE ev.id_tutoria = t.id_tutoria
  );

INSERT INTO evaluaciones_tutoria (id_tutoria, calificacion, comentario)
SELECT t.id_tutoria, 5, 'La práctica de subconsultas fue muy útil.'
FROM tutorias t
WHERE t.id_tutor = @tutor1
  AND t.id_estudiante = @estudiante2
  AND t.fecha = '2026-09-16'
  AND t.hora_inicio = '16:00'
  AND NOT EXISTS (
      SELECT 1
      FROM evaluaciones_tutoria ev
      WHERE ev.id_tutoria = t.id_tutoria
  );
