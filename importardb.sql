-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-04-2025 a las 17:53:44
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `dinohtml`;
USE `dinohtml`;

--
-- Base de datos: `dinohtml`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `canciones`
--

CREATE TABLE `canciones` (
  `id` int(11) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `autor` varchar(40) DEFAULT NULL,
  `img` varchar(40) DEFAULT NULL,
  `src` varchar(40) DEFAULT NULL,
  `duracion` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `canciones`
--

INSERT INTO `canciones` (`id`, `nombre`, `autor`, `img`, `src`, `duracion`) VALUES
(1, 'Sugar Red', '...', 'sugar_red.jpg', 'Sugar Red.mp3', '00:00:40'),
(2, 'Hope', 'Stephane Lopez', 'hope.jpg', 'HOPE.mp3', '00:01:19'),
(3, 'Running With the wolves', 'Aurora', 'running_with_the_wolves.jpg', 'Running With the wolves - Aurora.mp3', '00:02:43'),
(4, 'Time', 'Hans Zimmer', 'time.jpg', 'Time.mp3', '00:02:46'),
(5, 'Mi Fiesta', 'Bandalos Chinos', 'mi_fiesta.jpg', 'Mi Fiesta.mp3', '00:03:13'),
(6, 'Dawn of Faith', 'Eternal Eclipse', 'dawn_of_faith.jpg', 'Dawn of Faith - Eternal Eclipse.mp3', '00:03:16'),
(7, 'What`s Up Danger', 'Blackway & Black Caviar', 'what`s_up_danger.jpg', 'What`s Up Danger.mp3', '00:03:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `id` int(10) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `comentario` varchar(400) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `visible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `comentarios`
--

INSERT INTO `comentarios` (`id`, `id_usuario`, `comentario`, `fecha`, `visible`) VALUES
(32, 18, 'hola soy Prueba', '2024-01-10 18:45:33', 1),
(33, 20, 'hola soy el otro', '2024-01-10 18:46:13', 1),
(34, 18, 'es visible', '2024-01-10 18:57:21', 1),
(35, 18, 'no es visible', '2024-01-10 18:57:30', 0),
(36, 18, 'otro visible', '2024-01-10 19:07:27', 1),
(37, 18, 'otro no visible', '2024-01-10 19:07:35', 0),
(38, 18, 'aaa', '2024-01-12 23:40:22', 1),
(40, 18, 'ssssssssssssssssss', '2024-02-25 17:11:36', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `progreso`
--

CREATE TABLE `progreso` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_cancion` int(11) NOT NULL,
  `porcentaje` int(12) DEFAULT NULL,
  `pts` int(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `progreso`
--

INSERT INTO `progreso` (`id`, `id_usuario`, `id_cancion`, `porcentaje`, `pts`) VALUES
(1, 18, 1, 100, 38),
(2, 18, 4, 39, 67),
(4, 18, 2, 53, 41),
(5, 20, 1, 100, 39),
(6, 18, 3, 59, 100),
(7, 20, 2, 36, 23),
(10, 25, 3, 21, 32),
(11, 25, 7, 4, 5),
(12, 26, 4, 5, 6),
(13, 26, 6, 3, 3),
(14, 26, 1, 27, 6),
(16, 18, 6, 15, 25),
(18, 18, 5, 14, 26),
(19, 20, 3, 12, 16),
(20, 20, 7, 5, 7),
(21, 18, 7, 47, 104);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ranking`
--

CREATE TABLE `ranking` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `porcentaje_total` int(11) NOT NULL,
  `pts_total` int(11) NOT NULL,
  `n_canciones` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `ranking`
--

INSERT INTO `ranking` (`id`, `id_usuario`, `porcentaje_total`, `pts_total`, `n_canciones`) VALUES
(1, 18, 46, 401, 7),
(2, 20, 38, 85, 4),
(4, 25, 12, 37, 2),
(5, 26, 11, 15, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `register`
--

CREATE TABLE `register` (
  `id` int(11) NOT NULL,
  `usuario` varchar(40) NOT NULL,
  `nombres` varchar(60) NOT NULL,
  `email` varchar(60) NOT NULL,
  `pais` varchar(40) NOT NULL,
  `contraseña` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `register`
--

INSERT INTO `register` (`id`, `usuario`, `nombres`, `email`, `pais`, `contraseña`) VALUES
(18, 'prueba', 'hola', 'eee@gmail.com', 'Albania', '1234'),
(20, 'prueba2', 'pn prueba2', '', '', '1234'),
(25, 'prueba4', 'hola', 'eee@gmail.com', '', '1234'),
(26, 'prueba5', 'hola', 'galvezerwin28@gmail.com', '', '1234');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `canciones`
--
ALTER TABLE `canciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario3` (`id_usuario`);

--
-- Indices de la tabla `progreso`
--
ALTER TABLE `progreso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cancion` (`id_cancion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `ranking`
--
ALTER TABLE `ranking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario2` (`id_usuario`);

--
-- Indices de la tabla `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `canciones`
--
ALTER TABLE `canciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `progreso`
--
ALTER TABLE `progreso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `ranking`
--
ALTER TABLE `ranking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `id_usuario3` FOREIGN KEY (`id_usuario`) REFERENCES `register` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `progreso`
--
ALTER TABLE `progreso`
  ADD CONSTRAINT `id_cancion` FOREIGN KEY (`id_cancion`) REFERENCES `canciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `id_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `register` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ranking`
--
ALTER TABLE `ranking`
  ADD CONSTRAINT `id_usuario2` FOREIGN KEY (`id_usuario`) REFERENCES `register` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
