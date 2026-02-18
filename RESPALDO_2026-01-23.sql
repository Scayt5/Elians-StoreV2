-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: elians_db
-- ------------------------------------------------------
-- Server version	5.7.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `asistencia`
--

DROP TABLE IF EXISTS `asistencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asistencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `horas` decimal(4,2) DEFAULT NULL,
  `entrada` datetime DEFAULT NULL,
  `salida` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `empleado_id` (`empleado_id`),
  CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asistencia`
--

LOCK TABLES `asistencia` WRITE;
/*!40000 ALTER TABLE `asistencia` DISABLE KEYS */;
INSERT INTO `asistencia` VALUES (19,1,'2026-01-19',8.02,'2026-01-19 18:43:00','2026-01-20 02:44:00'),(20,1,'2026-01-20',22.22,'2026-01-20 01:40:00','2026-01-20 23:53:00'),(21,1,'2026-01-21',19.10,'2026-01-21 04:45:00','2026-01-21 23:51:00'),(22,1,'2026-01-22',20.00,'2026-01-22 03:40:00','2026-01-22 23:40:00'),(23,1,'2026-01-23',18.00,'2026-01-23 05:40:00','2026-01-23 23:40:00'),(24,1,'2026-01-24',7.00,'2026-01-24 03:40:00','2026-01-24 10:40:00');
/*!40000 ALTER TABLE `asistencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empleados`
--

DROP TABLE IF EXISTS `empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empleados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `pago_hora` decimal(10,2) NOT NULL DEFAULT '5.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empleados`
--

LOCK TABLES `empleados` WRITE;
/*!40000 ALTER TABLE `empleados` DISABLE KEYS */;
INSERT INTO `empleados` VALUES (1,'Maxwell','984710140','scaybor9@gmail.com','no',5.00);
/*!40000 ALTER TABLE `empleados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_personal`
--

DROP TABLE IF EXISTS `movimientos_personal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos_personal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_personal` varchar(50) DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT 'GASTO',
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_personal`
--

LOCK TABLES `movimientos_personal` WRITE;
/*!40000 ALTER TABLE `movimientos_personal` DISABLE KEYS */;
INSERT INTO `movimientos_personal` VALUES (16,'Maxwell','xxxxxxxxxxxxxx (6 hrs)',30.00,'DESCUENTO','2026-01-20 12:00:00'),(17,'Maxwell','xxxxxxxxxxxxxxx (11 hrs)',55.00,'DESCUENTO','2026-01-21 12:00:00'),(18,'Maxwell','xxxxxxxxxxxx (2 hrs)',10.00,'DESCUENTO','2026-01-22 12:00:00'),(19,'Maxwell','xxxxxxxxxxx (1 hrs)',5.00,'DESCUENTO','2026-01-23 12:00:00'),(20,'Maxwell','xxxxxxxxxx (6 hrs)',30.00,'DESCUENTO','2026-01-24 12:00:00');
/*!40000 ALTER TABLE `movimientos_personal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_nombre` varchar(100) NOT NULL,
  `cliente_telefono` varchar(20) DEFAULT NULL,
  `cliente_email` varchar(255) DEFAULT NULL,
  `cliente_direccion` varchar(255) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `costo_total` decimal(10,2) NOT NULL,
  `a_cuenta` decimal(10,2) DEFAULT '0.00',
  `saldo` decimal(10,2) GENERATED ALWAYS AS ((`costo_total` - `a_cuenta`)) STORED,
  `fecha_recepcion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_entrega` date NOT NULL,
  `estado` varchar(50) DEFAULT 'Pendiente',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` (`id`, `cliente_nombre`, `cliente_telefono`, `cliente_email`, `cliente_direccion`, `descripcion`, `costo_total`, `a_cuenta`, `fecha_recepcion`, `fecha_entrega`, `estado`) VALUES (1,'maxwell','99999','admin@tienda.com','xxxxxxxxxxxxxxxx','hgvfsdhfbdsbfs fsdjfdnfdbdf dfbdjf ',2000.00,100.00,'2026-01-21 22:50:06','2026-01-30','Pendiente'),(2,'colegio ramos ','99999','admin@tienda.com','xxxxxxxxxxxxxxxx','fdff df nbfnsfkjw nnvjfnfw ffvfgnrg fkjcf ',2000.00,100.00,'2026-01-21 22:50:42','2026-01-30','Pendiente'),(3,'colegio eduardo','99999','admin@tienda.com','xxxxxxxxxxxxxxxx','efdjsfjdnfdsjoifds',2000.00,1000.00,'2026-01-22 00:24:34','2026-01-30','Pendiente'),(4,'colegio prueba1','99999','admin@tienda.com','xxxxxxxxxxxxxxxx','efedfgdegfdgdfgdfgdfg',2000.00,2000.00,'2026-01-22 00:37:18','2026-01-29','Pendiente'),(5,'colegio prueba1','99999','admin@tienda.com','xxxxxxxxxxxxxxxx','efedfgdegfdgdfgdfgdfg',2000.00,2000.00,'2026-01-22 00:37:31','2026-01-31','Pendiente'),(6,'colegio de maxwell','99999','admin@tienda.com','xxxxxxxxxxxxxxxx','uguguvhgvvghvgy',2000.00,290.00,'2026-01-22 22:21:08','2026-02-06','Pendiente'),(7,'colegio de maxwell','99999','admin@tienda.com','xxxxxxxxxxxxxxxx','xxxxxxxxxxxxxxx',2000.00,500.00,'2026-01-22 22:25:09','2026-01-31','Pendiente');
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-23  1:13:05
