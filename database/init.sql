CREATE DATABASE IF NOT EXISTS student_portal_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE student_portal_db;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS
    evaluaciones_tutoria,
    tutorias,
    asistencia,
    calificaciones,
    inscripciones,
    tutor_materia,
    disponibilidad_tutor,
    cursos,
    materias,
    estudiantes,
    profesores,
    tutores,
    carreras,
    usuarios,
    roles,
    registro_accesos;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(30) NOT NULL UNIQUE,
    descripcion VARCHAR(150) NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE carreras (
    id_carrera INT AUTO_INCREMENT PRIMARY KEY,
    nombre_carrera VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE estudiantes (
    id_estudiante INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    id_carrera INT NOT NULL,
    semestre TINYINT UNSIGNED NOT NULL,
    registro_universitario VARCHAR(30) UNIQUE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera) ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE profesores (
    id_profesor INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    especialidad VARCHAR(150) NULL,
    biografia TEXT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tutores (
    id_tutor INT AUTO_INCREMENT PRIMARY KEY,
    id_profesor INT NOT NULL UNIQUE,
    especialidad VARCHAR(150) NULL,
    biografia TEXT NULL,
    FOREIGN KEY (id_profesor) REFERENCES profesores(id_profesor) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    nombre_curso VARCHAR(150) NOT NULL,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    id_carrera INT NULL,
    id_profesor INT NULL,
    semestre TINYINT UNSIGNED NULL,
    descripcion TEXT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (id_profesor) REFERENCES profesores(id_profesor) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE materias (
    id_materia INT AUTO_INCREMENT PRIMARY KEY,
    nombre_materia VARCHAR(150) NOT NULL,
    id_carrera INT NULL,
    id_curso INT NULL,
    FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera) ON DELETE SET NULL,
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inscripciones (
    id_inscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_curso INT NOT NULL,
    fecha_inscripcion DATE NOT NULL,
    estado ENUM('activa', 'retirada', 'finalizada') NOT NULL DEFAULT 'activa',
    UNIQUE KEY uq_inscripcion (id_estudiante, id_curso),
    FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON DELETE CASCADE,
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE calificaciones (
    id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    nota DECIMAL(5, 2) NOT NULL,
    observacion VARCHAR(255) NULL,
    fecha DATE NOT NULL,
    FOREIGN KEY (id_inscripcion) REFERENCES inscripciones(id_inscripcion) ON DELETE CASCADE,
    CHECK (nota >= 0 AND nota <= 100)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE asistencia (
    id_asistencia INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('presente', 'ausente', 'justificado', 'tarde') NOT NULL,
    observacion VARCHAR(255) NULL,
    UNIQUE KEY uq_asistencia (id_inscripcion, fecha),
    FOREIGN KEY (id_inscripcion) REFERENCES inscripciones(id_inscripcion) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tutor_materia (
    id_tutor INT NOT NULL,
    id_materia INT NOT NULL,
    PRIMARY KEY (id_tutor, id_materia),
    FOREIGN KEY (id_tutor) REFERENCES tutores(id_tutor) ON DELETE CASCADE,
    FOREIGN KEY (id_materia) REFERENCES materias(id_materia) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE disponibilidad_tutor (
    id_disponibilidad INT AUTO_INCREMENT PRIMARY KEY,
    id_tutor INT NOT NULL,
    dia_semana ENUM(
        'Lunes',
        'Martes',
        'Miercoles',
        'Jueves',
        'Viernes',
        'Sabado'
    ) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    FOREIGN KEY (id_tutor) REFERENCES tutores(id_tutor) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tutorias (
    id_tutoria INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_tutor INT NOT NULL,
    id_materia INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    modalidad ENUM('presencial', 'virtual') NOT NULL DEFAULT 'presencial',
    lugar_o_enlace VARCHAR(200) NULL,
    estado ENUM('pendiente', 'confirmada', 'realizada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    observaciones TEXT NULL,
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON DELETE CASCADE,
    FOREIGN KEY (id_tutor) REFERENCES tutores(id_tutor) ON DELETE CASCADE,
    FOREIGN KEY (id_materia) REFERENCES materias(id_materia) ON DELETE CASCADE,
    INDEX idx_tutorias_fecha (fecha),
    INDEX idx_tutorias_tutor_fecha (id_tutor, fecha),
    INDEX idx_tutorias_estudiante_fecha (id_estudiante, fecha),
    INDEX idx_tutorias_estado (estado)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evaluaciones_tutoria (
    id_evaluacion INT AUTO_INCREMENT PRIMARY KEY,
    id_tutoria INT NOT NULL UNIQUE,
    calificacion TINYINT NOT NULL,
    comentario TEXT NULL,
    fecha_evaluacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tutoria) REFERENCES tutorias(id_tutoria) ON DELETE CASCADE,
    CHECK (calificacion BETWEEN 1 AND 5)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registro_accesos (
    id_acceso INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_origen VARCHAR(45),
    resultado ENUM('exitoso', 'fallido') NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (id_rol, nombre_rol, descripcion) VALUES
    (1, 'administrador', 'Gestión completa del portal'),
    (2, 'profesor', 'Gestión académica de sus cursos y tutorías asignadas'),
    (3, 'estudiante', 'Consulta y solicitud de tutorías propias');

INSERT INTO carreras (id_carrera, nombre_carrera) VALUES
    (1, 'Ingeniería de Sistemas'),
    (2, 'Administración de Empresas');

INSERT INTO usuarios (
    id_usuario,
    id_rol,
    nombre,
    apellido,
    correo,
    usuario,
    contrasena_hash,
    telefono
) VALUES
    (
        1,
        1,
        'Admin',
        'Sistema',
        'admin@studentportal.local',
        'admin',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '70000001'
    ),
    (
        2,
        2,
        'Carlos',
        'Docente',
        'profesor@studentportal.local',
        'profesor1',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '70000002'
    ),
    (
        3,
        3,
        'Maria',
        'Estudiante',
        'estudiante@studentportal.local',
        'estudiante1',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '70000003'
    ),
    (
        4,
        2,
        'Laura',
        'Rojas',
        'laura@studentportal.local',
        'profesor2',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '70000004'
    ),
    (
        5,
        3,
        'Juan',
        'Perez',
        'juan@studentportal.local',
        'estudiante2',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '70000005'
    );

INSERT INTO profesores (
    id_profesor,
    id_usuario,
    especialidad,
    biografia
) VALUES
    (
        1,
        2,
        'Desarrollo Web y Bases de Datos',
        'Docente especializado en desarrollo backend y arquitecturas web.'
    ),
    (
        2,
        4,
        'Matemática Aplicada',
        'Docente de matemática y análisis cuantitativo.'
    );

INSERT INTO tutores (
    id_tutor,
    id_profesor,
    especialidad,
    biografia
) VALUES
    (
        1,
        1,
        'Desarrollo Web y Bases de Datos',
        'Tutor académico especializado en PHP, bases de datos y desarrollo web.'
    ),
    (
        2,
        2,
        'Matemática Aplicada',
        'Tutor académico para refuerzo de matemática y razonamiento cuantitativo.'
    );

INSERT INTO estudiantes (
    id_estudiante,
    id_usuario,
    id_carrera,
    semestre,
    registro_universitario
) VALUES
    (1, 3, 1, 4, 'RU-2026-98765'),
    (2, 5, 1, 2, 'RU-2026-12345');

INSERT INTO cursos (
    id_curso,
    nombre_curso,
    codigo,
    id_carrera,
    id_profesor,
    semestre,
    descripcion
) VALUES
    (
        1,
        'Base de Datos I',
        'BD-101',
        1,
        1,
        3,
        'Fundamentos de modelado, SQL y bases de datos.'
    ),
    (
        2,
        'Programación I',
        'PR-101',
        1,
        1,
        1,
        'Algoritmos y fundamentos de programación.'
    ),
    (
        3,
        'Tecnología Web I',
        'WEB-101',
        1,
        2,
        4,
        'Desarrollo web con PHP, HTML, CSS y JavaScript.'
    ),
    (
        4,
        'Matemática Discreta',
        'MAT-101',
        1,
        2,
        2,
        'Lógica, conjuntos, relaciones y estructuras discretas.'
    );

INSERT INTO materias (
    id_materia,
    nombre_materia,
    id_carrera,
    id_curso
) VALUES
    (1, 'Base de Datos I', 1, 1),
    (2, 'Programación I', 1, 2),
    (3, 'Tecnología Web I', 1, 3),
    (4, 'Matemática Discreta', 1, 4);

INSERT INTO tutor_materia (id_tutor, id_materia) VALUES
    (1, 1),
    (1, 3),
    (2, 4);

INSERT INTO disponibilidad_tutor (
    id_disponibilidad,
    id_tutor,
    dia_semana,
    hora_inicio,
    hora_fin
) VALUES
    (1, 1, 'Lunes', '14:00', '18:00'),
    (2, 1, 'Miercoles', '14:00', '18:00'),
    (3, 2, 'Martes', '09:00', '12:00');

INSERT INTO inscripciones (
    id_inscripcion,
    id_estudiante,
    id_curso,
    fecha_inscripcion,
    estado
) VALUES
    (1, 1, 1, '2026-02-02', 'activa'),
    (2, 1, 2, '2026-02-02', 'activa'),
    (3, 1, 3, '2026-02-02', 'activa'),
    (4, 2, 1, '2026-02-03', 'activa'),
    (5, 2, 4, '2026-02-03', 'activa');

INSERT INTO calificaciones (
    id_inscripcion,
    tipo,
    nota,
    observacion,
    fecha
) VALUES
    (1, 'Parcial 1', 86, 'Buen dominio de SQL', '2026-03-15'),
    (1, 'Trabajo práctico', 92, 'Excelente modelado', '2026-04-02'),
    (2, 'Parcial 1', 78, 'Debe reforzar ciclos', '2026-03-14'),
    (3, 'Proyecto', 95, 'Proyecto completo', '2026-04-20'),
    (4, 'Parcial 1', 73, 'Reforzar consultas', '2026-03-15'),
    (5, 'Parcial 1', 88, 'Buen desempeño', '2026-03-16');

INSERT INTO asistencia (
    id_inscripcion,
    fecha,
    estado,
    observacion
) VALUES
    (1, '2026-03-10', 'presente', NULL),
    (1, '2026-03-17', 'presente', NULL),
    (1, '2026-03-24', 'tarde', 'Llegó 10 minutos tarde'),
    (2, '2026-03-11', 'presente', NULL),
    (2, '2026-03-18', 'ausente', 'Sin justificativo'),
    (3, '2026-03-12', 'presente', NULL),
    (3, '2026-03-19', 'presente', NULL),
    (4, '2026-03-10', 'presente', NULL),
    (5, '2026-03-12', 'justificado', 'Certificación médica');

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
) VALUES
    (1, 1, 1, '2026-09-07', '14:00', '15:00', 'virtual', 'https://meet.google.com/portal-demo', 'realizada', 'Repaso de consultas JOIN.'),
    (2, 2, 4, '2026-09-08', '09:00', '10:00', 'presencial', 'Aula 204', 'realizada', 'Preparación para evaluación.'),
    (2, 1, 3, '2026-09-09', '14:00', '15:00', 'virtual', 'https://meet.google.com/web-demo', 'realizada', 'Revisión de formulario PHP.'),
    (1, 1, 3, '2026-09-14', '15:00', '16:00', 'presencial', 'Laboratorio 3', 'realizada', 'Repaso de MVC y sesiones.'),
    (1, 2, 4, '2026-09-15', '10:00', '11:00', 'presencial', 'Aula 204', 'cancelada', 'Se canceló por actividad institucional.'),
    (2, 1, 1, '2026-09-16', '16:00', '17:00', 'virtual', 'https://meet.google.com/bd-demo', 'realizada', 'Práctica de subconsultas.'),
    (2, 1, 1, '2026-09-21', '14:00', '15:00', 'presencial', 'Laboratorio 2', 'confirmada', 'Preparación para parcial.'),
    (1, 2, 4, '2026-09-22', '09:00', '10:00', 'virtual', 'https://meet.google.com/mat-demo', 'pendiente', 'Ejercicios de lógica proposicional.'),
    (1, 1, 3, '2026-09-23', '15:00', '16:00', 'virtual', 'https://meet.google.com/web-demo-2', 'confirmada', 'Revisión del proyecto web.'),
    (2, 1, 3, '2026-09-28', '16:00', '17:00', 'presencial', 'Laboratorio 3', 'pendiente', 'Práctica de JavaScript.'),
    (2, 2, 4, '2026-09-29', '10:00', '11:00', 'presencial', 'Aula 204', 'confirmada', 'Repaso de relaciones y conjuntos.'),
    (1, 1, 1, '2026-09-30', '14:00', '15:00', 'virtual', 'https://meet.google.com/sql-demo', 'pendiente', 'Preparación para consultas SQL.'),
    (2, 1, 1, '2026-10-05', '15:00', '16:00', 'presencial', 'Laboratorio 2', 'confirmada', 'Modelado relacional.'),
    (1, 2, 4, '2026-10-06', '11:00', '12:00', 'virtual', 'https://meet.google.com/mat-demo-2', 'pendiente', 'Ejercicios de matrices.'),
    (1, 1, 3, '2026-10-07', '16:00', '17:00', 'presencial', 'Laboratorio 3', 'confirmada', 'Revisión final del proyecto.');

INSERT INTO evaluaciones_tutoria (
    id_tutoria,
    calificacion,
    comentario
) VALUES
    (1, 5, 'Excelente explicación y seguimiento.'),
    (2, 4, 'Explicó los ejercicios con claridad.'),
    (3, 5, 'Me ayudó a resolver el problema y entender el código.'),
    (4, 4, 'Buena tutoría, especialmente en sesiones y MVC.'),
    (6, 5, 'La práctica de subconsultas fue muy útil.');
