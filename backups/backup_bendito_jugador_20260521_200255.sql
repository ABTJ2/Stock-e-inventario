-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: bendito_jugador
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ajustes_inventario`
--

DROP TABLE IF EXISTS `ajustes_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ajustes_inventario` (
  `id_ajuste` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_almacen` int(11) DEFAULT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_nuevo` int(11) NOT NULL,
  `diferencia` int(11) NOT NULL DEFAULT 0,
  `motivo` text NOT NULL,
  `estado` enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_ajuste`),
  KEY `id_producto` (`id_producto`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `ajustes_inventario_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `ajustes_inventario_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ajustes_inventario`
--

LOCK TABLES `ajustes_inventario` WRITE;
/*!40000 ALTER TABLE `ajustes_inventario` DISABLE KEYS */;
/*!40000 ALTER TABLE `ajustes_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `almacenes`
--

DROP TABLE IF EXISTS `almacenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `almacenes` (
  `id_almacen` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_almacen`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `almacenes`
--

LOCK TABLES `almacenes` WRITE;
/*!40000 ALTER TABLE `almacenes` DISABLE KEYS */;
INSERT INTO `almacenes` VALUES (1,'Principal','Almacén principal de la empresa','Zona Centro','activo','2026-04-18 23:30:47'),(2,'Secundario','Almacén secundario','Zona Sur','activo','2026-04-18 23:30:47'),(3,'Exhibición','Salón de ventas y exhibición','Zona Norte','activo','2026-04-18 23:30:47'),(4,'Almacen Central','Deposito principal','Deposito principal','activo','2026-05-07 16:15:33'),(5,'Deposito Norte','Sucursal norte','Sucursal norte','activo','2026-05-07 16:15:33'),(6,'Deposito Sur','Sucursal sur','Sucursal sur','activo','2026-05-07 16:15:33'),(7,'Almacen Central','Deposito principal','Deposito principal','activo','2026-05-07 21:41:47'),(8,'Deposito Norte','Sucursal norte','Sucursal norte','activo','2026-05-07 21:41:47'),(9,'Deposito Sur','Sucursal sur','Sucursal sur','activo','2026-05-07 21:41:47');
/*!40000 ALTER TABLE `almacenes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auditoria_inventario`
--

DROP TABLE IF EXISTS `auditoria_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria_inventario` (
  `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_almacen` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `stock_sistema` int(11) NOT NULL,
  `stock_real` int(11) NOT NULL,
  `diferencia` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_auditoria`),
  KEY `id_producto` (`id_producto`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `auditoria_inventario_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `auditoria_inventario_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria_inventario`
--

LOCK TABLES `auditoria_inventario` WRITE;
/*!40000 ALTER TABLE `auditoria_inventario` DISABLE KEYS */;
/*!40000 ALTER TABLE `auditoria_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auditoria_sistema`
--

DROP TABLE IF EXISTS `auditoria_sistema`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria_sistema` (
  `id_auditoria` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `accion` varchar(80) NOT NULL,
  `modulo` varchar(80) NOT NULL,
  `entidad` varchar(80) DEFAULT NULL,
  `id_entidad` bigint(20) DEFAULT NULL,
  `detalle` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_auditoria_usuario` (`id_usuario`),
  KEY `idx_auditoria_accion` (`accion`),
  KEY `idx_auditoria_modulo` (`modulo`),
  CONSTRAINT `fk_auditoria_sistema_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria_sistema`
--

LOCK TABLES `auditoria_sistema` WRITE;
/*!40000 ALTER TABLE `auditoria_sistema` DISABLE KEYS */;
INSERT INTO `auditoria_sistema` VALUES (1,1,'password_change','auth',NULL,NULL,'Cambio obligatorio de contraseña por primer ingreso.','','','2026-05-21 23:01:48');
/*!40000 ALTER TABLE `auditoria_sistema` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias_producto`
--

DROP TABLE IF EXISTS `categorias_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias_producto` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias_producto`
--

LOCK TABLES `categorias_producto` WRITE;
/*!40000 ALTER TABLE `categorias_producto` DISABLE KEYS */;
INSERT INTO `categorias_producto` VALUES (1,'Indumentaria','Ropa deportiva',1),(2,'Calzado','Botines y calzado deportivo',1),(3,'Accesorios','Complementos deportivos',1),(4,'Deportes','Articulos para practica deportiva',1),(5,'Kits','Combos y conjuntos',1);
/*!40000 ALTER TABLE `categorias_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `condiciones_iva`
--

DROP TABLE IF EXISTS `condiciones_iva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `condiciones_iva` (
  `id_condicion_iva` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id_condicion_iva`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `condiciones_iva`
--

LOCK TABLES `condiciones_iva` WRITE;
/*!40000 ALTER TABLE `condiciones_iva` DISABLE KEYS */;
INSERT INTO `condiciones_iva` VALUES (1,'Responsable Inscripto',1),(2,'Monotributista',1),(3,'Exento',1),(4,'Consumidor Final',1),(5,'No Responsable',1);
/*!40000 ALTER TABLE `condiciones_iva` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_ingreso`
--

DROP TABLE IF EXISTS `detalle_ingreso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_ingreso` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_ingreso` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `id_ingreso` (`id_ingreso`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_ingreso_ibfk_1` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos_mercaderia` (`id_ingreso`),
  CONSTRAINT `detalle_ingreso_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_ingreso`
--

LOCK TABLES `detalle_ingreso` WRITE;
/*!40000 ALTER TABLE `detalle_ingreso` DISABLE KEYS */;
/*!40000 ALTER TABLE `detalle_ingreso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estados_producto`
--

DROP TABLE IF EXISTS `estados_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estados_producto` (
  `id_estado_producto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_estado` varchar(50) NOT NULL,
  PRIMARY KEY (`id_estado_producto`),
  UNIQUE KEY `nombre_estado` (`nombre_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estados_producto`
--

LOCK TABLES `estados_producto` WRITE;
/*!40000 ALTER TABLE `estados_producto` DISABLE KEYS */;
INSERT INTO `estados_producto` VALUES (1,'Activo'),(3,'Discontinuado'),(2,'Inactivo');
/*!40000 ALTER TABLE `estados_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estados_proveedor`
--

DROP TABLE IF EXISTS `estados_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estados_proveedor` (
  `id_estado_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_estado` varchar(50) NOT NULL,
  PRIMARY KEY (`id_estado_proveedor`),
  UNIQUE KEY `nombre_estado` (`nombre_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estados_proveedor`
--

LOCK TABLES `estados_proveedor` WRITE;
/*!40000 ALTER TABLE `estados_proveedor` DISABLE KEYS */;
INSERT INTO `estados_proveedor` VALUES (1,'Activo'),(2,'Inactivo');
/*!40000 ALTER TABLE `estados_proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingresos_mercaderia`
--

DROP TABLE IF EXISTS `ingresos_mercaderia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingresos_mercaderia` (
  `id_ingreso` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_almacen` int(11) DEFAULT NULL,
  `numero_factura` varchar(50) DEFAULT NULL,
  `fecha` date NOT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('pendiente','confirmado','cancelado') DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_ingreso`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `ingresos_mercaderia_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  CONSTRAINT `ingresos_mercaderia_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingresos_mercaderia`
--

LOCK TABLES `ingresos_mercaderia` WRITE;
/*!40000 ALTER TABLE `ingresos_mercaderia` DISABLE KEYS */;
/*!40000 ALTER TABLE `ingresos_mercaderia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `localidades`
--

DROP TABLE IF EXISTS `localidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `localidades` (
  `id_localidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_provincia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id_localidad`),
  UNIQUE KEY `uq_localidad_provincia` (`id_provincia`,`nombre`),
  CONSTRAINT `fk_localidades_provincia` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id_provincia`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `localidades`
--

LOCK TABLES `localidades` WRITE;
/*!40000 ALTER TABLE `localidades` DISABLE KEYS */;
INSERT INTO `localidades` VALUES (1,1,'La Plata',1),(2,1,'Mar del Plata',1),(3,2,'CABA',1),(4,3,'Cordoba',1),(5,4,'Rosario',1);
/*!40000 ALTER TABLE `localidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marcas`
--

DROP TABLE IF EXISTS `marcas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marcas` (
  `id_marca` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id_marca`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marcas`
--

LOCK TABLES `marcas` WRITE;
/*!40000 ALTER TABLE `marcas` DISABLE KEYS */;
INSERT INTO `marcas` VALUES (1,'Bendito Jugador',1),(2,'Adidas',1),(3,'Nike',1),(4,'Penalty',1),(5,'Topper',1);
/*!40000 ALTER TABLE `marcas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_stock`
--

DROP TABLE IF EXISTS `movimientos_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos_stock` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_almacen` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('ingreso','egreso','ajuste_positivo','ajuste_negativo','traspaso') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_nuevo` int(11) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `entidad_origen` enum('ingreso','ajuste','traspaso','producto') DEFAULT NULL,
  `id_entidad_origen` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_movimiento`),
  KEY `fk_movimientos_producto` (`id_producto`),
  KEY `fk_movimientos_usuario` (`id_usuario`),
  KEY `idx_movimientos_almacen` (`id_almacen`),
  KEY `idx_movimientos_origen` (`entidad_origen`,`id_entidad_origen`),
  CONSTRAINT `fk_movimientos_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `fk_movimientos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_stock`
--

LOCK TABLES `movimientos_stock` WRITE;
/*!40000 ALTER TABLE `movimientos_stock` DISABLE KEYS */;
INSERT INTO `movimientos_stock` VALUES (1,16,1,NULL,'traspaso',456,1234,778,'Traspaso - salida','traspaso:2',NULL,NULL,'2026-05-07 23:30:48'),(2,16,1,NULL,'traspaso',456,0,456,'Traspaso - ingreso','traspaso:2',NULL,NULL,'2026-05-07 23:30:48');
/*!40000 ALTER TABLE `movimientos_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paises`
--

DROP TABLE IF EXISTS `paises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paises` (
  `id_pais` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id_pais`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paises`
--

LOCK TABLES `paises` WRITE;
/*!40000 ALTER TABLE `paises` DISABLE KEYS */;
INSERT INTO `paises` VALUES (1,'Argentina',1);
/*!40000 ALTER TABLE `paises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parametros_sistema`
--

DROP TABLE IF EXISTS `parametros_sistema`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parametros_sistema` (
  `id_parametro` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo` varchar(30) NOT NULL DEFAULT 'texto',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_parametro`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parametros_sistema`
--

LOCK TABLES `parametros_sistema` WRITE;
/*!40000 ALTER TABLE `parametros_sistema` DISABLE KEYS */;
INSERT INTO `parametros_sistema` VALUES (1,'empresa_nombre','Bendito Jugador','Nombre comercial mostrado en reportes y respaldos.','texto',1,'2026-05-21 22:26:18','2026-05-21 22:26:18'),(2,'stock_alerta_visual','1','Activa alertas visuales de stock bajo.','booleano',1,'2026-05-21 22:26:18','2026-05-21 22:26:18'),(3,'backup_carpeta','backups','Carpeta local donde se guardan los respaldos SQL.','texto',1,'2026-05-21 22:26:18','2026-05-21 22:26:18'),(4,'csv_separador',';','Separador usado en exportaciones CSV.','texto',1,'2026-05-21 22:26:18','2026-05-21 22:26:18');
/*!40000 ALTER TABLE `parametros_sistema` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT 0.00,
  `stock_actual` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 0,
  `categoria` varchar(50) DEFAULT NULL,
  `unidad_medida` varchar(20) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `precio_referencia` decimal(10,2) DEFAULT 0.00,
  `id_categoria` int(11) DEFAULT NULL,
  `id_marca` int(11) DEFAULT NULL,
  `id_unidad_medida` int(11) DEFAULT NULL,
  `id_estado_producto` int(11) DEFAULT NULL,
  `fecha_alta` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,'PROD001','Camiseta Bendito Jugador','Camiseta oficial edición limitada',2500.00,150,10,'Indumentaria','unidad','activo','2026-04-18 23:30:47',2500.00,1,1,1,1,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(2,'PROD002','Short Deportivo','Short deportivo profesional',1800.00,90,5,'Indumentaria','unidad','activo','2026-04-18 23:30:47',1800.00,1,1,1,1,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(3,'PROD003','Pelota de Fútbol','Pelota profesional de match',3200.00,300,20,'Deportes','unidad','activo','2026-04-18 23:30:47',3200.00,4,1,1,1,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(4,'PROD004','Medias Profesionales','Medias de fútbol con refuerzo',850.00,240,15,'Accesorios','par','activo','2026-04-18 23:30:47',850.00,3,1,2,1,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(5,'PROD005','Canilleras','Canilleras protectoras',1200.00,120,10,'Accesorios','unidad','activo','2026-04-18 23:30:47',1200.00,3,1,1,1,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(6,'PROD006','Guantes de Arquero','Guantes profesionales',4500.00,45,5,'Deportes','unidad','activo','2026-04-18 23:30:47',4500.00,4,1,1,1,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(7,'PROD007','Bolso Deportivo','Bolso grande con compartimentos',5500.00,75,5,'Accesorios','unidad','inactivo','2026-04-18 23:30:47',5500.00,3,1,1,2,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(8,'PROD008','Botines Elite','Botines de alta gama',8500.00,60,8,'Calzado','unidad','activo','2026-04-18 23:30:47',8500.00,2,1,1,1,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(9,'PROD009','Rompevientos','Rompevientos impermeable',4200.00,54,5,'Indumentaria','unidad','activo','2026-04-18 23:30:47',4200.00,1,1,1,1,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(10,'PROD010','Kit Entrenamiento','Conjunto completo entrenamiento',6800.00,36,3,'Kits','unidad','activo','2026-04-18 23:30:47',6800.00,5,1,1,1,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(11,'2112','Jesus Funes','1212',123.00,9,123,'xd','par','inactivo','2026-05-05 11:22:14',123.00,1,1,2,2,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(12,'PROD0080','asdaasdaassa','asdsa',1.00,12,0,'sa','kit','activo','2026-05-05 11:26:02',1.00,1,1,1,1,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(14,'121313','asdsadas','dsadasds',123445.00,387988479,1,'Calzado','unidad','inactivo','2026-05-05 11:32:54',123445.00,2,1,1,2,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(16,'123458','admin','adajei',213.00,3702,123124,'remera','unidad','activo','2026-05-05 15:20:20',213.00,1,1,1,1,'2026-05-07 13:15:27','2026-05-07 18:41:48'),(17,'123','Jesus Funes','asjddnjasd',123.00,-6,-2,'remera','unidad','activo','2026-05-07 13:22:21',123.00,1,1,1,1,'2026-05-07 13:15:27','2026-05-07 18:41:48');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `cuit` varchar(20) DEFAULT NULL,
  `razon_social` varchar(100) NOT NULL,
  `nombre_fantasia` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `sitio_web` varchar(150) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `codigo_postal` varchar(20) DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `plazo_pago` varchar(100) DEFAULT NULL,
  `cbu` varchar(30) DEFAULT NULL,
  `alias` varchar(80) DEFAULT NULL,
  `datos_bancarios` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `id_estado_proveedor` int(11) DEFAULT NULL,
  `id_rubro_proveedor` int(11) DEFAULT NULL,
  `id_condicion_iva` int(11) DEFAULT NULL,
  `id_pais` int(11) DEFAULT NULL,
  `id_provincia` int(11) DEFAULT NULL,
  `id_localidad` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `cuit` (`cuit`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'20-12345678-5','Deportes Argentina S.A.',NULL,'011-4567-8901','ventas@deportesarg.com',NULL,'Av. Corrientes 1234, CABA',NULL,'Juan Pérez',NULL,NULL,NULL,NULL,NULL,'activo',1,NULL,NULL,NULL,NULL,NULL,'2026-04-18 23:30:47','2026-05-21 18:53:00'),(2,'27-87654321-0','Indumentaria Norte S.R.L.',NULL,'011-4789-0123','info@indnorte.com',NULL,'Av. Rivadavia 5678, CABA',NULL,'María González',NULL,NULL,NULL,NULL,NULL,'activo',1,NULL,NULL,NULL,NULL,NULL,'2026-04-18 23:30:47','2026-05-21 18:53:00'),(3,'30-11223344-5','Sport World Import',NULL,'011-3456-7890','contacto@sportworld.com',NULL,'Av. Santa Fe 2345, CABA',NULL,'Carlos López',NULL,NULL,NULL,NULL,NULL,'activo',1,NULL,NULL,NULL,NULL,NULL,'2026-04-18 23:30:47','2026-05-21 18:53:00');
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provincias`
--

DROP TABLE IF EXISTS `provincias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `provincias` (
  `id_provincia` int(11) NOT NULL AUTO_INCREMENT,
  `id_pais` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id_provincia`),
  UNIQUE KEY `uq_provincia_pais` (`id_pais`,`nombre`),
  CONSTRAINT `fk_provincias_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provincias`
--

LOCK TABLES `provincias` WRITE;
/*!40000 ALTER TABLE `provincias` DISABLE KEYS */;
INSERT INTO `provincias` VALUES (1,1,'Buenos Aires',1),(2,1,'Ciudad Autonoma de Buenos Aires',1),(3,1,'Cordoba',1),(4,1,'Santa Fe',1);
/*!40000 ALTER TABLE `provincias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador','Usuario con acceso total al sistema','activo','2026-04-18 23:30:45'),(2,'Empleado','Usuario con acceso limitado a operaciones básicas','activo','2026-04-18 23:30:45'),(3,'Supervisor Administrativo','Usuario con permisos de supervisión y reportes','activo','2026-04-18 23:30:45'),(4,'Supervisor Auditor','Usuario con permisos de auditoría y control','activo','2026-04-18 23:30:45'),(5,'Gerente Zonal','Usuario con permisos de gestión por zona','activo','2026-04-18 23:30:45');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rubros_proveedor`
--

DROP TABLE IF EXISTS `rubros_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rubros_proveedor` (
  `id_rubro_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id_rubro_proveedor`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rubros_proveedor`
--

LOCK TABLES `rubros_proveedor` WRITE;
/*!40000 ALTER TABLE `rubros_proveedor` DISABLE KEYS */;
INSERT INTO `rubros_proveedor` VALUES (1,'Indumentaria deportiva',1),(2,'Calzado deportivo',1),(3,'Accesorios deportivos',1),(4,'Equipamiento deportivo',1),(5,'Servicios generales',1);
/*!40000 ALTER TABLE `rubros_proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_por_almacen`
--

DROP TABLE IF EXISTS `stock_por_almacen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_por_almacen` (
  `id_stock` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_almacen` int(11) NOT NULL,
  `stock_actual` int(11) DEFAULT 0,
  `stock_reservado` int(11) DEFAULT 0,
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_stock`),
  UNIQUE KEY `uq_producto_almacen` (`id_producto`,`id_almacen`),
  KEY `id_almacen` (`id_almacen`),
  CONSTRAINT `stock_por_almacen_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `stock_por_almacen_ibfk_2` FOREIGN KEY (`id_almacen`) REFERENCES `almacenes` (`id_almacen`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_por_almacen`
--

LOCK TABLES `stock_por_almacen` WRITE;
/*!40000 ALTER TABLE `stock_por_almacen` DISABLE KEYS */;
INSERT INTO `stock_por_almacen` VALUES (1,1,1,50,0,'2026-05-07 13:14:50'),(2,2,1,30,0,'2026-05-07 13:14:50'),(3,3,1,100,0,'2026-05-07 13:14:50'),(4,4,1,80,0,'2026-05-07 13:14:50'),(5,5,1,40,0,'2026-05-07 13:14:50'),(6,6,1,15,0,'2026-05-07 13:14:50'),(7,7,1,25,0,'2026-05-07 13:14:50'),(8,8,1,20,0,'2026-05-07 13:14:50'),(9,9,1,18,0,'2026-05-07 13:14:50'),(10,10,1,12,0,'2026-05-07 13:14:50'),(11,11,1,3,0,'2026-05-07 13:14:50'),(12,12,1,4,0,'2026-05-07 13:14:50'),(13,14,1,129329493,0,'2026-05-07 13:14:50'),(14,16,1,778,0,'2026-05-07 20:30:48'),(15,17,1,-2,0,'2026-05-07 13:14:50'),(16,1,4,50,0,'2026-05-07 18:41:48'),(17,2,4,30,0,'2026-05-07 18:41:48'),(18,3,4,100,0,'2026-05-07 18:41:48'),(19,4,4,80,0,'2026-05-07 18:41:48'),(20,5,4,40,0,'2026-05-07 18:41:48'),(21,6,4,15,0,'2026-05-07 18:41:48'),(22,7,4,25,0,'2026-05-07 18:41:48'),(23,8,4,20,0,'2026-05-07 18:41:48'),(24,9,4,18,0,'2026-05-07 18:41:48'),(25,10,4,12,0,'2026-05-07 18:41:48'),(26,11,4,3,0,'2026-05-07 18:41:48'),(27,12,4,4,0,'2026-05-07 18:41:48'),(28,14,4,129329493,0,'2026-05-07 18:41:48'),(29,1,7,50,0,'2026-05-07 18:41:48'),(30,2,7,30,0,'2026-05-07 18:41:48'),(31,3,7,100,0,'2026-05-07 18:41:48'),(32,4,7,80,0,'2026-05-07 18:41:48'),(33,5,7,40,0,'2026-05-07 18:41:48'),(34,6,7,15,0,'2026-05-07 18:41:48'),(35,7,7,25,0,'2026-05-07 18:41:48'),(36,8,7,20,0,'2026-05-07 18:41:48'),(37,9,7,18,0,'2026-05-07 18:41:48'),(38,10,7,12,0,'2026-05-07 18:41:48'),(39,11,7,3,0,'2026-05-07 18:41:48'),(40,12,7,4,0,'2026-05-07 18:41:48'),(41,14,7,129329493,0,'2026-05-07 18:41:48'),(42,16,4,1234,0,'2026-05-07 18:41:48'),(43,17,4,-2,0,'2026-05-07 18:41:48'),(44,16,7,1234,0,'2026-05-07 18:41:48'),(45,17,7,-2,0,'2026-05-07 18:41:48'),(47,16,2,456,0,'2026-05-07 20:30:48');
/*!40000 ALTER TABLE `stock_por_almacen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_por_almacen_respaldo_20260507`
--

DROP TABLE IF EXISTS `stock_por_almacen_respaldo_20260507`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_por_almacen_respaldo_20260507` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_movimiento` enum('ingreso','egreso','ajuste_positivo','ajuste_negativo','traspaso') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_nuevo` int(11) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_movimiento`),
  KEY `id_producto` (`id_producto`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `stock_por_almacen_respaldo_20260507_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `stock_por_almacen_respaldo_20260507_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_por_almacen_respaldo_20260507`
--

LOCK TABLES `stock_por_almacen_respaldo_20260507` WRITE;
/*!40000 ALTER TABLE `stock_por_almacen_respaldo_20260507` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_por_almacen_respaldo_20260507` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traspasos`
--

DROP TABLE IF EXISTS `traspasos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traspasos` (
  `id_traspaso` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_almacen_origen` int(11) NOT NULL,
  `id_almacen_destino` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `estado` enum('pendiente','confirmado','cancelado') DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_traspaso`),
  KEY `id_producto` (`id_producto`),
  KEY `id_almacen_origen` (`id_almacen_origen`),
  KEY `id_almacen_destino` (`id_almacen_destino`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `traspasos_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `traspasos_ibfk_2` FOREIGN KEY (`id_almacen_origen`) REFERENCES `almacenes` (`id_almacen`),
  CONSTRAINT `traspasos_ibfk_3` FOREIGN KEY (`id_almacen_destino`) REFERENCES `almacenes` (`id_almacen`),
  CONSTRAINT `traspasos_ibfk_4` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traspasos`
--

LOCK TABLES `traspasos` WRITE;
/*!40000 ALTER TABLE `traspasos` DISABLE KEYS */;
INSERT INTO `traspasos` VALUES (2,16,1,2,456,1,'confirmado','2026-05-07 23:30:48');
/*!40000 ALTER TABLE `traspasos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unidades_medida`
--

DROP TABLE IF EXISTS `unidades_medida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unidades_medida` (
  `id_unidad_medida` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `abreviatura` varchar(20) DEFAULT NULL,
  `estado` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id_unidad_medida`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidades_medida`
--

LOCK TABLES `unidades_medida` WRITE;
/*!40000 ALTER TABLE `unidades_medida` DISABLE KEYS */;
INSERT INTO `unidades_medida` VALUES (1,'Unidad','Unid.',1),(2,'Par','Par',1),(3,'Caja','Caja',1),(4,'Pack','Pack',1);
/*!40000 ALTER TABLE `unidades_medida` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `primer_ingreso` tinyint(1) DEFAULT 1,
  `fecha_ultimo_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin','$2y$10$HBCYhIQ0O2sUDTHVOQ2FaeUA4cC0z.Rt31x/6ufmiw2jgN.Vb3X66','Administrador Principal',1,'activo',1,'2026-05-21 19:18:31','2026-04-18 23:30:47'),(2,'supervisor','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Supervisor de Stock',3,'activo',1,NULL,'2026-04-18 23:30:47'),(3,'operario','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Operario de склад',2,'activo',1,NULL,'2026-04-18 23:30:47'),(4,'carlitos','$2y$10$XCtVpWmu0cLyuECVzzDqFe0YEWzd0CvClNkxjXkdcT/u3F2QaST7y','Carlitos  no',5,'activo',1,'2026-05-05 18:22:31','2026-05-05 21:20:58');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-21 20:02:55
