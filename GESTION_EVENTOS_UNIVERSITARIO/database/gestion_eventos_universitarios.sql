-- ============================================================
-- BASE DE DATOS: gestion_eventos_universitarios
-- Sistema de Gestión de Eventos Universitarios
-- Estructura completamente en español
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================
-- 1. CREAR Y SELECCIONAR BASE DE DATOS
-- ============================================================

CREATE DATABASE IF NOT EXISTS `gestion_eventos_universitarios`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `gestion_eventos_universitarios`;

-- Eliminar tablas si existen (en orden inverso de dependencias)
DROP TABLE IF EXISTS `certificados`;
DROP TABLE IF EXISTS `asistencias`;
DROP TABLE IF EXISTS `inscripciones`;
DROP TABLE IF EXISTS `eventos`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `categorias_eventos`;
DROP TABLE IF EXISTS `roles`;

-- También eliminar versiones anteriores en inglés si existían
DROP TABLE IF EXISTS `certificates`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `event_categories`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- 2. TABLA: roles
-- ============================================================

CREATE TABLE `roles` (
  `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nombre`          VARCHAR(50)      NOT NULL,
  `descripcion`     VARCHAR(255)     DEFAULT NULL,
  `fecha_creacion`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. TABLA: categorias_eventos
-- ============================================================

CREATE TABLE `categorias_eventos` (
  `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nombre`          VARCHAR(100)     NOT NULL,
  `descripcion`     VARCHAR(255)     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categorias_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. TABLA: usuarios
-- ============================================================

CREATE TABLE `usuarios` (
  `id`                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nombre`              VARCHAR(100)     NOT NULL,
  `apellido`            VARCHAR(100)     NOT NULL,
  `email`               VARCHAR(150)     NOT NULL,
  `password`            VARCHAR(255)     NOT NULL,
  `telefono`            VARCHAR(20)      DEFAULT NULL,
  `institucion`         VARCHAR(150)     DEFAULT NULL,
  `rol_id`              INT UNSIGNED     NOT NULL,
  `activo`              TINYINT(1)       NOT NULL DEFAULT 1,
  `fecha_registro`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  KEY `idx_usuarios_rol_id` (`rol_id`),
  KEY `idx_usuarios_activo` (`activo`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`)
    REFERENCES `roles` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. TABLA: eventos
-- ============================================================

CREATE TABLE `eventos` (
  `id`                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  `nombre`              VARCHAR(200)        NOT NULL,
  `descripcion`         TEXT                DEFAULT NULL,
  `categoria_id`        INT UNSIGNED        NOT NULL,
  `fecha_inicio`        DATE                NOT NULL,
  `fecha_fin`           DATE                NOT NULL,
  `hora_inicio`         TIME                NOT NULL,
  `hora_fin`            TIME                NOT NULL,
  `lugar`               VARCHAR(200)        DEFAULT NULL,
  `modalidad`           ENUM('presencial','virtual','hibrido') NOT NULL DEFAULT 'presencial',
  `cupo_maximo`         INT UNSIGNED        NOT NULL DEFAULT 0,
  `cupo_disponible`     INT UNSIGNED        NOT NULL DEFAULT 0,
  `organizador_id`      INT UNSIGNED        NOT NULL,
  `estado`              ENUM('programado','en_curso','finalizado','cancelado') NOT NULL DEFAULT 'programado',
  `imagen`              VARCHAR(255)        DEFAULT NULL,
  `fecha_creacion`      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_eventos_categoria_id`   (`categoria_id`),
  KEY `idx_eventos_organizador_id` (`organizador_id`),
  KEY `idx_eventos_estado`         (`estado`),
  KEY `idx_eventos_fecha_inicio`   (`fecha_inicio`),
  CONSTRAINT `fk_eventos_categoria`   FOREIGN KEY (`categoria_id`)
    REFERENCES `categorias_eventos` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT `fk_eventos_organizador` FOREIGN KEY (`organizador_id`)
    REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. TABLA: inscripciones
-- ============================================================

CREATE TABLE `inscripciones` (
  `id`                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `usuario_id`          INT UNSIGNED     NOT NULL,
  `evento_id`           INT UNSIGNED     NOT NULL,
  `fecha_inscripcion`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado`              ENUM('activa','cancelada') NOT NULL DEFAULT 'activa',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inscripciones_usuario_evento` (`usuario_id`, `evento_id`),
  KEY `idx_inscripciones_usuario_id` (`usuario_id`),
  KEY `idx_inscripciones_evento_id`  (`evento_id`),
  KEY `idx_inscripciones_estado`     (`estado`),
  CONSTRAINT `fk_inscripciones_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT `fk_inscripciones_evento` FOREIGN KEY (`evento_id`)
    REFERENCES `eventos` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. TABLA: asistencias
-- ============================================================

CREATE TABLE `asistencias` (
  `id`                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `inscripcion_id`      INT UNSIGNED     DEFAULT NULL,
  `usuario_id`          INT UNSIGNED     NOT NULL,
  `evento_id`           INT UNSIGNED     NOT NULL,
  `presente`            TINYINT(1)       NOT NULL DEFAULT 0,
  `fecha_registro`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `registrado_por`      INT UNSIGNED     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asistencias_usuario_evento` (`usuario_id`, `evento_id`),
  KEY `idx_asistencias_usuario_id` (`usuario_id`),
  KEY `idx_asistencias_evento_id`  (`evento_id`),
  KEY `idx_asistencias_presente`   (`presente`),
  CONSTRAINT `fk_asistencias_inscripcion` FOREIGN KEY (`inscripcion_id`)
    REFERENCES `inscripciones` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk_asistencias_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT `fk_asistencias_evento` FOREIGN KEY (`evento_id`)
    REFERENCES `eventos` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk_asistencias_registrador` FOREIGN KEY (`registrado_por`)
    REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. TABLA: certificados
-- ============================================================

CREATE TABLE `certificados` (
  `id`                      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `usuario_id`              INT UNSIGNED     NOT NULL,
  `evento_id`               INT UNSIGNED     NOT NULL,
  `codigo_certificado`      VARCHAR(50)      NOT NULL,
  `fecha_emision`           TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `porcentaje_asistencia`   DECIMAL(5,2)     NOT NULL DEFAULT 100.00,
  `estado`                  ENUM('emitido','revocado') NOT NULL DEFAULT 'emitido',
  `fecha_creacion`          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_certificados_codigo` (`codigo_certificado`),
  UNIQUE KEY `uq_certificados_usuario_evento` (`usuario_id`, `evento_id`),
  KEY `idx_certificados_usuario_id` (`usuario_id`),
  KEY `idx_certificados_evento_id`  (`evento_id`),
  KEY `idx_certificados_estado`     (`estado`),
  CONSTRAINT `fk_certificados_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT `fk_certificados_evento` FOREIGN KEY (`evento_id`)
    REFERENCES `eventos` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. DATOS INICIALES DEL SISTEMA (ROLES, CATEGORÍAS Y ADMIN)
-- ============================================================

-- Roles fundamentales del sistema
INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Administrador', 'Acceso total a todas las funciones y configuraciones del sistema.'),
(2, 'Organizador',   'Gestión de eventos asignados, control de asistencia y certificados.'),
(3, 'Participante',  'Inscripción a eventos, consulta de asistencia y descarga de certificados.');

-- Categorías iniciales para los eventos académicos
INSERT INTO `categorias_eventos` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Congreso',      'Reuniones académicas periódicas de conferencias sobre una disciplina.'),
(2, 'Conferencia',   'Disertación pública sobre temas científicos o académicos.'),
(3, 'Taller',        'Sesión práctica de aprendizaje y desarrollo de habilidades.'),
(4, 'Seminario',     'Reunión especializada de naturaleza técnica y académica.'),
(5, 'Capacitación',  'Actividad formativa orientada al perfeccionamiento profesional.'),
(6, 'Simposio',      'Compendio de expertos que exponen sobre diferentes aspectos de un tema.');

-- ============================================================
-- 10. USUARIO ADMINISTRADOR INICIAL
-- Credenciales de acceso:
-- Correo:      admin@universidad.edu
-- Contraseña:  Admin2024!
-- Hash Bcrypt: $2y$10$b2eKDL5yZ3rVwg/BWjhIZ.P/7C2iLYtsgZJnqRHt0Bt4cwY4zDtbW
-- ============================================================

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password`, `telefono`, `institucion`, `rol_id`, `activo`) VALUES
(1, 'Administrador', 'General', 'admin@universidad.edu', '$2y$10$b2eKDL5yZ3rVwg/BWjhIZ.P/7C2iLYtsgZJnqRHt0Bt4cwY4zDtbW', '555-0100', 'Universidad Central', 1, 1);

COMMIT;
