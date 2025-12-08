-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-05-2025 a las 04:39:05
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `servicio`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamento`
--

CREATE TABLE `departamento` (
  `id_departamento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `departamento`
--

INSERT INTO `departamento` (`id_departamento`, `nombre`) VALUES
(1, 'Departamento de Sistemas y Computación');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos`
--

CREATE TABLE `documentos` (
  `id_documento` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `tipo_documento` enum('acta','horario','otros_licenciatura','otros_maestria','otros_doctorado','otros_cursos','otros_otros') NOT NULL,
  `periodo` enum('ENE-JUN','AGO-DIC') NOT NULL,
  `anio` year(4) NOT NULL,
  `clave_materia` varchar(15) NOT NULL,
  `grupo` text NOT NULL,
  `imagen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `documentos`
--

INSERT INTO `documentos` (`id_documento`, `id_profesor`, `tipo_documento`, `periodo`, `anio`, `clave_materia`, `grupo`, `imagen`) VALUES
(53, 34, 'acta', 'AGO-DIC', '2018', 'TIF1001', 'C', 'acta_AGO-DIC_2018_Perez_Rojas_Beatriz_TIF1001_C.jpg'),
(56, 34, 'otros_licenciatura', 'ENE-JUN', '2011', '', 'A', 'otros_licenciatura_ENE-JUN_2011_Perez_Rojas_Beatriz__A.png'),
(57, 5, 'horario', 'AGO-DIC', '2015', 'ADD-2507', 'XYZ', 'horario_AGO-DIC_2015_Carmona_Ventura_Pablo_Jose_de_Jesus_ADD-2507_XYZ.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formacion`
--

CREATE TABLE `formacion` (
  `id_formacion` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `nivel_formacion` enum('Licenciatura','Maestría','Doctorado') NOT NULL,
  `imagen` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id_grupo` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id_grupo`, `nombre`) VALUES
(1, 'C'),
(2, 'A'),
(3, 'B'),
(4, 'XYZ');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `clave_materia` varchar(15) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`clave_materia`, `nombre`) VALUES
('ACA-0909', 'Taller de Investigación I'),
('ACA-0910', 'Taller de Investigación II'),
('ACC-0906', 'Fundamentos de Investigación'),
('ACD-0908', 'Desarrollo Sustentable'),
('ACF-0902', 'Cálculo Integral'),
('ACF-0903', 'Álgebra Lineal'),
('ACF-2301', 'Cálculo Diferencial'),
('ACH-2307', 'Taller de Ética'),
('ADB-2504', 'CIBERSEG'),
('ADD-2501', 'EST. ANALISIS DAT'),
('ADD-2505', 'SQL EXP. DAT'),
('ADD-2506', 'EXP. BD. NO REL'),
('ADD-2507', 'INT. APR. AUT'),
('ADV-2502', 'HER. AV. ANALISIS DAT'),
('ADV-2503', 'PROG. ANALISIS DAT'),
('AEB-1011', 'Desarrollo de Aplicaciones para Dispositivos Móviles'),
('AEB-1054', 'Programación Orientada a Objetos'),
('AEB-1055', 'Programación Web'),
('AEC-1061', 'Sistemas Operativos I'),
('AED-1062', 'Sistemas Operativos II'),
('AEF-1031', 'Fundamentos de Base de Datos'),
('AEF-1032', 'Fundamentos de Programación'),
('AEF-1052', 'Probabilidad y Estadística'),
('AEH-1063', 'Taller de Base de Datos'),
('ICB-2203', 'Fundamentos de Internet de las Cosas'),
('ICB-2204', 'Ciberseguridad'),
('ICB-2206', 'Internet de las Cosas Avanzado'),
('ICV-2201', 'Sistema Web con Servlets y Oracle'),
('ICV-2202', 'Cubos OLAP para Inteligencia Empresarial'),
('ICV-2205', 'Programación de Aplicaciones para Ambientes Distribuidos'),
('TIB-1024', 'Programación II'),
('TIC-1002', 'Administración Gerencial'),
('TIC-1005', 'Arquitectura de Computadoras'),
('TIC-1006', 'Auditoría en Tecnologías de la Información'),
('TIC-1011', 'Electricidad y Magnetismo'),
('TIC-1014', 'Ingeniería de Software'),
('TIC-1015', 'Ingeniería del Conocimiento'),
('TIC-1022', 'Negocios Electrónicos I'),
('TIC-1023', 'Negocios Electrónicos II'),
('TIC-1027', 'Taller de Ingeniería de Software'),
('TIC-1028', 'Tecnologías Inalámbricas'),
('TID-1004', 'Análisis de Señales y Sistemas de Comunicación'),
('TID-1008', 'Circuitos Eléctricos y Electrónicos'),
('TID-1010', 'Desarrollo de Emprendedores'),
('TID-1012', 'Estructuras y Organización de Datos'),
('TIE-1018', 'Matemáticas Aplicadas a Comunicaciones'),
('TIF-1001', 'Administración de Proyectos'),
('TIF-1003', 'Administración y Seguridad de Redes'),
('TIF-1007', 'Base de Datos Distribuidas'),
('TIF-1009', 'Contabilidad y Costos'),
('TIF-1013', 'Fundamentos de Redes'),
('TIF-1019', 'Matemáticas Discretas I'),
('TIF-1020', 'Matemáticas Discretas II'),
('TIF-1021', 'Matemáticas para la Toma de Decisiones'),
('TIF-1025', 'Redes de Computadoras'),
('TIF-1026', 'Redes Emergentes'),
('TIF-1029', 'Telecomunicaciones'),
('TIH-1016', 'Interacción Humano Computadora'),
('TIP-1017', 'Introducción a las TIC');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `id_profesor` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `id_departamento` int(11) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `anio_ingreso` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`id_profesor`, `nombre`, `id_departamento`, `imagen`, `anio_ingreso`) VALUES
(1, 'Aguilar Rico Adolfo', 1, '../CRUD/img/profesores/mermaid-diagram-2025-05-12-184443.png', NULL),
(2, 'Alvarez Jimenez Leticia', 1, '../CRUD/img/profesores/mermaid-flow-1x (1).png', NULL),
(3, 'Alvarez Jimenez Norma', 1, '../CRUD/img/profesores/Imagen de WhatsApp 2025-05-07 a las 23.02.25_c29f80af.jpg', NULL),
(4, 'Alvarez Sanchez Gloria', 1, NULL, NULL),
(5, 'Carmona Ventura Pablo Jose de Jesus', 1, NULL, NULL),
(6, 'Carrasco Peral Melchor Eduardo', 1, NULL, NULL),
(7, 'Castañeda Roldan Yolanda Carolina', 1, NULL, NULL),
(8, 'Contreras Guzman Maria Juana', 1, NULL, NULL),
(9, 'Cuanalo Bautista Hector', 1, NULL, NULL),
(10, 'De la rosa Gonzalez Manuel', 1, NULL, NULL),
(11, 'Farias Martinez German', 1, NULL, NULL),
(12, 'Flores Becerra Georgina', 1, NULL, NULL),
(13, 'Flores Sanchez Omar', 1, NULL, NULL),
(14, 'Garcia Avalos Mauricio', 1, NULL, NULL),
(15, 'Garcia Ramirez Ruben Senen', 1, NULL, NULL),
(16, 'Gonzalez Perales Silvia Alicia', 1, NULL, NULL),
(17, 'Hernandez Morales Oscar', 1, NULL, NULL),
(18, 'Larios Avila Martha Elena', 1, NULL, NULL),
(19, 'Lopez Ponciano Jose Rosario', 1, NULL, NULL),
(20, 'Luciano Machorro Teresa', 1, NULL, NULL),
(21, 'Maldonado Rojas Mario Ernesto', 1, NULL, NULL),
(22, 'Martinez Rabanales Susana', 1, NULL, NULL),
(23, 'Martinez Ramirez Violeta', 1, NULL, NULL),
(24, 'Meza Garcia Rafael', 1, NULL, NULL),
(25, 'Morales Carrasco Raul', 1, NULL, NULL),
(26, 'Muñoz Flores Andres', 1, NULL, NULL),
(27, 'Nieto Gomez Jose Javier', 1, NULL, NULL),
(28, 'Ortiz Socorro Jose Luis', 1, NULL, NULL),
(29, 'Osorio Ramirez Efren Armando', 1, NULL, NULL),
(30, 'Parra Victorino Jose Bernardo', 1, NULL, NULL),
(31, 'Perez Altamirano Ricardo Josue', 1, NULL, NULL),
(32, 'Perez Cordero Jose Alfonso', 1, NULL, NULL),
(33, 'Perez Ramirez Jorge Alejandro', 1, NULL, NULL),
(34, 'Perez Rojas Beatriz', 1, '../CRUD/img/profesores/Acta_AgosDic_2018_BeatrizPerez_TIF1019A.jpg', NULL),
(35, 'Porras Aguirre Josefina', 1, NULL, NULL),
(36, 'Ramirez Martha Jose Omar', 1, NULL, NULL),
(37, 'Reyes Velez Alejandra', 1, NULL, NULL),
(38, 'Reynoso Muñoz Carlos Moises', 1, NULL, NULL),
(39, 'Rico Aguilar Adolfo', 1, NULL, NULL),
(40, 'Rojas Cuevas Irma Delia', 1, NULL, NULL),
(41, 'Rosales Villegas Javier Anibal', 1, NULL, NULL),
(42, 'Ruiz Vargas Saul', 1, NULL, NULL),
(43, 'Sanchez Armenta Veronica', 1, NULL, NULL),
(44, 'Sanchez Sanchez Nicolas', 1, NULL, NULL),
(45, 'Santos Palacios Alberto', 1, NULL, NULL),
(46, 'Solar Quiroz Maria Magdalena Guadalupe', 1, NULL, NULL),
(47, 'Solis Aragon Aldo Sinael', 1, NULL, NULL),
(48, 'Solis Salazar Juan Manuel', 1, NULL, NULL),
(49, 'Sosa Pintle Ana Maria', 1, NULL, NULL),
(50, 'Tello Martinez Jose Tomas', 1, NULL, NULL),
(51, 'Tobon Perez Juan Carlos', 1, NULL, NULL),
(52, 'Torres Chavez Francisco Refugio', 1, NULL, NULL),
(53, 'Vera Alba Morales Torres', 1, NULL, NULL),
(54, 'Zarza Arronte Gildardo David', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `id_profesor` int(11) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `rol` enum('Administrador','Jefe','Coordinador','Visitante') NOT NULL,
  `intentos_fallidos` int(11) DEFAULT 0,
  `bloqueado_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `id_profesor`, `usuario`, `contraseña`, `rol`, `intentos_fallidos`, `bloqueado_until`) VALUES
(8, NULL, 'admin', '$2y$10$7k.kuuLW3pa0f3mCtR66y.foObmDaogj6rpLr3ETPMVKmnpNPPTF.', 'Administrador', 0, NULL),
(9, NULL, 'servicio', '$2y$10$vCCoq/.mlEWsZfhXwgSlCeb2w6PZ5oGxG/JvLBA0MlOtijIWHdKN2', 'Visitante', 0, NULL),
(10, NULL, 'Jefe', '$2y$10$jklvxSYpvHpg1e.BRqVg3ePVTVbq5A6U2YwPw7AgMDj/i6oCNmL8W', 'Jefe', 0, NULL),
(12, NULL, 'Coordinador', '$2y$10$oOKeUwzpkZj2RmOxwuI0iurA2LS6hSDrN3MymyEgI.1FD7zOnOCWu', 'Coordinador', 0, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `departamento`
--
ALTER TABLE `departamento`
  ADD PRIMARY KEY (`id_departamento`);

--
-- Indices de la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `id_profesor` (`id_profesor`);

--
-- Indices de la tabla `formacion`
--
ALTER TABLE `formacion`
  ADD PRIMARY KEY (`id_formacion`),
  ADD KEY `id_profesor` (`id_profesor`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id_grupo`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`clave_materia`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`id_profesor`),
  ADD KEY `profesores_ibfk_2` (`id_departamento`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `id_profesor` (`id_profesor`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `departamento`
--
ALTER TABLE `departamento`
  MODIFY `id_departamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `formacion`
--
ALTER TABLE `formacion`
  MODIFY `id_formacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id_profesor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`);

--
-- Filtros para la tabla `formacion`
--
ALTER TABLE `formacion`
  ADD CONSTRAINT `formacion_ibfk_1` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`);

--
-- Filtros para la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD CONSTRAINT `profesores_ibfk_2` FOREIGN KEY (`id_departamento`) REFERENCES `departamento` (`id_departamento`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
