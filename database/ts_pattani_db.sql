-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ts_pattani_db
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
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(100) NOT NULL,
  `admin_phone` varchar(10) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `admin_role` varchar(50) NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `admin_phone` (`admin_phone`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (2,'เจ้าของสนาม (Admin)','0993241657','$2y$10$UKMnzzV0xtVOrhoy76ZjnuOv/vTftCl0tjrvkwI7qrtxh9rPmaWc6','Super Admin'),(3,'สาดไป พนักงาน','0622210477','$2y$10$AAi0bZC51c8BHaiRbnBQ4.4M3q7EgaxI56uLwO5jT2QmWyf.3TWL6','Admin'),(4,'มูฮัมหมัด ฟาริศ','0123456789','$2y$10$JPCcDFB8A.o6zzfzH270o.rIkWd.ja3GDwkRvR36d1p8xVatXXiVG','Admin'),(5,'แอดมิน สุดหล่อ','0191166919','$2y$10$DBTha71rAC1xWEDZhLK8QO7t86NmR21vRKrXFww9BvJH3yK4Yscau','Admin');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `actor_type` enum('Admin','Member','System') NOT NULL DEFAULT 'System',
  `actor_id` int(11) DEFAULT NULL,
  `actor_name` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_actor` (`actor_type`,`actor_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,'System',0,'System Engine','SYSTEM_INIT','System',NULL,'{\n    \"message\": \"Audit logging engine initialized successfully\"\n}','127.0.0.1','Unknown','2026-11-01 12:07:39'),(2,'Admin',2,'เจ้าของสนาม (Admin)','ADMIN_LOGIN','Admin',2,'เข้าสู่ระบบสำเร็จผ่านเบอร์ 0993241657','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-01 12:54:10'),(3,'Admin',2,'เจ้าของสนาม (Admin)','ADMIN_LOGOUT','Admin',2,'ออกจากระบบ','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-01 12:54:30'),(4,'Admin',2,'เจ้าของสนาม (Admin)','ADMIN_LOGIN','Admin',2,'เข้าสู่ระบบสำเร็จผ่านเบอร์ 0993241657','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-01 12:54:38'),(5,'Admin',2,'เจ้าของสนาม (Admin)','APPROVE_PAYMENT','Payment',49,'{\n    \"booking_id\": 77,\n    \"member_id\": 5,\n    \"amount\": \"200.00\"\n}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-01 13:29:41'),(6,'Member',NULL,'0999999999','LOGIN_FAILED','Member',NULL,'พยายามเข้าสู่ระบบไม่สำเร็จ: 0999999999 (Test fail #1)','127.0.0.1','Unknown','2026-11-01 13:38:35'),(7,'Member',NULL,'0999999999','LOGIN_FAILED','Member',NULL,'พยายามเข้าสู่ระบบไม่สำเร็จ: 0999999999 (Test fail #2)','127.0.0.1','Unknown','2026-11-01 13:38:35'),(8,'Member',NULL,'0999999999','LOGIN_FAILED','Member',NULL,'พยายามเข้าสู่ระบบไม่สำเร็จ: 0999999999 (Test fail #3)','127.0.0.1','Unknown','2026-11-01 13:38:35'),(9,'Member',NULL,'0999999999','LOGIN_FAILED','Member',NULL,'พยายามเข้าสู่ระบบไม่สำเร็จ: 0999999999 (Test fail #4)','127.0.0.1','Unknown','2026-11-01 13:38:35'),(10,'Member',NULL,'0999999999','LOGIN_FAILED','Member',NULL,'พยายามเข้าสู่ระบบไม่สำเร็จ: 0999999999 (Test fail #5)','127.0.0.1','Unknown','2026-11-01 13:38:35'),(11,'Member',NULL,'0993241657','LOGIN_FAILED','Member',NULL,'พยายามเข้าสู่ระบบไม่สำเร็จ: 0993241657 (รหัสผ่านสมาชิกไม่ถูกต้อง)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-01 13:44:37'),(12,'Member',NULL,'0993241657','LOGIN_FAILED','Member',NULL,'พยายามเข้าสู่ระบบไม่สำเร็จ: 0993241657 (รหัสผ่านสมาชิกไม่ถูกต้อง)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-01 13:44:40'),(13,'Admin',2,'เจ้าของสนาม (Admin)','POS_SHIFT_CLOSE','Shift',2,'{\n    \"shift_code\": \"SHIFT-20261101-001\",\n    \"total_revenue\": 9500,\n    \"expected_cash\": 4500,\n    \"actual_cash\": 4500,\n    \"cash_difference\": 0,\n    \"admin_name\": \"เจ้าของสนาม (Admin)\"\n}','127.0.0.1','Unknown','2026-11-01 13:57:48'),(14,'Admin',2,'เจ้าของสนาม (Admin)','POS_CHECKOUT','POS',79,'{\n    \"total_amount\": 200,\n    \"payment_method\": \"cash\",\n    \"customer_phone\": \"\",\n    \"items_count\": 1\n}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-01 14:03:00'),(15,'Admin',2,'เจ้าของสนาม (Admin)','POS_SHIFT_CLOSE','Shift',3,'{\n    \"shift_code\": \"SHIFT-20261101-002\",\n    \"total_revenue\": 400,\n    \"expected_cash\": 0,\n    \"actual_cash\": 300,\n    \"cash_difference\": 300,\n    \"admin_name\": \"เจ้าของสนาม (Admin)\"\n}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-01 14:03:34'),(16,'Admin',2,'เจ้าของสนาม (Admin)','POS_SHIFT_CLOSE','Shift',4,'{\n    \"shift_code\": \"SHIFT-20261101-003\",\n    \"total_revenue\": 400,\n    \"expected_cash\": 1000,\n    \"actual_cash\": 1500,\n    \"cash_difference\": 500,\n    \"admin_name\": \"เจ้าของสนาม (Admin)\"\n}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-01 14:05:47'),(17,'Member',NULL,'0993241657','LOGIN_FAILED','Member',NULL,'พยายามเข้าสู่ระบบไม่สำเร็จ: 0993241657 (รหัสผ่านสมาชิกไม่ถูกต้อง)','192.168.1.18','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/27.0.1 Mobile/15E148 Safari/604.1','2026-11-01 14:30:44'),(18,'Member',5,'Farid cheloh','MEMBER_LOGIN','Member',5,'เข้าสู่ระบบสำเร็จผ่านเบอร์ 0993241657','192.168.1.18','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/27.0.1 Mobile/15E148 Safari/604.1','2026-11-01 14:30:51'),(19,'Admin',2,'เจ้าของสนาม (Admin)','ADMIN_LOGIN','Admin',2,'เข้าสู่ระบบสำเร็จผ่านเบอร์ 0993241657','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-01 14:31:53');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking`
--

DROP TABLE IF EXISTS `booking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) DEFAULT NULL,
  `booking_nickname` varchar(50) DEFAULT NULL,
  `court_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_start_time` time NOT NULL,
  `booking_end_time` time NOT NULL,
  `booking_status` enum('รอตรวจสอบ','จองแล้ว','ยกเลิก') NOT NULL DEFAULT 'รอตรวจสอบ',
  `booking_court_price` decimal(8,2) NOT NULL DEFAULT 0.00,
  `booking_rental_price` decimal(8,2) NOT NULL DEFAULT 0.00,
  `booking_product_price` decimal(8,2) NOT NULL DEFAULT 0.00,
  `booking_total_price` decimal(8,2) NOT NULL DEFAULT 0.00,
  `booking_created_at` datetime DEFAULT current_timestamp(),
  `booking_lock_expire` datetime DEFAULT NULL,
  `payment_slip` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`booking_id`),
  KEY `member_id` (`member_id`),
  KEY `court_id` (`court_id`),
  CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE,
  CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`court_id`) REFERENCES `court` (`court_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking`
--

LOCK TABLES `booking` WRITE;
/*!40000 ALTER TABLE `booking` DISABLE KEYS */;
INSERT INTO `booking` VALUES (6,5,NULL,4,'2026-09-26','20:00:00','22:00:00','จองแล้ว',360.00,360.00,0.00,720.00,'2026-09-26 02:01:07','2026-09-26 02:16:07','slip_6_1790362885.png'),(7,5,NULL,14,'2026-09-28','19:00:00','20:00:00','ยกเลิก',180.00,100.00,0.00,280.00,'2026-09-26 02:04:58','2026-09-26 02:19:58','slip_7_1790363109.png'),(8,6,NULL,4,'2026-09-28','13:00:00','15:00:00','ยกเลิก',360.00,0.00,0.00,360.00,'2026-09-28 19:32:55','2026-09-28 19:47:55','slip_8_1790598900.png'),(9,5,NULL,14,'2026-09-28','15:00:00','16:00:00','จองแล้ว',180.00,0.00,0.00,180.00,'2026-09-28 19:33:41','2026-09-28 19:48:41','slip_9_1790598884.png'),(10,6,NULL,5,'2026-09-28','13:00:00','15:00:00','ยกเลิก',360.00,0.00,0.00,360.00,'2026-09-28 19:38:55','2026-09-28 19:53:55','slip_10_1790599150.png'),(11,6,NULL,4,'2026-09-29','20:00:00','21:00:00','ยกเลิก',150.00,130.00,28.00,308.00,'2026-09-28 23:39:28','2026-09-28 23:54:28','slip_11_1790613582.png'),(12,5,NULL,6,'2026-10-01','14:00:00','15:00:00','ยกเลิก',150.00,0.00,0.00,150.00,'2026-10-01 13:56:39','2026-10-01 14:11:39',NULL),(13,5,NULL,4,'2026-10-05','15:00:00','16:00:00','จองแล้ว',180.00,0.00,0.00,180.00,'2026-10-05 01:26:12','2026-10-05 01:41:12','slip_13_1791138498.png'),(15,5,'ก็จะมาตี',6,'2026-10-05','13:00:00','16:00:00','ยกเลิก',540.00,0.00,40.00,580.00,'2026-10-05 02:57:11','2026-10-05 03:12:11','slip_15_1791143840.png'),(16,5,'Farid Cheloh',4,'2026-10-07','13:00:00','16:00:00','ยกเลิก',540.00,0.00,0.00,540.00,'2026-10-06 11:09:27','2026-10-06 11:24:27',NULL),(17,6,'เปิ้ล',4,'2026-10-06','13:00:00','14:00:00','จองแล้ว',150.00,0.00,0.00,150.00,'2026-10-06 12:46:29','2026-10-06 13:01:29','slip_17_1791265628.png'),(18,5,'Farid Cheloh',6,'2026-10-06','13:00:00','14:00:00','ยกเลิก',150.00,0.00,0.00,150.00,'2026-10-06 12:51:25','2026-10-06 13:06:25','slip_18_1791265892.png'),(19,NULL,NULL,4,'2026-10-06','18:00:00','19:00:00','จองแล้ว',150.00,0.00,0.00,150.00,'2026-10-06 12:55:26',NULL,NULL),(20,NULL,NULL,8,'2026-10-06','12:00:00','13:00:00','จองแล้ว',150.00,0.00,0.00,150.00,'2026-10-06 12:56:58',NULL,NULL),(21,5,'Farid',11,'2026-10-07','20:00:00','22:00:00','ยกเลิก',360.00,0.00,0.00,360.00,'2026-10-06 13:22:03','2026-10-06 13:37:03','slip_21_1791267740.png'),(22,6,'อานัส เปิ้ล',4,'2026-10-06','14:00:00','15:00:00','ยกเลิก',150.00,0.00,0.00,150.00,'2026-10-06 13:25:49','2026-10-06 13:40:49','slip_22_1791267958.png'),(23,5,'Farid Cheloh',4,'2026-10-06','16:00:00','17:00:00','ยกเลิก',150.00,0.00,0.00,150.00,'2026-10-06 14:24:19','2026-10-06 14:39:19','slip_23_1791271475.png'),(24,5,'Farid Cheloh',4,'2026-10-08','22:00:00','23:00:00','ยกเลิก',150.00,50.00,60.00,260.00,'2026-10-08 18:40:22','2026-10-08 18:55:22','slip_24_1791459639.png'),(25,NULL,NULL,6,'2026-10-08','22:00:00','23:00:00','จองแล้ว',150.00,0.00,0.00,150.00,'2026-10-08 18:44:27',NULL,NULL),(26,NULL,NULL,7,'2026-10-08','22:00:00','23:00:00','จองแล้ว',150.00,0.00,0.00,150.00,'2026-10-08 18:45:37',NULL,NULL),(27,5,'Farid Cheloh',5,'2026-10-08','22:00:00','23:00:00','ยกเลิก',150.00,0.00,0.00,150.00,'2026-10-08 20:56:54','2026-10-08 21:11:54',NULL),(28,5,'Farid Cheloh',10,'2026-10-10','20:00:00','21:00:00','ยกเลิก',200.00,0.00,0.00,200.00,'2026-10-10 14:31:58','2026-10-10 14:46:58',NULL),(29,5,'อุอิ๊อ่า',7,'2026-10-10','22:00:00','23:00:00','ยกเลิก',200.00,0.00,0.00,200.00,'2026-10-10 15:22:52','2026-10-10 15:37:52',NULL),(30,5,'Farid Cheloh',14,'2026-10-11','09:00:00','10:00:00','ยกเลิก',200.00,0.00,0.00,200.00,'2026-10-10 21:23:16','2026-10-10 21:38:16',NULL),(31,5,'Farid Cheloh',5,'2026-10-11','08:00:00','09:00:00','ยกเลิก',200.00,0.00,0.00,200.00,'2026-10-10 22:34:05','2026-10-10 22:49:05',NULL),(32,5,'Farid Cheloh',14,'2026-10-11','11:00:00','12:00:00','ยกเลิก',200.00,0.00,0.00,200.00,'2026-10-10 22:34:36','2026-10-10 22:49:36',NULL),(33,5,'Farid',5,'2026-10-11','08:00:00','09:00:00','จองแล้ว',200.00,0.00,0.00,200.00,'2026-10-11 00:15:20','2026-10-11 00:30:20','img_6aca72c6d22879.61736165.png'),(34,5,'kjkk',6,'2026-10-11','08:00:00','12:00:00','ยกเลิก',800.00,0.00,0.00,800.00,'2026-10-11 01:07:23','2026-10-11 01:22:23',NULL),(35,5,'Farid Cheloh',6,'2026-10-12','20:00:00','21:00:00','ยกเลิก',180.00,0.00,0.00,180.00,'2026-10-11 01:55:01','2026-10-11 02:10:01',NULL),(36,7,'Mister bin',6,'2026-10-11','08:00:00','09:00:00','จองแล้ว',200.00,0.00,0.00,200.00,'2026-10-11 02:23:10','2026-10-11 02:38:10','img_6aca90a56a1a68.46593125.png'),(37,8,'ซุรฮาน แดรอ',5,'2026-10-12','08:00:00','09:00:00','จองแล้ว',180.00,0.00,0.00,180.00,'2026-10-11 16:20:57','2026-10-11 16:35:57','img_6acb55160e98d2.33731579.png'),(38,5,'M.Fariz CHeloh',6,'2026-10-12','08:00:00','09:00:00','จองแล้ว',180.00,0.00,0.00,180.00,'2026-10-12 00:34:10','2026-10-12 00:49:10','img_6acbcaf85c7780.18126146.png'),(39,6,'อานัส เปิ้ล',5,'2026-10-14','08:00:00','12:00:00','จองแล้ว',720.00,0.00,0.00,720.00,'2026-10-12 00:49:15','2026-10-12 01:04:15','img_6acbcc2c86f9e5.50831811.png'),(40,8,'ซุรฮานคนอ้วน',4,'2026-10-12','17:00:00','18:00:00','ยกเลิก',180.00,0.00,0.00,180.00,'2026-10-12 16:41:22','2026-10-12 16:56:22','img_6accab5180bac5.06551762.png'),(41,NULL,NULL,5,'2026-10-12','17:00:00','18:00:00','จองแล้ว',180.00,0.00,0.00,180.00,'2026-10-12 16:42:16',NULL,NULL),(42,5,'M.Fariz CHeloh',7,'2026-10-13','21:00:00','22:00:00','ยกเลิก',150.00,0.00,0.00,150.00,'2026-10-13 03:06:13','2026-10-13 03:21:13',NULL),(43,5,'M.Fariz CHeloh',4,'2026-10-16','16:00:00','18:00:00','ยกเลิก',330.00,0.00,0.00,330.00,'2026-10-16 01:04:15','2026-10-16 01:19:15',NULL),(44,5,'M.Fariz CHeloh',6,'2026-10-19','10:00:00','12:00:00','จองแล้ว',300.00,0.00,0.00,300.00,'2026-10-19 02:02:06','2026-10-19 02:17:06','img_6ad517bd014438.07503855.png'),(45,7,'จีมีไนท์ เท่สุด',12,'2026-10-20','19:00:00','23:00:00','จองแล้ว',720.00,160.00,0.00,880.00,'2026-10-19 03:54:13','2026-10-19 04:09:13','img_6ad5320034cc89.08644906.png'),(46,9,'ฟาดีละห์',7,'2026-10-19','15:00:00','17:00:00','จองแล้ว',300.00,0.00,0.00,300.00,'2026-10-19 04:14:24','2026-10-19 04:29:24','img_6ad536c34c7cc0.49819652.png'),(47,NULL,NULL,4,'2026-10-19','08:00:00','09:00:00','จองแล้ว',150.00,0.00,0.00,150.00,'2026-10-19 04:30:32',NULL,NULL),(48,9,'ฟาดีละห์ เย็งยูนอ',6,'2026-10-19','17:00:00','18:00:00','ยกเลิก',180.00,0.00,0.00,180.00,'2026-10-19 16:54:24','2026-10-19 17:09:24',NULL),(49,5,'M.Fariz CHeloh',4,'2026-10-20','08:00:00','09:00:00','ยกเลิก',150.00,0.00,0.00,150.00,'2026-10-20 01:50:47','2026-10-20 02:05:47',NULL),(50,6,'อานัส เปิ้ล',4,'2026-10-20','08:00:00','12:00:00','จองแล้ว',600.00,0.00,120.00,720.00,'2026-10-20 02:47:32','2026-10-20 03:02:32','img_6ad673e7027f04.66799394.png'),(51,8,'ซุรฮาน แดรอ',11,'2026-10-20','08:00:00','11:00:00','จองแล้ว',450.00,0.00,250.00,700.00,'2026-10-20 03:07:19','2026-10-20 03:22:19','img_6ad6788900aa32.25943267.png'),(52,10,'ร๊ารา🎃',8,'2026-10-20','14:00:00','16:00:00','จองแล้ว',300.00,260.00,110.00,670.00,'2026-10-20 12:14:38','2026-10-20 12:29:38','img_6ad6f995a59223.18147841.jpg'),(53,9,'ฟาดีละห์ เย็งยูนอ',5,'2026-10-20','14:00:00','17:00:00','จองแล้ว',450.00,640.00,0.00,1090.00,'2026-10-20 14:00:03','2026-10-20 14:15:03','img_6ad711812bef08.48967839.jpg'),(54,6,'เปิ้ล',12,'2026-10-20','15:00:00','17:00:00','จองแล้ว',300.00,0.00,200.00,500.00,'2026-10-20 14:26:08','2026-10-20 14:41:08','img_6ad717a12ed411.08593535.jpg'),(55,5,'Farid cheloh',14,'2026-10-21','19:00:00','21:00:00','จองแล้ว',360.00,0.00,0.00,360.00,'2026-10-20 14:27:29','2026-10-20 14:42:29','img_6ad717e7d77a93.42104114.jpg'),(56,5,'ริส',4,'2026-10-20','19:00:00','23:00:00','จองแล้ว',720.00,400.00,0.00,1120.00,'2026-10-20 14:28:33','2026-10-20 14:43:33','img_6ad718298d2e22.80196507.jpg'),(57,5,'Farid cheloh',4,'2026-10-20','15:00:00','18:00:00','จองแล้ว',480.00,0.00,0.00,480.00,'2026-10-20 14:44:56','2026-10-20 14:59:56','img_6ad71c11c8a227.53839864.jpg'),(58,9,'ฟาดีละห์ เย็งยูนอ',6,'2026-10-20','15:00:00','16:00:00','ยกเลิก',150.00,340.00,30.00,520.00,'2026-10-20 14:57:54','2026-10-20 15:12:54','img_6ad71f0a34cf00.12773605.jpg'),(59,7,'กิต เรนเจอร์',4,'2026-10-22','21:00:00','23:00:00','ยกเลิก',360.00,0.00,0.00,360.00,'2026-10-22 20:18:04','2026-10-22 20:33:04','img_6ada0d14248659.10043930.jpg'),(60,5,'Ridddddddddddd',4,'2026-10-22','21:00:00','22:00:00','จองแล้ว',180.00,0.00,0.00,180.00,'2026-10-22 20:35:26','2026-10-22 20:50:26','img_6ada119cc4f269.03070733.jpg'),(61,9,'หมาล่า',4,'2026-10-22','22:00:00','23:00:00','ยกเลิก',180.00,0.00,0.00,180.00,'2026-10-22 21:30:28','2026-10-22 21:45:28',NULL),(62,5,'Farid cheloh',5,'2026-10-22','22:00:00','23:00:00','จองแล้ว',180.00,0.00,0.00,180.00,'2026-10-22 21:30:59','2026-10-22 21:45:59','img_6ada1e29351d82.05307754.jpg'),(63,6,'อานัส เปิ้ล',13,'2026-10-23','08:00:00','09:00:00','ยกเลิก',150.00,0.00,0.00,150.00,'2026-10-22 22:05:35','2026-10-22 22:20:35',NULL),(64,5,'Farid cheloh',14,'2026-10-23','15:00:00','16:00:00','ยกเลิก',150.00,0.00,0.00,150.00,'2026-10-23 15:02:15','2026-10-23 15:17:15',NULL),(65,5,'Farid cheloh',4,'2026-10-23','16:00:00','18:00:00','ยกเลิก',330.00,0.00,0.00,330.00,'2026-10-23 15:37:13','2026-10-23 15:52:13','img_6adb1cbe4fbe02.64549875.jpg'),(66,5,'อุอิ๊อ่า',4,'2026-10-26','16:00:00','18:00:00','จองแล้ว',330.00,150.00,53.00,533.00,'2026-10-26 15:14:30','2026-10-26 15:29:30','img_6adf0bfa4727c2.77814165.jpg'),(67,5,'cimmmmm',5,'2026-10-26','17:00:00','19:00:00','จองแล้ว',360.00,180.00,30.00,570.00,'2026-10-26 15:30:44','2026-10-26 15:45:44','img_6adf0fced875c6.45187375.jpg'),(68,5,'อะไรก็ได้',7,'2026-10-27','20:00:00','22:00:00','จองแล้ว',360.00,80.00,90.00,530.00,'2026-10-26 16:12:11','2026-10-26 16:27:11','img_6adf19926340f1.41538627.jpg'),(69,5,'อะไรก็ได้',4,'2026-10-27','16:00:00','18:00:00','ยกเลิก',330.00,160.00,0.00,490.00,'2026-10-26 19:00:08','2026-10-26 19:15:08',NULL),(70,5,'Farid cheloh',5,'2026-10-26','21:00:00','22:00:00','ยกเลิก',180.00,180.00,0.00,360.00,'2026-10-26 19:18:20','2026-10-26 19:33:20',NULL),(71,5,'whatt',4,'2026-10-27','08:00:00','10:00:00','จองแล้ว',300.00,160.00,0.00,460.00,'2026-10-26 23:24:38','2026-10-26 23:39:38','img_6adf7edf26c3c3.96539585.jpg'),(72,7,'กิต เรนเจอร์',6,'2026-10-27','08:00:00','12:00:00','ยกเลิก',600.00,0.00,0.00,600.00,'2026-10-27 00:04:02','2026-10-27 00:19:02','img_6adf8807952d43.56070004.jpg'),(73,7,'กิต เรนเจอร์',6,'2026-10-27','08:00:00','11:00:00','ยกเลิก',450.00,0.00,0.00,450.00,'2026-10-27 00:06:46','2026-10-27 00:21:46','img_6adf88abde8c34.81748345.jpg'),(74,7,'อนุทินมาตีแบด',6,'2026-10-27','08:00:00','11:00:00','จองแล้ว',450.00,0.00,0.00,450.00,'2026-10-27 00:12:23','2026-10-27 00:27:23','img_6adf89fd0440c0.09592982.jpg'),(75,5,'cim5555555',5,'2026-10-27','08:00:00','09:00:00','จองแล้ว',150.00,160.00,0.00,310.00,'2026-10-27 00:17:30','2026-10-27 00:32:30','img_6adf8b3cb71042.84119567.jpg'),(76,5,'อะไรก็ได้',7,'2026-10-27','08:00:00','11:00:00','จองแล้ว',450.00,130.00,12.00,592.00,'2026-10-27 01:09:03','2026-10-27 01:24:03','img_6adf975b047a68.63750782.jpg'),(77,5,'Farid cheloh',4,'2026-11-01','14:00:00','15:00:00','จองแล้ว',200.00,0.00,0.00,200.00,'2026-11-01 13:11:06','2026-11-01 13:26:06','img_6ae6d802cb2160.64669397.jpg'),(78,5,'Farid cheloh',12,'2026-11-02','22:00:00','23:00:00','รอตรวจสอบ',180.00,0.00,0.00,180.00,'2026-11-01 13:30:05','2026-11-01 13:45:05','img_6ae6dc72728e18.02934419.jpg'),(79,NULL,NULL,5,'2026-11-01','14:00:00','15:00:00','จองแล้ว',200.00,0.00,0.00,200.00,'2026-11-01 14:03:00',NULL,NULL),(80,5,'Farid cheloh',4,'2026-11-02','08:00:00','09:00:00','รอตรวจสอบ',150.00,80.00,0.00,230.00,'2026-11-01 14:31:23','2026-11-01 14:46:23','img_6ae6eada73f280.46170968.jpeg');
/*!40000 ALTER TABLE `booking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cancellation`
--

DROP TABLE IF EXISTS `cancellation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cancellation` (
  `cancel_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `cancel_reason` varchar(255) NOT NULL,
  `cancel_by` enum('สมาชิก','Admin') NOT NULL,
  `refund_status` enum('คืนแล้ว','ไม่คืน','รอดำเนินการ') DEFAULT 'รอดำเนินการ',
  `refund_bank` varchar(50) DEFAULT NULL,
  `refund_account_no` varchar(30) DEFAULT NULL,
  `refund_account_name` varchar(100) DEFAULT NULL,
  `refund_point` int(11) DEFAULT 0,
  `cancel_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`cancel_id`),
  KEY `booking_id` (`booking_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `cancellation_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `cancellation_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cancellation`
--

LOCK TABLES `cancellation` WRITE;
/*!40000 ALTER TABLE `cancellation` DISABLE KEYS */;
INSERT INTO `cancellation` VALUES (2,8,2,'ไม่สะดวก','สมาชิก','คืนแล้ว',NULL,NULL,NULL,0,'2026-09-28 19:52:18'),(3,11,2,'ติดธุระ','สมาชิก','คืนแล้ว',NULL,NULL,NULL,0,'2026-09-28 23:40:22'),(4,12,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-05 01:25:52'),(5,16,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-06 12:38:11'),(6,17,2,'ไม่สะดวก','สมาชิก','คืนแล้ว',NULL,NULL,NULL,0,'2026-10-06 12:49:45'),(7,18,2,'ไม่ว่างอีกแล้ว','สมาชิก','คืนแล้ว',NULL,NULL,NULL,0,'2026-10-06 12:52:40'),(8,21,NULL,'มีประชุม (ยกเลิกระหว่างรอตรวจสอบ - ไม่มีสิทธิ์ขอคืนเงิน)','สมาชิก','ไม่คืน',NULL,NULL,NULL,0,'2026-10-06 13:24:06'),(9,22,2,'ขอไม่ตอบ','สมาชิก','คืนแล้ว',NULL,NULL,NULL,0,'2026-10-06 13:26:55'),(10,23,2,'ม่วาง (ลูกค้ายกเลิกหลังแนบสลิปชำระเงิน - รอตรวจสลิปและโอนเงินคืน 100%)','สมาชิก','คืนแล้ว','พร้อมเพย์ (PromptPay)','0993241657','Farid Cheloh',0,'2026-10-06 14:24:58'),(11,24,2,'ลืมว่ามีสอบ (ลูกค้ายกเลิกหลังแนบสลิปชำระเงิน - รอตรวจสลิปและโอนเงินคืน 100%)','สมาชิก','ไม่คืน','พร้อมเพย์ (PromptPay)','0993241657','Farid Cheloh',0,'2026-10-08 18:41:45'),(12,27,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-08 23:01:58'),(13,28,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-10 15:11:45'),(14,29,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-10 21:22:04'),(15,30,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-10 21:43:05'),(16,31,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-10 23:07:14'),(17,32,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-10 23:07:14'),(18,34,NULL,'กดผิด (ยกเลิกก่อนชำระเงิน)','สมาชิก','ไม่คืน',NULL,NULL,NULL,0,'2026-10-11 01:10:41'),(19,35,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-11 02:18:39'),(20,42,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-13 03:21:44'),(21,43,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-16 01:36:36'),(22,48,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-20 01:36:53'),(23,49,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-20 02:47:00'),(24,59,2,'Busy (ลูกค้ายกเลิกหลังแนบสลิปชำระเงิน - รอตรวจสลิปและโอนเงินคืน 100%)','สมาชิก','คืนแล้ว','พร้อมเพย์ (PromptPay)','0993241658','กิต เรนเจอร์',0,'2026-10-22 20:18:30'),(25,61,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-22 21:46:25'),(26,63,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-23 14:41:06'),(27,64,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-23 15:36:59'),(28,65,2,'ไม่ว่าง (ลูกค้ายกเลิกหลังแนบสลิปชำระเงิน - รอตรวจสลิปและโอนเงินคืน 100%)','สมาชิก','คืนแล้ว','พร้อมเพย์ (PromptPay)','0993241657','Farid cheloh',0,'2026-10-23 15:39:03'),(29,69,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-26 19:16:09'),(30,70,NULL,'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)','Admin','ไม่คืน',NULL,NULL,NULL,0,'2026-10-26 23:23:13'),(31,72,2,'9bfT6it','สมาชิก','คืนแล้ว','พร้อมเพย์ (PromptPay)','0993241658','กิต เรนเจอร์',0,'2026-10-27 00:05:42'),(32,73,2,'wef','สมาชิก','ไม่คืน','พร้อมเพย์ (PromptPay)','0993241658','กิต เรนเจอร์',0,'2026-10-27 00:11:28');
/*!40000 ALTER TABLE `cancellation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat`
--

DROP TABLE IF EXISTS `chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat` (
  `chat_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `chat_message` text NOT NULL,
  `chat_sender` enum('สมาชิก','Admin') NOT NULL,
  `chat_datetime` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`chat_id`),
  KEY `member_id` (`member_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `chat_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE,
  CONSTRAINT `chat_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat`
--

LOCK TABLES `chat` WRITE;
/*!40000 ALTER TABLE `chat` DISABLE KEYS */;
INSERT INTO `chat` VALUES (2,5,NULL,'สวัสดีครับ','สมาชิก','2026-09-25 23:20:55'),(3,5,NULL,'ถ้าต้องการกดยกเลิกทำยังไง','สมาชิก','2026-09-25 23:21:02'),(4,5,2,'สักครู่','Admin','2026-09-25 23:21:13'),(5,5,NULL,'คือว่า สนามนี้ระบบยังไม่สมบูรณ์เลย','สมาชิก','2026-09-26 01:10:48'),(6,5,2,'ขออภัย แอดมินไม่ฉลาดพอ','Admin','2026-09-26 01:11:23'),(7,8,NULL,'สวัสดีแอดมิน','สมาชิก','2026-10-12 15:59:58'),(8,8,2,'คุณมีอะไร','Admin','2026-10-12 16:00:12'),(9,9,NULL,'สวัสดี','สมาชิก','2026-10-16 02:09:57'),(10,8,2,'อะไรไออ้วน','Admin','2026-10-18 00:24:01'),(11,9,NULL,'ตอบฉันหน่อยยย','สมาชิก','2026-10-19 04:15:42'),(12,9,2,'ว่าไงงงงงงงงงงงงงงงงงงงงง','Admin','2026-10-19 04:15:52'),(13,9,NULL,'อยากเล่นแบบฟรี','สมาชิก','2026-10-19 04:16:09'),(14,9,2,'ของฟรีไม่มีในโลก','Admin','2026-10-22 20:21:01'),(15,5,NULL,'สวัสดีครับ','สมาชิก','2026-10-26 15:34:30'),(16,5,2,'ว่าอะไรครับ','Admin','2026-10-26 15:34:45'),(17,5,2,'555555555','Admin','2026-10-26 18:56:04');
/*!40000 ALTER TABLE `chat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `court`
--

DROP TABLE IF EXISTS `court`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `court` (
  `court_id` int(11) NOT NULL AUTO_INCREMENT,
  `court_name` varchar(50) NOT NULL,
  `court_status` enum('ว่าง','รอตรวจสอบ','จองแล้ว','ปิดปรับปรุง') NOT NULL DEFAULT 'ว่าง',
  `court_price_per_hour` decimal(8,2) NOT NULL,
  `court_peak_price` decimal(8,2) DEFAULT NULL,
  `court_offpeak_price` decimal(8,2) DEFAULT NULL,
  `court_open_time` time DEFAULT NULL,
  `court_close_time` time DEFAULT NULL,
  PRIMARY KEY (`court_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `court`
--

LOCK TABLES `court` WRITE;
/*!40000 ALTER TABLE `court` DISABLE KEYS */;
INSERT INTO `court` VALUES (4,'courts 1','ว่าง',180.00,200.00,150.00,'08:00:00','23:00:00'),(5,'courts 2','ว่าง',180.00,200.00,150.00,'08:00:00','23:00:00'),(6,'courts 3','ว่าง',180.00,200.00,150.00,'08:00:00','23:00:00'),(7,'courts 4','ว่าง',180.00,200.00,150.00,'08:00:00','23:00:00'),(8,'courts 5','ว่าง',180.00,200.00,150.00,'08:00:00','23:00:00'),(10,'courts 6','ว่าง',180.00,200.00,150.00,'08:00:00','23:00:00'),(11,'courts 7','ว่าง',180.00,200.00,150.00,'08:00:00','23:00:00'),(12,'courts 8','ว่าง',180.00,200.00,150.00,'08:00:00','23:00:00'),(13,'courts 9','ว่าง',180.00,200.00,150.00,'08:00:00','23:00:00'),(14,'courts 10','ว่าง',180.00,200.00,150.00,'08:00:00','23:00:00');
/*!40000 ALTER TABLE `court` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `court_repair`
--

DROP TABLE IF EXISTS `court_repair`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `court_repair` (
  `repair_id` int(11) NOT NULL AUTO_INCREMENT,
  `court_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `repair_cause` varchar(255) NOT NULL,
  `repair_start_date` date NOT NULL,
  `repair_expected_end` date DEFAULT NULL,
  `repair_actual_end` date DEFAULT NULL,
  `repair_cost` decimal(8,2) DEFAULT 0.00,
  `repair_status` enum('กำลังซ่อม','เสร็จแล้ว') NOT NULL DEFAULT 'กำลังซ่อม',
  PRIMARY KEY (`repair_id`),
  KEY `court_id` (`court_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `court_repair_ibfk_1` FOREIGN KEY (`court_id`) REFERENCES `court` (`court_id`) ON DELETE CASCADE,
  CONSTRAINT `court_repair_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `court_repair`
--

LOCK TABLES `court_repair` WRITE;
/*!40000 ALTER TABLE `court_repair` DISABLE KEYS */;
INSERT INTO `court_repair` VALUES (7,5,2,'พื้นยางหลวม','2026-10-05','2026-10-06','2026-10-08',0.00,'เสร็จแล้ว'),(8,14,2,'เปลี่ยนพื้นยาง','2026-10-16','2026-10-17','2026-10-17',500.00,'เสร็จแล้ว'),(9,7,2,'ไฟดับ','2026-10-22','2026-10-23','2026-10-23',300.00,'เสร็จแล้ว');
/*!40000 ALTER TABLE `court_repair` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_repair`
--

DROP TABLE IF EXISTS `equipment_repair`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment_repair` (
  `eq_repair_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `eq_repair_qty` int(11) NOT NULL DEFAULT 1,
  `eq_repair_cause` varchar(255) NOT NULL,
  `eq_repair_date` date NOT NULL,
  `eq_repair_status` enum('กำลังซ่อม','ซ่อมแล้ว','เสียหายถาวร') NOT NULL DEFAULT 'กำลังซ่อม',
  `eq_repair_cost` decimal(8,2) DEFAULT 0.00,
  PRIMARY KEY (`eq_repair_id`),
  KEY `product_id` (`product_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `equipment_repair_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `equipment_repair_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_repair`
--

LOCK TABLES `equipment_repair` WRITE;
/*!40000 ALTER TABLE `equipment_repair` DISABLE KEYS */;
INSERT INTO `equipment_repair` VALUES (4,35,2,1,'เอ็นขาด','2026-09-26','ซ่อมแล้ว',280.00),(5,41,2,1,'เปลี่ยนเอ็น','2026-10-16','ซ่อมแล้ว',50.00),(6,43,2,1,'เปียก','2026-10-19','ซ่อมแล้ว',50.00);
/*!40000 ALTER TABLE `equipment_repair` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_type` enum('Admin','Member') NOT NULL,
  `attempted_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`attempt_id`),
  KEY `idx_identifier` (`identifier`,`user_type`,`attempted_at`),
  KEY `idx_ip` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member`
--

DROP TABLE IF EXISTS `member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_gender` enum('ชาย','หญิง','อื่นๆ') DEFAULT 'อื่นๆ',
  `member_name` varchar(100) NOT NULL,
  `member_phone` varchar(10) NOT NULL,
  `member_password` varchar(255) NOT NULL,
  `member_age` int(3) DEFAULT NULL,
  `member_occupation` varchar(100) DEFAULT NULL,
  `member_avatar` varchar(255) DEFAULT NULL,
  `member_status` enum('ปกติ','ระงับสิทธิ์') DEFAULT 'ปกติ',
  `member_created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `member_phone` (`member_phone`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member`
--

LOCK TABLES `member` WRITE;
/*!40000 ALTER TABLE `member` DISABLE KEYS */;
INSERT INTO `member` VALUES (5,'ชาย','Farid cheloh','0993241657','$2y$10$mJd9YiPhd0/tpF5LmszSQOxEYk/4iNI0drwDmF5Ilgr9vfTDfTbbu',21,'ราชการ','ปกติ','2026-09-25 23:17:44'),(6,'ชาย','อานัส เปิ้ล','0123456789','$2y$10$V.Tqc7YGw2cPUIVEOi490.5GKtcMCOpZ3yVLulHLLryHTZFuaemRG',22,'นักศึกษา','ปกติ','2026-09-25 23:22:51'),(7,'ชาย','กิต เรนเจอร์','0993241658','$2y$10$83u8/i0Vm0b0mBr811tRieMuR2OgR.RnW969iS4vQxqVfZmNqi5DG',49,'ธุรกิจส่วนตัว / ค้าขาย','ปกติ','2026-10-11 02:22:04'),(8,'ชาย','ซุรฮาน แดรอ','0012345678','$2y$10$00j7beJrVquuVXpb6dArCeehKt/CP4qtUhYpA893q.4vECrJ6rjpu',22,'ธุรกิจส่วนตัว / ค้าขาย','ปกติ','2026-10-11 15:50:28'),(9,'หญิง','ฟาดีละห์ เย็งยูนอ','0832211015','$2y$10$uxZnj0t98.Dr1vEqhJ3zSeW3PaEH4l5S5jKwZOwrEHciEI8nOpODi',22,'นักเรียน / นักศึกษา','ปกติ','2026-10-16 02:08:20'),(10,'หญิง','นาดีรา มะเสน','0987890013','$2y$10$HaOwXGWjG6A3U3SB1/9pBO/ice2qRzeWMRdAwzaht60pWk18jliO.',40,'ธุรกิจส่วนตัว / ค้าขาย','ปกติ','2026-10-20 02:27:53'),(11,'ชาย','Farid CIM','0996100166','$2y$10$ER7iJRQpx4zvQqgJDXBjC.urs.2uSBS4IrA.whfIsZNQtmMIJS5lK',21,'นักเรียน / นักศึกษา','ปกติ','2026-10-26 15:13:53'),(12,'ชาย','Fariddd cim','0993241016','$2y$10$75V.emgRAVk1v26/omUf8eoS20mCYZcG4PN20uw9X3NPJirZLCDma',21,'นักเรียน / นักศึกษา','ปกติ','2026-10-26 15:27:30'),(13,'ชาย','farrr ridd','0993241166','$2y$10$ZKUj1rHiG35J.gRa09/cCuwFOSMeL9pPKCdmmOLdCmZTPfnDoL7me',25,'นักเรียน / นักศึกษา','ปกติ','2026-10-26 16:09:03');
/*!40000 ALTER TABLE `member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `news_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `news_title` varchar(255) NOT NULL,
  `news_content` text NOT NULL,
  `news_image` varchar(255) DEFAULT NULL,
  `news_published_at` datetime DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `news_type` enum('โปรโมชั่น','ข่าวสารกิจกรรม') NOT NULL DEFAULT 'ข่าวสารกิจกรรม',
  PRIMARY KEY (`news_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `news_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (3,2,'\"เมย์\" รัชนก อินทนนท์ โชว์ฟอร์มสมราคาเต็ง 1 รัวแซงชนะ ลีเน่ คริสโตเฟอร์เซ่น 2-1 เกม ทะลุชนรอบชิงชนะเลิศศึกแบดมินตัน อาร์กติก โอเพ่น 2026','การแข่งขันแบดมินตันระดับเวิลด์ทัวร์ ซูเปอร์ 500 รายการ อาร์กติก โอเพ่น 2026 ชิงเงินรางวัลรวม 500,000 เหรียญสหรัฐ (ประมาณ 16,800,000 บาท) ที่เมืองวันตา ประเทศฟินแลนด์ เมื่อวันเสาร์ที่ 10 ตุลาคม 2569 เป็นการลงสนามในรอบรองชนะเลิศ\r\nประเภทหญิงเดี่ยว \"เมย์\" รัชนก อินทนนท์ ยอดนักแบดมินตันสาวไทย มือวางอันดับ 1 ของรายการ และมืออันดับ 5 ของโลก ลงสนามพบกับ ลีเน่ คริสโตเฟอร์เซ่น มือวางอันดับ 7 ของรายการ มืออันดับ 13 ของโลกจากเดนมาร์ก','1791649194_1445.png','2026-10-10 23:19:54','active','ข่าวสารกิจกรรม'),(5,2,'เปิดรับสมัคการแข่งขัน 2028','เปิดรับสมัครแล้ว! \r\nASICS Badminton Championship 2026\r\nแข่งแบดมินตันประเภทคู่ 8 รุ่น ตั้งแต่ U12 จนถึง Open\r\nชิงเงินรางวัลรวม 204,000 บาท พร้อมของรางวัล \r\nรวมมูลค่ากว่า 370,000 บาท\r\n🏆 รางวัลชนะเลิศ รับรองเท้า ASICS ทุกรุ่นการแข่งขัน\r\n📅 อาทิตย์ 8 พ.ย. 2569 | 📍 BEAT Discovery\r\n📝 รับสมัครถึง 1 พ.ย. 2569 | รับสูงสุด 112 คู่\r\nสมัครได้แล้วที่ 👉https://asics-thailand.visitlink.me/h5mbmc','1791735201_7419.jpg','2026-10-11 23:13:21','active','ข่าวสารกิจกรรม'),(6,2,'วิว กุลวุฒิ','วิว กุลวุฒิ วิทิตศานต์ ที่สุดของวงการแบดมินตันไทยและวงการแบดมินตันโลกยุคใหม่นี้ ได้เหรียญรางวัลเกียรติยศครบมือแล้ว และครองมือ 1 ของโลกอย่างสมบูรณ์แบบ\r\n\r\n1️⃣ ครองมือ 1 ของโลก\r\n🥇🥇🥇 แชมป์เยาวชนโลก 3 สมัย\r\n🥇แชมป์โลก\r\n🥇คว้าเหรียญทองซีเกมส์\r\n🥇คว้าแชมป์เอเชีย\r\n🥇เหรียญทอง Asian Games 2026\r\n🥈 เหรียญเงินโอลิมปิก\r\n🥈🥈 รองแชมป์โลก\r\n8️⃣ คว้าแชมป์ World Tour 8 รายการ\r\n🥈รองแชมป์ World Tour Finals','1791793300_3106.jpg','2026-10-12 15:21:40','active','ข่าวสารกิจกรรม'),(7,2,'โปรโมชั่นสำหรับลูกค้า','ยิ่งเล่น ยิ่งคุ้ม! สำหรับลูกค้าที่จองสนามแบดมินตัน T.S. Pattani ครบ 2 ชั่วโมงต่อการจอง รับน้ำดื่มฟรี 1 ขวดต่อรายการจอง มาออกกำลังกายกับเพื่อน ๆ เติมพลังระหว่างเกม และสนุกกับทุกแมตช์ได้อย่างเต็มที่','1792472336_6318.png','2026-10-20 11:58:57','active','ข่าวสารกิจกรรม'),(8,2,'อัน เซยอง สร้างประวัติศาสตร์ คว้าแชมป์นานาชาติรายการที่ 47','อัน เซยอง นักแบดมินตันหญิงชาวเกาหลีใต้ สร้างสถิติครั้งสำคัญด้วยการคว้าแชมป์นานาชาติรายการที่ 47 หลังเอาชนะ อากาเนะ ยามากูจิ จากญี่ปุ่น ในรอบชิงชนะเลิศ Denmark Open 2026 ด้วยคะแนน 21–23, 21–14 และ 21–18 ความสำเร็จครั้งนี้ทำให้เธอขึ้นเป็นนักแบดมินตันหญิงเดี่ยวที่คว้าแชมป์มากที่สุดตามสถิติที่มีการบันทึกไว้ของกีฬานี้','1792472468_5780.jpg','2026-10-20 12:01:08','active','ข่าวสารกิจกรรม'),(9,2,'อเล็กซ์ ลาเนียร์ คว้าแชมป์โลกชายเดี่ยว แบดมินตันชิงแชมป์โลก 2026','อเล็กซ์ ลาเนียร์ นักแบดมินตันจากฝรั่งเศส คว้าเหรียญทองประเภทชายเดี่ยวในการแข่งขัน BWF World Championships 2026 ที่กรุงนิวเดลี ประเทศอินเดีย โดยเอาชนะ โคได นาราโอกะ จากญี่ปุ่นในรอบชิงชนะเลิศ ความสำเร็จนี้เป็นอีกหนึ่งหมุดหมายสำคัญของวงการแบดมินตันฝรั่งเศสบนเวทีระดับโลก','1792472544_6442.jpg','2026-10-20 12:02:24','active','ข่าวสารกิจกรรม');
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_member_id` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (2,5,'7f97869b7e119ec679b9c9388a9e4a01d652da523e83a85a6a539987179e0d12','2026-10-12 02:01:53',1,'2026-10-12 01:31:53'),(3,8,'6477702773cf9f08618019b341b503d82c5f5db2f9be2b5d6aa3928543df3886','2026-10-12 15:16:14',1,'2026-10-12 14:46:14'),(4,7,'e9c8450d1735f301200613c057c7266a0b7c9230964d352ed99c386ced422c75','2026-10-19 04:20:09',1,'2026-10-19 03:50:09'),(5,9,'dfbd67415d223d78265dbd316bcd58682d307ff2f442e1e0744b9b17f52fcf90','2026-10-19 04:43:38',1,'2026-10-19 04:13:38'),(6,7,'6168452fa517f50d6ffe4fefe293538d119ed00415149b28a61272661722c13f','2026-10-20 12:33:57',1,'2026-10-20 12:03:57'),(7,5,'c78017584f48fa0ca22853eaf38e247ee77fb2d3314daf4a4d9a0d81064a0158','2026-10-22 20:58:34',0,'2026-10-22 20:28:34');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `payment_slip_image` varchar(255) NOT NULL,
  `payment_amount` decimal(8,2) NOT NULL,
  `payment_transfer_time` datetime NOT NULL,
  `payment_status` enum('รอตรวจสอบ','ยืนยันแล้ว','ปฏิเสธ') NOT NULL DEFAULT 'รอตรวจสอบ',
  `payment_verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `booking_id` (`booking_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment`
--

LOCK TABLES `payment` WRITE;
/*!40000 ALTER TABLE `payment` DISABLE KEYS */;
INSERT INTO `payment` VALUES (4,6,2,'slip_6_1790362885.png',720.00,'2026-09-26 02:01:25','ยืนยันแล้ว','2026-09-26 02:02:00'),(5,7,2,'slip_7_1790363109.png',280.00,'2026-09-26 02:05:09','ปฏิเสธ','2026-09-26 02:06:24'),(6,9,2,'slip_9_1790598884.png',180.00,'2026-09-28 19:34:44','ยืนยันแล้ว','2026-09-28 23:09:31'),(7,8,2,'slip_8_1790598900.png',360.00,'2026-09-28 19:35:00','ยืนยันแล้ว','2026-09-28 19:35:36'),(8,10,2,'slip_10_1790599150.png',360.00,'2026-09-28 19:39:10','ปฏิเสธ','2026-09-28 23:31:18'),(9,11,2,'slip_11_1790613582.png',308.00,'2026-09-28 23:39:42','ยืนยันแล้ว','2026-09-28 23:39:58'),(10,13,2,'slip_13_1791138498.png',180.00,'2026-10-05 01:28:18','ยืนยันแล้ว','2026-10-05 01:49:13'),(11,15,2,'slip_15_1791143840.png',580.00,'2026-10-05 02:57:20','ปฏิเสธ','2026-10-06 13:23:33'),(12,17,2,'slip_17_1791265628.png',150.00,'2026-10-06 12:47:08','ยืนยันแล้ว','2026-10-06 12:52:08'),(13,18,2,'slip_18_1791265892.png',150.00,'2026-10-06 12:51:32','ยืนยันแล้ว','2026-10-06 12:52:13'),(14,21,NULL,'slip_21_1791267740.png',360.00,'2026-10-06 13:22:20','ปฏิเสธ',NULL),(15,22,2,'slip_22_1791267958.png',150.00,'2026-10-06 13:25:58','ยืนยันแล้ว','2026-10-06 13:26:13'),(16,23,2,'slip_23_1791271475.png',150.00,'2026-10-06 14:24:35','ยืนยันแล้ว','2026-10-06 14:25:25'),(17,24,2,'slip_24_1791459639.png',260.00,'2026-10-08 18:40:39','ปฏิเสธ','2026-10-08 18:42:32'),(18,33,2,'img_6aca72c6d22879.61736165.png',200.00,'2026-10-11 00:15:50','ยืนยันแล้ว','2026-10-11 00:16:15'),(19,36,2,'img_6aca90a56a1a68.46593125.png',200.00,'2026-10-11 02:23:17','ยืนยันแล้ว','2026-10-11 02:23:27'),(20,37,2,'img_6acb55160e98d2.33731579.png',180.00,'2026-10-11 16:21:26','ยืนยันแล้ว','2026-10-11 16:21:40'),(21,38,2,'img_6acbcaf85c7780.18126146.png',180.00,'2026-10-12 00:44:24','ยืนยันแล้ว','2026-10-12 00:46:23'),(22,39,2,'img_6acbcc2c86f9e5.50831811.png',720.00,'2026-10-12 00:49:32','ยืนยันแล้ว','2026-10-12 00:49:43'),(23,40,2,'img_6accab5180bac5.06551762.png',180.00,'2026-10-12 16:41:37','ปฏิเสธ','2026-10-19 02:01:03'),(24,44,2,'img_6ad517bd014438.07503855.png',300.00,'2026-10-19 02:02:21','ยืนยันแล้ว','2026-10-19 02:37:54'),(25,45,2,'img_6ad5320034cc89.08644906.png',880.00,'2026-10-19 03:54:24','ยืนยันแล้ว','2026-10-19 03:54:37'),(26,46,2,'img_6ad536c34c7cc0.49819652.png',300.00,'2026-10-19 04:14:43','ยืนยันแล้ว','2026-10-20 02:48:15'),(27,50,2,'img_6ad673e7027f04.66799394.png',720.00,'2026-10-20 02:47:51','ยืนยันแล้ว','2026-10-20 02:48:24'),(28,51,2,'img_6ad6788900aa32.25943267.png',700.00,'2026-10-20 03:07:37','ยืนยันแล้ว','2026-10-20 03:07:49'),(29,52,2,'img_6ad6f995a59223.18147841.jpg',670.00,'2026-10-20 12:18:13','ยืนยันแล้ว','2026-10-20 12:18:40'),(30,53,2,'img_6ad711812bef08.48967839.jpg',1090.00,'2026-10-20 14:00:17','ยืนยันแล้ว','2026-10-20 14:00:37'),(31,54,2,'img_6ad717a12ed411.08593535.jpg',500.00,'2026-10-20 14:26:25','ยืนยันแล้ว','2026-10-20 14:46:11'),(32,55,2,'img_6ad717e7d77a93.42104114.jpg',360.00,'2026-10-20 14:27:35','ยืนยันแล้ว','2026-10-27 01:00:03'),(33,56,2,'img_6ad718298d2e22.80196507.jpg',1120.00,'2026-10-20 14:28:41','ยืนยันแล้ว','2026-10-27 01:00:01'),(34,57,2,'img_6ad71c11c8a227.53839864.jpg',480.00,'2026-10-20 14:45:21','ยืนยันแล้ว','2026-10-20 14:47:20'),(35,58,2,'img_6ad71f0a34cf00.12773605.jpg',520.00,'2026-10-20 14:58:02','ปฏิเสธ','2026-10-22 20:17:37'),(36,59,2,'img_6ada0d14248659.10043930.jpg',360.00,'2026-10-22 20:18:12','ยืนยันแล้ว','2026-10-22 20:19:03'),(37,60,2,'img_6ada119cc4f269.03070733.jpg',180.00,'2026-10-22 20:37:32','ยืนยันแล้ว','2026-10-27 00:59:59'),(38,62,2,'img_6ada1e29351d82.05307754.jpg',180.00,'2026-10-22 21:31:05','ยืนยันแล้ว','2026-10-22 21:31:21'),(39,65,2,'img_6adb1cbe4fbe02.64549875.jpg',330.00,'2026-10-23 15:37:18','ยืนยันแล้ว','2026-10-23 15:39:53'),(40,66,2,'img_6adf0bfa4727c2.77814165.jpg',533.00,'2026-10-26 15:14:50','ยืนยันแล้ว','2026-10-26 15:15:34'),(41,67,2,'img_6adf0fced875c6.45187375.jpg',570.00,'2026-10-26 15:31:10','ยืนยันแล้ว','2026-10-26 15:32:44'),(42,68,2,'img_6adf19926340f1.41538627.jpg',530.00,'2026-10-26 16:12:50','ยืนยันแล้ว','2026-10-26 16:14:03'),(43,71,2,'img_6adf7edf26c3c3.96539585.jpg',460.00,'2026-10-26 23:25:03','ยืนยันแล้ว','2026-10-27 00:59:56'),(44,72,2,'img_6adf8807952d43.56070004.jpg',600.00,'2026-10-27 00:04:07','ยืนยันแล้ว','2026-10-27 00:04:36'),(45,73,2,'img_6adf88abde8c34.81748345.jpg',450.00,'2026-10-27 00:06:51','ยืนยันแล้ว','2026-10-27 00:07:01'),(46,74,2,'img_6adf89fd0440c0.09592982.jpg',450.00,'2026-10-27 00:12:29','ยืนยันแล้ว','2026-10-27 00:12:37'),(47,75,2,'img_6adf8b3cb71042.84119567.jpg',310.00,'2026-10-27 00:17:48','ยืนยันแล้ว','2026-10-27 00:38:58'),(48,76,2,'img_6adf975b047a68.63750782.jpg',592.00,'2026-10-27 01:09:31','ยืนยันแล้ว','2026-10-27 01:11:21'),(49,77,2,'img_6ae6d802cb2160.64669397.jpg',200.00,'2026-11-01 13:11:14','ยืนยันแล้ว','2026-11-01 13:29:41'),(50,78,NULL,'img_6ae6dc72728e18.02934419.jpg',180.00,'2026-11-01 13:30:10','รอตรวจสอบ',NULL),(51,80,NULL,'img_6ae6eada73f280.46170968.jpeg',230.00,'2026-11-01 14:31:38','รอตรวจสอบ',NULL);
/*!40000 ALTER TABLE `payment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point`
--

DROP TABLE IF EXISTS `point`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point` (
  `point_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `point_balance` int(11) NOT NULL DEFAULT 0,
  `point_total_earned` int(11) NOT NULL DEFAULT 0,
  `member_level` enum('Bronze','Silver','Gold') NOT NULL DEFAULT 'Bronze',
  `point_updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`point_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `point_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point`
--

LOCK TABLES `point` WRITE;
/*!40000 ALTER TABLE `point` DISABLE KEYS */;
INSERT INTO `point` VALUES (4,5,763,883,'Gold','2026-11-01 13:29:41'),(5,6,511,511,'Gold','2026-10-20 14:46:11'),(6,7,14,14,'Bronze','2026-10-27 00:12:37'),(7,8,26,106,'Silver','2026-10-20 03:08:09'),(8,9,13,13,'Bronze','2026-10-20 14:00:37'),(9,10,6,6,'Bronze','2026-10-20 12:18:40'),(10,11,0,0,'Bronze','2026-10-26 15:13:53'),(11,12,0,0,'Bronze','2026-10-26 15:27:30'),(12,13,0,0,'Bronze','2026-10-26 16:09:03');
/*!40000 ALTER TABLE `point` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_transaction`
--

DROP TABLE IF EXISTS `point_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_transaction` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `transaction_type` enum('ได้รับ','ใช้','ปรับโดย Admin') NOT NULL,
  `transaction_point` int(11) NOT NULL,
  `transaction_note` varchar(255) DEFAULT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `member_id` (`member_id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `point_transaction_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE,
  CONSTRAINT `point_transaction_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_transaction`
--

LOCK TABLES `point_transaction` WRITE;
/*!40000 ALTER TABLE `point_transaction` DISABLE KEYS */;
INSERT INTO `point_transaction` VALUES (2,5,NULL,'ได้รับ',3,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-09-25 23:20:10'),(3,6,NULL,'ได้รับ',4,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-09-25 23:25:34'),(4,5,NULL,'ปรับโดย Admin',2,'คืนพอยท์จากการยกเลิกการจอง','2026-09-26 00:28:42'),(5,5,6,'ได้รับ',7,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-09-26 02:02:00'),(6,6,8,'ได้รับ',3,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-09-28 19:35:36'),(7,5,9,'ได้รับ',1,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-09-28 23:09:31'),(8,6,11,'ได้รับ',3,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-09-28 23:39:58'),(9,5,13,'ได้รับ',1,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-05 01:49:13'),(10,6,17,'ได้รับ',1,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-06 12:52:08'),(11,5,18,'ได้รับ',1,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-06 12:52:13'),(12,5,18,'ปรับโดย Admin',-1,'ดึงคะแนนคืนเนื่องจากยกเลิกรายการจอง','2026-10-06 12:53:27'),(13,6,22,'ได้รับ',1,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-06 13:26:13'),(14,6,22,'ปรับโดย Admin',-1,'ดึงคะแนนคืนเนื่องจากยกเลิกรายการจอง','2026-10-06 13:27:17'),(15,5,33,'ได้รับ',2,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-11 00:16:15'),(16,5,NULL,'ปรับโดย Admin',984,'ผู้ดูแลระบบแก้ไขข้อมูลแต้มสะสมจากหน้าระบบสมาชิก','2026-10-11 00:17:10'),(17,7,36,'ได้รับ',2,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-11 02:23:27'),(18,8,37,'ได้รับ',1,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-11 16:21:40'),(19,5,NULL,'ใช้',50,'แลกของรางวัล: น้ำดื่มฟรี 1 ขวด','2026-10-11 23:32:10'),(20,5,38,'ได้รับ',1,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-12 00:46:23'),(21,6,39,'ได้รับ',7,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-12 00:49:43'),(22,5,44,'ได้รับ',3,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-19 02:37:54'),(23,7,45,'ได้รับ',8,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-19 03:54:37'),(24,5,NULL,'ใช้',120,'แลกของรางวัล: ลูกแบดมินตัน 1 ลูก','2026-10-20 02:39:07'),(25,6,NULL,'ปรับโดย Admin',481,'ผู้ดูแลระบบแก้ไขข้อมูลแต้มสะสมจากหน้าระบบสมาชิก','2026-10-20 02:46:27'),(26,9,46,'ได้รับ',3,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-20 02:48:15'),(27,6,50,'ได้รับ',7,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-20 02:48:24'),(28,7,NULL,'ได้รับ',1,'ทดสอบเลื่อนระดับสู่ Silver','2026-10-20 03:01:39'),(29,7,NULL,'ได้รับ',1,'ทดสอบเลื่อนระดับสู่ Gold','2026-10-20 03:01:39'),(30,8,NULL,'ปรับโดย Admin',98,'ผู้ดูแลระบบแก้ไขข้อมูลแต้มสะสมจากหน้าระบบสมาชิก','2026-10-20 03:06:01'),(31,8,51,'ได้รับ',7,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-20 03:07:49'),(32,8,NULL,'ใช้',80,'แลกของรางวัล: เครื่องดื่มเกลือแร่ฟรี 1 ขวด','2026-10-20 03:08:09'),(33,10,52,'ได้รับ',6,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-20 12:18:40'),(34,9,53,'ได้รับ',10,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-20 14:00:37'),(35,6,54,'ได้รับ',5,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-20 14:46:11'),(36,5,57,'ได้รับ',4,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-20 14:47:20'),(37,5,62,'ได้รับ',1,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-22 21:31:21'),(38,5,66,'ได้รับ',5,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-26 15:15:34'),(39,5,67,'ได้รับ',5,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-26 15:32:44'),(40,5,NULL,'ใช้',120,'แลกของรางวัล: ลูกแบดมินตัน 1 ลูก','2026-10-26 15:34:12'),(41,5,68,'ได้รับ',5,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-26 16:14:03'),(42,7,72,'ได้รับ',6,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-27 00:04:36'),(43,7,72,'ปรับโดย Admin',-6,'ดึงคะแนนคืนเนื่องจากยกเลิกรายการจอง','2026-10-27 00:06:05'),(44,7,73,'ได้รับ',4,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-27 00:07:01'),(45,7,73,'ปรับโดย Admin',-4,'ดึงคะแนนคืนเนื่องจากยกเลิกรายการจอง','2026-10-27 00:11:42'),(46,7,74,'ได้รับ',4,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-27 00:12:37'),(47,5,75,'ได้รับ',3,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-27 00:38:58'),(48,5,71,'ได้รับ',4,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-27 00:59:56'),(49,5,60,'ได้รับ',1,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-27 00:59:59'),(50,5,56,'ได้รับ',11,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-27 01:00:01'),(51,5,55,'ได้รับ',3,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-27 01:00:03'),(52,5,76,'ได้รับ',5,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-10-27 01:11:21'),(53,5,77,'ได้รับ',2,'ได้รับพอยท์จากการจองสนามออนไลน์','2026-11-01 13:29:41');
/*!40000 ALTER TABLE `point_transaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_sale`
--

DROP TABLE IF EXISTS `pos_sale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pos_sale` (
  `pos_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `pos_quantity` int(11) NOT NULL,
  `pos_total_price` decimal(8,2) NOT NULL,
  `pos_date` datetime DEFAULT current_timestamp(),
  `pos_payment_method` varchar(20) NOT NULL DEFAULT 'cash',
  PRIMARY KEY (`pos_id`),
  KEY `admin_id` (`admin_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `pos_sale_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`),
  CONSTRAINT `pos_sale_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_sale`
--

LOCK TABLES `pos_sale` WRITE;
/*!40000 ALTER TABLE `pos_sale` DISABLE KEYS */;
INSERT INTO `pos_sale` VALUES (12,2,43,1,80.00,'2026-10-06 12:56:58','cash'),(13,2,33,1,80.00,'2026-10-06 12:56:58','cash'),(17,2,39,1,80.00,'2026-10-12 17:00:32','cash');
/*!40000 ALTER TABLE `pos_sale` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `product_type` enum('อุปกรณ์เช่า','สินค้าบริโภค') NOT NULL,
  `product_price` decimal(8,2) NOT NULL,
  `product_stock` int(11) NOT NULL DEFAULT 0,
  `product_brand` varchar(100) DEFAULT NULL,
  `product_model` varchar(100) DEFAULT NULL,
  `product_size` varchar(20) DEFAULT NULL,
  `product_level` enum('มือใหม่','มือโปร') DEFAULT NULL,
  `product_min_stock` int(11) NOT NULL DEFAULT 5,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES (12,'น้ำดื่มคริสตัล 600ml.','PD_6aad755c8d395.png','สินค้าบริโภค',10.00,7,NULL,NULL,NULL,NULL,5),(13,'น้ำดื่มสิงค์ 600ml.','PD_6aad7bb52112a.png','สินค้าบริโภค',10.00,13,NULL,NULL,NULL,NULL,5),(14,'น้ำดื่มทิพย์ 420ml.','PD_6aad7c10aef64.png','สินค้าบริโภค',8.00,19,NULL,NULL,NULL,NULL,5),(15,'สปอนเซอร์ 420ml.','PD_6aad7c31f41e9.png','สินค้าบริโภค',25.00,1,NULL,NULL,NULL,NULL,5),(16,'เกเตอเรด 450ml.','PD_6aad7c7c1d23b.png','สินค้าบริโภค',30.00,19,NULL,NULL,NULL,NULL,5),(17,'น้ำเกลือแร่ 500ml.','PD_6aad7cb17b065.png','สินค้าบริโภค',25.00,20,NULL,NULL,NULL,NULL,5),(18,'น้ำโค้ก 450ml.','PD_6aad7cd133404.png','สินค้าบริโภค',12.00,19,NULL,NULL,NULL,NULL,5),(19,'Est Cola','PD_6aad7cf563967.png','สินค้าบริโภค',16.00,325,NULL,NULL,NULL,NULL,5),(20,'อิชิตัน 420ml.','PD_6aad7d227dd7a.png','สินค้าบริโภค',18.00,20,NULL,NULL,NULL,NULL,5),(21,'โอเอชิ 400ml.','PD_6aad7d439e36e.png','สินค้าบริโภค',20.00,20,NULL,NULL,NULL,NULL,5),(22,'ลูกแบดพลาสติก','PD_6aad7d74d4f20.png','สินค้าบริโภค',60.00,4,NULL,NULL,NULL,NULL,5),(23,'ลูกแบดขนไก่','PD_6aad7d888645b.png','สินค้าบริโภค',100.00,30,NULL,NULL,NULL,NULL,5),(24,'กริบแบดมินตัน Yonex','PD_6aad7da3381ec.png','สินค้าบริโภค',55.00,10,NULL,NULL,NULL,NULL,5),(25,'กริบแบดมินตัน Victor','PD_6aad7dba84fdb.png','สินค้าบริโภค',55.00,12,NULL,NULL,NULL,NULL,5),(26,'ผ้าเย็นเช็ดตัว','PD_6aad7dd3cc289.png','สินค้าบริโภค',35.00,15,NULL,NULL,NULL,NULL,5),(29,'พลาสเตอร์ยา','PD_6aad7e315f3db.png','สินค้าบริโภค',5.00,5,NULL,NULL,NULL,NULL,5),(30,'ไม้แบดมินตัน','PD_6ab6be69e4669.png','อุปกรณ์เช่า',50.00,7,'Victor','ARS 3200','','มือใหม่',3),(31,'ไม้แบดมินตัน','PD_6ab6bf05c40b2.png','อุปกรณ์เช่า',50.00,10,'Victor','Ryuga TD','','มือใหม่',5),(32,'ไม้แบดมินตัน','PD_6ab6bf5f9d296.png','อุปกรณ์เช่า',50.00,9,'Victor','Auraspeed 3800','','มือโปร',5),(33,'ไม้แบดมินตัน','PD_6ab6bfa71930b.png','อุปกรณ์เช่า',80.00,9,'Li-Ning','Tectonic 9','','มือโปร',5),(34,'ไม้แบดมินตัน','PD_6ab6c04fb9c2f.png','อุปกรณ์เช่า',80.00,10,'Kawasaki','Passion GZ-20','','มือใหม่',5),(35,'ไม้แบดมินตัน','PD_6ab6c07bd22ab.png','อุปกรณ์เช่า',100.00,4,'Kawasaki','Passion P20','','มือโปร',2),(36,'รองเท้าแบดมินตัน','PD_6ab6c199f1aec.png','อุปกรณ์เช่า',80.00,4,'Li-Ning','Li-NingUltraForce','39',NULL,0),(37,'รองเท้าแบดมินตัน','PD_6ab6c1ec6ec65.png','อุปกรณ์เช่า',80.00,3,'Li-Ning','Li-NingUltraForce','40',NULL,0),(38,'รองเท้าแบดมินตัน','PD_6ab6c2214e1e2.png','อุปกรณ์เช่า',80.00,4,'Yonex','Yonex SuperAce Light 2','41',NULL,0),(39,'รองเท้าแบดมินตัน','PD_6ab6c22be8519.png','อุปกรณ์เช่า',80.00,3,'Yonex','Yonex SuperAce Light 2','42',NULL,0),(40,'รองเท้าแบดมินตัน','PD_6ab6c284c83bd.png','อุปกรณ์เช่า',80.00,4,'Victor','Victor A311','43',NULL,0),(41,'รองเท้าแบดมินตัน','PD_6ab6c2ad60f54.png','อุปกรณ์เช่า',80.00,5,'Victor','Victor A311','44',NULL,0),(42,'รองเท้าแบดมินตัน','PD_6ab6c30bea92c.png','อุปกรณ์เช่า',80.00,5,'Victor','Victor 8500','45',NULL,0),(43,'รองเท้าแบดมินตัน','PD_6ab6c35f97eb3.png','อุปกรณ์เช่า',80.00,5,'Victor','Victor 8500','46',NULL,0);
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase`
--

DROP TABLE IF EXISTS `purchase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase` (
  `purchase_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `purchase_quantity` int(11) NOT NULL,
  `purchase_price_per_unit` decimal(8,2) NOT NULL,
  `purchase_total_price` decimal(8,2) NOT NULL,
  `purchase_date` date NOT NULL,
  PRIMARY KEY (`purchase_id`),
  KEY `product_id` (`product_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `purchase_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase`
--

LOCK TABLES `purchase` WRITE;
/*!40000 ALTER TABLE `purchase` DISABLE KEYS */;
INSERT INTO `purchase` VALUES (6,12,2,8,8.00,64.00,'2026-10-22');
/*!40000 ALTER TABLE `purchase` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rental`
--

DROP TABLE IF EXISTS `rental`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rental` (
  `rental_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `rental_quantity` int(11) NOT NULL,
  `rental_start_time` datetime NOT NULL,
  `rental_return_time` datetime DEFAULT NULL,
  `rental_status` enum('รอตรวจสอบ','กำลังเช่า','คืนแล้ว','ชำรุด','สูญหาย','ยกเลิก') NOT NULL DEFAULT 'กำลังเช่า',
  `rental_fine` decimal(8,2) NOT NULL DEFAULT 0.00,
  `rental_fine_reason` varchar(255) DEFAULT NULL,
  `rental_actual_return` datetime DEFAULT NULL,
  PRIMARY KEY (`rental_id`),
  KEY `booking_id` (`booking_id`),
  KEY `product_id` (`product_id`),
  KEY `rental_ibfk_3` (`member_id`),
  CONSTRAINT `rental_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `rental_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  CONSTRAINT `rental_ibfk_3` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental`
--

LOCK TABLES `rental` WRITE;
/*!40000 ALTER TABLE `rental` DISABLE KEYS */;
INSERT INTO `rental` VALUES (10,6,NULL,34,2,'2026-09-26 20:00:00','2026-09-26 22:00:00','คืนแล้ว',11560.00,'คืนอุปกรณ์ช้ากว่ากำหนด 289 ชม.','2026-10-08 23:00:09'),(11,6,NULL,35,2,'2026-09-26 20:00:00','2026-09-26 22:00:00','คืนแล้ว',11560.00,'คืนอุปกรณ์ช้ากว่ากำหนด 289 ชม.','2026-10-08 23:00:13'),(12,7,NULL,30,1,'2026-09-28 19:00:00','2026-09-28 20:00:00','ยกเลิก',0.00,NULL,NULL),(13,7,NULL,31,1,'2026-09-28 19:00:00','2026-09-28 20:00:00','ยกเลิก',0.00,NULL,NULL),(14,11,NULL,12,1,'2026-09-29 20:00:00','2026-09-29 21:00:00','ยกเลิก',0.00,NULL,NULL),(15,11,NULL,13,1,'2026-09-29 20:00:00','2026-09-29 21:00:00','ยกเลิก',0.00,NULL,NULL),(16,11,NULL,14,1,'2026-09-29 20:00:00','2026-09-29 21:00:00','ยกเลิก',0.00,NULL,NULL),(17,11,NULL,32,1,'2026-09-29 20:00:00','2026-09-29 21:00:00','ยกเลิก',0.00,NULL,NULL),(18,11,NULL,33,1,'2026-09-29 20:00:00','2026-09-29 21:00:00','ยกเลิก',0.00,NULL,NULL),(19,15,NULL,12,2,'2026-10-05 13:00:00','2026-10-05 16:00:00','ยกเลิก',0.00,NULL,NULL),(20,15,NULL,13,2,'2026-10-05 13:00:00','2026-10-05 16:00:00','ยกเลิก',0.00,NULL,NULL),(21,20,NULL,43,1,'2026-10-06 12:56:58','2026-10-06 14:56:58','คืนแล้ว',1140.00,'คืนอุปกรณ์ช้ากว่ากำหนด 57 ชม.','2026-10-08 23:00:56'),(22,20,NULL,33,1,'2026-10-06 12:56:58','2026-10-06 14:56:58','คืนแล้ว',1140.00,'คืนอุปกรณ์ช้ากว่ากำหนด 57 ชม.','2026-10-08 23:01:00'),(23,24,NULL,22,1,'2026-10-08 22:00:00','2026-10-08 23:00:00','ยกเลิก',0.00,NULL,NULL),(24,24,NULL,30,1,'2026-10-08 22:00:00','2026-10-08 23:00:00','ยกเลิก',0.00,NULL,NULL),(25,NULL,NULL,39,1,'2026-10-12 17:00:32','2026-10-12 19:00:32','คืนแล้ว',180.00,'คืนอุปกรณ์ช้ากว่ากำหนด 9 ชม.','2026-10-13 03:21:59'),(26,45,NULL,42,1,'2026-10-20 19:00:00','2026-10-20 23:00:00','คืนแล้ว',0.00,NULL,'2026-10-19 04:31:01'),(27,45,NULL,43,1,'2026-10-20 19:00:00','2026-10-20 23:00:00','ชำรุด',10.00,'อุปกรณ์ชำรุดจากการใช้งาน','2026-10-19 04:31:51'),(28,50,NULL,12,6,'2026-10-20 08:00:00','2026-10-20 12:00:00','คืนแล้ว',0.00,NULL,'2026-10-20 12:44:56'),(29,50,NULL,13,6,'2026-10-20 08:00:00','2026-10-20 12:00:00','คืนแล้ว',0.00,NULL,'2026-10-20 12:44:56'),(30,51,NULL,15,10,'2026-10-20 08:00:00','2026-10-20 11:00:00','คืนแล้ว',0.00,NULL,'2026-10-20 12:44:56'),(31,52,NULL,12,5,'2026-10-20 14:00:00','2026-10-20 16:00:00','คืนแล้ว',0.00,NULL,'2026-10-20 12:44:56'),(32,52,NULL,22,1,'2026-10-20 14:00:00','2026-10-20 16:00:00','คืนแล้ว',0.00,NULL,'2026-10-20 12:44:56'),(33,52,NULL,31,1,'2026-10-20 14:00:00','2026-10-20 16:00:00','คืนแล้ว',1440.00,'คืนอุปกรณ์ช้ากว่ากำหนด 72 ชม.','2026-10-23 15:09:37'),(34,52,NULL,32,1,'2026-10-20 14:00:00','2026-10-20 16:00:00','คืนแล้ว',1440.00,'คืนอุปกรณ์ช้ากว่ากำหนด 72 ชม.','2026-10-23 15:37:30'),(35,52,NULL,37,1,'2026-10-20 14:00:00','2026-10-20 16:00:00','คืนแล้ว',1440.00,'คืนอุปกรณ์ช้ากว่ากำหนด 72 ชม.','2026-10-23 15:37:37'),(36,52,NULL,38,1,'2026-10-20 14:00:00','2026-10-20 16:00:00','คืนแล้ว',1440.00,'คืนอุปกรณ์ช้ากว่ากำหนด 72 ชม.','2026-10-23 15:37:44'),(37,53,NULL,34,4,'2026-10-20 14:00:00','2026-10-20 17:00:00','คืนแล้ว',5680.00,'คืนอุปกรณ์ช้ากว่ากำหนด 71 ชม.','2026-10-23 15:37:27'),(38,53,NULL,36,1,'2026-10-20 14:00:00','2026-10-20 17:00:00','คืนแล้ว',1420.00,'คืนอุปกรณ์ช้ากว่ากำหนด 71 ชม.','2026-10-23 15:37:34'),(39,53,NULL,37,1,'2026-10-20 14:00:00','2026-10-20 17:00:00','คืนแล้ว',1420.00,'คืนอุปกรณ์ช้ากว่ากำหนด 71 ชม.','2026-10-23 15:37:40'),(40,53,NULL,38,1,'2026-10-20 14:00:00','2026-10-20 17:00:00','คืนแล้ว',1420.00,'คืนอุปกรณ์ช้ากว่ากำหนด 71 ชม.','2026-10-23 15:37:47'),(41,53,NULL,39,1,'2026-10-20 14:00:00','2026-10-20 17:00:00','คืนแล้ว',1420.00,'คืนอุปกรณ์ช้ากว่ากำหนด 71 ชม.','2026-10-23 15:37:50'),(42,54,NULL,15,8,'2026-10-20 15:00:00','2026-10-20 17:00:00','คืนแล้ว',0.00,NULL,'2026-10-20 14:46:11'),(43,56,NULL,30,4,'2026-10-20 19:00:00','2026-10-20 23:00:00','คืนแล้ว',5200.00,'คืนอุปกรณ์ช้ากว่ากำหนด 65 ชม.','2026-10-23 15:37:53'),(44,56,NULL,31,4,'2026-10-20 19:00:00','2026-10-20 23:00:00','คืนแล้ว',5200.00,'คืนอุปกรณ์ช้ากว่ากำหนด 65 ชม.','2026-10-23 15:37:57'),(45,58,NULL,13,3,'2026-10-20 15:00:00','2026-10-20 16:00:00','ยกเลิก',0.00,NULL,NULL),(46,58,NULL,30,1,'2026-10-20 15:00:00','2026-10-20 16:00:00','ยกเลิก',0.00,NULL,NULL),(47,58,NULL,31,1,'2026-10-20 15:00:00','2026-10-20 16:00:00','ยกเลิก',0.00,NULL,NULL),(48,58,NULL,36,2,'2026-10-20 15:00:00','2026-10-20 16:00:00','ยกเลิก',0.00,NULL,NULL),(49,58,NULL,37,1,'2026-10-20 15:00:00','2026-10-20 16:00:00','ยกเลิก',0.00,NULL,NULL),(50,66,NULL,12,1,'2026-10-26 16:00:00','2026-10-26 18:00:00','คืนแล้ว',0.00,NULL,'2026-10-26 15:15:34'),(51,66,NULL,13,1,'2026-10-26 16:00:00','2026-10-26 18:00:00','คืนแล้ว',0.00,NULL,'2026-10-26 15:15:34'),(52,66,NULL,14,1,'2026-10-26 16:00:00','2026-10-26 18:00:00','คืนแล้ว',0.00,NULL,'2026-10-26 15:15:34'),(53,66,NULL,15,1,'2026-10-26 16:00:00','2026-10-26 18:00:00','คืนแล้ว',0.00,NULL,'2026-10-26 15:15:34'),(54,66,NULL,30,1,'2026-10-26 16:00:00','2026-10-26 18:00:00','กำลังเช่า',0.00,NULL,NULL),(55,66,NULL,35,1,'2026-10-26 16:00:00','2026-10-26 18:00:00','กำลังเช่า',0.00,NULL,NULL),(56,67,NULL,12,3,'2026-10-26 17:00:00','2026-10-26 19:00:00','คืนแล้ว',0.00,NULL,'2026-10-26 15:32:44'),(57,67,NULL,30,1,'2026-10-26 17:00:00','2026-10-26 19:00:00','กำลังเช่า',0.00,NULL,NULL),(58,67,NULL,32,1,'2026-10-26 17:00:00','2026-10-26 19:00:00','กำลังเช่า',0.00,NULL,NULL),(59,67,NULL,36,1,'2026-10-26 17:00:00','2026-10-26 19:00:00','กำลังเช่า',0.00,NULL,NULL),(60,68,NULL,16,1,'2026-10-27 20:00:00','2026-10-27 22:00:00','คืนแล้ว',0.00,NULL,'2026-10-26 16:14:03'),(61,68,NULL,22,1,'2026-10-27 20:00:00','2026-10-27 22:00:00','คืนแล้ว',0.00,NULL,'2026-10-26 16:14:03'),(62,68,NULL,39,1,'2026-10-27 20:00:00','2026-10-27 22:00:00','กำลังเช่า',0.00,NULL,NULL),(63,69,NULL,34,1,'2026-10-27 16:00:00','2026-10-27 18:00:00','ยกเลิก',0.00,NULL,NULL),(64,69,NULL,36,1,'2026-10-27 16:00:00','2026-10-27 18:00:00','ยกเลิก',0.00,NULL,NULL),(65,70,NULL,35,1,'2026-10-26 21:00:00','2026-10-26 22:00:00','ยกเลิก',0.00,NULL,NULL),(66,70,NULL,36,1,'2026-10-26 21:00:00','2026-10-26 22:00:00','ยกเลิก',0.00,NULL,NULL),(67,71,NULL,33,1,'2026-10-27 08:00:00','2026-10-27 10:00:00','กำลังเช่า',0.00,NULL,NULL),(68,71,NULL,37,1,'2026-10-27 08:00:00','2026-10-27 10:00:00','กำลังเช่า',0.00,NULL,NULL),(69,75,NULL,37,1,'2026-10-27 08:00:00','2026-10-27 09:00:00','กำลังเช่า',0.00,NULL,NULL),(70,75,NULL,39,1,'2026-10-27 08:00:00','2026-10-27 09:00:00','กำลังเช่า',0.00,NULL,NULL),(71,76,NULL,18,1,'2026-10-27 08:00:00','2026-10-27 11:00:00','คืนแล้ว',0.00,NULL,'2026-10-27 01:11:21'),(72,76,NULL,30,1,'2026-10-27 08:00:00','2026-10-27 11:00:00','กำลังเช่า',0.00,NULL,NULL),(73,76,NULL,38,1,'2026-10-27 08:00:00','2026-10-27 11:00:00','กำลังเช่า',0.00,NULL,NULL),(74,80,NULL,40,1,'2026-11-02 08:00:00','2026-11-02 09:00:00','รอตรวจสอบ',0.00,NULL,NULL);
/*!40000 ALTER TABLE `rental` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revenue`
--

DROP TABLE IF EXISTS `revenue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revenue` (
  `revenue_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) DEFAULT NULL,
  `revenue_court_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `revenue_rental_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `revenue_product_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `revenue_total_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `revenue_date` date NOT NULL,
  `revenue_type` enum('ออนไลน์','หน้าร้าน') NOT NULL,
  `revenue_payment_method` enum('เงินสด','โอนเงิน') NOT NULL DEFAULT 'เงินสด',
  PRIMARY KEY (`revenue_id`),
  KEY `payment_id` (`payment_id`),
  CONSTRAINT `revenue_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`payment_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revenue`
--

LOCK TABLES `revenue` WRITE;
/*!40000 ALTER TABLE `revenue` DISABLE KEYS */;
INSERT INTO `revenue` VALUES (11,4,360.00,360.00,0.00,720.00,'2026-09-26','ออนไลน์','โอนเงิน'),(12,7,360.00,0.00,0.00,360.00,'2026-09-28','ออนไลน์','โอนเงิน'),(13,6,180.00,0.00,0.00,180.00,'2026-09-28','ออนไลน์','โอนเงิน'),(14,9,150.00,130.00,28.00,308.00,'2026-09-28','ออนไลน์','โอนเงิน'),(15,10,180.00,0.00,0.00,180.00,'2026-10-05','ออนไลน์','โอนเงิน'),(16,12,150.00,0.00,0.00,150.00,'2026-10-06','ออนไลน์','โอนเงิน'),(17,13,150.00,0.00,0.00,150.00,'2026-10-06','ออนไลน์','โอนเงิน'),(18,NULL,150.00,0.00,0.00,150.00,'2026-10-06','หน้าร้าน','เงินสด'),(19,NULL,150.00,160.00,0.00,310.00,'2026-10-06','หน้าร้าน','เงินสด'),(20,15,150.00,0.00,0.00,150.00,'2026-10-06','ออนไลน์','โอนเงิน'),(21,15,0.00,0.00,0.00,-75.00,'2026-10-06','ออนไลน์','โอนเงิน'),(22,NULL,150.00,0.00,0.00,150.00,'2026-10-08','หน้าร้าน','เงินสด'),(23,NULL,150.00,0.00,0.00,150.00,'2026-10-08','หน้าร้าน','เงินสด'),(24,NULL,0.00,11560.00,0.00,11560.00,'2026-10-08','หน้าร้าน','เงินสด'),(25,NULL,0.00,11560.00,0.00,11560.00,'2026-10-08','หน้าร้าน','เงินสด'),(26,NULL,0.00,1140.00,0.00,1140.00,'2026-10-08','หน้าร้าน','เงินสด'),(27,NULL,0.00,1140.00,0.00,1140.00,'2026-10-08','หน้าร้าน','เงินสด'),(28,18,200.00,0.00,0.00,200.00,'2026-10-11','ออนไลน์','โอนเงิน'),(29,19,200.00,0.00,0.00,200.00,'2026-10-11','ออนไลน์','โอนเงิน'),(30,20,180.00,0.00,0.00,180.00,'2026-10-11','ออนไลน์','โอนเงิน'),(31,21,180.00,0.00,0.00,180.00,'2026-10-12','ออนไลน์','โอนเงิน'),(32,22,720.00,0.00,0.00,720.00,'2026-10-12','ออนไลน์','โอนเงิน'),(33,NULL,180.00,0.00,0.00,180.00,'2026-10-12','หน้าร้าน','เงินสด'),(34,NULL,0.00,80.00,0.00,80.00,'2026-10-12','หน้าร้าน','เงินสด'),(35,NULL,0.00,180.00,0.00,180.00,'2026-10-13','หน้าร้าน','เงินสด'),(36,24,300.00,0.00,0.00,300.00,'2026-10-19','ออนไลน์','โอนเงิน'),(37,25,720.00,160.00,0.00,880.00,'2026-10-19','ออนไลน์','โอนเงิน'),(38,NULL,150.00,0.00,0.00,150.00,'2026-10-19','หน้าร้าน','เงินสด'),(39,NULL,0.00,10.00,0.00,10.00,'2026-10-19','หน้าร้าน','เงินสด'),(40,26,300.00,0.00,0.00,300.00,'2026-10-20','ออนไลน์','โอนเงิน'),(41,27,600.00,0.00,120.00,720.00,'2026-10-20','ออนไลน์','โอนเงิน'),(42,28,450.00,0.00,250.00,700.00,'2026-10-20','ออนไลน์','โอนเงิน'),(43,29,300.00,260.00,110.00,670.00,'2026-10-20','ออนไลน์','โอนเงิน'),(44,30,450.00,640.00,0.00,1090.00,'2026-10-20','ออนไลน์','โอนเงิน'),(45,31,300.00,0.00,200.00,500.00,'2026-10-20','ออนไลน์','โอนเงิน'),(46,34,480.00,0.00,0.00,480.00,'2026-10-20','ออนไลน์','โอนเงิน'),(47,38,180.00,0.00,0.00,180.00,'2026-10-22','ออนไลน์','โอนเงิน'),(48,NULL,0.00,1440.00,0.00,1440.00,'2026-10-23','หน้าร้าน','เงินสด'),(49,NULL,0.00,5680.00,0.00,5680.00,'2026-10-23','หน้าร้าน','เงินสด'),(50,NULL,0.00,1440.00,0.00,1440.00,'2026-10-23','หน้าร้าน','เงินสด'),(51,NULL,0.00,1420.00,0.00,1420.00,'2026-10-23','หน้าร้าน','เงินสด'),(52,NULL,0.00,1440.00,0.00,1440.00,'2026-10-23','หน้าร้าน','เงินสด'),(53,NULL,0.00,1420.00,0.00,1420.00,'2026-10-23','หน้าร้าน','เงินสด'),(54,NULL,0.00,1440.00,0.00,1440.00,'2026-10-23','หน้าร้าน','เงินสด'),(55,NULL,0.00,1420.00,0.00,1420.00,'2026-10-23','หน้าร้าน','เงินสด'),(56,NULL,0.00,1420.00,0.00,1420.00,'2026-10-23','หน้าร้าน','เงินสด'),(57,NULL,0.00,5200.00,0.00,5200.00,'2026-10-23','หน้าร้าน','เงินสด'),(58,NULL,0.00,5200.00,0.00,5200.00,'2026-10-23','หน้าร้าน','เงินสด'),(59,40,330.00,150.00,53.00,533.00,'2026-10-26','ออนไลน์','โอนเงิน'),(60,41,360.00,180.00,30.00,570.00,'2026-10-26','ออนไลน์','โอนเงิน'),(61,42,360.00,80.00,90.00,530.00,'2026-10-26','ออนไลน์','โอนเงิน'),(62,44,600.00,0.00,0.00,600.00,'2026-10-27','ออนไลน์','โอนเงิน'),(63,44,0.00,0.00,0.00,-300.00,'2026-10-27','ออนไลน์','โอนเงิน'),(64,45,450.00,0.00,0.00,450.00,'2026-10-27','ออนไลน์','โอนเงิน'),(65,46,450.00,0.00,0.00,450.00,'2026-10-27','ออนไลน์','โอนเงิน'),(66,47,150.00,160.00,0.00,310.00,'2026-10-27','ออนไลน์','โอนเงิน'),(67,43,300.00,160.00,0.00,460.00,'2026-10-27','ออนไลน์','โอนเงิน'),(68,37,180.00,0.00,0.00,180.00,'2026-10-27','ออนไลน์','โอนเงิน'),(69,33,720.00,400.00,0.00,1120.00,'2026-10-27','ออนไลน์','โอนเงิน'),(70,32,360.00,0.00,0.00,360.00,'2026-10-27','ออนไลน์','โอนเงิน'),(71,48,450.00,130.00,12.00,592.00,'2026-10-27','ออนไลน์','โอนเงิน'),(72,49,200.00,0.00,0.00,200.00,'2026-11-01','ออนไลน์','โอนเงิน'),(73,NULL,200.00,0.00,0.00,200.00,'2026-11-01','หน้าร้าน','เงินสด');
/*!40000 ALTER TABLE `revenue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward`
--

DROP TABLE IF EXISTS `reward`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reward` (
  `reward_id` int(11) NOT NULL AUTO_INCREMENT,
  `reward_name` varchar(100) NOT NULL,
  `reward_image` varchar(255) DEFAULT NULL,
  `reward_point_cost` int(11) NOT NULL,
  `reward_min_level` enum('Bronze','Silver','Gold') NOT NULL,
  `reward_stock` int(11) NOT NULL DEFAULT 0,
  `reward_description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`reward_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward`
--

LOCK TABLES `reward` WRITE;
/*!40000 ALTER TABLE `reward` DISABLE KEYS */;
INSERT INTO `reward` VALUES (3,'น้ำดื่มฟรี 1 ขวด','reward_1790360302.png',50,'Bronze',9,''),(5,'เครื่องดื่มเกลือแร่ฟรี 1 ขวด','reward_1790360453.png',80,'Bronze',9,''),(6,'ผ้าพันด้าม','reward_1790360655.jpg',100,'Silver',10,''),(7,'ลูกแบดมินตัน 1 ลูก','reward_1790360697.png',120,'Gold',8,'');
/*!40000 ALTER TABLE `reward` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_closeout`
--

DROP TABLE IF EXISTS `shift_closeout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shift_closeout` (
  `shift_id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_code` varchar(35) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `opened_at` datetime NOT NULL,
  `closed_at` datetime NOT NULL,
  `opening_cash_float` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pos_cash_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pos_qr_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `online_transfer_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `court_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rental_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `product_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expected_cash` decimal(10,2) NOT NULL DEFAULT 0.00,
  `actual_cash` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cash_difference` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_transactions` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `status` enum('ปิดกะแล้ว','ยกเลิก') NOT NULL DEFAULT 'ปิดกะแล้ว',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`shift_id`),
  UNIQUE KEY `shift_code` (`shift_code`),
  KEY `idx_shift_date` (`shift_date`),
  KEY `idx_admin_id` (`admin_id`),
  CONSTRAINT `shift_closeout_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_closeout`
--

LOCK TABLES `shift_closeout` WRITE;
/*!40000 ALTER TABLE `shift_closeout` DISABLE KEYS */;
INSERT INTO `shift_closeout` VALUES (2,'SHIFT-20261101-001',2,'2026-11-01','2026-11-01 08:00:00','2026-11-01 22:00:00',1000.00,3500.00,1200.00,4800.00,6000.00,1500.00,2000.00,9500.00,4500.00,4500.00,0.00,18,'ทดสอบระบบปิดกะสิ้นวัน เงินสดในลิ้นชักครบถ้วนถูกต้อง','ปิดกะแล้ว','2026-11-01 13:57:48'),(3,'SHIFT-20261101-002',2,'2026-11-01','2026-11-01 22:00:00','2026-11-01 14:03:34',0.00,0.00,0.00,0.00,400.00,0.00,0.00,400.00,0.00,300.00,300.00,0,'','ปิดกะแล้ว','2026-11-01 14:03:34'),(4,'SHIFT-20261101-003',2,'2026-11-01','2026-11-01 22:00:00','2026-11-01 14:05:47',1000.00,0.00,0.00,0.00,400.00,0.00,0.00,400.00,1000.00,1500.00,500.00,0,'','ปิดกะแล้ว','2026-11-01 14:05:47');
/*!40000 ALTER TABLE `shift_closeout` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-01 14:56:01
