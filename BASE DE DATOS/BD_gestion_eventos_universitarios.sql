-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 20-11-2026 a las 23:38:26
-- Versión del servidor: 8.4.7
-- Versión de PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gestion_eventos_universitarios`
--
CREATE DATABASE IF NOT EXISTS `gestion_eventos_universitarios` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gestion_eventos_universitarios`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

DROP TABLE IF EXISTS `asistencias`;
CREATE TABLE IF NOT EXISTS `asistencias` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `inscripcion_id` int UNSIGNED DEFAULT NULL,
  `usuario_id` int UNSIGNED NOT NULL,
  `evento_id` int UNSIGNED NOT NULL,
  `presente` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `registrado_por` int UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asistencias_usuario_evento` (`usuario_id`,`evento_id`),
  KEY `idx_asistencias_usuario_id` (`usuario_id`),
  KEY `idx_asistencias_evento_id` (`evento_id`),
  KEY `idx_asistencias_presente` (`presente`),
  KEY `fk_asistencias_inscripcion` (`inscripcion_id`),
  KEY `fk_asistencias_registrador` (`registrado_por`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asistencias`
--

INSERT INTO `asistencias` (`id`, `inscripcion_id`, `usuario_id`, `evento_id`, `presente`, `fecha_registro`, `registrado_por`) VALUES
(2, 1, 4, 2, 1, '2026-11-20 21:10:27', 2),
(3, 5, 4, 3, 1, '2026-11-20 23:01:25', 5),
(5, 4, 3, 3, 1, '2026-11-20 23:01:35', 5),
(6, 6, 6, 3, 1, '2026-11-20 23:34:03', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_eventos`
--

DROP TABLE IF EXISTS `categorias_eventos`;
CREATE TABLE IF NOT EXISTS `categorias_eventos` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categorias_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias_eventos`
--

INSERT INTO `categorias_eventos` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Congreso', 'Reuniones académicas periódicas de conferencias sobre una disciplina.'),
(2, 'Conferencia', 'Disertación pública sobre temas científicos o académicos.'),
(3, 'Taller', 'Sesión práctica de aprendizaje y desarrollo de habilidades.'),
(4, 'Seminario', 'Reunión especializada de naturaleza técnica y académica.'),
(5, 'Capacitación', 'Actividad formativa orientada al perfeccionamiento profesional.'),
(6, 'Simposio', 'Compendio de expertos que exponen sobre diferentes aspectos de un tema.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `certificados`
--

DROP TABLE IF EXISTS `certificados`;
CREATE TABLE IF NOT EXISTS `certificados` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` int UNSIGNED NOT NULL,
  `evento_id` int UNSIGNED NOT NULL,
  `codigo_certificado` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_emision` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `porcentaje_asistencia` decimal(5,2) NOT NULL DEFAULT '100.00',
  `estado` enum('emitido','revocado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'emitido',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_certificados_codigo` (`codigo_certificado`),
  UNIQUE KEY `uq_certificados_usuario_evento` (`usuario_id`,`evento_id`),
  KEY `idx_certificados_usuario_id` (`usuario_id`),
  KEY `idx_certificados_evento_id` (`evento_id`),
  KEY `idx_certificados_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `certificados`
--

INSERT INTO `certificados` (`id`, `usuario_id`, `evento_id`, `codigo_certificado`, `fecha_emision`, `porcentaje_asistencia`, `estado`, `fecha_creacion`) VALUES
(1, 4, 2, 'CERT-002-2026-072E1B0A', '2026-11-20 21:23:36', 100.00, 'emitido', '2026-11-20 21:23:36'),
(2, 4, 3, 'CERT-3-4-AE62BD', '2026-11-20 23:13:11', 100.00, 'emitido', '2026-11-20 23:13:11'),
(3, 3, 3, 'CERT-3-3-172E67', '2026-11-20 23:13:11', 100.00, 'emitido', '2026-11-20 23:13:11'),
(4, 6, 3, 'CERT-3-6-F0D837', '2026-11-20 23:34:12', 100.00, 'emitido', '2026-11-20 23:34:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

DROP TABLE IF EXISTS `eventos`;
CREATE TABLE IF NOT EXISTS `eventos` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `categoria_id` int UNSIGNED NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `lugar` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modalidad` enum('presencial','virtual','hibrido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'presencial',
  `cupo_maximo` int UNSIGNED NOT NULL DEFAULT '0',
  `cupo_disponible` int UNSIGNED NOT NULL DEFAULT '0',
  `organizador_id` int UNSIGNED NOT NULL,
  `estado` enum('programado','en_curso','finalizado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'programado',
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_eventos_categoria_id` (`categoria_id`),
  KEY `idx_eventos_organizador_id` (`organizador_id`),
  KEY `idx_eventos_estado` (`estado`),
  KEY `idx_eventos_fecha_inicio` (`fecha_inicio`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`id`, `nombre`, `descripcion`, `categoria_id`, `fecha_inicio`, `fecha_fin`, `hora_inicio`, `hora_fin`, `lugar`, `modalidad`, `cupo_maximo`, `cupo_disponible`, `organizador_id`, `estado`, `imagen`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Taller práctico de inteligencia artificial y productividad', 'Aprende a utilizar herramientas de IA generativa para optimizar tareas diarias', 3, '2026-11-21', '2026-11-21', '09:00:00', '12:00:00', 'Auditorio Principal', 'presencial', 50, 45, 2, 'programado', '', '2026-11-19 23:28:55', '2026-11-20 23:26:34'),
(2, 'Capacitación en seguridad informática y protección de datos.', 'Sesión formativa dirigida al personal para identificar amenazas cibernéticas.', 5, '2026-11-19', '2026-11-19', '17:30:00', '19:00:00', 'Enlace de Google meet', 'virtual', 50, 49, 2, 'finalizado', '', '2026-11-19 23:34:56', '2026-11-20 22:32:43'),
(3, 'Conferencia Magistral: Innovación y Sostenibilidad en el Agro', 'Charla abierta impartida por expertos invitados sobre el impacto de las nuevas tecnologías en la producción agrícola sostenible y los retos actuales del sector', 2, '2026-11-20', '2026-11-20', '17:00:00', '17:10:00', 'Enlace de Google meet', 'virtual', 30, 27, 5, 'finalizado', '', '2026-11-20 22:41:44', '2026-11-20 23:11:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

DROP TABLE IF EXISTS `inscripciones`;
CREATE TABLE IF NOT EXISTS `inscripciones` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` int UNSIGNED NOT NULL,
  `evento_id` int UNSIGNED NOT NULL,
  `fecha_inscripcion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activa','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inscripciones_usuario_evento` (`usuario_id`,`evento_id`),
  KEY `idx_inscripciones_usuario_id` (`usuario_id`),
  KEY `idx_inscripciones_evento_id` (`evento_id`),
  KEY `idx_inscripciones_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`id`, `usuario_id`, `evento_id`, `fecha_inscripcion`, `estado`) VALUES
(1, 4, 2, '2026-11-19 20:33:34', 'activa'),
(2, 4, 1, '2026-11-20 20:34:08', 'activa'),
(3, 3, 1, '2026-11-20 22:31:37', 'activa'),
(4, 3, 3, '2026-11-20 22:49:19', 'activa'),
(5, 4, 3, '2026-11-20 22:50:38', 'activa'),
(6, 6, 3, '2026-11-20 22:57:48', 'activa'),
(7, 6, 1, '2026-11-20 22:57:52', 'activa'),
(8, 7, 1, '2026-11-20 23:26:20', 'activa'),
(9, 8, 1, '2026-11-20 23:26:34', 'activa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `fecha_creacion`) VALUES
(1, 'Administrador', 'Acceso total a todas las funciones y configuraciones del sistema.', '2026-11-19 22:54:51'),
(2, 'Organizador', 'Gestión de eventos asignados, control de asistencia y certificados.', '2026-11-19 22:54:51'),
(3, 'Participante', 'Inscripción a eventos, consulta de asistencia y descarga de certificados.', '2026-11-19 22:54:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `institucion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol_id` int UNSIGNED NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  KEY `idx_usuarios_rol_id` (`rol_id`),
  KEY `idx_usuarios_activo` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password`, `telefono`, `institucion`, `rol_id`, `activo`, `fecha_registro`, `fecha_actualizacion`) VALUES
(1, 'Administrador', 'General', 'admin@universidad.edu', '$2y$10$gA3Zpa4tX8RQYDojj6M3SOoRycsB8Ms3TNjurUzMnzmb2fyh51d2O', '555-0100', 'UCENM sede SPS', 1, 1, '2026-11-19 22:54:51', '2026-11-20 22:35:55'),
(2, 'THAMY', 'PACHECO', 'thamy@gmail.com', '$2y$10$tgTA.kaWbv/oMRbJguLOzOOr89Xu4tPpiCvFjf1V2OL3pnnujXeL2', '98557787', 'UCENM sede SPS', 2, 1, '2026-11-19 23:08:53', '2026-11-20 22:35:26'),
(3, 'ASHLYN', 'PACHECO', 'ashlyn@gmail.com', '$2y$10$aTP/RY1/Jx7/OJzM1AQSOOA4hxRL6WIkmObPNd8oOBF.QIo4nWcle', '99788312', 'UCENM sede Catacamas', 3, 1, '2026-11-20 20:29:49', '2026-11-20 23:15:09'),
(4, 'LENIN', 'CUBIAS', 'lenin@gmail.com', '$2y$12$iHaa/C12ynrX8fOOwK0zTegWUL4Rox4gCFwB3LptkYsqJA86emOMS', '95634520', 'UCENM sede SPS', 3, 1, '2026-11-20 20:32:23', '2026-11-20 20:32:23'),
(5, 'ERICK', 'GUTIERREZ', 'erick@gmail.com', '$2y$10$fhGfIZaQEstRY5aXamAvVONdrU/vEXYnXuV7k.SlH2.0jSD0Pp/86', '96457485', 'UCENM sede SPS', 2, 1, '2026-11-20 22:38:12', '2026-11-20 22:52:01'),
(6, 'ALEX', 'GARCIA', 'alex@gmail.com', '$2y$10$XXqrFdOVBbcjtLyha8.BceyQaUow3L7mSeasSvkw6bP7N/wKdP7mu', '95412302', 'UCENM sede Catacamas', 3, 1, '2026-11-20 22:39:15', '2026-11-20 22:47:05'),
(7, 'NIXON', 'REYES', 'nixon@gmail.com', '$2y$12$uoMU2BKPUM.kw9rBdYEQK.xvH3BhgSeBz5rQ3.4Ys213/Viklxx4u', '89964512', 'UCENM sede SPS', 3, 1, '2026-11-20 23:22:05', '2026-11-20 23:22:05'),
(8, 'OSCAR', 'AGUILAR', 'oscar@gmail.com', '$2y$12$3u0otbrpDspjb7nXhHctMOWBfmgPF7I/M0tYFjLrTNw8hAn.XbkZy', '87775598', 'UCENM sede Santa Rosa de Copan', 3, 1, '2026-11-20 23:23:47', '2026-11-20 23:23:47');

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `fk_asistencias_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asistencias_inscripcion` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asistencias_registrador` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asistencias_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `certificados`
--
ALTER TABLE `certificados`
  ADD CONSTRAINT `fk_certificados_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_certificados_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD CONSTRAINT `fk_eventos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_eventos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eventos_organizador` FOREIGN KEY (`organizador_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `fk_inscripciones_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inscripciones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
