-- MySQL dump 10.13  Distrib 5.7.24, for osx11.1 (x86_64)
--
-- Host: localhost    Database: dtehm_insurance_api
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
-- Table structure for table `access_keys`
--

DROP TABLE IF EXISTS `access_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `access_key` varchar(130) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user` int(11) NOT NULL,
  `date_added` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_keys`
--

LOCK TABLES `access_keys` WRITE;
/*!40000 ALTER TABLE `access_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `access_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_transactions`
--

DROP TABLE IF EXISTS `account_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'User who owns this transaction',
  `amount` decimal(15,2) NOT NULL COMMENT 'Positive for credit, negative for debit',
  `transaction_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `source` enum('disbursement','withdrawal','deposit','product_commission','dtehm_referral_commission') COLLATE utf8mb4_unicode_ci NOT NULL,
  `commission_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type of commission: product_commission, dtehm_referral, etc.',
  `commission_reference_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Reference ID: ordered_item_id for product commissions, membership_id for referrals',
  `commission_amount` decimal(15,2) DEFAULT NULL COMMENT 'Commission amount for quick duplicate checks',
  `related_disbursement_id` bigint(20) unsigned DEFAULT NULL COMMENT 'If source is disbursement',
  `created_by_id` bigint(20) unsigned NOT NULL COMMENT 'User who created the transaction',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_transactions_user_id_index` (`user_id`),
  KEY `account_transactions_source_index` (`source`),
  KEY `account_transactions_transaction_date_index` (`transaction_date`),
  KEY `account_transactions_related_disbursement_id_index` (`related_disbursement_id`),
  KEY `account_transactions_created_by_id_index` (`created_by_id`),
  KEY `idx_commission_duplicate_check` (`user_id`,`commission_type`,`commission_reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_transactions`
--

LOCK TABLES `account_transactions` WRITE;
/*!40000 ALTER TABLE `account_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) DEFAULT NULL,
  `district` mediumint(9) DEFAULT NULL,
  `village` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` mediumint(9) DEFAULT NULL,
  `phone_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `box_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `temporary_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_menu`
--

DROP TABLE IF EXISTS `admin_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `order` int(11) NOT NULL DEFAULT '0',
  `title` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uri` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permission` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_menu`
--

LOCK TABLES `admin_menu` WRITE;
/*!40000 ALTER TABLE `admin_menu` DISABLE KEYS */;
INSERT INTO `admin_menu` VALUES (1,0,1,'Dashboard','fa-bar-chart','/',NULL,'2025-10-29 06:40:40','2025-11-14 18:45:23'),(2,0,14,'Investments','fa-building',NULL,NULL,'2025-10-29 06:38:02','2025-12-05 13:12:43'),(3,2,15,'Projects','fa-building-o','projects',NULL,'2025-10-29 06:38:02','2025-12-05 13:12:43'),(4,2,16,'Project Shares','fa-users','project-shares',NULL,'2025-10-29 06:38:02','2025-12-05 13:12:43'),(5,2,17,'Transactions','fa-money','project-transactions',NULL,'2025-10-29 06:38:02','2025-12-05 13:12:43'),(6,2,18,'Disbursements','fa-share-alt','disbursements',NULL,'2025-10-29 06:38:02','2025-12-05 13:12:43'),(7,22,24,'Account Transactions','fa-diamond','account-transactions',NULL,'2025-10-29 06:38:02','2025-12-05 13:12:43'),(8,0,8,'Insurance','fa-shield',NULL,NULL,'2025-10-29 06:38:02','2025-12-05 13:12:43'),(9,8,10,'Programs','fa-archive','insurance-programs',NULL,'2025-10-29 06:38:02','2025-12-05 13:12:43'),(10,8,11,'Subscriptions','fa-cc-paypal','insurance-subscriptions',NULL,'2025-10-29 06:38:02','2025-12-05 13:12:43'),(13,8,13,'Medical Services Requests','fa-stethoscope','medical-service-requests',NULL,'2025-10-29 06:38:02','2025-12-05 13:12:43'),(18,37,27,'System Users','fa-users','users',NULL,'2025-10-29 06:38:02','2025-12-05 13:12:43'),(21,22,22,'DIP Memberships','fa-cc-paypal','membership-payments',NULL,'2025-11-10 14:36:36','2025-12-05 13:17:15'),(22,0,19,'User Accounts','fa-bank','account-transactions',NULL,'2025-11-11 09:57:40','2025-12-05 13:12:43'),(23,22,23,'Withdraw Requests','fa-outdent','withdraw-requests',NULL,'2025-11-11 10:14:00','2025-12-05 13:12:43'),(24,8,12,'Insurance Monthly Payments','fa-money','insurance-subscription-payments',NULL,'2025-11-11 17:20:26','2025-12-05 13:12:43'),(25,22,20,'Pesapal-payments','fa-cc-mastercard','pesapal-payments',NULL,'2025-11-11 19:46:58','2025-12-05 13:12:43'),(26,8,9,'System Users','fa-users','users',NULL,'2025-11-12 05:16:08','2025-12-05 13:12:43'),(27,28,4,'Network Hierarchy','fa-sitemap','user-hierarchy',NULL,'2025-11-14 17:20:53','2025-12-05 11:45:39'),(28,0,2,'DTEHM','fa-folder-open','product-categories',NULL,'2025-11-14 18:44:26','2025-11-14 18:45:23'),(31,28,5,'Products','fa-archive','products',NULL,'2025-11-14 18:47:24','2025-12-05 13:12:43'),(33,28,6,'Sale Records','fa-adjust','ordered-items',NULL,'2025-11-14 18:56:54','2025-12-05 13:12:43'),(34,22,21,'DTEHM Memberships','fa-archive','dtehm-memberships',NULL,'2025-11-18 18:09:37','2025-12-05 13:12:43'),(37,0,25,'System','fa-cogs',NULL,NULL,'2025-11-24 18:28:47','2025-12-05 13:12:43'),(38,37,26,'Configurations','fa-wrench','system-configurations',NULL,'2025-11-24 18:28:47','2025-12-05 13:12:43'),(39,28,3,'Members','fa-users','users',NULL,'2025-12-05 10:42:14','2025-12-05 10:42:21'),(40,28,7,'Commisions','fa-money','account-transactions',NULL,'2025-12-05 13:11:37','2025-12-05 13:12:53');
/*!40000 ALTER TABLE `admin_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_operation_log`
--

DROP TABLE IF EXISTS `admin_operation_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_operation_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_operation_log_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1608 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_operation_log`
--

LOCK TABLES `admin_operation_log` WRITE;
/*!40000 ALTER TABLE `admin_operation_log` DISABLE KEYS */;
INSERT INTO `admin_operation_log` VALUES (1,1,'/','GET','::1','[]','2025-11-12 04:20:05','2025-11-12 04:20:05'),(2,1,'auth/setting','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:20:16','2025-11-12 04:20:16'),(3,1,'auth/setting','GET','::1','[]','2025-11-12 04:21:16','2025-11-12 04:21:16'),(4,1,'auth/setting','PUT','::1','{\"first_name\":\"Admin\",\"last_name\":\"User\",\"whatsapp\":\"+256783204665\",\"change_password\":\"No\",\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/login\"}','2025-11-12 04:21:47','2025-11-12 04:21:47'),(5,1,'auth/setting','GET','::1','[]','2025-11-12 04:21:47','2025-11-12 04:21:47'),(6,1,'auth/setting','GET','::1','[]','2025-11-12 04:22:21','2025-11-12 04:22:21'),(7,1,'auth/setting','PUT','::1','{\"first_name\":\"Admin\",\"last_name\":\"User\",\"phone_number\":\"+256783204665\",\"whatsapp\":\"+256783204665\",\"change_password\":\"No\",\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/login\"}','2025-11-12 04:22:25','2025-11-12 04:22:25'),(8,1,'auth/setting','GET','::1','[]','2025-11-12 04:22:25','2025-11-12 04:22:25'),(9,1,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:22:32','2025-11-12 04:22:32'),(10,1,'auth/roles','GET','::1','[]','2025-11-12 04:22:48','2025-11-12 04:22:48'),(11,1,'auth/roles/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:22:50','2025-11-12 04:22:50'),(12,1,'auth/roles','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:22:52','2025-11-12 04:22:52'),(13,1,'auth/roles/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:22:54','2025-11-12 04:22:54'),(14,1,'auth/roles','POST','::1','{\"slug\":\"manager\",\"name\":\"System Manager\",\"permissions\":[\"1\",null],\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/roles\"}','2025-11-12 04:23:19','2025-11-12 04:23:19'),(15,1,'auth/roles','GET','::1','[]','2025-11-12 04:23:19','2025-11-12 04:23:19'),(16,1,'auth/roles/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:23:30','2025-11-12 04:23:30'),(17,1,'auth/roles/1','PUT','::1','{\"slug\":\"admin\",\"name\":\"Super Admin\",\"permissions\":[\"1\",null],\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/roles\"}','2025-11-12 04:23:39','2025-11-12 04:23:39'),(18,1,'auth/roles','GET','::1','[]','2025-11-12 04:23:39','2025-11-12 04:23:39'),(19,1,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:23:54','2025-11-12 04:23:54'),(20,1,'auth/menu','GET','::1','[]','2025-11-12 04:24:12','2025-11-12 04:24:12'),(21,1,'auth/menu/8/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:24:23','2025-11-12 04:24:23'),(22,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:25:23','2025-11-12 04:25:23'),(23,1,'auth/menu/9/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:25:38','2025-11-12 04:25:38'),(24,1,'auth/menu/9','PUT','::1','{\"parent_id\":\"8\",\"title\":\"Programs\",\"icon\":\"fa-archive\",\"uri\":\"insurance-programs\",\"roles\":[\"1\",null],\"permission\":null,\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-11-12 04:25:43','2025-11-12 04:25:43'),(25,1,'auth/menu','GET','::1','[]','2025-11-12 04:25:43','2025-11-12 04:25:43'),(26,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:25:56','2025-11-12 04:25:56'),(27,1,'system-configurations','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:26:03','2025-11-12 04:26:03'),(28,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:26:10','2025-11-12 04:26:10'),(29,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:26:17','2025-11-12 04:26:17'),(30,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:26:40','2025-11-12 04:26:40'),(31,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:34:26','2025-11-12 04:34:26'),(32,1,'users/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:34:29','2025-11-12 04:34:29'),(33,1,'users/1/edit','GET','::1','[]','2025-11-12 04:36:52','2025-11-12 04:36:52'),(34,1,'users/1','PUT','::1','{\"first_name\":\"Hilary\",\"last_name\":\"Mercer\",\"sex\":\"Female\",\"user_type\":\"Customer\",\"dob\":null,\"phone_number\":\"+1 (678) 373-9165\",\"email\":\"bezaqot@mailinator.com\",\"country\":\"DRC\",\"tribe\":\"Other\",\"address\":\"Delectus eos repreh\",\"father_name\":\"Eliana Combs\",\"mother_name\":\"Daryl Oneal\",\"child_1\":\"Mollit consectetur d\",\"child_2\":\"Quo atque consequatu\",\"child_3\":\"Ut voluptates offici\",\"child_4\":\"In laboriosam sequi\",\"sponsor_id\":\"Proident asperiores\",\"business_license_number\":\"40\",\"roles\":[\"2\",null],\"status\":\"Active\",\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-12 04:37:16','2025-11-12 04:37:16'),(35,1,'users/1/edit','GET','::1','[]','2025-11-12 04:37:16','2025-11-12 04:37:16'),(36,1,'users/1','PUT','::1','{\"first_name\":\"Hilary\",\"last_name\":\"Mercer\",\"sex\":\"Female\",\"user_type\":\"Customer\",\"dob\":null,\"phone_number\":\"+1 (678) 373-9165\",\"email\":\"bezaqot@mailinator.com\",\"country\":\"DRC\",\"tribe\":\"Other\",\"address\":\"Delectus eos repreh\",\"father_name\":\"Eliana Combs\",\"mother_name\":\"Daryl Oneal\",\"child_1\":\"Mollit consectetur d\",\"child_2\":\"Quo atque consequatu\",\"child_3\":\"Ut voluptates offici\",\"child_4\":\"In laboriosam sequi\",\"sponsor_id\":\"Proident asperiores\",\"business_license_number\":\"40\",\"roles\":[\"2\",null],\"status\":\"Active\",\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-12 04:40:24','2025-11-12 04:40:24'),(37,1,'users/1','GET','::1','[]','2025-11-12 04:40:28','2025-11-12 04:40:28'),(38,1,'users/1','GET','::1','[]','2025-11-12 04:41:05','2025-11-12 04:41:05'),(39,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:41:09','2025-11-12 04:41:09'),(40,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:41:11','2025-11-12 04:41:11'),(41,1,'users','POST','::1','{\"first_name\":\"Abel\",\"last_name\":\"Knowles\",\"sex\":\"Female\",\"user_type\":\"Admin\",\"dob\":null,\"phone_number\":\"+1 (699) 449-7641\",\"email\":\"pefunuh@mailinator.com\",\"country\":\"DRC\",\"tribe\":\"Kakwa\",\"address\":\"Officia et sunt ut\",\"father_name\":\"Renee Butler\",\"mother_name\":\"Regina Holder\",\"child_1\":\"Aute animi ut persp\",\"child_2\":\"Deserunt qui nihil e\",\"child_3\":\"Nisi eaque in ut est\",\"child_4\":\"Ut eum eaque est eaq\",\"sponsor_id\":\"Cupidatat quia dolor\",\"business_license_number\":\"30\",\"roles\":[\"2\",null],\"status\":\"Inactive\",\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"after-save\":\"1\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-12 04:41:17','2025-11-12 04:41:17'),(42,1,'users/2/edit','GET','::1','[]','2025-11-12 04:41:17','2025-11-12 04:41:17'),(43,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:41:20','2025-11-12 04:41:20'),(44,1,'users/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:41:37','2025-11-12 04:41:37'),(45,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:42:47','2025-11-12 04:42:47'),(46,1,'users/2/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:42:56','2025-11-12 04:42:56'),(47,1,'users/2','PUT','::1','{\"first_name\":\"Abel\",\"last_name\":\"Knowles\",\"sex\":\"Male\",\"user_type\":\"Admin\",\"dob\":null,\"phone_number\":\"+256706638484\",\"email\":\"pefunuh@mailinator.com\",\"country\":\"DRC\",\"tribe\":\"Kakwa\",\"address\":\"Officia et sunt ut\",\"father_name\":\"Renee Butler\",\"mother_name\":\"Regina Holder\",\"child_1\":\"Aute animi ut persp\",\"child_2\":\"Deserunt qui nihil e\",\"child_3\":\"Nisi eaque in ut est\",\"child_4\":\"Ut eum eaque est eaq\",\"sponsor_id\":\"Cupidatat quia dolor\",\"business_license_number\":\"30\",\"roles\":[\"2\",null],\"status\":\"Inactive\",\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-12 04:43:38','2025-11-12 04:43:38'),(48,1,'users/2/edit','GET','::1','[]','2025-11-12 04:43:38','2025-11-12 04:43:38'),(49,1,'users/2/edit','GET','::1','[]','2025-11-12 04:43:42','2025-11-12 04:43:42'),(50,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:44:27','2025-11-12 04:44:27'),(51,1,'users/2/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:44:57','2025-11-12 04:44:57'),(52,1,'users/2','PUT','::1','{\"first_name\":\"Abel\",\"last_name\":\"Knowles\",\"sex\":\"Male\",\"user_type\":\"Admin\",\"dob\":null,\"phone_number\":\"+256706638484\",\"email\":\"pefunuh@mailinator.com\",\"country\":\"DRC\",\"tribe\":\"Kakwa\",\"address\":\"Officia et sunt ut\",\"father_name\":\"Renee Butler\",\"mother_name\":\"Regina Holder\",\"child_1\":\"Aute animi ut persp\",\"child_2\":\"Deserunt qui nihil e\",\"child_3\":\"Nisi eaque in ut est\",\"child_4\":\"Ut eum eaque est eaq\",\"sponsor_id\":\"Cupidatat quia dolor\",\"business_license_number\":\"30\",\"roles\":[\"2\",null],\"status\":\"Active\",\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-12 04:47:40','2025-11-12 04:47:40'),(53,1,'users/2/edit','GET','::1','[]','2025-11-12 04:47:40','2025-11-12 04:47:40'),(54,2,'/','GET','::1','[]','2025-11-12 04:47:46','2025-11-12 04:47:46'),(55,1,'insurance-programs','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:48:00','2025-11-12 04:48:00'),(56,1,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:48:03','2025-11-12 04:48:03'),(57,1,'auth/menu','GET','::1','[]','2025-11-12 04:56:12','2025-11-12 04:56:12'),(58,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:56:16','2025-11-12 04:56:16'),(59,2,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:57:10','2025-11-12 04:57:10'),(60,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 04:58:23','2025-11-12 04:58:23'),(61,2,'users/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 05:08:51','2025-11-12 05:08:51'),(62,1,'users/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 05:08:57','2025-11-12 05:08:57'),(63,2,'users/1/edit','GET','::1','[]','2025-11-12 05:09:14','2025-11-12 05:09:14'),(64,2,'users/1/edit','GET','::1','[]','2025-11-12 05:09:32','2025-11-12 05:09:32'),(65,2,'users/1/edit','GET','::1','[]','2025-11-12 05:10:47','2025-11-12 05:10:47'),(66,2,'auth/roles','GET','::1','[]','2025-11-12 05:11:37','2025-11-12 05:11:37'),(67,1,'users/1/edit','GET','::1','[]','2025-11-12 05:12:02','2025-11-12 05:12:02'),(68,1,'users/1/edit','GET','::1','[]','2025-11-12 05:12:12','2025-11-12 05:12:12'),(69,1,'auth/menu','GET','::1','[]','2025-11-12 05:12:40','2025-11-12 05:12:40'),(70,2,'medical-service-requests','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 05:14:32','2025-11-12 05:14:32'),(71,2,'insurance-subscription-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 05:14:34','2025-11-12 05:14:34'),(72,2,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 05:14:40','2025-11-12 05:14:40'),(73,2,'projects','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 05:14:44','2025-11-12 05:14:44'),(74,1,'auth/menu/18/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 05:14:56','2025-11-12 05:14:56'),(75,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 05:15:04','2025-11-12 05:15:04'),(76,1,'auth/menu','POST','::1','{\"parent_id\":\"8\",\"title\":\"System Users\",\"icon\":\"fa-users\",\"uri\":\"users\",\"roles\":[null],\"permission\":null,\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\"}','2025-11-12 05:16:08','2025-11-12 05:16:08'),(77,1,'auth/menu','GET','::1','[]','2025-11-12 05:16:08','2025-11-12 05:16:08'),(78,2,'projects','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:02:34','2025-11-12 06:02:34'),(79,1,'auth/menu','GET','::1','[]','2025-11-12 06:05:52','2025-11-12 06:05:52'),(80,2,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:01','2025-11-12 06:06:01'),(81,2,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:04','2025-11-12 06:06:04'),(82,2,'insurance-subscription-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:09','2025-11-12 06:06:09'),(83,2,'medical-service-requests','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:19','2025-11-12 06:06:19'),(84,2,'projects','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:28','2025-11-12 06:06:28'),(85,2,'project-shares','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:34','2025-11-12 06:06:34'),(86,2,'project-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:35','2025-11-12 06:06:35'),(87,2,'disbursements','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:39','2025-11-12 06:06:39'),(88,2,'pesapal-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:42','2025-11-12 06:06:42'),(89,2,'membership-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:44','2025-11-12 06:06:44'),(90,2,'withdraw-requests','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:47','2025-11-12 06:06:47'),(91,2,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:06:56','2025-11-12 06:06:56'),(92,2,'system-configurations','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:07:01','2025-11-12 06:07:01'),(93,2,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:07:02','2025-11-12 06:07:02'),(94,2,'system-configurations','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:07:04','2025-11-12 06:07:04'),(95,2,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:07:08','2025-11-12 06:07:08'),(96,1,'auth/menu/18/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:07:21','2025-11-12 06:07:21'),(97,1,'auth/menu/18','PUT','::1','{\"parent_id\":\"17\",\"title\":\"System Users\",\"icon\":\"fa-users\",\"uri\":\"users\",\"roles\":[null],\"permission\":null,\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-11-12 06:07:31','2025-11-12 06:07:31'),(98,1,'auth/menu','GET','::1','[]','2025-11-12 06:07:31','2025-11-12 06:07:31'),(99,1,'auth/menu','POST','::1','{\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":18},{\\\"id\\\":17,\\\"children\\\":[{\\\"id\\\":20}]}]\"}','2025-11-12 06:07:38','2025-11-12 06:07:38'),(100,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:07:38','2025-11-12 06:07:38'),(101,1,'auth/menu/17','DELETE','::1','{\"_method\":\"delete\",\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\"}','2025-11-12 06:07:50','2025-11-12 06:07:50'),(102,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:07:50','2025-11-12 06:07:50'),(103,1,'auth/menu','GET','::1','[]','2025-11-12 06:07:53','2025-11-12 06:07:53'),(104,2,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:29:06','2025-11-12 06:29:06'),(105,2,'/','GET','::1','[]','2025-11-12 06:29:17','2025-11-12 06:29:17'),(106,1,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:29:22','2025-11-12 06:29:22'),(107,2,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:29:53','2025-11-12 06:29:53'),(108,1,'auth/menu','GET','::1','[]','2025-11-12 06:48:03','2025-11-12 06:48:03'),(109,1,'projects','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:48:08','2025-11-12 06:48:08'),(110,1,'projects/4/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:48:21','2025-11-12 06:48:21'),(111,1,'/','GET','::1','[]','2025-11-12 06:55:28','2025-11-12 06:55:28'),(112,1,'projects','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:55:30','2025-11-12 06:55:30'),(113,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:55:33','2025-11-12 06:55:33'),(114,1,'insurance-programs','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:55:34','2025-11-12 06:55:34'),(115,1,'insurance-programs/1','PUT','::1','{\"name\":\"status\",\"value\":\"Active\",\"pk\":\"1\",\"_token\":\"mIEkvIuJDy6caRD3cN1IuoJgX1nuaM3k9H4M0Hji\",\"_editable\":\"1\",\"_method\":\"PUT\"}','2025-11-12 06:55:47','2025-11-12 06:55:47'),(116,1,'insurance-programs','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 06:55:48','2025-11-12 06:55:48'),(117,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 07:01:21','2025-11-12 07:01:21'),(118,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-12 07:01:24','2025-11-12 07:01:24'),(119,1,'/','GET','::1','[]','2025-11-14 17:11:05','2025-11-14 17:11:05'),(120,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 17:11:28','2025-11-14 17:11:28'),(121,1,'user-hierarchy','GET','::1','[]','2025-11-14 17:12:24','2025-11-14 17:12:24'),(122,1,'user-hierarchy/102','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 17:12:28','2025-11-14 17:12:28'),(123,1,'user-hierarchy/102','GET','::1','[]','2025-11-14 17:12:28','2025-11-14 17:12:28'),(124,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-11-14 17:12:48','2025-11-14 17:12:48'),(125,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-11-14 17:12:49','2025-11-14 17:12:49'),(126,1,'user-hierarchy/2','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 17:12:56','2025-11-14 17:12:56'),(127,1,'user-hierarchy/2','GET','::1','[]','2025-11-14 17:12:56','2025-11-14 17:12:56'),(128,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:13:47','2025-11-14 17:13:47'),(129,1,'auth/menu','GET','::1','[]','2025-11-14 17:20:20','2025-11-14 17:20:20'),(130,1,'auth/menu','POST','::1','{\"parent_id\":\"0\",\"title\":\"User-hierarchy\",\"icon\":\"fa-adjust\",\"uri\":\"user-hierarchy\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 17:20:53','2025-11-14 17:20:53'),(131,1,'auth/menu','GET','::1','[]','2025-11-14 17:20:53','2025-11-14 17:20:53'),(132,1,'auth/menu','GET','::1','[]','2025-11-14 17:24:03','2025-11-14 17:24:03'),(133,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 17:24:04','2025-11-14 17:24:04'),(134,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-14 17:24:10','2025-11-14 17:24:10'),(135,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:24:11','2025-11-14 17:24:11'),(136,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:26:36','2025-11-14 17:26:36'),(137,1,'users','GET','::1','[]','2025-11-14 17:26:40','2025-11-14 17:26:40'),(138,1,'users/99/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 17:27:01','2025-11-14 17:27:01'),(139,1,'auth/users','GET','::1','[]','2025-11-14 17:28:51','2025-11-14 17:28:51'),(140,1,'auth/users/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 17:28:54','2025-11-14 17:28:54'),(141,1,'auth/users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 17:28:57','2025-11-14 17:28:57'),(142,1,'users/99/edit','GET','::1','[]','2025-11-14 17:29:38','2025-11-14 17:29:38'),(143,1,'users/99/edit','GET','::1','[]','2025-11-14 17:29:55','2025-11-14 17:29:55'),(144,1,'users/99','PUT','::1','{\"first_name\":\"Robert\",\"last_name\":\"Morar\",\"sex\":\"Male\",\"user_type\":\"Customer\",\"dob\":\"1997-08-17\",\"phone_number\":\"+256764924683\",\"email\":\"robert.morar97@test.com\",\"country\":\"Uganda\",\"tribe\":null,\"address\":\"South Addison, Uganda\",\"father_name\":\"Clinton Bosco\",\"mother_name\":\"Onie Herzog\",\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"is_dtehm_member\":\"No\",\"is_dip_member\":\"Yes\",\"dtehm_member_id\":null,\"dtehm_member_membership_date\":null,\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"sponsor_id\":null,\"business_license_number\":null,\"roles\":[null],\"status\":\"Pending\",\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-14 17:29:59','2025-11-14 17:29:59'),(145,1,'users/99/edit','GET','::1','[]','2025-11-14 17:29:59','2025-11-14 17:29:59'),(146,1,'users/99','PUT','::1','{\"first_name\":\"Robert\",\"last_name\":\"Morar\",\"sex\":\"Male\",\"user_type\":\"Customer\",\"dob\":\"1997-08-17\",\"phone_number\":\"+256764924683\",\"email\":\"robert.morar97@test.com\",\"country\":\"Uganda\",\"tribe\":null,\"address\":\"South Addison, Uganda\",\"father_name\":\"Clinton Bosco\",\"mother_name\":\"Onie Herzog\",\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"is_dtehm_member\":\"No\",\"is_dip_member\":\"Yes\",\"dtehm_member_id\":null,\"dtehm_member_membership_date\":null,\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"sponsor_id\":\"DIP0001\",\"business_license_number\":null,\"roles\":[null],\"status\":\"Pending\",\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-14 17:30:15','2025-11-14 17:30:15'),(147,1,'users/99/edit','GET','::1','[]','2025-11-14 17:30:16','2025-11-14 17:30:16'),(148,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:30:18','2025-11-14 17:30:18'),(149,1,'users/99/edit','GET','::1','[]','2025-11-14 17:30:21','2025-11-14 17:30:21'),(150,1,'users/99','PUT','::1','{\"first_name\":\"Robert\",\"last_name\":\"Morar\",\"sex\":\"Male\",\"user_type\":\"Customer\",\"dob\":\"1997-08-17\",\"phone_number\":\"+256764924683\",\"email\":\"robert.morar97@test.com\",\"country\":\"Uganda\",\"tribe\":null,\"address\":\"South Addison, Uganda\",\"father_name\":\"Clinton Bosco\",\"mother_name\":\"Onie Herzog\",\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_id\":null,\"dtehm_member_membership_date\":null,\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"sponsor_id\":\"DIP0001\",\"business_license_number\":null,\"roles\":[null],\"status\":\"Pending\",\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-14 17:30:36','2025-11-14 17:30:36'),(151,1,'users/99/edit','GET','::1','[]','2025-11-14 17:30:37','2025-11-14 17:30:37'),(152,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:30:39','2025-11-14 17:30:39'),(153,1,'users/99','PUT','::1','{\"first_name\":\"Robert\",\"last_name\":\"Morar\",\"sex\":\"Male\",\"user_type\":\"Customer\",\"dob\":\"1997-08-17\",\"phone_number\":\"+256764924683\",\"email\":\"robert.morar97@test.com\",\"country\":\"Uganda\",\"tribe\":null,\"address\":\"South Addison, Uganda\",\"father_name\":\"Clinton Bosco\",\"mother_name\":\"Onie Herzog\",\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_id\":\"DTEHM20250070\",\"dtehm_member_membership_date\":\"2025-11-14 20:30:36\",\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"sponsor_id\":\"DIP0001\",\"business_license_number\":null,\"roles\":[null],\"status\":\"Pending\",\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-14 17:34:00','2025-11-14 17:34:00'),(154,1,'users/99/edit','GET','::1','[]','2025-11-14 17:34:00','2025-11-14 17:34:00'),(155,1,'users/99/edit','GET','::1','[]','2025-11-14 17:35:23','2025-11-14 17:35:23'),(156,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:35:27','2025-11-14 17:35:27'),(157,1,'users/99','PUT','::1','{\"first_name\":\"Robert\",\"last_name\":\"Morar\",\"sex\":\"Male\",\"user_type\":\"Customer\",\"dob\":\"1997-08-17\",\"phone_number\":\"+256764924683\",\"email\":\"robert.morar97@test.com\",\"country\":\"Uganda\",\"tribe\":null,\"address\":\"South Addison, Uganda\",\"father_name\":\"Clinton Bosco\",\"mother_name\":\"Onie Herzog\",\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_id\":\"DTEHM20250070\",\"dtehm_member_membership_date\":\"2025-11-14 20:30:36\",\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"sponsor_id\":\"DIP0002\",\"business_license_number\":null,\"roles\":[null],\"status\":\"Pending\",\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-14 17:35:43','2025-11-14 17:35:43'),(158,1,'users/99/edit','GET','::1','[]','2025-11-14 17:35:43','2025-11-14 17:35:43'),(159,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:35:46','2025-11-14 17:35:46'),(160,1,'users/99','PUT','::1','{\"first_name\":\"Robert\",\"last_name\":\"Morar\",\"sex\":\"Male\",\"user_type\":\"Customer\",\"dob\":\"1997-08-17\",\"phone_number\":\"+256764924683\",\"email\":\"robert.morar97@test.com\",\"country\":\"Uganda\",\"tribe\":null,\"address\":\"South Addison, Uganda\",\"father_name\":\"Clinton Bosco\",\"mother_name\":\"Onie Herzog\",\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_id\":\"DTEHM20250070\",\"dtehm_member_membership_date\":\"2025-11-14 20:30:36\",\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"sponsor_id\":\"DIP0001\",\"business_license_number\":null,\"roles\":[null],\"status\":\"Pending\",\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-14 17:35:56','2025-11-14 17:35:56'),(161,1,'users/99/edit','GET','::1','[]','2025-11-14 17:35:56','2025-11-14 17:35:56'),(162,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:35:59','2025-11-14 17:35:59'),(163,1,'users/99','PUT','::1','{\"first_name\":\"Robert\",\"last_name\":\"Morar\",\"sex\":\"Male\",\"user_type\":\"Customer\",\"dob\":\"1997-08-17\",\"phone_number\":\"+256764924683\",\"email\":\"robert.morar97@test.com\",\"country\":\"Uganda\",\"tribe\":null,\"address\":\"South Addison, Uganda\",\"father_name\":\"Clinton Bosco\",\"mother_name\":\"Onie Herzog\",\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_id\":\"DTEHM20250070\",\"dtehm_member_membership_date\":\"2025-11-14 20:30:36\",\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"sponsor_id\":\"DIP0003\",\"business_license_number\":null,\"roles\":[null],\"status\":\"Pending\",\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-14 17:36:10','2025-11-14 17:36:10'),(164,1,'users/99/edit','GET','::1','[]','2025-11-14 17:36:10','2025-11-14 17:36:10'),(165,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:36:12','2025-11-14 17:36:12'),(166,1,'user-hierarchy/1','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 17:37:16','2025-11-14 17:37:16'),(167,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:37:16','2025-11-14 17:37:16'),(168,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-11-14 17:37:17','2025-11-14 17:37:17'),(169,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:39:41','2025-11-14 17:39:41'),(170,1,'user-hierarchy/2','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 17:40:49','2025-11-14 17:40:49'),(171,1,'user-hierarchy/2','GET','::1','[]','2025-11-14 17:40:49','2025-11-14 17:40:49'),(172,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:40:55','2025-11-14 17:40:55'),(173,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:40:58','2025-11-14 17:40:58'),(174,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:43:03','2025-11-14 17:43:03'),(175,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:43:41','2025-11-14 17:43:41'),(176,1,'user-hierarchy/1','GET','::1','[]','2025-11-14 17:43:48','2025-11-14 17:43:48'),(177,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 17:45:03','2025-11-14 17:45:03'),(178,1,'user-hierarchy/1','GET','::1','[]','2025-11-14 17:45:09','2025-11-14 17:45:09'),(179,1,'user-hierarchy/1','GET','::1','[]','2025-11-14 17:46:10','2025-11-14 17:46:10'),(180,1,'auth/menu','GET','::1','[]','2025-11-14 17:47:49','2025-11-14 17:47:49'),(181,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 17:56:51','2025-11-14 17:56:51'),(182,1,'user-hierarchy/102','GET','::1','[]','2025-11-14 17:56:53','2025-11-14 17:56:53'),(183,1,'user-hierarchy/102','GET','::1','[]','2025-11-14 18:03:20','2025-11-14 18:03:20'),(184,1,'user-hierarchy/102','GET','::1','[]','2025-11-14 18:03:35','2025-11-14 18:03:35'),(185,1,'user-hierarchy/102','GET','::1','[]','2025-11-14 18:03:36','2025-11-14 18:03:36'),(186,1,'user-hierarchy/102','GET','::1','[]','2025-11-14 18:03:37','2025-11-14 18:03:37'),(187,1,'user-hierarchy/102','GET','::1','[]','2025-11-14 18:08:05','2025-11-14 18:08:05'),(188,1,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:10:08','2025-11-14 18:10:08'),(189,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:10:10','2025-11-14 18:10:10'),(190,1,'user-hierarchy/102','GET','::1','[]','2025-11-14 18:10:12','2025-11-14 18:10:12'),(191,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-14 18:10:18','2025-11-14 18:10:18'),(192,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-14 18:10:19','2025-11-14 18:10:19'),(193,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:10:24','2025-11-14 18:10:24'),(194,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:11:27','2025-11-14 18:11:27'),(195,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:15:54','2025-11-14 18:15:54'),(196,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:16:19','2025-11-14 18:16:19'),(197,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:16:30','2025-11-14 18:16:30'),(198,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:16:36','2025-11-14 18:16:36'),(199,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:17:02','2025-11-14 18:17:02'),(200,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:17:16','2025-11-14 18:17:16'),(201,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:17:26','2025-11-14 18:17:26'),(202,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:17:36','2025-11-14 18:17:36'),(203,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:20:39','2025-11-14 18:20:39'),(204,1,'user-hierarchy/40','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:20:41','2025-11-14 18:20:41'),(205,1,'user-hierarchy/40','GET','::1','[]','2025-11-14 18:20:47','2025-11-14 18:20:47'),(206,1,'user-hierarchy/11','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:20:49','2025-11-14 18:20:49'),(207,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:20:51','2025-11-14 18:20:51'),(208,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:24:21','2025-11-14 18:24:21'),(209,1,'user-hierarchy/31','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:24:57','2025-11-14 18:24:57'),(210,1,'user-hierarchy/31','GET','::1','[]','2025-11-14 18:25:02','2025-11-14 18:25:02'),(211,1,'user-hierarchy/11','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:25:09','2025-11-14 18:25:09'),(212,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:25:12','2025-11-14 18:25:12'),(213,1,'user-hierarchy/11','GET','::1','[]','2025-11-14 18:27:38','2025-11-14 18:27:38'),(214,1,'user-hierarchy/21','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:27:46','2025-11-14 18:27:46'),(215,1,'user-hierarchy/40','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:27:51','2025-11-14 18:27:51'),(216,1,'user-hierarchy/40','GET','::1','[]','2025-11-14 18:29:52','2025-11-14 18:29:52'),(217,1,'user-hierarchy/40','GET','::1','[]','2025-11-14 18:29:59','2025-11-14 18:29:59'),(218,1,'user-hierarchy/21','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:30:14','2025-11-14 18:30:14'),(219,1,'user-hierarchy/11','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:30:18','2025-11-14 18:30:18'),(220,1,'auth/menu','GET','::1','[]','2025-11-14 18:43:42','2025-11-14 18:43:42'),(221,1,'auth/menu','POST','::1','{\"parent_id\":\"0\",\"title\":\"DTEHM\",\"icon\":\"fa-folder-open\",\"uri\":\"product-categories\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 18:44:26','2025-11-14 18:44:26'),(222,1,'auth/menu','GET','::1','[]','2025-11-14 18:44:26','2025-11-14 18:44:26'),(223,1,'auth/menu','POST','::1','{\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_order\":\"[{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":27}]},{\\\"id\\\":1},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":18}]\"}','2025-11-14 18:44:32','2025-11-14 18:44:32'),(224,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:44:32','2025-11-14 18:44:32'),(225,1,'auth/menu/27/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:44:34','2025-11-14 18:44:34'),(226,1,'auth/menu/27','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Members Network Hierarchy\",\"icon\":\"fa-adjust\",\"uri\":\"user-hierarchy\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-11-14 18:44:51','2025-11-14 18:44:51'),(227,1,'auth/menu','GET','::1','[]','2025-11-14 18:44:51','2025-11-14 18:44:51'),(228,1,'auth/menu','POST','::1','{\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":27}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":18}]\"}','2025-11-14 18:45:23','2025-11-14 18:45:23'),(229,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:45:23','2025-11-14 18:45:23'),(230,1,'auth/menu','POST','::1','{\"parent_id\":\"0\",\"title\":\"Product Categories\",\"icon\":\"fa-adjust\",\"uri\":null,\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 18:45:54','2025-11-14 18:45:54'),(231,1,'auth/menu','GET','::1','[]','2025-11-14 18:45:54','2025-11-14 18:45:54'),(232,1,'auth/menu','POST','::1','{\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":27},{\\\"id\\\":29}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":18}]\"}','2025-11-14 18:45:58','2025-11-14 18:45:58'),(233,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:45:58','2025-11-14 18:45:58'),(234,1,'auth/menu','GET','::1','[]','2025-11-14 18:46:00','2025-11-14 18:46:00'),(235,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:46:02','2025-11-14 18:46:02'),(236,1,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:46:05','2025-11-14 18:46:05'),(237,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:46:07','2025-11-14 18:46:07'),(238,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:46:10','2025-11-14 18:46:10'),(239,1,'auth/menu/29/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:46:12','2025-11-14 18:46:12'),(240,1,'auth/menu/29','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Product Categories\",\"icon\":\"fa-tree\",\"uri\":\"product-categories\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-11-14 18:46:22','2025-11-14 18:46:22'),(241,1,'auth/menu','GET','::1','[]','2025-11-14 18:46:22','2025-11-14 18:46:22'),(242,1,'auth/menu','POST','::1','{\"parent_id\":\"0\",\"title\":\"Delivery Addresses\",\"icon\":\"fa-delicious\",\"uri\":null,\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 18:46:59','2025-11-14 18:46:59'),(243,1,'auth/menu','GET','::1','[]','2025-11-14 18:46:59','2025-11-14 18:46:59'),(244,1,'auth/menu','POST','::1','{\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":27},{\\\"id\\\":29},{\\\"id\\\":30}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":18}]\"}','2025-11-14 18:47:04','2025-11-14 18:47:04'),(245,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:47:04','2025-11-14 18:47:04'),(246,1,'auth/menu','POST','::1','{\"parent_id\":\"0\",\"title\":\"products\",\"icon\":\"fa-archive\",\"uri\":\"products\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 18:47:24','2025-11-14 18:47:24'),(247,1,'auth/menu','GET','::1','[]','2025-11-14 18:47:24','2025-11-14 18:47:24'),(248,1,'auth/menu','POST','::1','{\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":27},{\\\"id\\\":30},{\\\"id\\\":29},{\\\"id\\\":31}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":18}]\"}','2025-11-14 18:47:34','2025-11-14 18:47:34'),(249,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:47:34','2025-11-14 18:47:34'),(250,1,'auth/menu','POST','::1','{\"parent_id\":\"0\",\"title\":\"Product Orders\",\"icon\":\"fa-cart-plus\",\"uri\":\"product-orders\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 18:47:58','2025-11-14 18:47:58'),(251,1,'auth/menu','GET','::1','[]','2025-11-14 18:47:59','2025-11-14 18:47:59'),(252,1,'auth/menu','POST','::1','{\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":27},{\\\"id\\\":30},{\\\"id\\\":29},{\\\"id\\\":32},{\\\"id\\\":31}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":18}]\"}','2025-11-14 18:48:04','2025-11-14 18:48:04'),(253,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:48:04','2025-11-14 18:48:04'),(254,1,'auth/menu/32/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:48:06','2025-11-14 18:48:06'),(255,1,'auth/menu/32','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Product Sales\",\"icon\":\"fa-cart-plus\",\"uri\":\"product-orders\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-11-14 18:48:13','2025-11-14 18:48:13'),(256,1,'auth/menu','GET','::1','[]','2025-11-14 18:48:13','2025-11-14 18:48:13'),(257,1,'auth/menu/32/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:48:16','2025-11-14 18:48:16'),(258,1,'auth/menu/32','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Sales\",\"icon\":\"fa-cart-plus\",\"uri\":\"product-orders\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-11-14 18:48:20','2025-11-14 18:48:20'),(259,1,'auth/menu','GET','::1','[]','2025-11-14 18:48:20','2025-11-14 18:48:20'),(260,1,'auth/menu/32/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:48:24','2025-11-14 18:48:24'),(261,1,'auth/menu/32','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Sale Records\",\"icon\":\"fa-cart-plus\",\"uri\":\"product-orders\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-11-14 18:48:29','2025-11-14 18:48:29'),(262,1,'auth/menu','GET','::1','[]','2025-11-14 18:48:29','2025-11-14 18:48:29'),(263,1,'auth/menu','GET','::1','[]','2025-11-14 18:48:31','2025-11-14 18:48:31'),(264,1,'auth/menu','GET','::1','[]','2025-11-14 18:48:34','2025-11-14 18:48:34'),(265,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:48:36','2025-11-14 18:48:36'),(266,1,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:48:40','2025-11-14 18:48:40'),(267,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:48:48','2025-11-14 18:48:48'),(268,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:48:49','2025-11-14 18:48:49'),(269,1,'auth/menu/30/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:48:52','2025-11-14 18:48:52'),(270,1,'auth/menu/30','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Delivery Addresses\",\"icon\":\"fa-delicious\",\"uri\":\"delivery-addresses\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-11-14 18:49:02','2025-11-14 18:49:02'),(271,1,'auth/menu','GET','::1','[]','2025-11-14 18:49:03','2025-11-14 18:49:03'),(272,1,'auth/menu','GET','::1','[]','2025-11-14 18:49:05','2025-11-14 18:49:05'),(273,1,'delivery-addresses','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:49:07','2025-11-14 18:49:07'),(274,1,'deliveries','GET','::1','[]','2025-11-14 18:49:16','2025-11-14 18:49:16'),(275,1,'delivery-addresses','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:49:19','2025-11-14 18:49:19'),(276,1,'delivery-addresses/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:49:20','2025-11-14 18:49:20'),(277,1,'delivery-addresses','POST','::1','{\"address\":\"Kasese\",\"latitude\":null,\"longitude\":null,\"shipping_cost\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/delivery-addresses\"}','2025-11-14 18:49:30','2025-11-14 18:49:30'),(278,1,'delivery-addresses/1/edit','GET','::1','[]','2025-11-14 18:49:30','2025-11-14 18:49:30'),(279,1,'delivery-addresses','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:49:33','2025-11-14 18:49:33'),(280,1,'delivery-addresses/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:49:36','2025-11-14 18:49:36'),(281,1,'delivery-addresses/1','PUT','::1','{\"address\":\"Kasese\",\"latitude\":null,\"longitude\":null,\"shipping_cost\":\"10000\",\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/delivery-addresses\"}','2025-11-14 18:49:41','2025-11-14 18:49:41'),(282,1,'delivery-addresses','GET','::1','[]','2025-11-14 18:49:41','2025-11-14 18:49:41'),(283,1,'product-categories','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:49:44','2025-11-14 18:49:44'),(284,1,'product-categories/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:49:45','2025-11-14 18:49:45'),(285,1,'product-orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:49:48','2025-11-14 18:49:48'),(286,1,'product-categories','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:49:51','2025-11-14 18:49:51'),(287,1,'product-orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:49:51','2025-11-14 18:49:51'),(288,1,'orders','GET','::1','[]','2025-11-14 18:50:25','2025-11-14 18:50:25'),(289,1,'auth/menu','GET','::1','[]','2025-11-14 18:50:31','2025-11-14 18:50:31'),(290,1,'auth/menu/32/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:50:45','2025-11-14 18:50:45'),(291,1,'auth/menu/32','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Product Sales\",\"icon\":\"fa-cart-plus\",\"uri\":\"orders\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-11-14 18:50:58','2025-11-14 18:50:58'),(292,1,'auth/menu','GET','::1','[]','2025-11-14 18:50:58','2025-11-14 18:50:58'),(293,1,'auth/menu','POST','::1','{\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":27},{\\\"id\\\":30},{\\\"id\\\":29},{\\\"id\\\":31},{\\\"id\\\":32}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":18}]\"}','2025-11-14 18:51:03','2025-11-14 18:51:03'),(294,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:51:03','2025-11-14 18:51:03'),(295,1,'auth/menu/31/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:51:04','2025-11-14 18:51:04'),(296,1,'auth/menu/31','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Products\",\"icon\":\"fa-archive\",\"uri\":\"products\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-11-14 18:51:08','2025-11-14 18:51:08'),(297,1,'auth/menu','GET','::1','[]','2025-11-14 18:51:08','2025-11-14 18:51:08'),(298,1,'auth/menu','GET','::1','[]','2025-11-14 18:51:10','2025-11-14 18:51:10'),(299,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:51:13','2025-11-14 18:51:13'),(300,1,'user-hierarchy','GET','::1','[]','2025-11-14 18:51:16','2025-11-14 18:51:16'),(301,1,'delivery-addresses','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:51:19','2025-11-14 18:51:19'),(302,1,'product-categories','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:51:20','2025-11-14 18:51:20'),(303,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:51:22','2025-11-14 18:51:22'),(304,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:51:23','2025-11-14 18:51:23'),(305,1,'product-categories','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:51:25','2025-11-14 18:51:25'),(306,1,'delivery-addresses','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:51:26','2025-11-14 18:51:26'),(307,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:51:26','2025-11-14 18:51:26'),(308,1,'delivery-addresses','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:51:28','2025-11-14 18:51:28'),(309,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:53:05','2025-11-14 18:53:05'),(310,1,'product-categories','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:53:06','2025-11-14 18:53:06'),(311,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:53:07','2025-11-14 18:53:07'),(312,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:53:08','2025-11-14 18:53:08'),(313,1,'orders/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:53:10','2025-11-14 18:53:10'),(314,1,'auth/menu','GET','::1','[]','2025-11-14 18:56:27','2025-11-14 18:56:27'),(315,1,'auth/menu','GET','::1','[]','2025-11-14 18:56:31','2025-11-14 18:56:31'),(316,1,'auth/menu','POST','::1','{\"parent_id\":\"0\",\"title\":\"Sale Records\",\"icon\":\"fa-adjust\",\"uri\":\"ordered-items\",\"roles\":[null],\"permission\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 18:56:54','2025-11-14 18:56:54'),(317,1,'auth/menu','GET','::1','[]','2025-11-14 18:56:54','2025-11-14 18:56:54'),(318,1,'auth/menu','POST','::1','{\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":27},{\\\"id\\\":30},{\\\"id\\\":29},{\\\"id\\\":31},{\\\"id\\\":32},{\\\"id\\\":33}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":18}]\"}','2025-11-14 18:57:04','2025-11-14 18:57:04'),(319,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:57:04','2025-11-14 18:57:04'),(320,1,'auth/menu','POST','::1','{\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":27},{\\\"id\\\":30},{\\\"id\\\":29},{\\\"id\\\":31},{\\\"id\\\":32},{\\\"id\\\":33}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":18}]\"}','2025-11-14 18:57:05','2025-11-14 18:57:05'),(321,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 18:57:05','2025-11-14 18:57:05'),(322,1,'auth/menu','GET','::1','[]','2025-11-14 19:03:01','2025-11-14 19:03:01'),(323,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:03:04','2025-11-14 19:03:04'),(324,1,'delivery-addresses','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:03:05','2025-11-14 19:03:05'),(325,1,'product-categories','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:03:06','2025-11-14 19:03:06'),(326,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:03:16','2025-11-14 19:03:16'),(327,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:03:58','2025-11-14 19:03:58'),(328,1,'orders/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:04:00','2025-11-14 19:04:00'),(329,1,'orders','GET','::1','[]','2025-11-14 19:22:38','2025-11-14 19:22:38'),(330,1,'orders','GET','::1','[]','2025-11-14 19:23:39','2025-11-14 19:23:39'),(331,1,'orders/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:23:40','2025-11-14 19:23:40'),(332,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:24:04','2025-11-14 19:24:04'),(333,1,'orders/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:24:05','2025-11-14 19:24:05'),(334,1,'orders/create','GET','::1','[]','2025-11-14 19:24:07','2025-11-14 19:24:07'),(335,1,'orders/create','GET','::1','[]','2025-11-14 19:25:45','2025-11-14 19:25:45'),(336,1,'orders/create','GET','::1','[]','2025-11-14 19:26:27','2025-11-14 19:26:27'),(337,1,'orders/create','GET','::1','[]','2025-11-14 19:26:41','2025-11-14 19:26:41'),(338,1,'orders','POST','::1','{\"order_date\":\"2025-11-14\",\"user\":\"5\",\"customer_name\":\"Muhindo Mubaraka\",\"customer_phone_number_1\":\"0783204665\",\"customer_phone_number_2\":null,\"mail\":\"mubahood360@gmail.com\",\"customer_address\":\"Ntinda, Kisaasi, Uganda\",\"delivery_method\":\"delivery\",\"delivery_address_id\":\"1\",\"delivery_district\":\"some\",\"delivery_address_text\":\"sokem\",\"delivery_amount\":\"0\",\"orderedItems\":{\"new_1\":{\"product\":\"15\",\"qty\":\"1\",\"unit_price\":null,\"color\":null,\"size\":null,\"id\":null,\"_remove_\":\"0\"}},\"payment_gateway\":\"manual\",\"payment_status\":\"PENDING_PAYMENT\",\"payment_confirmation\":null,\"tax\":\"0\",\"discount\":\"0\",\"order_state\":\"0\",\"notes\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\"}','2025-11-14 19:27:05','2025-11-14 19:27:05'),(339,1,'orders/create','GET','::1','[]','2025-11-14 19:27:06','2025-11-14 19:27:06'),(340,1,'orders/create','GET','::1','[]','2025-11-14 19:30:30','2025-11-14 19:30:30'),(341,1,'orders','POST','::1','{\"order_date\":\"2025-11-14\",\"user\":\"1\",\"customer_name\":\"Muhindo Mubaraka\",\"customer_phone_number_1\":\"0783204665\",\"customer_phone_number_2\":null,\"mail\":\"mubahood360@gmail.com\",\"customer_address\":\"Ntinda, Kisaasi, Uganda\",\"delivery_method\":\"delivery\",\"delivery_address_id\":\"1\",\"delivery_district\":\"some\",\"delivery_address_text\":\"Ntinda, Kisaasi, Uganda\",\"delivery_amount\":\"0\",\"orderedItems\":{\"new_1\":{\"product\":\"14\",\"qty\":\"1\",\"unit_price\":null,\"color\":null,\"size\":null,\"id\":null,\"_remove_\":\"0\"}},\"payment_gateway\":\"manual\",\"payment_status\":\"PENDING_PAYMENT\",\"payment_confirmation\":null,\"tax\":\"0\",\"discount\":\"0\",\"order_state\":\"0\",\"notes\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\"}','2025-11-14 19:30:46','2025-11-14 19:30:46'),(342,1,'orders/1/edit','GET','::1','[]','2025-11-14 19:31:17','2025-11-14 19:31:17'),(343,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:31:44','2025-11-14 19:31:44'),(344,1,'orders/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:31:51','2025-11-14 19:31:51'),(345,1,'orders/1/edit','GET','::1','[]','2025-11-14 19:37:14','2025-11-14 19:37:14'),(346,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:37:18','2025-11-14 19:37:18'),(347,1,'orders','GET','::1','[]','2025-11-14 19:37:19','2025-11-14 19:37:19'),(348,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:37:21','2025-11-14 19:37:21'),(349,1,'orders','GET','::1','[]','2025-11-14 19:37:21','2025-11-14 19:37:21'),(350,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:37:24','2025-11-14 19:37:24'),(351,1,'orders/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:37:27','2025-11-14 19:37:27'),(352,1,'orders/create','GET','::1','[]','2025-11-14 19:37:33','2025-11-14 19:37:33'),(353,1,'orders','POST','::1','{\"order_date\":\"2025-11-14\",\"user\":\"87\",\"customer_name\":\"Akeem Lloyd\",\"customer_phone_number_1\":\"+1 (757) 166-5606\",\"customer_phone_number_2\":\"+1 (904) 635-2097\",\"mail\":\"ditogaz@mailinator.com\",\"customer_address\":\"Mollitia ut laborios\",\"delivery_method\":\"delivery\",\"delivery_address_id\":\"1\",\"delivery_district\":\"Eum voluptatum velit\",\"delivery_address_text\":\"Et eaque pariatur A\",\"delivery_amount\":\"0\",\"orderedItems\":{\"new_1\":{\"product\":\"15\",\"qty\":\"1\",\"unit_price\":null,\"color\":null,\"size\":null,\"id\":null,\"_remove_\":\"0\"}},\"payment_gateway\":\"manual\",\"payment_status\":\"FAILED\",\"payment_confirmation\":\"Dolore cupidatat ape\",\"tax\":null,\"discount\":null,\"order_state\":\"0\",\"notes\":\"Consequuntur hic ali\",\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"2\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/orders\"}','2025-11-14 19:37:51','2025-11-14 19:37:51'),(354,1,'orders/create','GET','::1','[]','2025-11-14 19:37:51','2025-11-14 19:37:51'),(355,1,'orders/create','GET','::1','[]','2025-11-14 19:41:23','2025-11-14 19:41:23'),(356,1,'orders/create','GET','::1','[]','2025-11-14 19:41:27','2025-11-14 19:41:27'),(357,1,'orders','POST','::1','{\"order_date\":\"2025-11-14\",\"user\":\"13\",\"customer_name\":\"Muhindo Mubaraka\",\"customer_phone_number_1\":\"0783204665\",\"customer_phone_number_2\":\"0783204665\",\"mail\":\"mubahood360@gmail.com\",\"customer_address\":\"Ntinda, Kisaasi, Uganda\",\"delivery_method\":\"pickup\",\"delivery_address_id\":\"1\",\"delivery_district\":\"some\",\"delivery_address_text\":\"Ntinda, Kisaasi, Uganda\",\"delivery_amount\":\"0\",\"orderedItems\":{\"new_1\":{\"product\":\"15\",\"qty\":\"1\",\"unit_price\":null,\"color\":null,\"size\":null,\"id\":null,\"_remove_\":\"0\"}},\"payment_gateway\":\"manual\",\"payment_status\":\"PENDING_PAYMENT\",\"payment_confirmation\":\"Dolore cupidatat ape\",\"tax\":\"0\",\"discount\":\"0\",\"order_state\":\"0\",\"notes\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 19:41:56','2025-11-14 19:41:56'),(358,1,'orders','GET','::1','[]','2025-11-14 19:41:56','2025-11-14 19:41:56'),(359,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:42:12','2025-11-14 19:42:12'),(360,1,'orders','GET','::1','[]','2025-11-14 19:42:14','2025-11-14 19:42:14'),(361,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:42:18','2025-11-14 19:42:18'),(362,1,'orders','GET','::1','[]','2025-11-14 19:42:18','2025-11-14 19:42:18'),(363,1,'ordered-items','GET','::1','[]','2025-11-14 19:42:22','2025-11-14 19:42:22'),(364,1,'ordered-items','GET','::1','[]','2025-11-14 19:42:32','2025-11-14 19:42:32'),(365,1,'ordered-items','GET','::1','[]','2025-11-14 19:42:32','2025-11-14 19:42:32'),(366,1,'ordered-items','GET','::1','[]','2025-11-14 19:43:55','2025-11-14 19:43:55'),(367,1,'ordered-items','GET','::1','[]','2025-11-14 19:43:57','2025-11-14 19:43:57'),(368,1,'ordered-items','GET','::1','[]','2025-11-14 19:43:58','2025-11-14 19:43:58'),(369,1,'ordered-items','GET','::1','[]','2025-11-14 19:44:50','2025-11-14 19:44:50'),(370,1,'ordered-items','GET','::1','[]','2025-11-14 19:45:04','2025-11-14 19:45:04'),(371,1,'orders','GET','::1','[]','2025-11-14 19:45:07','2025-11-14 19:45:07'),(372,1,'ordered-items','GET','::1','[]','2025-11-14 19:45:13','2025-11-14 19:45:13'),(373,1,'orders','GET','::1','[]','2025-11-14 19:45:46','2025-11-14 19:45:46'),(374,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:45:47','2025-11-14 19:45:47'),(375,1,'ordered-items','GET','::1','[]','2025-11-14 19:45:54','2025-11-14 19:45:54'),(376,1,'ordered-items','GET','::1','[]','2025-11-14 19:46:03','2025-11-14 19:46:03'),(377,1,'ordered-items','GET','::1','[]','2025-11-14 19:46:15','2025-11-14 19:46:15'),(378,1,'ordered-items','GET','::1','[]','2025-11-14 19:46:25','2025-11-14 19:46:25'),(379,1,'ordered-items','GET','::1','[]','2025-11-14 19:46:32','2025-11-14 19:46:32'),(380,1,'ordered-items','GET','::1','[]','2025-11-14 19:46:40','2025-11-14 19:46:40'),(381,1,'ordered-items','GET','::1','[]','2025-11-14 19:46:54','2025-11-14 19:46:54'),(382,1,'ordered-items/2/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:46:57','2025-11-14 19:46:57'),(383,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:46:58','2025-11-14 19:46:58'),(384,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:46:59','2025-11-14 19:46:59'),(385,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:47:02','2025-11-14 19:47:02'),(386,1,'ordered-items','GET','::1','[]','2025-11-14 19:51:16','2025-11-14 19:51:16'),(387,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-14 19:51:17','2025-11-14 19:51:17'),(388,1,'ordered-items/create','GET','::1','[]','2025-11-14 19:52:55','2025-11-14 19:52:55'),(389,1,'ordered-items/create','GET','::1','[]','2025-11-14 19:53:10','2025-11-14 19:53:10'),(390,1,'ordered-items/create','GET','::1','[]','2025-11-14 19:53:18','2025-11-14 19:53:18'),(391,1,'ordered-items','POST','::1','{\"product\":\"2\",\"qty\":\"1\",\"unit_price\":\"0\",\"color\":null,\"size\":null,\"order\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 19:53:35','2025-11-14 19:53:35'),(392,1,'ordered-items/create','GET','::1','[]','2025-11-14 19:53:35','2025-11-14 19:53:35'),(393,1,'ordered-items','GET','::1','[]','2025-11-14 19:53:45','2025-11-14 19:53:45'),(394,1,'ordered-items/create','GET','::1','[]','2025-11-14 19:57:34','2025-11-14 19:57:34'),(395,1,'ordered-items','POST','::1','{\"product\":\"2\",\"qty\":\"1\",\"unit_price\":\"0\",\"color\":null,\"size\":null,\"order\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-11-14 19:57:42','2025-11-14 19:57:42'),(396,1,'ordered-items/create','GET','::1','[]','2025-11-14 19:57:43','2025-11-14 19:57:43'),(397,1,'ordered-items/create','GET','::1','[]','2025-11-14 19:59:29','2025-11-14 19:59:29'),(398,1,'ordered-items','POST','::1','{\"product\":\"18\",\"qty\":\"1\",\"unit_price\":\"0\",\"color\":null,\"size\":null,\"order\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\",\"after-save\":\"1\"}','2025-11-14 19:59:35','2025-11-14 19:59:35'),(399,1,'ordered-items/create','GET','::1','[]','2025-11-14 19:59:36','2025-11-14 19:59:36'),(400,1,'ordered-items','POST','::1','{\"product\":\"18\",\"qty\":\"1\",\"unit_price\":\"0\",\"color\":null,\"size\":null,\"order\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 20:00:13','2025-11-14 20:00:13'),(401,1,'ordered-items/create','GET','::1','[]','2025-11-14 20:00:13','2025-11-14 20:00:13'),(402,1,'ordered-items','POST','::1','{\"product\":\"18\",\"qty\":\"1\",\"unit_price\":\"0\",\"color\":null,\"size\":null,\"order\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 20:00:20','2025-11-14 20:00:20'),(403,1,'ordered-items/create','GET','::1','[]','2025-11-14 20:00:20','2025-11-14 20:00:20'),(404,1,'ordered-items/create','GET','::1','[]','2025-11-14 20:03:17','2025-11-14 20:03:17'),(405,1,'ordered-items','POST','::1','{\"product\":\"2\",\"qty\":\"1\",\"unit_price\":\"0\",\"color\":null,\"size\":null,\"order\":null,\"_token\":\"p3GCkdPBIW4lg3dC9rWWUb5GeCok6Y6SJZVkJKKe\"}','2025-11-14 20:03:23','2025-11-14 20:03:23'),(406,1,'ordered-items','GET','::1','[]','2025-11-14 20:03:23','2025-11-14 20:03:23'),(407,1,'/','GET','::1','[]','2025-11-15 05:08:59','2025-11-15 05:08:59'),(408,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:09:16','2025-11-15 05:09:16'),(409,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:09:20','2025-11-15 05:09:20'),(410,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:09:54','2025-11-15 05:09:54'),(411,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"payment_status\",\"type\":\"desc\"}}','2025-11-15 05:10:00','2025-11-15 05:10:00'),(412,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"payment_status\",\"type\":\"asc\"}}','2025-11-15 05:10:02','2025-11-15 05:10:02'),(413,1,'orders/3/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:10:05','2025-11-15 05:10:05'),(414,1,'orders/3','PUT','::1','{\"order_date\":\"2025-10-23\",\"user\":\"13\",\"customer_name\":\"Test Customer 1\",\"customer_phone_number_1\":\"+256709619025\",\"customer_phone_number_2\":null,\"mail\":null,\"customer_address\":\"Kampala, Uganda\",\"delivery_method\":\"delivery\",\"delivery_address_id\":null,\"delivery_district\":null,\"delivery_address_text\":null,\"delivery_amount\":\"280\",\"payment_gateway\":\"cash_on_delivery\",\"payment_status\":\"PAID\",\"payment_confirmation\":null,\"tax\":\"0\",\"discount\":\"0\",\"order_state\":\"2\",\"notes\":null,\"_token\":\"pxzWKHOVCbsVKdpaOsiu3XiaHJqCxbsyrTTiez8w\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/orders?&_sort%5Bcolumn%5D=payment_status&_sort%5Btype%5D=asc\"}','2025-11-15 05:10:37','2025-11-15 05:10:37'),(415,1,'orders','GET','::1','{\"_sort\":{\"column\":\"payment_status\",\"type\":\"asc\"}}','2025-11-15 05:10:37','2025-11-15 05:10:37'),(416,1,'orders','GET','::1','{\"_sort\":{\"column\":\"payment_status\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-11-15 05:10:42','2025-11-15 05:10:42'),(417,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:10:46','2025-11-15 05:10:46'),(418,1,'withdraw-requests','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:11:00','2025-11-15 05:11:00'),(419,1,'membership-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:11:01','2025-11-15 05:11:01'),(420,1,'withdraw-requests','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:11:02','2025-11-15 05:11:02'),(421,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:11:03','2025-11-15 05:11:03'),(422,1,'withdraw-requests','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:11:19','2025-11-15 05:11:19'),(423,1,'membership-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:11:20','2025-11-15 05:11:20'),(424,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:11:29','2025-11-15 05:11:29'),(425,1,'ordered-items','GET','::1','[]','2025-11-15 05:14:21','2025-11-15 05:14:21'),(426,1,'ordered-items','GET','::1','[]','2025-11-15 05:20:27','2025-11-15 05:20:27'),(427,1,'ordered-items/70/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:20:33','2025-11-15 05:20:33'),(428,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:20:35','2025-11-15 05:20:35'),(429,1,'auth/menu','GET','::1','[]','2025-11-15 05:35:06','2025-11-15 05:35:06'),(430,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:35:16','2025-11-15 05:35:16'),(431,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-15 05:35:21','2025-11-15 05:35:21'),(432,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-15 05:35:22','2025-11-15 05:35:22'),(433,1,'user-hierarchy/2','GET','::1','[]','2025-11-15 05:35:27','2025-11-15 05:35:27'),(434,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:35:59','2025-11-15 05:35:59'),(435,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:36:01','2025-11-15 05:36:01'),(436,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 05:36:05','2025-11-15 05:36:05'),(437,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 06:55:32','2025-11-15 06:55:32'),(438,1,'users/151/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 06:55:53','2025-11-15 06:55:53'),(439,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 06:58:11','2025-11-15 06:58:11'),(440,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-15 07:01:28','2025-11-15 07:01:28'),(441,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-15 07:01:29','2025-11-15 07:01:29'),(442,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-15 07:02:22','2025-11-15 07:02:22'),(443,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:04:42','2025-11-15 07:04:42'),(444,1,'users','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-11-15 07:05:42','2025-11-15 07:05:42'),(445,1,'users','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-11-15 07:05:45','2025-11-15 07:05:45'),(446,1,'users','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-11-15 07:07:46','2025-11-15 07:07:46'),(447,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:07:53','2025-11-15 07:07:53'),(448,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-15 07:07:56','2025-11-15 07:07:56'),(449,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-15 07:07:57','2025-11-15 07:07:57'),(450,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"},\"per_page\":\"100\"}','2025-11-15 07:10:33','2025-11-15 07:10:33'),(451,1,'user-hierarchy/2','GET','::1','[]','2025-11-15 07:10:52','2025-11-15 07:10:52'),(452,1,'user-hierarchy/29','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:16:56','2025-11-15 07:16:56'),(453,1,'user-hierarchy/74','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:17:57','2025-11-15 07:17:57'),(454,1,'user-hierarchy/29','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:17:59','2025-11-15 07:17:59'),(455,1,'user-hierarchy/34','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:18:02','2025-11-15 07:18:02'),(456,1,'user-hierarchy/29','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:18:07','2025-11-15 07:18:07'),(457,1,'user-hierarchy/2','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:18:09','2025-11-15 07:18:09'),(458,1,'user-hierarchy/2','GET','::1','[]','2025-11-15 07:18:17','2025-11-15 07:18:17'),(459,1,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:18:44','2025-11-15 07:18:44'),(460,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:18:47','2025-11-15 07:18:47'),(461,1,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:19:06','2025-11-15 07:19:06'),(462,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:19:06','2025-11-15 07:19:06'),(463,1,'users/151/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:19:13','2025-11-15 07:19:13'),(464,1,'users/151','PUT','::1','{\"first_name\":\"Extra\",\"last_name\":\"Seller10\",\"sex\":\"Female\",\"user_type\":\"Customer\",\"dob\":\"1997-08-19\",\"phone_number\":\"+256790000010\",\"email\":\"extra.seller10@dtehm.test\",\"country\":\"Uganda\",\"tribe\":null,\"address\":\"Kampala, Uganda\",\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"dtehm_member_id\":\"DTEHM20259010\",\"dtehm_member_membership_date\":\"2025-04-19 07:49:07\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-03-28 07:49:07\",\"dtehm_membership_paid_amount\":\"50000\",\"sponsor_id\":\"DIP0001\",\"business_license_number\":null,\"roles\":[null],\"_token\":\"pxzWKHOVCbsVKdpaOsiu3XiaHJqCxbsyrTTiez8w\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-15 07:19:58','2025-11-15 07:19:58'),(465,1,'users/151/edit','GET','::1','[]','2025-11-15 07:19:58','2025-11-15 07:19:58'),(466,1,'users/151/edit','GET','::1','[]','2025-11-15 07:20:06','2025-11-15 07:20:06'),(467,1,'users/151','PUT','::1','{\"first_name\":\"Extra\",\"last_name\":\"Seller10\",\"sex\":\"Female\",\"user_type\":\"Customer\",\"dob\":\"1997-08-19\",\"phone_number\":\"+256790000010\",\"email\":\"extra.seller10@dtehm.test\",\"country\":\"Uganda\",\"tribe\":null,\"address\":\"Kampala, Uganda\",\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"dtehm_member_id\":\"DTEHM20259010\",\"dtehm_member_membership_date\":\"2025-04-19 07:49:07\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-03-28 07:49:07\",\"dtehm_membership_paid_amount\":\"50000\",\"sponsor_id\":\"DIP0001\",\"business_license_number\":null,\"roles\":[null],\"status\":\"Active\",\"_token\":\"pxzWKHOVCbsVKdpaOsiu3XiaHJqCxbsyrTTiez8w\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-15 07:20:23','2025-11-15 07:20:23'),(468,1,'users/151/edit','GET','::1','[]','2025-11-15 07:20:23','2025-11-15 07:20:23'),(469,1,'users/151/edit','GET','::1','[]','2025-11-15 07:20:29','2025-11-15 07:20:29'),(470,1,'users/151','PUT','::1','{\"first_name\":\"Hope\",\"last_name\":\"Herrera\",\"sex\":\"Female\",\"user_type\":\"Admin\",\"dob\":\"1997-08-19\",\"phone_number\":\"+1 (298) 242-5632\",\"email\":\"racijubyp@mailinator.com\",\"country\":\"Uganda\",\"tribe\":\"Bagisu\",\"address\":\"Fugiat illo ullam q\",\"father_name\":\"Delilah Dotson\",\"mother_name\":\"Maggie Walls\",\"child_1\":\"A aut deleniti volup\",\"child_2\":\"Quia quia facilis ap\",\"child_3\":\"Error in itaque aliq\",\"child_4\":\"Eos voluptatem dolor\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_id\":\"DTEHM20259010\",\"dtehm_member_membership_date\":\"2025-04-19 07:49:07\",\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":\"2025-03-28 07:49:07\",\"dtehm_membership_paid_amount\":null,\"sponsor_id\":\"DIP0001\",\"business_license_number\":\"776\",\"roles\":[\"2\",null],\"status\":\"Inactive\",\"_token\":\"pxzWKHOVCbsVKdpaOsiu3XiaHJqCxbsyrTTiez8w\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-15 07:20:47','2025-11-15 07:20:47'),(471,1,'users/151/edit','GET','::1','[]','2025-11-15 07:20:47','2025-11-15 07:20:47'),(472,1,'user-hierarchy/2','GET','::1','[]','2025-11-15 07:21:00','2025-11-15 07:21:00'),(473,1,'users/151/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:22:08','2025-11-15 07:22:08'),(474,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:22:11','2025-11-15 07:22:11'),(475,1,'users/150/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:22:23','2025-11-15 07:22:23'),(476,1,'users/150','PUT','::1','{\"first_name\":\"Extra\",\"last_name\":\"Seller9\",\"sex\":\"Male\",\"user_type\":\"Customer\",\"dob\":\"1994-04-17\",\"phone_number\":\"+256790000009\",\"email\":\"extra.seller9@dtehm.test\",\"country\":\"Uganda\",\"tribe\":null,\"address\":\"Kampala, Uganda\",\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"dtehm_member_id\":\"DTEHM20259009\",\"dtehm_member_membership_date\":\"2025-03-01 07:49:07\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2024-11-26 07:49:07\",\"dtehm_membership_paid_amount\":\"50000\",\"sponsor_id\":\"DIP0079\",\"business_license_number\":null,\"roles\":[null],\"status\":\"Active\",\"_token\":\"pxzWKHOVCbsVKdpaOsiu3XiaHJqCxbsyrTTiez8w\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-15 07:22:33','2025-11-15 07:22:33'),(477,1,'users/150/edit','GET','::1','[]','2025-11-15 07:22:34','2025-11-15 07:22:34'),(478,1,'users/150/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:22:36','2025-11-15 07:22:36'),(479,1,'users/150','PUT','::1','{\"first_name\":\"Hedwig\",\"last_name\":\"Hardy\",\"sex\":\"Female\",\"user_type\":\"Customer\",\"dob\":\"1994-04-17\",\"phone_number\":\"+1 (846) 465-9808\",\"email\":\"dixyta@mailinator.com\",\"country\":\"Rwanda\",\"tribe\":\"Banyankole\",\"address\":\"In minus in sed et e\",\"father_name\":\"Alexa Barry\",\"mother_name\":\"Hilary Nash\",\"child_1\":\"Consequat Eos esse\",\"child_2\":\"Aut eu quas nesciunt\",\"child_3\":\"Non perferendis perf\",\"child_4\":\"Eu voluptas qui nece\",\"is_dtehm_member\":\"No\",\"is_dip_member\":\"No\",\"dtehm_member_id\":\"DTEHM20259009\",\"dtehm_member_membership_date\":\"2025-03-01 07:49:07\",\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":\"2024-11-26 07:49:07\",\"dtehm_membership_paid_amount\":null,\"sponsor_id\":\"DIP0079\",\"business_license_number\":\"741\",\"roles\":[\"2\",null],\"status\":\"Active\",\"_token\":\"pxzWKHOVCbsVKdpaOsiu3XiaHJqCxbsyrTTiez8w\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-15 07:22:46','2025-11-15 07:22:46'),(480,1,'users/150/edit','GET','::1','[]','2025-11-15 07:22:46','2025-11-15 07:22:46'),(481,1,'user-hierarchy/2','GET','::1','[]','2025-11-15 07:23:26','2025-11-15 07:23:26'),(482,1,'user-hierarchy/80','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 07:23:42','2025-11-15 07:23:42'),(483,1,'/','GET','::1','[]','2025-11-15 08:25:27','2025-11-15 08:25:27'),(484,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:30:04','2025-11-15 08:30:04'),(485,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-15 08:30:13','2025-11-15 08:30:13'),(486,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-15 08:30:16','2025-11-15 08:30:16'),(487,1,'user-hierarchy/3','GET','::1','[]','2025-11-15 08:30:22','2025-11-15 08:30:22'),(488,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:30:35','2025-11-15 08:30:35'),(489,1,'products/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:30:54','2025-11-15 08:30:54'),(490,1,'products','GET','::1','[]','2025-11-15 08:30:54','2025-11-15 08:30:54'),(491,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:32:30','2025-11-15 08:32:30'),(492,1,'orders/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:32:45','2025-11-15 08:32:45'),(493,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:34:00','2025-11-15 08:34:00'),(494,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:34:02','2025-11-15 08:34:02'),(495,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:34:05','2025-11-15 08:34:05'),(496,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:34:07','2025-11-15 08:34:07'),(497,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:35:22','2025-11-15 08:35:22'),(498,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:35:22','2025-11-15 08:35:22'),(499,1,'orders/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:35:23','2025-11-15 08:35:23'),(500,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:37:53','2025-11-15 08:37:53'),(501,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:37:55','2025-11-15 08:37:55'),(502,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:38:21','2025-11-15 08:38:21'),(503,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:42:19','2025-11-15 08:42:19'),(504,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:46:38','2025-11-15 08:46:38'),(505,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:53:11','2025-11-15 08:53:11'),(506,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:53:12','2025-11-15 08:53:12'),(507,1,'users','GET','::1','[]','2025-11-15 08:53:12','2025-11-15 08:53:12'),(508,1,'users','GET','::1','[]','2025-11-15 08:55:11','2025-11-15 08:55:11'),(509,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:55:13','2025-11-15 08:55:13'),(510,1,'users','GET','::1','[]','2025-11-15 08:55:13','2025-11-15 08:55:13'),(511,1,'disbursements','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:55:35','2025-11-15 08:55:35'),(512,1,'project-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:55:37','2025-11-15 08:55:37'),(513,1,'disbursements','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:56:02','2025-11-15 08:56:02'),(514,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:56:05','2025-11-15 08:56:05'),(515,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:56:07','2025-11-15 08:56:07'),(516,1,'users','GET','::1','[]','2025-11-15 08:56:07','2025-11-15 08:56:07'),(517,1,'users','GET','::1','[]','2025-11-15 08:57:21','2025-11-15 08:57:21'),(518,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:57:23','2025-11-15 08:57:23'),(519,1,'users','GET','::1','[]','2025-11-15 08:57:23','2025-11-15 08:57:23'),(520,1,'users','GET','::1','[]','2025-11-15 08:57:56','2025-11-15 08:57:56'),(521,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:57:57','2025-11-15 08:57:57'),(522,1,'users','GET','::1','[]','2025-11-15 08:57:58','2025-11-15 08:57:58'),(523,1,'users','GET','::1','[]','2025-11-15 08:58:56','2025-11-15 08:58:56'),(524,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:58:58','2025-11-15 08:58:58'),(525,1,'users','GET','::1','[]','2025-11-15 08:58:58','2025-11-15 08:58:58'),(526,1,'users','GET','::1','[]','2025-11-15 08:59:32','2025-11-15 08:59:32'),(527,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:59:33','2025-11-15 08:59:33'),(528,1,'users','GET','::1','[]','2025-11-15 08:59:34','2025-11-15 08:59:34'),(529,1,'users/create','GET','::1','[]','2025-11-15 08:59:44','2025-11-15 08:59:44'),(530,1,'users','GET','::1','[]','2025-11-15 08:59:53','2025-11-15 08:59:53'),(531,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 08:59:55','2025-11-15 08:59:55'),(532,1,'users','GET','::1','[]','2025-11-15 08:59:55','2025-11-15 08:59:55'),(533,1,'users','GET','::1','[]','2025-11-15 09:01:43','2025-11-15 09:01:43'),(534,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-15 09:01:44','2025-11-15 09:01:44'),(535,1,'/','GET','::1','[]','2025-11-18 15:54:16','2025-11-18 15:54:16'),(536,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 15:54:27','2025-11-18 15:54:27'),(537,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 16:01:40','2025-11-18 16:01:40'),(538,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 16:01:41','2025-11-18 16:01:41'),(539,1,'users/create','GET','::1','[]','2025-11-18 16:11:38','2025-11-18 16:11:38'),(540,1,'users/create','GET','::1','[]','2025-11-18 16:12:41','2025-11-18 16:12:41'),(541,1,'users/create','GET','::1','[]','2025-11-18 16:12:51','2025-11-18 16:12:51'),(542,1,'users/create','GET','::1','[]','2025-11-18 16:13:01','2025-11-18 16:13:01'),(543,1,'users/create','GET','::1','[]','2025-11-18 16:41:05','2025-11-18 16:41:05'),(544,1,'users/create','GET','::1','[]','2025-11-18 16:42:11','2025-11-18 16:42:11'),(545,1,'users/create','GET','::1','[]','2025-11-18 16:42:36','2025-11-18 16:42:36'),(546,1,'users/create','GET','::1','[]','2025-11-18 16:44:23','2025-11-18 16:44:23'),(547,1,'users/create','GET','::1','[]','2025-11-18 16:45:48','2025-11-18 16:45:48'),(548,1,'users/create','GET','::1','[]','2025-11-18 16:47:39','2025-11-18 16:47:39'),(549,1,'users/create','GET','::1','[]','2025-11-18 16:48:44','2025-11-18 16:48:44'),(550,1,'users/create','GET','::1','[]','2025-11-18 16:57:35','2025-11-18 16:57:35'),(551,1,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 17:07:41','2025-11-18 17:07:41'),(552,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 17:07:43','2025-11-18 17:07:43'),(553,1,'membership-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 17:08:09','2025-11-18 17:08:09'),(554,1,'membership-payments','GET','::1','[]','2025-11-18 18:07:43','2025-11-18 18:07:43'),(555,1,'auth/menu','GET','::1','[]','2025-11-18 18:08:16','2025-11-18 18:08:16'),(556,1,'auth/menu','POST','::1','{\"parent_id\":\"28\",\"title\":\"DTEHM Memberships\",\"icon\":\"fa-archive\",\"uri\":\"dtehm-memberships\",\"roles\":[null],\"permission\":null,\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\"}','2025-11-18 18:09:37','2025-11-18 18:09:37'),(557,1,'auth/menu','GET','::1','[]','2025-11-18 18:09:37','2025-11-18 18:09:37'),(558,1,'dtehm-memberships','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:09:40','2025-11-18 18:09:40'),(559,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:09:58','2025-11-18 18:09:58'),(560,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:10:03','2025-11-18 18:10:03'),(561,1,'users/create','GET','::1','[]','2025-11-18 18:15:08','2025-11-18 18:15:08'),(562,1,'users','POST','::1','{\"first_name\":\"Caleb\",\"last_name\":\"Knight\",\"phone_number\":\"+1 (434) 745-7202\",\"sex\":\"Male\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"3\"}','2025-11-18 18:15:25','2025-11-18 18:15:25'),(563,1,'users/152','GET','::1','[]','2025-11-18 18:15:25','2025-11-18 18:15:25'),(564,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:15:29','2025-11-18 18:15:29'),(565,1,'dtehm-memberships','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:15:36','2025-11-18 18:15:36'),(566,1,'users','GET','::1','[]','2025-11-18 18:18:16','2025-11-18 18:18:16'),(567,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:18:32','2025-11-18 18:18:32'),(568,1,'users','POST','::1','{\"first_name\":\"Avram\",\"last_name\":\"Brady\",\"phone_number\":\"+1 (954) 448-8779\",\"sex\":\"Male\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"3\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-18 18:18:43','2025-11-18 18:18:43'),(569,1,'users/153','GET','::1','[]','2025-11-18 18:18:43','2025-11-18 18:18:43'),(570,1,'dtehm-memberships','GET','::1','[]','2025-11-18 18:18:47','2025-11-18 18:18:47'),(571,1,'users/153/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:18:55','2025-11-18 18:18:55'),(572,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:24:00','2025-11-18 18:24:00'),(573,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:24:03','2025-11-18 18:24:03'),(574,1,'users/create','GET','::1','[]','2025-11-18 18:24:48','2025-11-18 18:24:48'),(575,1,'users','POST','::1','{\"first_name\":\"Holly\",\"last_name\":\"Glass\",\"phone_number\":\"+1 (212) 855-6587\",\"sex\":\"Male\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"1\"}','2025-11-18 18:24:59','2025-11-18 18:24:59'),(576,1,'users/154/edit','GET','::1','[]','2025-11-18 18:24:59','2025-11-18 18:24:59'),(577,1,'dtehm-memberships','GET','::1','[]','2025-11-18 18:25:04','2025-11-18 18:25:04'),(578,1,'users/154','PUT','::1','{\"first_name\":\"Holly\",\"last_name\":\"Glass\",\"sex\":\"Male\",\"phone_number\":\"+1 (212) 855-6587\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-18 21:24:59\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":null,\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":null,\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"status\":\"Active\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\\/create\"}','2025-11-18 18:29:57','2025-11-18 18:29:57'),(579,1,'users/154/edit','GET','::1','[]','2025-11-18 18:29:57','2025-11-18 18:29:57'),(580,1,'dtehm-memberships','GET','::1','[]','2025-11-18 18:30:00','2025-11-18 18:30:00'),(581,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:30:45','2025-11-18 18:30:45'),(582,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:30:47','2025-11-18 18:30:47'),(583,1,'users','POST','::1','{\"first_name\":\"Gwendolyn\",\"last_name\":\"Lara\",\"phone_number\":\"+1 (267) 795-8451\",\"sex\":\"Male\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"2\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-18 18:30:54','2025-11-18 18:30:54'),(584,1,'users/create','GET','::1','[]','2025-11-18 18:30:55','2025-11-18 18:30:55'),(585,1,'dtehm-memberships','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:30:59','2025-11-18 18:30:59'),(586,1,'users/create','GET','::1','[]','2025-11-18 18:31:03','2025-11-18 18:31:03'),(587,1,'users','POST','::1','{\"first_name\":\"Elliott\",\"last_name\":\"Ashley\",\"phone_number\":\"+1 (875) 848-4591\",\"sex\":\"Male\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"1\"}','2025-11-18 18:31:12','2025-11-18 18:31:12'),(588,1,'users/156/edit','GET','::1','[]','2025-11-18 18:31:12','2025-11-18 18:31:12'),(589,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:35:07','2025-11-18 18:35:07'),(590,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:49:04','2025-11-18 18:49:04'),(591,1,'dtehm-memberships','GET','::1','[]','2025-11-18 18:49:27','2025-11-18 18:49:27'),(592,1,'users','POST','::1','{\"first_name\":\"Joshua\",\"last_name\":\"May\",\"phone_number\":\"+1 (808) 248-4843\",\"sex\":\"Female\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"1\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-18 18:49:36','2025-11-18 18:49:36'),(593,1,'users/157/edit','GET','::1','[]','2025-11-18 18:49:36','2025-11-18 18:49:36'),(594,1,'dtehm-memberships','GET','::1','[]','2025-11-18 18:49:41','2025-11-18 18:49:41'),(595,1,'membership-payments','GET','::1','[]','2025-11-18 18:50:18','2025-11-18 18:50:18'),(596,1,'users/157','PUT','::1','{\"first_name\":\"Joshua\",\"last_name\":\"May\",\"sex\":\"Female\",\"phone_number\":\"+1 (808) 248-4843\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"dtehm_member_membership_date\":\"2025-11-18 21:49:36\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-18 21:49:36\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":null,\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":null,\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"status\":\"Active\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\\/create\"}','2025-11-18 18:50:24','2025-11-18 18:50:24'),(597,1,'users/157/edit','GET','::1','[]','2025-11-18 18:50:24','2025-11-18 18:50:24'),(598,1,'membership-payments','GET','::1','[]','2025-11-18 18:50:26','2025-11-18 18:50:26'),(599,1,'users/157','PUT','::1','{\"first_name\":\"Joshua\",\"last_name\":\"May\",\"sex\":\"Female\",\"phone_number\":\"+1 (808) 248-4843\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"dtehm_member_membership_date\":\"2025-11-18 21:49:36\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-18 21:49:36\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":\"2025-11-19\",\"sponsor_id\":null,\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":null,\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"status\":\"Active\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-18 18:56:39','2025-11-18 18:56:39'),(600,1,'users/157/edit','GET','::1','[]','2025-11-18 18:56:40','2025-11-18 18:56:40'),(601,1,'membership-payments','GET','::1','[]','2025-11-18 18:56:46','2025-11-18 18:56:46'),(602,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:56:58','2025-11-18 18:56:58'),(603,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:57:00','2025-11-18 18:57:00'),(604,1,'users','POST','::1','{\"first_name\":\"Hayley\",\"last_name\":\"Snider\",\"phone_number\":\"+1 (207) 508-1202\",\"sex\":\"Male\",\"is_dtehm_member\":\"No\",\"is_dip_member\":\"Yes\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"1\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-18 18:57:12','2025-11-18 18:57:12'),(605,1,'users/158/edit','GET','::1','[]','2025-11-18 18:57:12','2025-11-18 18:57:12'),(606,1,'membership-payments','GET','::1','[]','2025-11-18 18:57:15','2025-11-18 18:57:15'),(607,1,'membership-payments/6','PUT','::1','{\"name\":\"status\",\"value\":\"CONFIRMED\",\"pk\":\"6\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"_editable\":\"1\",\"_method\":\"PUT\"}','2025-11-18 18:57:23','2025-11-18 18:57:23'),(608,1,'membership-payments','GET','::1','[]','2025-11-18 18:58:02','2025-11-18 18:58:02'),(609,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:59:20','2025-11-18 18:59:20'),(610,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 18:59:22','2025-11-18 18:59:22'),(611,1,'users','POST','::1','{\"first_name\":\"Muhindo\",\"last_name\":\"Mubaraka\",\"phone_number\":\"0783204665\",\"sex\":\"Male\",\"is_dtehm_member\":\"No\",\"is_dip_member\":\"Yes\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-18 18:59:34','2025-11-18 18:59:34'),(612,1,'users','GET','::1','[]','2025-11-18 18:59:34','2025-11-18 18:59:34'),(613,1,'users/159/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 19:01:37','2025-11-18 19:01:37'),(614,1,'users/159','PUT','::1','{\"first_name\":\"Muhindo\",\"last_name\":\"Mubaraka\",\"sex\":\"Male\",\"phone_number\":\"0783204665\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":null,\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":null,\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":null,\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"status\":\"Active\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-18 19:01:42','2025-11-18 19:01:42'),(615,1,'users/159/edit','GET','::1','[]','2025-11-18 19:01:43','2025-11-18 19:01:43'),(616,1,'users/159/edit','GET','::1','[]','2025-11-18 19:04:32','2025-11-18 19:04:32'),(617,1,'users/159','PUT','::1','{\"first_name\":\"Muhindo\",\"last_name\":\"Mubaraka\",\"sex\":\"Male\",\"phone_number\":\"0783204665\",\"is_dtehm_member\":\"No\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":null,\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":null,\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":null,\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"status\":\"Active\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-18 19:04:40','2025-11-18 19:04:40'),(618,1,'users/159/edit','GET','::1','[]','2025-11-18 19:04:41','2025-11-18 19:04:41'),(619,1,'membership-payments','GET','::1','[]','2025-11-18 19:04:45','2025-11-18 19:04:45'),(620,1,'membership-payments','GET','::1','[]','2025-11-18 19:10:46','2025-11-18 19:10:46'),(621,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 19:10:52','2025-11-18 19:10:52'),(622,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 19:10:56','2025-11-18 19:10:56'),(623,1,'users','POST','::1','{\"first_name\":\"Noah\",\"last_name\":\"Shaffer\",\"phone_number\":\"+1 (996) 366-4035\",\"sex\":\"Male\",\"sponsor_id\":null,\"is_dtehm_member\":\"No\",\"is_dip_member\":\"Yes\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-18 19:11:09','2025-11-18 19:11:09'),(624,1,'users/create','GET','::1','[]','2025-11-18 19:11:10','2025-11-18 19:11:10'),(625,1,'users','POST','::1','{\"first_name\":\"Noah\",\"last_name\":\"Shaffer\",\"phone_number\":\"+1 (996) 366-4035\",\"sex\":\"Male\",\"sponsor_id\":\"DIP0001\",\"is_dtehm_member\":\"No\",\"is_dip_member\":\"Yes\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"1\"}','2025-11-18 19:11:28','2025-11-18 19:11:28'),(626,1,'users/160/edit','GET','::1','[]','2025-11-18 19:11:28','2025-11-18 19:11:28'),(627,1,'membership-payments','GET','::1','[]','2025-11-18 19:11:33','2025-11-18 19:11:33'),(628,1,'membership-payments','GET','::1','[]','2025-11-18 19:15:14','2025-11-18 19:15:14'),(629,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 19:15:18','2025-11-18 19:15:18'),(630,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-18 19:15:24','2025-11-18 19:15:24'),(631,1,'users','POST','::1','{\"first_name\":\"Dorothy\",\"last_name\":\"Blackwell\",\"phone_number\":\"+1 (424) 667-7554\",\"sex\":\"Male\",\"sponsor_id\":\"DIP0001\",\"is_dtehm_member\":\"No\",\"is_dip_member\":\"Yes\",\"_token\":\"HgzdMLb4zo1bwW7rBUCRHQqKAjDfPmUgyndv5Q7S\",\"after-save\":\"3\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-18 19:15:36','2025-11-18 19:15:36'),(632,1,'users/161','GET','::1','[]','2025-11-18 19:15:36','2025-11-18 19:15:36'),(633,1,'membership-payments','GET','::1','[]','2025-11-18 19:15:51','2025-11-18 19:15:51'),(634,1,'/','GET','::1','[]','2025-11-20 09:29:52','2025-11-20 09:29:52'),(635,1,'auth/menu','GET','::1','[]','2025-11-20 09:30:28','2025-11-20 09:30:28'),(636,1,'auth/menu/2/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 09:30:35','2025-11-20 09:30:35'),(637,1,'auth/menu/3/edit','GET','::1','[]','2025-11-20 09:31:07','2025-11-20 09:31:07'),(638,1,'auth/menu/4/edit','GET','::1','[]','2025-11-20 09:31:59','2025-11-20 09:31:59'),(639,1,'auth/menu/5/edit','GET','::1','[]','2025-11-20 09:34:41','2025-11-20 09:34:41'),(640,1,'auth/menu/6/edit','GET','::1','[]','2025-11-20 09:38:05','2025-11-20 09:38:05'),(641,1,'auth/menu/7/edit','GET','::1','[]','2025-11-20 09:43:53','2025-11-20 09:43:53'),(642,1,'auth/menu/7/edit','GET','::1','[]','2025-11-20 09:53:14','2025-11-20 09:53:14'),(643,1,'auth/menu','GET','::1','[]','2025-11-20 09:53:21','2025-11-20 09:53:21'),(644,1,'auth/menu/3/edit','GET','::1','[]','2025-11-20 09:53:32','2025-11-20 09:53:32'),(645,1,'auth/menu/3','PUT','::1','{\"parent_id\":\"2\",\"title\":\"Projects\",\"icon\":\"fa-building-o\",\"uri\":\"projects\",\"roles\":[null],\"permission\":null,\"_token\":\"9ZmVnEOiInFz32f3w2UNbIQGIjwTTYTRoe510ovU\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-11-20 09:53:38','2025-11-20 09:53:38'),(646,1,'auth/menu','GET','::1','[]','2025-11-20 09:53:38','2025-11-20 09:53:38'),(647,1,'projects','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 09:53:42','2025-11-20 09:53:42'),(648,1,'auth/menu','GET','::1','[]','2025-11-20 09:56:33','2025-11-20 09:56:33'),(649,1,'membership-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 09:56:48','2025-11-20 09:56:48'),(650,1,'pesapal-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 09:56:58','2025-11-20 09:56:58'),(651,1,'membership-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 09:56:59','2025-11-20 09:56:59'),(652,1,'withdraw-requests','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 09:57:01','2025-11-20 09:57:01'),(653,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 09:57:02','2025-11-20 09:57:02'),(654,1,'withdraw-requests','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 09:57:04','2025-11-20 09:57:04'),(655,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 09:57:05','2025-11-20 09:57:05'),(656,1,'pesapal-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 10:00:13','2025-11-20 10:00:13'),(657,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 10:09:33','2025-11-20 10:09:33'),(658,1,'auth/menu','GET','::1','[]','2025-11-20 10:13:01','2025-11-20 10:13:01'),(659,1,'auth/menu/13/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 10:13:14','2025-11-20 10:13:14'),(660,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 10:13:18','2025-11-20 10:13:18'),(661,1,'auth/menu/23/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 10:13:22','2025-11-20 10:13:22'),(662,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-20 10:23:53','2025-11-20 10:23:53'),(663,1,'/','GET','::1','[]','2025-11-23 16:15:38','2025-11-23 16:15:38'),(664,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 17:36:11','2025-11-23 17:36:11'),(665,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 17:37:25','2025-11-23 17:37:25'),(666,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 17:37:28','2025-11-23 17:37:28'),(667,1,'orders/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 17:37:32','2025-11-23 17:37:32'),(668,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 17:39:48','2025-11-23 17:39:48'),(669,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 17:39:49','2025-11-23 17:39:49'),(670,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 17:39:50','2025-11-23 17:39:50'),(671,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 17:39:54','2025-11-23 17:39:54'),(672,1,'dtehm-memberships','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:18','2025-11-23 18:03:18'),(673,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:21','2025-11-23 18:03:21'),(674,1,'delivery-addresses','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:24','2025-11-23 18:03:24'),(675,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:28','2025-11-23 18:03:28'),(676,1,'insurance-programs','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:29','2025-11-23 18:03:29'),(677,1,'insurance-programs/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:30','2025-11-23 18:03:30'),(678,1,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:33','2025-11-23 18:03:33'),(679,1,'insurance-subscription-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:36','2025-11-23 18:03:36'),(680,1,'medical-service-requests','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:39','2025-11-23 18:03:39'),(681,1,'projects','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:43','2025-11-23 18:03:43'),(682,1,'projects/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:45','2025-11-23 18:03:45'),(683,1,'project-shares','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:46','2025-11-23 18:03:46'),(684,1,'project-shares/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:03:48','2025-11-23 18:03:48'),(685,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:14:14','2025-11-23 18:14:14'),(686,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:14:16','2025-11-23 18:14:16'),(687,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:17:11','2025-11-23 18:17:11'),(688,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:18:24','2025-11-23 18:18:24'),(689,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:20:14','2025-11-23 18:20:14'),(690,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:21:10','2025-11-23 18:21:10'),(691,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:23:15','2025-11-23 18:23:15'),(692,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:24:55','2025-11-23 18:24:55'),(693,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:25:15','2025-11-23 18:25:15'),(694,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:25:39','2025-11-23 18:25:39'),(695,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:28:07','2025-11-23 18:28:07'),(696,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:30:53','2025-11-23 18:30:53'),(697,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:33:30','2025-11-23 18:33:30'),(698,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:36:58','2025-11-23 18:36:58'),(699,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:43:50','2025-11-23 18:43:50'),(700,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:46:43','2025-11-23 18:46:43'),(701,1,'ordered-items/create','GET','::1','[]','2025-11-23 18:57:35','2025-11-23 18:57:35'),(702,1,'dtehm-memberships','GET','::1','[]','2025-11-23 18:57:47','2025-11-23 18:57:47'),(703,1,'dtehm-memberships/11','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:58:03','2025-11-23 18:58:03'),(704,1,'dtehm-memberships','GET','::1','[]','2025-11-23 18:58:04','2025-11-23 18:58:04'),(705,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 18:58:16','2025-11-23 18:58:16'),(706,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-23 18:58:27','2025-11-23 18:58:27'),(707,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-23 18:58:29','2025-11-23 18:58:29'),(708,1,'user-hierarchy/2','GET','::1','[]','2025-11-23 18:59:30','2025-11-23 18:59:30'),(709,1,'ordered-items/create','GET','::1','[]','2025-11-23 19:05:58','2025-11-23 19:05:58'),(710,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-23 19:06:24','2025-11-23 19:06:24'),(711,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-11-23 19:06:25','2025-11-23 19:06:25'),(712,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-11-23 19:06:26','2025-11-23 19:06:26'),(713,1,'ordered-items/create','GET','::1','[]','2025-11-23 19:10:27','2025-11-23 19:10:27'),(714,1,'ordered-items','POST','::1','{\"product\":\"18\",\"sponsor_id\":\"DTEHM20250001\",\"stockist_id\":\"DTEHM20250001\",\"qty\":\"1\",\"unit_price\":\"35000\",\"subtotal\":\"35000\",\"amount\":\"35000\",\"sponsor_user_id\":\"3\",\"stockist_user_id\":\"3\",\"_token\":\"qL44PcrTUKxZc74lYdckXoUBRvch7pfMQf3EAG6m\"}','2025-11-23 19:11:01','2025-11-23 19:11:01'),(715,1,'ordered-items','GET','::1','[]','2025-11-23 19:11:01','2025-11-23 19:11:01'),(716,1,'ordered-items','GET','::1','[]','2025-11-23 19:11:06','2025-11-23 19:11:06'),(717,1,'ordered-items/71/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 19:12:09','2025-11-23 19:12:09'),(718,1,'ordered-items/71','PUT','::1','{\"product\":\"18\",\"sponsor_id\":\"DTEHM20250001\",\"stockist_id\":\"DTEHM20250001\",\"qty\":\"1\",\"unit_price\":\"35000\",\"subtotal\":\"35000\",\"amount\":\"35000\",\"sponsor_user_id\":\"3\",\"stockist_user_id\":\"3\",\"_token\":\"qL44PcrTUKxZc74lYdckXoUBRvch7pfMQf3EAG6m\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-11-23 19:12:13','2025-11-23 19:12:13'),(719,1,'ordered-items','GET','::1','[]','2025-11-23 19:12:13','2025-11-23 19:12:13'),(720,1,'ordered-items','GET','::1','[]','2025-11-23 19:12:18','2025-11-23 19:12:18'),(721,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 19:13:11','2025-11-23 19:13:11'),(722,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 19:14:51','2025-11-23 19:14:51'),(723,1,'account-transactions','GET','::1','[]','2025-11-23 19:15:51','2025-11-23 19:15:51'),(724,1,'account-transactions','GET','::1','[]','2025-11-23 19:15:59','2025-11-23 19:15:59'),(725,1,'account-transactions','GET','::1','[]','2025-11-23 19:16:11','2025-11-23 19:16:11'),(726,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 19:17:12','2025-11-23 19:17:12'),(727,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 19:17:15','2025-11-23 19:17:15'),(728,1,'ordered-items','GET','::1','[]','2025-11-23 19:18:14','2025-11-23 19:18:14'),(729,1,'ordered-items/71','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 19:18:17','2025-11-23 19:18:17'),(730,1,'ordered-items','GET','::1','[]','2025-11-23 19:18:17','2025-11-23 19:18:17'),(731,1,'ordered-items/71','GET','::1','[]','2025-11-23 19:18:23','2025-11-23 19:18:23'),(732,1,'ordered-items/71','GET','::1','[]','2025-11-23 19:18:23','2025-11-23 19:18:23'),(733,1,'ordered-items/71','GET','::1','[]','2025-11-23 19:20:51','2025-11-23 19:20:51'),(734,1,'ordered-items/71','GET','::1','[]','2025-11-23 19:20:51','2025-11-23 19:20:51'),(735,1,'ordered-items/71','GET','::1','[]','2025-11-23 19:23:01','2025-11-23 19:23:01'),(736,1,'ordered-items/71','GET','::1','[]','2025-11-23 19:23:02','2025-11-23 19:23:02'),(737,1,'ordered-items/71','GET','::1','[]','2025-11-23 19:26:50','2025-11-23 19:26:50'),(738,1,'ordered-items/71','GET','::1','[]','2025-11-23 19:26:51','2025-11-23 19:26:51'),(739,1,'ordered-items/71','GET','::1','[]','2025-11-23 19:30:40','2025-11-23 19:30:40'),(740,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 19:30:51','2025-11-23 19:30:51'),(741,1,'ordered-items','GET','::1','[]','2025-11-23 19:31:00','2025-11-23 19:31:00'),(742,1,'ordered-items/71','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-23 19:31:02','2025-11-23 19:31:02'),(743,1,'ordered-items/71','GET','::1','[]','2025-11-23 19:31:03','2025-11-23 19:31:03'),(744,1,'ordered-items','GET','::1','[]','2025-11-23 20:10:30','2025-11-23 20:10:30'),(745,1,'auth/menu','GET','::1','[]','2025-11-24 12:52:46','2025-11-24 12:52:46'),(746,1,'auth/menu','GET','::1','[]','2025-11-24 12:54:37','2025-11-24 12:54:37'),(747,1,'auth/menu','GET','::1','[]','2025-11-24 13:55:00','2025-11-24 13:55:00'),(748,1,'dtehm-memberships','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 13:55:08','2025-11-24 13:55:08'),(749,1,'delivery-addresses','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 13:55:11','2025-11-24 13:55:11'),(750,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 13:55:12','2025-11-24 13:55:12'),(751,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 13:55:16','2025-11-24 13:55:16'),(752,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 13:55:25','2025-11-24 13:55:25'),(753,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-24 13:55:28','2025-11-24 13:55:28'),(754,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-24 13:55:29','2025-11-24 13:55:29'),(755,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 14:06:11','2025-11-24 14:06:11'),(756,1,'user-hierarchy','GET','::1','[]','2025-11-24 14:19:07','2025-11-24 14:19:07'),(757,1,'pesapal-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 14:20:35','2025-11-24 14:20:35'),(758,1,'pesapal-payments/1','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 14:20:40','2025-11-24 14:20:40'),(759,1,'pesapal-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 14:20:42','2025-11-24 14:20:42'),(760,1,'pesapal-payments/1/edit','GET','::1','[]','2025-11-24 14:21:27','2025-11-24 14:21:27'),(761,1,'pesapal-payments','GET','::1','[]','2025-11-24 14:24:41','2025-11-24 14:24:41'),(762,1,'pesapal-payments/1/edit','GET','::1','[]','2025-11-24 14:24:44','2025-11-24 14:24:44'),(763,1,'pesapal-payments/1/edit','GET','::1','[]','2025-11-24 14:25:21','2025-11-24 14:25:21'),(764,1,'pesapal-payments','GET','::1','[]','2025-11-24 14:25:24','2025-11-24 14:25:24'),(765,1,'pesapal-payments','GET','::1','[]','2025-11-24 14:25:26','2025-11-24 14:25:26'),(766,1,'pesapal-payments','GET','::1','[]','2025-11-24 14:28:02','2025-11-24 14:28:02'),(767,1,'pesapal-payments/1','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 14:28:06','2025-11-24 14:28:06'),(768,1,'pesapal-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 14:28:08','2025-11-24 14:28:08'),(769,1,'pesapal-payments/1','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 14:28:13','2025-11-24 14:28:13'),(770,1,'pesapal-payments/1/edit','GET','::1','[]','2025-11-24 14:28:16','2025-11-24 14:28:16'),(771,1,'pesapal-payments/1/edit','GET','::1','[]','2025-11-24 14:29:51','2025-11-24 14:29:51'),(772,1,'pesapal-payments/1','GET','::1','[]','2025-11-24 14:30:22','2025-11-24 14:30:22'),(773,1,'pesapal-payments/1','GET','::1','[]','2025-11-24 14:30:24','2025-11-24 14:30:24'),(774,1,'pesapal-payments/1','GET','::1','[]','2025-11-24 14:31:25','2025-11-24 14:31:25'),(775,1,'pesapal-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 14:31:28','2025-11-24 14:31:28'),(776,1,'pesapal-payments','GET','::1','[]','2025-11-24 14:34:43','2025-11-24 14:34:43'),(777,1,'pesapal-payments/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 14:34:46','2025-11-24 14:34:46'),(778,1,'pesapal-payments/1','PUT','::1','{\"payment_reference\":\"UNI-PAY-1764004824-BJ26C8\",\"status\":\"COMPLETED\",\"payment_type\":\"MEMBERSHIP\",\"payment_category\":\"membership\",\"description\":\"DTEHM + DIP Membership Fee - One-time payment\",\"user_id\":\"180\",\"customer_name\":\"Kule Swaleh\",\"customer_email\":\"0772111117@dtehm.app\",\"customer_phone\":\"0772111117\",\"customer_address\":null,\"amount\":\"96000.00\",\"currency\":\"UGX\",\"refund_amount\":\"0.00\",\"refunded_at\":null,\"refund_reason\":null,\"payment_gateway\":\"pesapal\",\"payment_method\":null,\"payment_account\":null,\"confirmation_code\":null,\"pesapal_order_tracking_id\":\"8502308a-4b03-4b23-97cf-db0d4cbfce61\",\"pesapal_merchant_reference\":\"PAYMENT_1_1764004824\",\"pesapal_notification_id\":null,\"pesapal_status_code\":\"0\",\"pesapal_redirect_url\":\"https:\\/\\/pay.pesapal.com\\/iframe\\/PesapalIframe3\\/Index?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61\",\"pesapal_callback_url\":\"http:\\/\\/10.0.2.2:8888\\/dtehm-insurance-api\\/api\\/universal-payments\\/callback\",\"pesapal_response\":null,\"ipn_count\":\"0\",\"last_ipn_at\":null,\"last_status_check\":null,\"items_count\":\"1\",\"payment_items\":[{\"item_type\":null,\"item_id\":null,\"description\":\"DTEHM + DIP Membership Fee\",\"amount\":\"96000.00\",\"_remove_\":\"0\"}],\"items_processed\":\"off\",\"items_processed_at\":null,\"processing_notes\":null,\"processed_by\":null,\"project_id\":null,\"number_of_shares\":null,\"payment_status_code\":null,\"status_message\":null,\"payment_date\":null,\"confirmed_at\":null,\"ip_address\":\"127.0.0.1\",\"user_agent\":\"Dart\\/3.8 (dart:io)\",\"error_message\":null,\"retry_count\":\"0\",\"last_retry_at\":null,\"metadata\":null,\"created_by\":\"180\",\"updated_by\":null,\"_token\":\"zzqpmG5YRFTkr7XD08zOFcJLg6AuPHfg7ZlYTWj2\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/pesapal-payments\"}','2025-11-24 14:35:06','2025-11-24 14:35:06'),(779,1,'pesapal-payments/1/edit','GET','::1','[]','2025-11-24 14:35:06','2025-11-24 14:35:06'),(780,1,'pesapal-payments/1/edit','GET','::1','[]','2025-11-24 14:39:18','2025-11-24 14:39:18'),(781,1,'pesapal-payments/1','PUT','::1','{\"payment_reference\":\"UNI-PAY-1764004824-BJ26C8\",\"status\":\"COMPLETED\",\"payment_type\":\"MEMBERSHIP\",\"payment_category\":\"membership\",\"description\":\"DTEHM + DIP Membership Fee - One-time payment\",\"user_id\":\"180\",\"customer_name\":\"Kule Swaleh\",\"customer_email\":\"0772111117@dtehm.app\",\"customer_phone\":\"0772111117\",\"customer_address\":null,\"amount\":\"96000.00\",\"currency\":\"UGX\",\"refund_amount\":\"0.00\",\"refunded_at\":null,\"refund_reason\":null,\"payment_gateway\":\"pesapal\",\"payment_method\":null,\"payment_account\":null,\"confirmation_code\":null,\"pesapal_order_tracking_id\":\"8502308a-4b03-4b23-97cf-db0d4cbfce61\",\"pesapal_merchant_reference\":\"PAYMENT_1_1764004824\",\"pesapal_notification_id\":null,\"pesapal_status_code\":\"0\",\"pesapal_redirect_url\":\"https:\\/\\/pay.pesapal.com\\/iframe\\/PesapalIframe3\\/Index?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61\",\"pesapal_callback_url\":\"http:\\/\\/10.0.2.2:8888\\/dtehm-insurance-api\\/api\\/universal-payments\\/callback\",\"pesapal_response\":\"{\\r\\n    \\\"payment_method\\\": \\\"\\\",\\r\\n    \\\"amount\\\": 96000,\\r\\n    \\\"created_date\\\": \\\"2025-11-24T20:20:28.083\\\",\\r\\n    \\\"confirmation_code\\\": \\\"\\\",\\r\\n    \\\"order_tracking_id\\\": \\\"8502308a-4b03-4b23-97cf-db0d4cbfce61\\\",\\r\\n    \\\"payment_status_description\\\": \\\"INVALID\\\",\\r\\n    \\\"description\\\": null,\\r\\n    \\\"message\\\": \\\"Request processed successfully\\\",\\r\\n    \\\"payment_account\\\": \\\"\\\",\\r\\n    \\\"call_back_url\\\": \\\"http:\\\\\\/\\\\\\/10.0.2.2:8888\\\\\\/dtehm-insurance-api\\\\\\/api\\\\\\/universal-payments\\\\\\/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\\\",\\r\\n    \\\"status_code\\\": 0,\\r\\n    \\\"merchant_reference\\\": \\\"PAYMENT_1_1764004824\\\",\\r\\n    \\\"account_number\\\": null,\\r\\n    \\\"payment_status_code\\\": \\\"\\\",\\r\\n    \\\"currency\\\": \\\"UGX\\\",\\r\\n    \\\"error\\\": {\\r\\n        \\\"error_type\\\": \\\"api_error\\\",\\r\\n        \\\"code\\\": \\\"payment_details_not_found\\\",\\r\\n        \\\"message\\\": \\\"Pending Payment\\\",\\r\\n        \\\"call_back_url\\\": \\\"http:\\\\\\/\\\\\\/10.0.2.2:8888\\\\\\/dtehm-insurance-api\\\\\\/api\\\\\\/universal-payments\\\\\\/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\\\"\\r\\n    },\\r\\n    \\\"status\\\": \\\"500\\\"\\r\\n}\",\"ipn_count\":\"0\",\"last_ipn_at\":null,\"last_status_check\":\"2025-11-24 17:39:09\",\"items_count\":\"1\",\"payment_items\":[{\"item_type\":null,\"item_id\":null,\"description\":\"DTEHM + DIP Membership Fee\",\"amount\":\"96000.00\",\"_remove_\":\"0\"}],\"items_processed\":\"off\",\"items_processed_at\":null,\"processing_notes\":null,\"processed_by\":null,\"project_id\":null,\"number_of_shares\":null,\"payment_status_code\":\"0\",\"status_message\":\"INVALID\",\"payment_date\":null,\"confirmed_at\":\"2025-11-24 17:35:06\",\"ip_address\":\"127.0.0.1\",\"user_agent\":\"Dart\\/3.8 (dart:io)\",\"error_message\":null,\"retry_count\":\"0\",\"last_retry_at\":null,\"metadata\":null,\"created_by\":\"180\",\"updated_by\":\"Admin User\",\"_token\":\"zzqpmG5YRFTkr7XD08zOFcJLg6AuPHfg7ZlYTWj2\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/pesapal-payments\"}','2025-11-24 14:39:25','2025-11-24 14:39:25'),(782,1,'pesapal-payments/1/edit','GET','::1','[]','2025-11-24 14:39:25','2025-11-24 14:39:25'),(783,1,'pesapal-payments/1/edit','GET','::1','[]','2025-11-24 14:39:29','2025-11-24 14:39:29'),(784,1,'pesapal-payments/1/edit','GET','::1','[]','2025-11-24 14:39:39','2025-11-24 14:39:39'),(785,1,'pesapal-payments/1','PUT','::1','{\"payment_reference\":\"UNI-PAY-1764004824-BJ26C8\",\"status\":\"PENDING\",\"payment_type\":\"MEMBERSHIP\",\"payment_category\":\"membership\",\"description\":\"DTEHM + DIP Membership Fee - One-time payment\",\"user_id\":\"180\",\"customer_name\":\"Kule Swaleh\",\"customer_email\":\"0772111117@dtehm.app\",\"customer_phone\":\"0772111117\",\"customer_address\":null,\"amount\":\"500.00\",\"currency\":\"UGX\",\"refund_amount\":\"0.00\",\"refunded_at\":null,\"refund_reason\":null,\"payment_gateway\":\"pesapal\",\"payment_method\":null,\"payment_account\":null,\"confirmation_code\":null,\"pesapal_order_tracking_id\":\"8502308a-4b03-4b23-97cf-db0d4cbfce61\",\"pesapal_merchant_reference\":\"PAYMENT_1_1764004824\",\"pesapal_notification_id\":null,\"pesapal_status_code\":\"0\",\"pesapal_redirect_url\":\"https:\\/\\/pay.pesapal.com\\/iframe\\/PesapalIframe3\\/Index?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61\",\"pesapal_callback_url\":\"http:\\/\\/10.0.2.2:8888\\/dtehm-insurance-api\\/api\\/universal-payments\\/callback\",\"pesapal_response\":\"{\\r\\n    \\\"payment_method\\\": \\\"\\\",\\r\\n    \\\"amount\\\": 96000,\\r\\n    \\\"created_date\\\": \\\"2025-11-24T20:20:28.083\\\",\\r\\n    \\\"confirmation_code\\\": \\\"\\\",\\r\\n    \\\"order_tracking_id\\\": \\\"8502308a-4b03-4b23-97cf-db0d4cbfce61\\\",\\r\\n    \\\"payment_status_description\\\": \\\"INVALID\\\",\\r\\n    \\\"description\\\": null,\\r\\n    \\\"message\\\": \\\"Request processed successfully\\\",\\r\\n    \\\"payment_account\\\": \\\"\\\",\\r\\n    \\\"call_back_url\\\": \\\"http:\\\\\\/\\\\\\/10.0.2.2:8888\\\\\\/dtehm-insurance-api\\\\\\/api\\\\\\/universal-payments\\\\\\/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\\\",\\r\\n    \\\"status_code\\\": 0,\\r\\n    \\\"merchant_reference\\\": \\\"PAYMENT_1_1764004824\\\",\\r\\n    \\\"account_number\\\": null,\\r\\n    \\\"payment_status_code\\\": \\\"\\\",\\r\\n    \\\"currency\\\": \\\"UGX\\\",\\r\\n    \\\"error\\\": {\\r\\n        \\\"error_type\\\": \\\"api_error\\\",\\r\\n        \\\"code\\\": \\\"payment_details_not_found\\\",\\r\\n        \\\"message\\\": \\\"Pending Payment\\\",\\r\\n        \\\"call_back_url\\\": \\\"http:\\\\\\/\\\\\\/10.0.2.2:8888\\\\\\/dtehm-insurance-api\\\\\\/api\\\\\\/universal-payments\\\\\\/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\\\"\\r\\n    },\\r\\n    \\\"status\\\": \\\"500\\\"\\r\\n}\",\"ipn_count\":\"0\",\"last_ipn_at\":null,\"last_status_check\":\"2025-11-24 17:39:35\",\"items_count\":\"1\",\"payment_items\":[{\"item_type\":null,\"item_id\":null,\"description\":\"DTEHM + DIP Membership Fee\",\"amount\":\"96000.00\",\"_remove_\":\"0\"}],\"items_processed\":\"off\",\"items_processed_at\":null,\"processing_notes\":null,\"processed_by\":null,\"project_id\":null,\"number_of_shares\":null,\"payment_status_code\":\"0\",\"status_message\":\"INVALID\",\"payment_date\":null,\"confirmed_at\":\"2025-11-24 17:35:06\",\"ip_address\":\"127.0.0.1\",\"user_agent\":\"Dart\\/3.8 (dart:io)\",\"error_message\":null,\"retry_count\":\"0\",\"last_retry_at\":null,\"metadata\":null,\"created_by\":\"180\",\"updated_by\":\"Admin User\",\"_token\":\"zzqpmG5YRFTkr7XD08zOFcJLg6AuPHfg7ZlYTWj2\",\"_method\":\"PUT\"}','2025-11-24 14:40:11','2025-11-24 14:40:11'),(786,1,'pesapal-payments','GET','::1','[]','2025-11-24 14:40:11','2025-11-24 14:40:11'),(787,1,'pesapal-payments','GET','::1','[]','2025-11-24 15:31:02','2025-11-24 15:31:02'),(788,1,'system-configurations','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 15:31:08','2025-11-24 15:31:08'),(789,1,'system-configurations/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 15:31:19','2025-11-24 15:31:19'),(790,1,'system-configurations/1','PUT','::1','{\"company_name\":\"DTEHM Health Insurance\",\"company_email\":\"info@dtehmhealth.com\",\"company_phone\":\"+256 700 000 000\",\"company_pobox\":null,\"company_address\":null,\"company_website\":null,\"company_details\":null,\"dtehm_membership_fee\":\"300.00\",\"dip_membership_fee\":\"200.00\",\"currency\":\"UGX\",\"referral_bonus_percentage\":\"5\",\"insurance_start_date\":\"2025-11-24 18:31:19\",\"insurance_price\":null,\"minimum_investment_amount\":\"10000.00\",\"share_price\":\"50000.00\",\"payment_gateway\":\"pesapal\",\"payment_callback_url\":null,\"app_version\":\"1.0.0\",\"force_update\":\"off\",\"maintenance_mode\":\"off\",\"maintenance_message\":\"System is under maintenance. Please try again later.\",\"contact_phone\":null,\"contact_email\":null,\"contact_address\":null,\"social_facebook\":null,\"social_twitter\":null,\"social_instagram\":null,\"social_linkedin\":null,\"terms_and_conditions\":null,\"privacy_policy\":null,\"about_us\":null,\"_token\":\"zzqpmG5YRFTkr7XD08zOFcJLg6AuPHfg7ZlYTWj2\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/system-configurations\"}','2025-11-24 15:31:39','2025-11-24 15:31:39'),(791,1,'system-configurations','GET','::1','[]','2025-11-24 15:31:40','2025-11-24 15:31:40'),(792,1,'system-configurations/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 15:31:46','2025-11-24 15:31:46'),(793,1,'system-configurations/1/edit','GET','::1','[]','2025-11-24 15:31:48','2025-11-24 15:31:48'),(794,1,'system-configurations/1/edit','GET','::1','[]','2025-11-24 15:51:52','2025-11-24 15:51:52'),(795,1,'projects','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 17:17:36','2025-11-24 17:17:36'),(796,1,'projects/4/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 17:17:38','2025-11-24 17:17:38'),(797,1,'projects/4','PUT','::1','{\"title\":\"Motorcycle Taxi Fleet\",\"description\":\"**Ride the Wave of Uganda\'s Transport Revolution**\\r\\n\\r\\nInvest in DTEHM\'s managed motorcycle taxi fleet and earn daily returns from Uganda\'s fastest-growing transport sector.\\r\\n\\r\\n**What You Get:**\\r\\n\\u2022 Well-maintained motorcycles in high-demand areas\\r\\n\\u2022 Vetted, trained riders with insurance coverage\\r\\n\\u2022 Daily income tracking & transparent reporting\\r\\n\\u2022 Fleet management & maintenance included\\r\\n\\u2022 Rider accountability systems\\r\\n\\r\\n**Ideal for:** Investors seeking regular cash flow from Uganda\'s thriving boda boda transport industry.\",\"status\":\"ongoing\",\"share_price\":\"100000\",\"total_shares\":\"100\",\"start_date\":\"2025-11-12\",\"end_date\":null,\"_token\":\"zzqpmG5YRFTkr7XD08zOFcJLg6AuPHfg7ZlYTWj2\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/projects\"}','2025-11-24 17:17:42','2025-11-24 17:17:42'),(798,1,'projects/4/edit','GET','::1','[]','2025-11-24 17:17:42','2025-11-24 17:17:42'),(799,1,'projects/4','PUT','::1','{\"title\":\"Motorcycle Taxi Fleet\",\"description\":\"**Ride the Wave of Uganda\'s Transport Revolution**\\r\\n\\r\\nInvest in DTEHM\'s managed motorcycle taxi fleet and earn daily returns from Uganda\'s fastest-growing transport sector.\\r\\n\\r\\n**What You Get:**\\r\\n\\u2022 Well-maintained motorcycles in high-demand areas\\r\\n\\u2022 Vetted, trained riders with insurance coverage\\r\\n\\u2022 Daily income tracking & transparent reporting\\r\\n\\u2022 Fleet management & maintenance included\\r\\n\\u2022 Rider accountability systems\\r\\n\\r\\n**Ideal for:** Investors seeking regular cash flow from Uganda\'s thriving boda boda transport industry.\",\"status\":\"ongoing\",\"share_price\":\"100000\",\"total_shares\":\"100\",\"start_date\":\"2025-11-12\",\"end_date\":\"2025-11-24\",\"_token\":\"zzqpmG5YRFTkr7XD08zOFcJLg6AuPHfg7ZlYTWj2\",\"_method\":\"PUT\"}','2025-11-24 17:17:48','2025-11-24 17:17:48'),(800,1,'projects','GET','::1','[]','2025-11-24 17:17:48','2025-11-24 17:17:48'),(801,1,'projects/4/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-24 17:22:38','2025-11-24 17:22:38'),(802,1,'projects/4','PUT','::1','{\"title\":\"Motorcycle Taxi Fleet\",\"description\":\"**Ride the Wave of Uganda\'s Transport Revolution**\\r\\n\\r\\nInvest in DTEHM\'s managed motorcycle taxi fleet and earn daily returns from Uganda\'s fastest-growing transport sector.\\r\\n\\r\\n**What You Get:**\\r\\n\\u2022 Well-maintained motorcycles in high-demand areas\\r\\n\\u2022 Vetted, trained riders with insurance coverage\\r\\n\\u2022 Daily income tracking & transparent reporting\\r\\n\\u2022 Fleet management & maintenance included\\r\\n\\u2022 Rider accountability systems\\r\\n\\r\\n**Ideal for:** Investors seeking regular cash flow from Uganda\'s thriving boda boda transport industry.\",\"status\":\"ongoing\",\"share_price\":\"100\",\"total_shares\":\"100\",\"start_date\":\"2025-11-12\",\"end_date\":\"2025-11-24\",\"_token\":\"zzqpmG5YRFTkr7XD08zOFcJLg6AuPHfg7ZlYTWj2\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/projects\"}','2025-11-24 17:22:45','2025-11-24 17:22:45'),(803,1,'projects','GET','::1','[]','2025-11-24 17:22:45','2025-11-24 17:22:45'),(804,1,'/','GET','::1','[]','2025-11-25 15:58:57','2025-11-25 15:58:57'),(805,1,'system-configurations','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-25 15:59:19','2025-11-25 15:59:19'),(806,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-25 15:59:38','2025-11-25 15:59:38'),(807,1,'account-transactions/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-25 15:59:41','2025-11-25 15:59:41'),(808,1,'account-transactions','POST','::1','{\"user_id\":\"180\",\"amount\":\"10000\",\"source\":\"deposit\",\"description\":\"Some testing treansaction\",\"transaction_date\":\"2025-11-25\",\"created_by_id\":\"1\",\"_token\":\"doQ4XY6Vvipt1K4cITXzdqAe2HsMGD4hzmRbptU7\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/account-transactions\"}','2025-11-25 16:00:05','2025-11-25 16:00:05'),(809,1,'account-transactions','GET','::1','[]','2025-11-25 16:00:06','2025-11-25 16:00:06'),(810,1,'account-transactions/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-25 16:01:57','2025-11-25 16:01:57'),(811,1,'account-transactions','POST','::1','{\"user_id\":\"180\",\"amount\":\"-2000\",\"source\":\"withdrawal\",\"description\":\"Just withdraw test\",\"transaction_date\":\"2025-11-25\",\"created_by_id\":\"1\",\"_token\":\"doQ4XY6Vvipt1K4cITXzdqAe2HsMGD4hzmRbptU7\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/account-transactions\"}','2025-11-25 16:02:39','2025-11-25 16:02:39'),(812,1,'account-transactions','GET','::1','[]','2025-11-25 16:02:40','2025-11-25 16:02:40'),(813,1,'account-transactions','GET','::1','[]','2025-11-25 17:10:38','2025-11-25 17:10:38'),(814,1,'account-transactions/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-25 17:10:41','2025-11-25 17:10:41'),(815,1,'account-transactions','POST','::1','{\"user_id\":\"180\",\"amount\":\"120000\",\"source\":\"deposit\",\"description\":\"some test 2\",\"transaction_date\":\"2025-11-25\",\"created_by_id\":\"1\",\"_token\":\"doQ4XY6Vvipt1K4cITXzdqAe2HsMGD4hzmRbptU7\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/account-transactions\"}','2025-11-25 17:11:00','2025-11-25 17:11:00'),(816,1,'account-transactions','GET','::1','[]','2025-11-25 17:11:00','2025-11-25 17:11:00'),(817,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-25 17:12:31','2025-11-25 17:12:31'),(818,1,'auth/menu','GET','::1','[]','2025-11-25 18:52:37','2025-11-25 18:52:37'),(819,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-25 18:52:45','2025-11-25 18:52:45'),(820,1,'/','GET','::1','[]','2025-11-26 16:24:26','2025-11-26 16:24:26'),(821,1,'insurance-programs','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:25:42','2025-11-26 16:25:42'),(822,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:25:45','2025-11-26 16:25:45'),(823,1,'product-categories','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:27:40','2025-11-26 16:27:40'),(824,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:28:00','2025-11-26 16:28:00'),(825,1,'dtehm-memberships','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:28:05','2025-11-26 16:28:05'),(826,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:28:23','2025-11-26 16:28:23'),(827,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:28:30','2025-11-26 16:28:30'),(828,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-26 16:28:35','2025-11-26 16:28:35'),(829,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-26 16:28:37','2025-11-26 16:28:37'),(830,1,'user-hierarchy/2','GET','::1','[]','2025-11-26 16:28:46','2025-11-26 16:28:46'),(831,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-26 16:29:46','2025-11-26 16:29:46'),(832,1,'user-hierarchy/180','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:29:50','2025-11-26 16:29:50'),(833,1,'user-hierarchy','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-11-26 16:29:54','2025-11-26 16:29:54'),(834,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:29:58','2025-11-26 16:29:58'),(835,1,'users/180/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:30:01','2025-11-26 16:30:01'),(836,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"Swaleh\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":null,\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DIP0046\",\"business_license_number\":null,\"roles\":[null],\"country\":null,\"address\":\"some address\",\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"status\":\"Active\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-11-26 16:30:08','2025-11-26 16:30:08'),(837,1,'users/180/edit','GET','::1','[]','2025-11-26 16:30:08','2025-11-26 16:30:08'),(838,1,'users/180/edit','GET','::1','[]','2025-11-26 16:30:35','2025-11-26 16:30:35'),(839,1,'user-hierarchy/47','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:30:42','2025-11-26 16:30:42'),(840,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"Swaleh\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250033\",\"business_license_number\":null,\"roles\":[null],\"country\":null,\"address\":\"some address\",\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"status\":\"Active\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:30:52','2025-11-26 16:30:52'),(841,1,'users/180/edit','GET','::1','[]','2025-11-26 16:30:52','2025-11-26 16:30:52'),(842,1,'users/180/edit','GET','::1','[]','2025-11-26 16:30:55','2025-11-26 16:30:55'),(843,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":null,\"address\":\"some address\",\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"status\":\"Active\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:31:02','2025-11-26 16:31:02'),(844,1,'users/180/edit','GET','::1','[]','2025-11-26 16:31:02','2025-11-26 16:31:02'),(845,1,'users/180/edit','GET','::1','[]','2025-11-26 16:31:05','2025-11-26 16:31:05'),(846,1,'users/180/edit','GET','::1','[]','2025-11-26 16:34:25','2025-11-26 16:34:25'),(847,1,'users/180','PUT','::1','{\"first_name\":\"Kule.\",\"last_name\":\"Swaleh\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":null,\"address\":\"some address\",\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"status\":\"Active\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:34:31','2025-11-26 16:34:31'),(848,1,'users/180/edit','GET','::1','[]','2025-11-26 16:34:31','2025-11-26 16:34:31'),(849,1,'users/180/edit','GET','::1','[]','2025-11-26 16:34:33','2025-11-26 16:34:33'),(850,1,'users/180/edit','GET','::1','[]','2025-11-26 16:34:43','2025-11-26 16:34:43'),(851,1,'users/180/edit','GET','::1','[]','2025-11-26 16:35:33','2025-11-26 16:35:33'),(852,1,'users/180/edit','GET','::1','[]','2025-11-26 16:35:55','2025-11-26 16:35:55'),(853,1,'users/180/edit','GET','::1','[]','2025-11-26 16:36:12','2025-11-26 16:36:12'),(854,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:36:16','2025-11-26 16:36:16'),(855,1,'users/180/edit','GET','::1','[]','2025-11-26 16:36:16','2025-11-26 16:36:16'),(856,1,'users/180/edit','GET','::1','[]','2025-11-26 16:36:18','2025-11-26 16:36:18'),(857,1,'users/180/edit','GET','::1','[]','2025-11-26 16:36:47','2025-11-26 16:36:47'),(858,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"Swaleh\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:36:52','2025-11-26 16:36:52'),(859,1,'users/180/edit','GET','::1','[]','2025-11-26 16:36:52','2025-11-26 16:36:52'),(860,1,'users/180/edit','GET','::1','[]','2025-11-26 16:36:53','2025-11-26 16:36:53'),(861,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:36:57','2025-11-26 16:36:57'),(862,1,'users/180/edit','GET','::1','[]','2025-11-26 16:36:58','2025-11-26 16:36:58'),(863,1,'users/180/edit','GET','::1','[]','2025-11-26 16:36:59','2025-11-26 16:36:59'),(864,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"Swaleh..\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:37:04','2025-11-26 16:37:04'),(865,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:04','2025-11-26 16:37:04'),(866,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:05','2025-11-26 16:37:05'),(867,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:06','2025-11-26 16:37:06'),(868,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:12','2025-11-26 16:37:12'),(869,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:37:15','2025-11-26 16:37:15'),(870,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:15','2025-11-26 16:37:15'),(871,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:17','2025-11-26 16:37:17'),(872,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:24','2025-11-26 16:37:24'),(873,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:37:27','2025-11-26 16:37:27'),(874,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:27','2025-11-26 16:37:27'),(875,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\".Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:37:32','2025-11-26 16:37:32'),(876,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:33','2025-11-26 16:37:33'),(877,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:34','2025-11-26 16:37:34'),(878,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:46','2025-11-26 16:37:46'),(879,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:37:50','2025-11-26 16:37:50'),(880,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:50','2025-11-26 16:37:50'),(881,1,'users/180/edit','GET','::1','[]','2025-11-26 16:37:52','2025-11-26 16:37:52'),(882,1,'users/180/edit','GET','::1','[]','2025-11-26 16:38:06','2025-11-26 16:38:06'),(883,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"..Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":null,\"address\":\"some address\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:38:10','2025-11-26 16:38:10'),(884,1,'users/180/edit','GET','::1','[]','2025-11-26 16:38:10','2025-11-26 16:38:10'),(885,1,'users/180/edit','GET','::1','[]','2025-11-26 16:38:13','2025-11-26 16:38:13'),(886,1,'users/180/edit','GET','::1','[]','2025-11-26 16:38:56','2025-11-26 16:38:56'),(887,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\"Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":\"some address\",\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"child_3\":null,\"child_4\":null,\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:39:02','2025-11-26 16:39:02'),(888,1,'users/180/edit','GET','::1','[]','2025-11-26 16:39:03','2025-11-26 16:39:03'),(889,1,'users/180/edit','GET','::1','[]','2025-11-26 16:39:04','2025-11-26 16:39:04'),(890,1,'users/180/edit','GET','::1','[]','2025-11-26 16:39:10','2025-11-26 16:39:10'),(891,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\".Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":\"some address\",\"father_name\":null,\"mother_name\":null,\"child_1\":null,\"child_2\":null,\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"_method\":\"PUT\"}','2025-11-26 16:39:13','2025-11-26 16:39:13'),(892,1,'users/180/edit','GET','::1','[]','2025-11-26 16:39:13','2025-11-26 16:39:13'),(893,1,'users/180/edit','GET','::1','[]','2025-11-26 16:39:14','2025-11-26 16:39:14'),(894,1,'users/180/edit','GET','::1','[]','2025-11-26 16:39:23','2025-11-26 16:39:23'),(895,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\".Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":\"some address\",\"father_name\":null,\"mother_name\":null,\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"_method\":\"PUT\"}','2025-11-26 16:39:26','2025-11-26 16:39:26'),(896,1,'users/180/edit','GET','::1','[]','2025-11-26 16:39:26','2025-11-26 16:39:26'),(897,1,'users/180/edit','GET','::1','[]','2025-11-26 16:39:28','2025-11-26 16:39:28'),(898,1,'users/180/edit','GET','::1','[]','2025-11-26 16:39:34','2025-11-26 16:39:34'),(899,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\".Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":\"some address\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"_method\":\"PUT\"}','2025-11-26 16:39:37','2025-11-26 16:39:37'),(900,1,'users','GET','::1','[]','2025-11-26 16:39:38','2025-11-26 16:39:38'),(901,1,'users/180/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:39:39','2025-11-26 16:39:39'),(902,1,'users/180/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:39:40','2025-11-26 16:39:40'),(903,1,'users/180/edit','GET','::1','[]','2025-11-26 16:39:42','2025-11-26 16:39:42'),(904,1,'users/180/edit','GET','::1','[]','2025-11-26 16:39:56','2025-11-26 16:39:56'),(905,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\".Swaleh...\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":\"some address\",\"father_name\":null,\"mother_name\":null,\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:40:01','2025-11-26 16:40:01'),(906,1,'users/180/edit','GET','::1','[]','2025-11-26 16:40:01','2025-11-26 16:40:01'),(907,1,'users/180/edit','GET','::1','[]','2025-11-26 16:40:03','2025-11-26 16:40:03'),(908,1,'users/180/edit','GET','::1','[]','2025-11-26 16:40:10','2025-11-26 16:40:10'),(909,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\".Swaleh...\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":\"some address\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:40:14','2025-11-26 16:40:14'),(910,1,'users/180/edit','GET','::1','[]','2025-11-26 16:40:14','2025-11-26 16:40:14'),(911,1,'users/180/edit','GET','::1','[]','2025-11-26 16:40:15','2025-11-26 16:40:15'),(912,1,'users/180/edit','GET','::1','[]','2025-11-26 16:40:59','2025-11-26 16:40:59'),(913,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\".Swaleh...\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":\"some address\",\"status\":\"Active\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:41:07','2025-11-26 16:41:07'),(914,1,'users/180/edit','GET','::1','[]','2025-11-26 16:41:07','2025-11-26 16:41:07'),(915,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\".Swaleh...\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"tribe\":\"Basoga\",\"address\":\"some address\",\"status\":\"Active\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:41:14','2025-11-26 16:41:14'),(916,1,'users/180/edit','GET','::1','[]','2025-11-26 16:41:14','2025-11-26 16:41:14'),(917,1,'users/180/edit','GET','::1','[]','2025-11-26 16:41:17','2025-11-26 16:41:17'),(918,1,'users/180/edit','GET','::1','[]','2025-11-26 16:42:03','2025-11-26 16:42:03'),(919,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\".Swaleh...\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"tribe\":\"Bakonzo\",\"address\":\"some address\",\"status\":\"Active\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:42:11','2025-11-26 16:42:11'),(920,1,'users/180/edit','GET','::1','[]','2025-11-26 16:42:12','2025-11-26 16:42:12'),(921,1,'users/180/edit','GET','::1','[]','2025-11-26 16:42:14','2025-11-26 16:42:14'),(922,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\".Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250003\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"tribe\":\"Bakonzo\",\"address\":\"some address\",\"status\":\"Active\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"_method\":\"PUT\"}','2025-11-26 16:42:16','2025-11-26 16:42:16'),(923,1,'users','GET','::1','[]','2025-11-26 16:42:16','2025-11-26 16:42:16'),(924,1,'users/180/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:42:18','2025-11-26 16:42:18'),(925,1,'users/180/edit','GET','::1','[]','2025-11-26 16:42:19','2025-11-26 16:42:19'),(926,1,'users/180','PUT','::1','{\"first_name\":\"Kule\",\"last_name\":\".Swaleh.\",\"sex\":\"Male\",\"phone_number\":\"0772111117\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_membership_paid_date\":\"2025-11-26 19:30:08\",\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":null,\"sponsor_id\":\"DTEHM20250033\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"tribe\":\"Bakonzo\",\"address\":\"some address\",\"status\":\"Active\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 16:42:32','2025-11-26 16:42:32'),(927,1,'users/180/edit','GET','::1','[]','2025-11-26 16:42:33','2025-11-26 16:42:33'),(928,1,'users/180/edit','GET','::1','[]','2025-11-26 16:42:36','2025-11-26 16:42:36'),(929,1,'user-hierarchy/2','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:52:04','2025-11-26 16:52:04'),(930,1,'user-hierarchy/74','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:52:13','2025-11-26 16:52:13'),(931,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:52:22','2025-11-26 16:52:22'),(932,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-11-26 16:52:33','2025-11-26 16:52:33'),(933,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-11-26 16:52:35','2025-11-26 16:52:35'),(934,1,'users/180/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:52:55','2025-11-26 16:52:55'),(935,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 16:52:57','2025-11-26 16:52:57'),(936,1,'users/6/edit','GET','::1','[]','2025-11-26 16:53:07','2025-11-26 16:53:07'),(937,1,'users/6','PUT','::1','{\"first_name\":\"Sydney\",\"last_name\":\"Kertzmann\",\"sex\":\"Male\",\"phone_number\":\"+256793432270\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"dtehm_member_membership_date\":\"2025-11-14 20:07:06\",\"dtehm_membership_is_paid\":\"No\",\"dtehm_membership_paid_date\":null,\"dtehm_membership_paid_amount\":null,\"user_type\":\"Customer\",\"dob\":\"1983-05-21\",\"sponsor_id\":\"DTEHM20259180\",\"business_license_number\":null,\"roles\":[null],\"country\":\"Uganda\",\"address\":\"Ullrichburgh, Uganda\",\"status\":\"Pending\",\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\\/180\\/edit\"}','2025-11-26 16:53:12','2025-11-26 16:53:12'),(938,1,'users/6/edit','GET','::1','[]','2025-11-26 16:53:12','2025-11-26 16:53:12'),(939,1,'users/6/edit','GET','::1','[]','2025-11-26 17:00:30','2025-11-26 17:00:30'),(940,1,'users/6','PUT','::1','{\"first_name\":\"Sydney\",\"last_name\":\"Kertzmann\",\"phone_number\":\"+256793432270\",\"sex\":\"Male\",\"email\":\"sydney.kertzmann4@test.com\",\"dob\":\"1983-05-21\",\"sponsor_id\":\"DTEHM20259180\",\"business_name\":\"DIP0005\",\"dtehm_member_id\":\"DTEHM20250003\",\"is_dip_member\":\"No\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_member_membership_date\":\"2025-11-26 19:53:12\",\"dtehm_membership_paid_date\":\"2025-11-26 19:53:12\",\"dtehm_membership_paid_amount\":\"76000\",\"business_license_number\":null,\"address\":\"Ullrichburgh, Uganda\",\"country\":\"Uganda\",\"tribe\":\"Bagwere\",\"user_type\":\"Customer\",\"status\":\"Pending\",\"roles\":[\"1\",null],\"_token\":\"bKOYeqY0iF2C5CY3OxwOTXlTaJOJKIppL02JiBWB\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-11-26 17:01:07','2025-11-26 17:01:07'),(941,1,'users/6/edit','GET','::1','[]','2025-11-26 17:01:07','2025-11-26 17:01:07'),(942,1,'users/6/edit','GET','::1','[]','2025-11-26 17:01:09','2025-11-26 17:01:09'),(943,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 17:01:13','2025-11-26 17:01:13'),(944,1,'withdraw-requests','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 17:39:55','2025-11-26 17:39:55'),(945,1,'withdraw-requests/1','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 17:39:57','2025-11-26 17:39:57'),(946,1,'withdraw-requests/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 17:39:58','2025-11-26 17:39:58'),(947,1,'withdraw-requests/1','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 17:40:00','2025-11-26 17:40:00'),(948,1,'withdraw-requests/1/approve','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-26 17:40:08','2025-11-26 17:40:08'),(949,1,'withdraw-requests/1','GET','::1','[]','2025-11-26 17:40:08','2025-11-26 17:40:08'),(950,1,'/','GET','::1','[]','2025-11-29 06:04:38','2025-11-29 06:04:38'),(951,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-11-29 06:04:41','2025-11-29 06:04:41'),(952,1,'/','GET','::1','[]','2025-12-01 18:21:05','2025-12-01 18:21:05'),(953,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-01 18:21:12','2025-12-01 18:21:12'),(954,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-12-01 18:21:16','2025-12-01 18:21:16'),(955,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-12-01 18:21:17','2025-12-01 18:21:17'),(956,1,'users','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-01 18:21:26','2025-12-01 18:21:26'),(957,1,'users/180/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-01 18:25:36','2025-12-01 18:25:36'),(958,1,'auth/menu','GET','::1','[]','2025-12-01 18:52:09','2025-12-01 18:52:09'),(959,1,'system-configurations','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-01 18:52:12','2025-12-01 18:52:12'),(960,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-01 18:52:13','2025-12-01 18:52:13'),(961,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-01 18:52:14','2025-12-01 18:52:14'),(962,1,'users','POST','::1','{\"first_name\":\"Muhindo\",\"last_name\":\"Mubaraka\",\"phone_number\":\"O783204665\",\"sex\":\"Male\",\"sponsor_id\":\"DTEHM001\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"_token\":\"Hq7w0sOWyebsiCcaCEPWn6ldrTrz5mCXJu59lwR3\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-12-01 18:52:50','2025-12-01 18:52:50'),(963,1,'users','GET','::1','[]','2025-12-01 18:52:50','2025-12-01 18:52:50'),(964,1,'users/181/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-01 18:52:55','2025-12-01 18:52:55'),(965,1,'users/181','PUT','::1','{\"first_name\":\"Muhindo\",\"last_name\":\"Mubaraka\",\"phone_number\":\"O783204665\",\"sex\":\"Male\",\"email\":null,\"dob\":null,\"sponsor_id\":\"DTEHM001\",\"business_name\":\"DIP0155\",\"dtehm_member_id\":\"DTEHM20259181\",\"is_dip_member\":\"No\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_member_membership_date\":\"2025-12-01 21:52:50\",\"dtehm_membership_paid_date\":\"2025-12-01 21:52:50\",\"dtehm_membership_paid_amount\":\"76000\",\"business_license_number\":\"8technologies Consults Limited\",\"address\":\"Ntinda, Kisaasi, Uganda\",\"country\":\"Uganda\",\"tribe\":null,\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[null],\"_token\":\"Hq7w0sOWyebsiCcaCEPWn6ldrTrz5mCXJu59lwR3\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-12-01 18:53:02','2025-12-01 18:53:02'),(966,1,'users/181/edit','GET','::1','[]','2025-12-01 18:53:03','2025-12-01 18:53:03'),(967,1,'users/181','PUT','::1','{\"first_name\":\"Muhindo\",\"last_name\":\"Mubaraka\",\"phone_number\":\"O783204665\",\"sex\":\"Male\",\"email\":null,\"dob\":null,\"sponsor_id\":\"DIP0001\",\"business_name\":\"DIP0155\",\"dtehm_member_id\":\"DTEHM20259181\",\"is_dip_member\":\"No\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_member_membership_date\":\"2025-12-01 21:52:50\",\"dtehm_membership_paid_date\":\"2025-12-01 21:52:50\",\"dtehm_membership_paid_amount\":\"76000\",\"business_license_number\":\"8technologies Consults Limited\",\"address\":\"Ntinda, Kisaasi, Uganda\",\"country\":\"Uganda\",\"tribe\":null,\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[null],\"_token\":\"Hq7w0sOWyebsiCcaCEPWn6ldrTrz5mCXJu59lwR3\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-12-01 18:53:30','2025-12-01 18:53:30'),(968,1,'users/181/edit','GET','::1','[]','2025-12-01 18:53:30','2025-12-01 18:53:30'),(969,1,'users/181/edit','GET','::1','[]','2025-12-01 18:53:32','2025-12-01 18:53:32'),(970,1,'users/181','PUT','::1','{\"first_name\":\"Muhindo\",\"last_name\":\"Mubaraka\",\"phone_number\":\"O783204665\",\"sex\":\"Male\",\"email\":null,\"dob\":null,\"sponsor_id\":\"DIP0001\",\"business_name\":\"DIP0155\",\"dtehm_member_id\":\"DTEHM20259181\",\"is_dip_member\":\"No\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_member_membership_date\":\"2025-12-01 21:52:50\",\"dtehm_membership_paid_date\":\"2025-12-01 21:52:50\",\"dtehm_membership_paid_amount\":\"76000\",\"business_license_number\":\"8technologies Consults Limited.\",\"address\":\"Ntinda, Kisaasi, Uganda\",\"country\":\"Uganda\",\"tribe\":null,\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[null],\"_token\":\"Hq7w0sOWyebsiCcaCEPWn6ldrTrz5mCXJu59lwR3\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-12-01 19:06:33','2025-12-01 19:06:33'),(971,1,'users/181/edit','GET','::1','[]','2025-12-01 19:06:33','2025-12-01 19:06:33'),(972,1,'users/181/edit','GET','::1','[]','2025-12-01 19:06:36','2025-12-01 19:06:36'),(973,1,'users/181','PUT','::1','{\"first_name\":\"Muhindo\",\"last_name\":\"Mubaraka\",\"phone_number\":\"O783204665\",\"sex\":\"Male\",\"email\":null,\"dob\":null,\"sponsor_id\":\"DIP0001\",\"business_name\":\"DIP0155\",\"dtehm_member_id\":\"DTEHM20259181\",\"is_dip_member\":\"No\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_member_membership_date\":\"2025-12-01 21:52:50\",\"dtehm_membership_paid_date\":\"2025-12-01 21:52:50\",\"dtehm_membership_paid_amount\":\"76000\",\"business_license_number\":\"8technologies Consults Limited.\",\"address\":\"Ntinda, Kisaasi, Uganda\",\"country\":\"Uganda\",\"tribe\":null,\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[\"2\",null],\"_token\":\"Hq7w0sOWyebsiCcaCEPWn6ldrTrz5mCXJu59lwR3\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-12-01 19:06:42','2025-12-01 19:06:42'),(974,1,'users/181/edit','GET','::1','[]','2025-12-01 19:06:42','2025-12-01 19:06:42'),(975,1,'users/181/edit','GET','::1','[]','2025-12-01 19:06:44','2025-12-01 19:06:44'),(976,1,'/','GET','::1','[]','2025-12-02 01:45:42','2025-12-02 01:45:42'),(977,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-02 01:45:46','2025-12-02 01:45:46'),(978,1,'users/181/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-02 01:46:07','2025-12-02 01:46:07'),(979,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-02 01:49:00','2025-12-02 01:49:00'),(980,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-02 01:49:04','2025-12-02 01:49:04'),(981,1,'orders/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-02 01:49:14','2025-12-02 01:49:14'),(982,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-02 01:49:26','2025-12-02 01:49:26'),(983,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-02 01:49:28','2025-12-02 01:49:28'),(984,1,'/','GET','::1','[]','2025-12-04 13:35:50','2025-12-04 13:35:50'),(985,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 13:36:08','2025-12-04 13:36:08'),(986,1,'products/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 13:36:10','2025-12-04 13:36:10'),(987,1,'products','GET','::1','[]','2025-12-04 13:36:11','2025-12-04 13:36:11'),(988,1,'products','GET','::1','[]','2025-12-04 13:42:03','2025-12-04 13:42:03'),(989,1,'products/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 13:42:05','2025-12-04 13:42:05'),(990,1,'products','GET','::1','[]','2025-12-04 13:42:05','2025-12-04 13:42:05'),(991,1,'products','GET','::1','[]','2025-12-04 13:49:29','2025-12-04 13:49:29'),(992,1,'products/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 13:49:31','2025-12-04 13:49:31'),(993,1,'products','GET','::1','[]','2025-12-04 13:49:31','2025-12-04 13:49:31'),(994,1,'products/create','GET','::1','[]','2025-12-04 13:49:35','2025-12-04 13:49:35'),(995,1,'products/create','GET','::1','[]','2025-12-04 13:52:12','2025-12-04 13:52:12'),(996,1,'products/create','GET','::1','[]','2025-12-04 14:30:48','2025-12-04 14:30:48'),(997,1,'products/create','GET','::1','[]','2025-12-04 14:32:57','2025-12-04 14:32:57'),(998,1,'/','GET','::1','[]','2025-12-04 17:15:01','2025-12-04 17:15:01'),(999,1,'/','GET','::1','[]','2025-12-04 17:36:50','2025-12-04 17:36:50'),(1000,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 17:37:07','2025-12-04 17:37:07'),(1001,1,'products/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 17:37:27','2025-12-04 17:37:27'),(1002,1,'products','POST','::1','{\"local_id\":\"1764880647-227184-982753\",\"currency\":\"UGX\",\"has_colors\":\"No\",\"has_sizes\":\"No\",\"home_section_1\":\"No\",\"home_section_2\":\"No\",\"home_section_3\":\"No\",\"status\":\"active\",\"in_stock\":\"Yes\",\"p_type\":\"product\",\"name\":\"Gavin Shepherd\",\"price_1\":\"108\",\"description\":\"<p>Mollitia inventore r.<\\/p>\",\"category\":\"3\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"after-save\":\"1\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/products\"}','2025-12-04 17:38:19','2025-12-04 17:38:19'),(1003,1,'products/create','GET','::1','[]','2025-12-04 17:38:20','2025-12-04 17:38:20'),(1004,1,'products','POST','::1','{\"local_id\":\"1764880700-185886-699370\",\"currency\":\"UGX\",\"has_colors\":\"No\",\"has_sizes\":\"No\",\"home_section_1\":\"No\",\"home_section_2\":\"No\",\"home_section_3\":\"No\",\"status\":\"active\",\"in_stock\":\"Yes\",\"p_type\":\"product\",\"name\":\"Gavin Shepherd\",\"price_1\":\"108\",\"description\":\"<p>Mollitia inventore r.<\\/p>\",\"category\":\"3\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"after-save\":\"1\"}','2025-12-04 17:39:17','2025-12-04 17:39:17'),(1005,1,'products/21/edit','GET','::1','[]','2025-12-04 17:39:17','2025-12-04 17:39:17'),(1006,1,'products/21','PUT','::1','{\"local_id\":\"1764880700-185886-699370\",\"currency\":\"UGX\",\"has_colors\":\"No\",\"has_sizes\":\"No\",\"home_section_1\":\"No\",\"home_section_2\":\"No\",\"home_section_3\":\"No\",\"status\":\"active\",\"in_stock\":\"Yes\",\"p_type\":\"product\",\"name\":\"Gavin Shepherd\",\"price_1\":\"100000\",\"description\":\"<p>Mollitia inventore r.<\\/p>\",\"category\":\"3\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/products\\/create\"}','2025-12-04 17:40:36','2025-12-04 17:40:36'),(1007,1,'products/create','GET','::1','[]','2025-12-04 17:40:37','2025-12-04 17:40:37'),(1008,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 17:40:40','2025-12-04 17:40:40'),(1009,1,'orders','GET','::1','[]','2025-12-04 17:40:51','2025-12-04 17:40:51'),(1010,1,'orders/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 17:40:59','2025-12-04 17:40:59'),(1011,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 17:41:19','2025-12-04 17:41:19'),(1012,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 17:41:21','2025-12-04 17:41:21'),(1013,1,'ordered-items/create','GET','::1','[]','2025-12-04 17:41:46','2025-12-04 17:41:46'),(1014,1,'ordered-items/create','GET','::1','[]','2025-12-04 17:51:09','2025-12-04 17:51:09'),(1015,1,'ordered-items/create','GET','::1','[]','2025-12-04 17:57:10','2025-12-04 17:57:10'),(1016,1,'ordered-items/create','GET','::1','[]','2025-12-04 17:59:09','2025-12-04 17:59:09'),(1017,1,'dtehm-memberships','GET','::1','[]','2025-12-04 17:59:46','2025-12-04 17:59:46'),(1018,1,'ordered-items/create','GET','::1','[]','2025-12-04 18:00:27','2025-12-04 18:00:27'),(1019,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 18:00:52','2025-12-04 18:00:52'),(1020,1,'ordered-items/create','GET','::1','[]','2025-12-04 18:27:56','2025-12-04 18:27:56'),(1021,1,'ordered-items/create','GET','::1','[]','2025-12-04 18:28:44','2025-12-04 18:28:44'),(1022,1,'ordered-items','GET','::1','[]','2025-12-04 18:31:45','2025-12-04 18:31:45'),(1023,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 18:31:48','2025-12-04 18:31:48'),(1024,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DTEHM20259181\",\"stockist_id\":\"DTEHM20259181\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"181\",\"stockist_user_id\":\"181\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\"}','2025-12-04 18:38:14','2025-12-04 18:38:14'),(1025,1,'ordered-items','GET','::1','[]','2025-12-04 18:38:14','2025-12-04 18:38:14'),(1026,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 18:39:53','2025-12-04 18:39:53'),(1027,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-12-04 18:40:00','2025-12-04 18:40:00'),(1028,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-12-04 18:40:02','2025-12-04 18:40:02'),(1029,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-12-04 18:40:05','2025-12-04 18:40:05'),(1030,1,'ordered-items/create','GET','::1','[]','2025-12-04 18:40:20','2025-12-04 18:40:20'),(1031,1,'ordered-items/create','GET','::1','[]','2025-12-04 18:42:03','2025-12-04 18:42:03'),(1032,1,'ordered-items','GET','::1','[]','2025-12-04 18:46:13','2025-12-04 18:46:13'),(1033,1,'ordered-items','GET','::1','[]','2025-12-04 18:50:33','2025-12-04 18:50:33'),(1034,1,'ordered-items','GET','::1','[]','2025-12-04 18:59:01','2025-12-04 18:59:01'),(1035,1,'ordered-items','GET','::1','[]','2025-12-04 19:00:34','2025-12-04 19:00:34'),(1036,1,'ordered-items','GET','::1','[]','2025-12-04 19:00:35','2025-12-04 19:00:35'),(1037,1,'ordered-items','GET','::1','[]','2025-12-04 19:01:18','2025-12-04 19:01:18'),(1038,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 19:02:47','2025-12-04 19:02:47'),(1039,1,'products','GET','::1','[]','2025-12-04 19:10:39','2025-12-04 19:10:39'),(1040,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 19:10:56','2025-12-04 19:10:56'),(1041,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 19:10:58','2025-12-04 19:10:58'),(1042,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 19:11:02','2025-12-04 19:11:02'),(1043,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 19:11:11','2025-12-04 19:11:11'),(1044,1,'ordered-items','GET','::1','[]','2025-12-04 19:13:51','2025-12-04 19:13:51'),(1045,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 19:13:56','2025-12-04 19:13:56'),(1046,1,'ordered-items','GET','::1','[]','2025-12-04 19:13:57','2025-12-04 19:13:57'),(1047,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 19:13:58','2025-12-04 19:13:58'),(1048,1,'ordered-items','GET','::1','[]','2025-12-04 19:16:04','2025-12-04 19:16:04'),(1049,1,'ordered-items','GET','::1','[]','2025-12-04 19:16:07','2025-12-04 19:16:07'),(1050,1,'ordered-items','GET','::1','[]','2025-12-04 19:16:57','2025-12-04 19:16:57'),(1051,1,'ordered-items','GET','::1','[]','2025-12-04 19:17:36','2025-12-04 19:17:36'),(1052,1,'ordered-items','GET','::1','[]','2025-12-04 19:17:56','2025-12-04 19:17:56'),(1053,1,'ordered-items','GET','::1','[]','2025-12-04 19:18:26','2025-12-04 19:18:26'),(1054,1,'ordered-items','GET','::1','[]','2025-12-04 19:20:53','2025-12-04 19:20:53'),(1055,1,'ordered-items','GET','::1','[]','2025-12-04 19:22:10','2025-12-04 19:22:10'),(1056,1,'ordered-items','GET','::1','[]','2025-12-04 19:22:25','2025-12-04 19:22:25'),(1057,1,'ordered-items','GET','::1','[]','2025-12-04 19:23:18','2025-12-04 19:23:18'),(1058,1,'ordered-items','GET','::1','[]','2025-12-04 19:48:32','2025-12-04 19:48:32'),(1059,1,'account-transactions','GET','::1','[]','2025-12-04 19:48:47','2025-12-04 19:48:47'),(1060,1,'account-transactions','GET','::1','[]','2025-12-04 19:49:42','2025-12-04 19:49:42'),(1061,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 19:49:52','2025-12-04 19:49:52'),(1062,1,'users','GET','::1','[]','2025-12-04 19:50:23','2025-12-04 19:50:23'),(1063,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 19:50:29','2025-12-04 19:50:29'),(1064,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-12-04 19:50:37','2025-12-04 19:50:37'),(1065,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-12-04 19:50:39','2025-12-04 19:50:39'),(1066,1,'user-hierarchy/2','GET','::1','[]','2025-12-04 19:50:47','2025-12-04 19:50:47'),(1067,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DIP0046\",\"stockist_id\":\"DTEHM20259181\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"181\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-04 19:51:51','2025-12-04 19:51:51'),(1068,1,'ordered-items','GET','::1','[]','2025-12-04 19:51:52','2025-12-04 19:51:52'),(1069,1,'account-transactions','GET','::1','[]','2025-12-04 19:52:41','2025-12-04 19:52:41'),(1070,1,'ordered-items','GET','::1','[]','2025-12-04 20:14:38','2025-12-04 20:14:38'),(1071,1,'account-transactions','GET','::1','[]','2025-12-04 20:14:43','2025-12-04 20:14:43'),(1072,1,'ordered-items/create','GET','::1','[]','2025-12-04 20:15:00','2025-12-04 20:15:00'),(1073,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DIP0001\",\"stockist_id\":\"DIP0046\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"2\",\"stockist_user_id\":\"47\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-04 20:15:30','2025-12-04 20:15:30'),(1074,1,'ordered-items','GET','::1','[]','2025-12-04 20:15:30','2025-12-04 20:15:30'),(1075,1,'account-transactions','GET','::1','[]','2025-12-04 20:15:33','2025-12-04 20:15:33'),(1076,1,'account-transactions','GET','::1','[]','2025-12-04 20:15:35','2025-12-04 20:15:35'),(1077,1,'ordered-items','GET','::1','[]','2025-12-04 20:23:49','2025-12-04 20:23:49'),(1078,1,'account-transactions','GET','::1','[]','2025-12-04 20:23:52','2025-12-04 20:23:52'),(1079,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 20:24:09','2025-12-04 20:24:09'),(1080,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DIP0046\",\"stockist_id\":\"DIP0046\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"47\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-04 20:24:20','2025-12-04 20:24:20'),(1081,1,'ordered-items','GET','::1','[]','2025-12-04 20:24:21','2025-12-04 20:24:21'),(1082,1,'account-transactions','GET','::1','[]','2025-12-04 20:24:26','2025-12-04 20:24:26'),(1083,1,'ordered-items','GET','::1','[]','2025-12-04 20:25:10','2025-12-04 20:25:10'),(1084,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 20:25:12','2025-12-04 20:25:12'),(1085,1,'ordered-items','POST','::1','{\"product\":\"2\",\"sponsor_id\":\"DIP0046\",\"stockist_id\":\"DIP0046\",\"qty\":\"1\",\"unit_price\":\"45000\",\"subtotal\":\"45000\",\"amount\":\"45000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"47\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-04 20:25:26','2025-12-04 20:25:26'),(1086,1,'ordered-items','GET','::1','[]','2025-12-04 20:25:26','2025-12-04 20:25:26'),(1087,1,'account-transactions','GET','::1','[]','2025-12-04 20:25:29','2025-12-04 20:25:29'),(1088,1,'account-transactions','GET','::1','[]','2025-12-04 20:29:12','2025-12-04 20:29:12'),(1089,1,'account-transactions','GET','::1','[]','2025-12-04 20:41:20','2025-12-04 20:41:20'),(1090,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 20:41:43','2025-12-04 20:41:43'),(1091,1,'ordered-items','POST','::1','{\"product\":\"15\",\"sponsor_id\":\"DIP0046\",\"stockist_id\":\"DIP0046\",\"qty\":\"1\",\"unit_price\":\"1800000\",\"subtotal\":\"1800000\",\"amount\":\"1800000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"47\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-04 20:41:51','2025-12-04 20:41:51'),(1092,1,'ordered-items','GET','::1','[]','2025-12-04 20:41:52','2025-12-04 20:41:52'),(1093,1,'account-transactions','GET','::1','[]','2025-12-04 20:41:56','2025-12-04 20:41:56'),(1094,1,'account-transactions','GET','::1','[]','2025-12-04 21:15:05','2025-12-04 21:15:05'),(1095,1,'ordered-items','GET','::1','[]','2025-12-04 21:15:52','2025-12-04 21:15:52'),(1096,1,'ordered-items','GET','::1','[]','2025-12-04 21:16:03','2025-12-04 21:16:03'),(1097,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 21:16:22','2025-12-04 21:16:22'),(1098,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DIP0046\",\"stockist_id\":\"DIP0046\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"47\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-04 21:16:31','2025-12-04 21:16:31'),(1099,1,'ordered-items','GET','::1','[]','2025-12-04 21:16:31','2025-12-04 21:16:31'),(1100,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 21:16:40','2025-12-04 21:16:40'),(1101,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 21:16:51','2025-12-04 21:16:51'),(1102,1,'account-transactions','GET','::1','[]','2025-12-04 21:29:46','2025-12-04 21:29:46'),(1103,1,'ordered-items','GET','::1','[]','2025-12-04 21:30:23','2025-12-04 21:30:23'),(1104,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 21:30:25','2025-12-04 21:30:25'),(1105,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DIP0046\",\"stockist_id\":\"DIP0046\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"47\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-04 21:30:35','2025-12-04 21:30:35'),(1106,1,'ordered-items','GET','::1','[]','2025-12-04 21:30:35','2025-12-04 21:30:35'),(1107,1,'account-transactions','GET','::1','[]','2025-12-04 21:30:39','2025-12-04 21:30:39'),(1108,1,'account-transactions','GET','::1','[]','2025-12-04 21:36:41','2025-12-04 21:36:41'),(1109,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 21:36:46','2025-12-04 21:36:46'),(1110,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DIP0046\",\"stockist_id\":\"DIP0046\",\"qty\":\"1\",\"unit_price\":null,\"subtotal\":null,\"amount\":null,\"sponsor_user_id\":null,\"stockist_user_id\":null,\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-04 21:36:53','2025-12-04 21:36:53'),(1111,1,'ordered-items','GET','::1','[]','2025-12-04 21:36:53','2025-12-04 21:36:53'),(1112,1,'account-transactions','GET','::1','[]','2025-12-04 21:36:56','2025-12-04 21:36:56'),(1113,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 21:38:36','2025-12-04 21:38:36'),(1114,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 21:38:37','2025-12-04 21:38:37'),(1115,1,'ordered-items','POST','::1','{\"product\":\"5\",\"sponsor_id\":\"DIP0046\",\"stockist_id\":\"DIP0046\",\"qty\":\"1\",\"unit_price\":\"180000\",\"subtotal\":\"180000\",\"amount\":\"180000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"47\",\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\"}','2025-12-04 21:38:49','2025-12-04 21:38:49'),(1116,1,'ordered-items','GET','::1','[]','2025-12-04 21:38:49','2025-12-04 21:38:49'),(1117,1,'account-transactions','GET','::1','[]','2025-12-04 21:38:51','2025-12-04 21:38:51'),(1118,1,'account-transactions','GET','::1','[]','2025-12-04 21:48:39','2025-12-04 21:48:39'),(1119,1,'ordered-items','GET','::1','[]','2025-12-04 21:52:17','2025-12-04 21:52:17'),(1120,1,'account-transactions','GET','::1','[]','2025-12-04 21:52:22','2025-12-04 21:52:22'),(1121,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-04 21:52:30','2025-12-04 21:52:30'),(1122,1,'ordered-items','POST','::1','{\"product\":\"18\",\"sponsor_id\":\"DIP0046\",\"stockist_id\":\"DIP0046\",\"qty\":\"1\",\"unit_price\":null,\"subtotal\":null,\"amount\":null,\"sponsor_user_id\":null,\"stockist_user_id\":null,\"_token\":\"sm5cKMRmfXnaq8oG73uol7EIb6D70eS6thPa979e\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-04 21:52:35','2025-12-04 21:52:35'),(1123,1,'ordered-items','GET','::1','[]','2025-12-04 21:52:36','2025-12-04 21:52:36'),(1124,1,'account-transactions','GET','::1','[]','2025-12-04 21:52:41','2025-12-04 21:52:41'),(1125,1,'/','GET','::1','[]','2025-12-05 06:12:38','2025-12-05 06:12:38'),(1126,1,'/','GET','::1','[]','2025-12-05 06:13:14','2025-12-05 06:13:14'),(1127,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 06:13:21','2025-12-05 06:13:21'),(1128,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"business_name\",\"type\":\"desc\"}}','2025-12-05 06:13:26','2025-12-05 06:13:26'),(1129,1,'products','GET','::1','[]','2025-12-05 07:01:12','2025-12-05 07:01:12'),(1130,1,'orders','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 07:01:16','2025-12-05 07:01:16'),(1131,1,'products','GET','::1','[]','2025-12-05 07:01:17','2025-12-05 07:01:17'),(1132,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 07:01:20','2025-12-05 07:01:20'),(1133,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 07:02:31','2025-12-05 07:02:31'),(1134,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DTEHM20250033\",\"stockist_id\":\"DTEHM20250033\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"47\",\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 07:02:40','2025-12-05 07:02:40'),(1135,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DTEHM20250033\",\"stockist_id\":\"DTEHM20250033\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"47\",\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 07:02:50','2025-12-05 07:02:50'),(1136,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DTEHM20250033\",\"stockist_id\":\"DTEHM20250033\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"47\",\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 07:03:32','2025-12-05 07:03:32'),(1137,1,'orders','GET','::1','[]','2025-12-05 07:04:09','2025-12-05 07:04:09'),(1138,1,'ordered-items','GET','::1','[]','2025-12-05 07:04:18','2025-12-05 07:04:18'),(1139,1,'ordered-items','GET','::1','[]','2025-12-05 07:08:19','2025-12-05 07:08:19'),(1140,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DTEHM20250033\",\"stockist_id\":\"DTEHM20250033\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"47\",\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 07:08:27','2025-12-05 07:08:27'),(1141,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DTEHM20250033\",\"stockist_id\":\"DTEHM20250033\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"47\",\"stockist_user_id\":\"47\",\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 07:08:54','2025-12-05 07:08:54'),(1142,1,'ordered-items','GET','::1','[]','2025-12-05 07:24:48','2025-12-05 07:24:48'),(1143,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 07:24:49','2025-12-05 07:24:49'),(1144,1,'ordered-items','POST','::1','{\"product\":\"18\",\"sponsor_id\":\"DTEHM20250003\",\"stockist_id\":\"DTEHM20250003\",\"qty\":\"1\",\"unit_price\":\"35000\",\"subtotal\":\"35000\",\"amount\":\"35000\",\"sponsor_user_id\":\"6\",\"stockist_user_id\":\"6\",\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 07:25:01','2025-12-05 07:25:01'),(1145,1,'ordered-items','POST','::1','{\"product\":\"18\",\"sponsor_id\":\"DTEHM20250003\",\"stockist_id\":\"DTEHM20250003\",\"qty\":\"1\",\"unit_price\":\"35000\",\"subtotal\":\"35000\",\"amount\":\"35000\",\"sponsor_user_id\":\"6\",\"stockist_user_id\":\"6\",\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 07:28:07','2025-12-05 07:28:07'),(1146,1,'ordered-items','GET','::1','[]','2025-12-05 07:28:10','2025-12-05 07:28:10'),(1147,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 07:28:11','2025-12-05 07:28:11'),(1148,1,'ordered-items','POST','::1','{\"product\":\"18\",\"sponsor_id\":\"DTEHM20250003\",\"stockist_id\":\"DTEHM20250003\",\"qty\":\"1\",\"unit_price\":\"35000\",\"subtotal\":\"35000\",\"amount\":\"35000\",\"sponsor_user_id\":\"6\",\"stockist_user_id\":\"6\",\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 07:28:18','2025-12-05 07:28:18'),(1149,1,'ordered-items','GET','::1','[]','2025-12-05 07:28:31','2025-12-05 07:28:31'),(1150,1,'ordered-items','POST','::1','{\"product\":\"18\",\"sponsor_id\":\"DTEHM20250003\",\"stockist_id\":\"DTEHM20250003\",\"qty\":\"1\",\"unit_price\":\"35000\",\"subtotal\":\"35000\",\"amount\":\"35000\",\"sponsor_user_id\":\"6\",\"stockist_user_id\":\"6\",\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 07:32:44','2025-12-05 07:32:44'),(1151,1,'ordered-items','GET','::1','[]','2025-12-05 07:32:45','2025-12-05 07:32:45'),(1152,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 07:32:57','2025-12-05 07:32:57'),(1153,1,'ordered-items','GET','::1','[]','2025-12-05 07:35:38','2025-12-05 07:35:38'),(1154,1,'ordered-items/9','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 07:35:43','2025-12-05 07:35:43'),(1155,1,'ordered-items/9','GET','::1','[]','2025-12-05 07:35:43','2025-12-05 07:35:43'),(1156,1,'system-configurations','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 07:54:44','2025-12-05 07:54:44'),(1157,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 07:54:45','2025-12-05 07:54:45'),(1158,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 07:54:53','2025-12-05 07:54:53'),(1159,1,'users','POST','::1','{\"first_name\":\"Katelyn\",\"last_name\":\"Miller\",\"phone_number\":\"+1 (721) 228-2159\",\"sex\":\"Female\",\"sponsor_id\":\"Nesciunt veniam oc\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-12-05 07:55:09','2025-12-05 07:55:09'),(1160,1,'users','GET','::1','[]','2025-12-05 07:55:09','2025-12-05 07:55:09'),(1161,1,'users/183/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 07:55:23','2025-12-05 07:55:23'),(1162,1,'users/183','PUT','::1','{\"first_name\":\"Katelyn\",\"last_name\":\"Miller\",\"phone_number\":\"+1 (721) 228-2159\",\"sex\":\"Female\",\"email\":null,\"dob\":null,\"sponsor_id\":\"DIP0156\",\"business_name\":\"DIP157\",\"dtehm_member_id\":\"DTEHM20259182\",\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_member_membership_date\":\"2025-12-05 10:55:09\",\"dtehm_membership_paid_date\":\"2025-12-05 10:55:09\",\"dtehm_membership_paid_amount\":\"76000\",\"business_license_number\":null,\"address\":null,\"country\":\"Uganda\",\"tribe\":null,\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[null],\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-12-05 07:55:30','2025-12-05 07:55:30'),(1163,1,'users/183/edit','GET','::1','[]','2025-12-05 07:55:31','2025-12-05 07:55:31'),(1164,1,'users/183/edit','GET','::1','[]','2025-12-05 07:55:33','2025-12-05 07:55:33'),(1165,1,'users/183','PUT','::1','{\"first_name\":\"Katelyn\",\"last_name\":\"Miller\",\"phone_number\":\"+1 (721) 228-2159\",\"sex\":\"Female\",\"email\":null,\"dob\":\"2025-12-04\",\"sponsor_id\":\"Nesciunt veniam oc\",\"business_name\":\"DIP157\",\"dtehm_member_id\":\"DTEHM20259182\",\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_member_membership_date\":\"2025-12-05 10:55:09\",\"dtehm_membership_paid_date\":\"2025-12-05 10:55:09\",\"dtehm_membership_paid_amount\":\"76000\",\"business_license_number\":null,\"address\":null,\"country\":\"Uganda\",\"tribe\":null,\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[null],\"_token\":\"YituVQ9Zw2Ix2qf1Bwh3dukjfmBRtMzIutx31W56\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-12-05 07:57:01','2025-12-05 07:57:01'),(1166,1,'users/183/edit','GET','::1','[]','2025-12-05 07:57:01','2025-12-05 07:57:01'),(1167,1,'ordered-items/create','GET','::1','[]','2025-12-05 10:40:42','2025-12-05 10:40:42'),(1168,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:40:44','2025-12-05 10:40:44'),(1169,1,'ordered-items/10','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:40:53','2025-12-05 10:40:53'),(1170,1,'ordered-items/10','GET','::1','[]','2025-12-05 10:40:53','2025-12-05 10:40:53'),(1171,1,'ordered-items/create','GET','::1','[]','2025-12-05 10:41:01','2025-12-05 10:41:01'),(1172,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:41:06','2025-12-05 10:41:06'),(1173,1,'dtehm-memberships','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:41:23','2025-12-05 10:41:23'),(1174,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:41:27','2025-12-05 10:41:27'),(1175,1,'user-hierarchy/183','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:41:35','2025-12-05 10:41:35'),(1176,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:41:36','2025-12-05 10:41:36'),(1177,1,'auth/menu','GET','::1','[]','2025-12-05 10:41:45','2025-12-05 10:41:45'),(1178,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:41:53','2025-12-05 10:41:53'),(1179,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:41:57','2025-12-05 10:41:57'),(1180,1,'auth/menu','POST','::1','{\"parent_id\":\"0\",\"title\":\"Members\",\"icon\":\"fa-users\",\"uri\":\"users\",\"roles\":[\"2\",null],\"permission\":null,\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\"}','2025-12-05 10:42:14','2025-12-05 10:42:14'),(1181,1,'auth/menu','GET','::1','[]','2025-12-05 10:42:14','2025-12-05 10:42:14'),(1182,1,'auth/menu','POST','::1','{\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":39},{\\\"id\\\":34},{\\\"id\\\":27},{\\\"id\\\":30},{\\\"id\\\":29},{\\\"id\\\":31},{\\\"id\\\":32},{\\\"id\\\":33}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":37,\\\"children\\\":[{\\\"id\\\":38},{\\\"id\\\":18}]}]\"}','2025-12-05 10:42:21','2025-12-05 10:42:21'),(1183,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:42:21','2025-12-05 10:42:21'),(1184,1,'auth/menu','POST','::1','{\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":39},{\\\"id\\\":27},{\\\"id\\\":30},{\\\"id\\\":29},{\\\"id\\\":31},{\\\"id\\\":32},{\\\"id\\\":33}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":34},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":37,\\\"children\\\":[{\\\"id\\\":38},{\\\"id\\\":18}]}]\"}','2025-12-05 10:42:39','2025-12-05 10:42:39'),(1185,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:42:39','2025-12-05 10:42:39'),(1186,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:42:44','2025-12-05 10:42:44'),(1187,1,'users/183/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:42:51','2025-12-05 10:42:51'),(1188,1,'users/183','PUT','::1','{\"first_name\":\"Katelyn\",\"last_name\":\"Miller\",\"phone_number\":\"+1 (721) 228-2159\",\"sex\":\"Female\",\"email\":null,\"dob\":\"2025-12-16\",\"sponsor_id\":\"Nesciunt veniam oc\",\"business_name\":\"DIP157\",\"dtehm_member_id\":\"DTEHM20259182\",\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"dtehm_member_membership_date\":\"2025-12-05 10:55:09\",\"dtehm_membership_paid_date\":\"2025-12-05 10:55:09\",\"dtehm_membership_paid_amount\":\"76000\",\"business_license_number\":null,\"address\":null,\"country\":\"Uganda\",\"tribe\":null,\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[null],\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-12-05 10:42:56','2025-12-05 10:42:56'),(1189,1,'users/183/edit','GET','::1','[]','2025-12-05 10:42:56','2025-12-05 10:42:56'),(1190,1,'users/183/edit','GET','::1','[]','2025-12-05 10:42:58','2025-12-05 10:42:58'),(1191,1,'users/183/edit','GET','::1','[]','2025-12-05 10:43:55','2025-12-05 10:43:55'),(1192,1,'users/183/edit','GET','::1','[]','2025-12-05 10:45:13','2025-12-05 10:45:13'),(1193,1,'users/183/edit','GET','::1','[]','2025-12-05 10:45:40','2025-12-05 10:45:40'),(1194,1,'users/183/edit','GET','::1','[]','2025-12-05 10:45:59','2025-12-05 10:45:59'),(1195,1,'users/183/edit','GET','::1','[]','2025-12-05 10:46:48','2025-12-05 10:46:48'),(1196,1,'users/183/edit','GET','::1','[]','2025-12-05 10:47:16','2025-12-05 10:47:16'),(1197,1,'users/183/edit','GET','::1','[]','2025-12-05 10:47:30','2025-12-05 10:47:30'),(1198,1,'users/183/edit','GET','::1','[]','2025-12-05 10:47:36','2025-12-05 10:47:36'),(1199,1,'users/183/edit','GET','::1','[]','2025-12-05 10:48:34','2025-12-05 10:48:34'),(1200,1,'users/183/edit','GET','::1','[]','2025-12-05 10:48:42','2025-12-05 10:48:42'),(1201,1,'users/183/edit','GET','::1','[]','2025-12-05 10:48:55','2025-12-05 10:48:55'),(1202,1,'users/183/edit','GET','::1','[]','2025-12-05 10:49:01','2025-12-05 10:49:01'),(1203,1,'users/183/edit','GET','::1','[]','2025-12-05 10:49:41','2025-12-05 10:49:41'),(1204,1,'users/183/edit','GET','::1','[]','2025-12-05 10:50:24','2025-12-05 10:50:24'),(1205,1,'users/183/edit','GET','::1','[]','2025-12-05 10:50:36','2025-12-05 10:50:36'),(1206,1,'users/183/edit','GET','::1','[]','2025-12-05 10:51:45','2025-12-05 10:51:45'),(1207,1,'users/183/edit','GET','::1','[]','2025-12-05 10:52:26','2025-12-05 10:52:26'),(1208,1,'users/183/edit','GET','::1','[]','2025-12-05 10:52:48','2025-12-05 10:52:48'),(1209,1,'users/183/edit','GET','::1','[]','2025-12-05 10:53:14','2025-12-05 10:53:14'),(1210,1,'users/183/edit','GET','::1','[]','2025-12-05 10:53:26','2025-12-05 10:53:26'),(1211,1,'users/183/edit','GET','::1','[]','2025-12-05 10:53:39','2025-12-05 10:53:39'),(1212,1,'users/183/edit','GET','::1','[]','2025-12-05 10:54:07','2025-12-05 10:54:07'),(1213,1,'users/183','PUT','::1','{\"first_name\":\"Katelyn\",\"last_name\":\"Miller\",\"phone_number\":\"+1 (721) 228-2159\",\"sex\":\"Female\",\"sponsor_id\":\"DTEHM20250001\",\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"user_type\":\"Customer\",\"status\":\"Pending\",\"roles\":[\"2\",null],\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-12-05 10:54:59','2025-12-05 10:54:59'),(1214,1,'users/183/edit','GET','::1','[]','2025-12-05 10:55:00','2025-12-05 10:55:00'),(1215,1,'users/183/edit','GET','::1','[]','2025-12-05 10:55:02','2025-12-05 10:55:02'),(1216,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:55:11','2025-12-05 10:55:11'),(1217,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 10:55:13','2025-12-05 10:55:13'),(1218,1,'users/create','GET','::1','[]','2025-12-05 10:55:40','2025-12-05 10:55:40'),(1219,1,'users/create','GET','::1','[]','2025-12-05 10:55:46','2025-12-05 10:55:46'),(1220,1,'users/create','GET','::1','[]','2025-12-05 10:56:29','2025-12-05 10:56:29'),(1221,1,'users/create','GET','::1','[]','2025-12-05 10:57:24','2025-12-05 10:57:24'),(1222,1,'users/create','GET','::1','[]','2025-12-05 10:57:41','2025-12-05 10:57:41'),(1223,1,'users/create','GET','::1','[]','2025-12-05 10:57:52','2025-12-05 10:57:52'),(1224,1,'users/create','GET','::1','[]','2025-12-05 10:58:01','2025-12-05 10:58:01'),(1225,1,'users/create','GET','::1','[]','2025-12-05 10:58:38','2025-12-05 10:58:38'),(1226,1,'users/create','GET','::1','[]','2025-12-05 10:59:00','2025-12-05 10:59:00'),(1227,1,'users/create','GET','::1','[]','2025-12-05 10:59:16','2025-12-05 10:59:16'),(1228,1,'users/create','GET','::1','[]','2025-12-05 10:59:33','2025-12-05 10:59:33'),(1229,1,'users/create','GET','::1','[]','2025-12-05 11:00:44','2025-12-05 11:00:44'),(1230,1,'users/create','GET','::1','[]','2025-12-05 11:02:53','2025-12-05 11:02:53'),(1231,1,'users/create','GET','::1','[]','2025-12-05 11:03:18','2025-12-05 11:03:18'),(1232,1,'users/create','GET','::1','[]','2025-12-05 11:04:06','2025-12-05 11:04:06'),(1233,1,'users/create','GET','::1','[]','2025-12-05 11:04:23','2025-12-05 11:04:23'),(1234,1,'users/create','GET','::1','[]','2025-12-05 11:06:27','2025-12-05 11:06:27'),(1235,1,'users/create','GET','::1','[]','2025-12-05 11:07:02','2025-12-05 11:07:02'),(1236,1,'users/create','GET','::1','[]','2025-12-05 11:07:54','2025-12-05 11:07:54'),(1237,1,'users','POST','::1','{\"first_name\":\"Finn\",\"last_name\":\"Shaffer\",\"sex\":\"Female\",\"phone_number\":\"+1 (338) 764-4376\",\"is_dtehm_member\":\"No\",\"is_dip_member\":\"No\",\"sponsor_id\":\"DTEHM20259002\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\"}','2025-12-05 11:08:00','2025-12-05 11:08:00'),(1238,1,'users/184/edit','GET','::1','[]','2025-12-05 11:08:00','2025-12-05 11:08:00'),(1239,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:08:40','2025-12-05 11:08:40'),(1240,1,'users/create','GET','::1','[]','2025-12-05 11:08:42','2025-12-05 11:08:42'),(1241,1,'users','POST','::1','{\"first_name\":\"Garth\",\"last_name\":\"Buckner\",\"sex\":\"Male\",\"phone_number\":\"+1 (884) 391-5813\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"Yes\",\"sponsor_id\":\"DTEHM20250043\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\"}','2025-12-05 11:09:56','2025-12-05 11:09:56'),(1242,1,'users','GET','::1','[]','2025-12-05 11:09:56','2025-12-05 11:09:56'),(1243,1,'users','GET','::1','[]','2025-12-05 11:10:33','2025-12-05 11:10:33'),(1244,1,'users','GET','::1','[]','2025-12-05 11:12:24','2025-12-05 11:12:24'),(1245,1,'users','GET','::1','[]','2025-12-05 11:21:28','2025-12-05 11:21:28'),(1246,1,'users','GET','::1','[]','2025-12-05 11:21:43','2025-12-05 11:21:43'),(1247,1,'users','GET','::1','[]','2025-12-05 11:23:07','2025-12-05 11:23:07'),(1248,1,'users','GET','::1','[]','2025-12-05 11:23:42','2025-12-05 11:23:42'),(1249,1,'users','GET','::1','[]','2025-12-05 11:23:57','2025-12-05 11:23:57'),(1250,1,'users','GET','::1','[]','2025-12-05 11:24:41','2025-12-05 11:24:41'),(1251,1,'users','GET','::1','[]','2025-12-05 11:25:14','2025-12-05 11:25:14'),(1252,1,'users','GET','::1','[]','2025-12-05 11:25:32','2025-12-05 11:25:32'),(1253,1,'users','GET','::1','[]','2025-12-05 11:26:05','2025-12-05 11:26:05'),(1254,1,'users','GET','::1','[]','2025-12-05 11:26:14','2025-12-05 11:26:14'),(1255,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 11:26:16','2025-12-05 11:26:16'),(1256,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 11:26:17','2025-12-05 11:26:17'),(1257,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"asc\"}}','2025-12-05 11:26:25','2025-12-05 11:26:25'),(1258,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 11:26:29','2025-12-05 11:26:29'),(1259,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:26:30','2025-12-05 11:26:30'),(1260,1,'users','GET','::1','[]','2025-12-05 11:26:32','2025-12-05 11:26:32'),(1261,1,'users','GET','::1','[]','2025-12-05 11:26:42','2025-12-05 11:26:42'),(1262,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:26:46','2025-12-05 11:26:46'),(1263,1,'users','POST','::1','{\"first_name\":\"Branden\",\"last_name\":\"Fletcher\",\"sex\":\"Female\",\"phone_number\":\"+1 (524) 568-9655\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"sponsor_id\":\"DTEHM20250059\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-12-05 11:26:54','2025-12-05 11:26:54'),(1264,1,'users/186/edit','GET','::1','[]','2025-12-05 11:26:54','2025-12-05 11:26:54'),(1265,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:26:56','2025-12-05 11:26:56'),(1266,1,'users/186/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:27:03','2025-12-05 11:27:03'),(1267,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:27:04','2025-12-05 11:27:04'),(1268,1,'users','GET','::1','[]','2025-12-05 11:27:18','2025-12-05 11:27:18'),(1269,1,'users','GET','::1','[]','2025-12-05 11:27:47','2025-12-05 11:27:47'),(1270,1,'users','GET','::1','[]','2025-12-05 11:28:08','2025-12-05 11:28:08'),(1271,1,'users','GET','::1','[]','2025-12-05 11:29:23','2025-12-05 11:29:23'),(1272,1,'users','GET','::1','[]','2025-12-05 11:30:29','2025-12-05 11:30:29'),(1273,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:30:59','2025-12-05 11:30:59'),(1274,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:31:10','2025-12-05 11:31:10'),(1275,1,'users','GET','::1','[]','2025-12-05 11:31:13','2025-12-05 11:31:13'),(1276,1,'users','GET','::1','[]','2025-12-05 11:32:25','2025-12-05 11:32:25'),(1277,1,'users','GET','::1','[]','2025-12-05 11:32:52','2025-12-05 11:32:52'),(1278,1,'users','GET','::1','[]','2025-12-05 11:33:07','2025-12-05 11:33:07'),(1279,1,'users','GET','::1','[]','2025-12-05 11:33:17','2025-12-05 11:33:17'),(1280,1,'users','GET','::1','[]','2025-12-05 11:33:42','2025-12-05 11:33:42'),(1281,1,'users','GET','::1','[]','2025-12-05 11:33:52','2025-12-05 11:33:52'),(1282,1,'users','GET','::1','{\"_sort\":{\"column\":\"account_balance\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 11:34:07','2025-12-05 11:34:07'),(1283,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:34:08','2025-12-05 11:34:08'),(1284,1,'users','GET','::1','[]','2025-12-05 11:34:30','2025-12-05 11:34:30'),(1285,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:34:56','2025-12-05 11:34:56'),(1286,1,'users','POST','::1','{\"first_name\":\"Hoyt\",\"last_name\":\"Fletcher\",\"sex\":\"Female\",\"phone_number\":\"+1 (749) 688-1182\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"sponsor_id\":\"DTEHM001\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-12-05 11:35:05','2025-12-05 11:35:05'),(1287,1,'users/187/edit','GET','::1','[]','2025-12-05 11:35:05','2025-12-05 11:35:05'),(1288,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:35:07','2025-12-05 11:35:07'),(1289,1,'users','GET','::1','[]','2025-12-05 11:36:15','2025-12-05 11:36:15'),(1290,1,'users','GET','::1','[]','2025-12-05 11:36:25','2025-12-05 11:36:25'),(1291,1,'users','GET','::1','[]','2025-12-05 11:36:35','2025-12-05 11:36:35'),(1292,1,'products','GET','::1','[]','2025-12-05 11:36:49','2025-12-05 11:36:49'),(1293,1,'products/21/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:36:54','2025-12-05 11:36:54'),(1294,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:36:58','2025-12-05 11:36:58'),(1295,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:37:08','2025-12-05 11:37:08'),(1296,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:37:18','2025-12-05 11:37:18'),(1297,1,'ordered-items','POST','::1','{\"product\":\"15\",\"sponsor_id\":\"DTEHM002\",\"stockist_id\":\"DTEHM002\",\"qty\":\"1\",\"unit_price\":\"1800000\",\"subtotal\":\"1800000\",\"amount\":\"1800000\",\"sponsor_user_id\":\"187\",\"stockist_user_id\":\"187\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 11:37:28','2025-12-05 11:37:28'),(1298,1,'ordered-items','GET','::1','[]','2025-12-05 11:37:28','2025-12-05 11:37:28'),(1299,1,'users','GET','::1','[]','2025-12-05 11:37:31','2025-12-05 11:37:31'),(1300,1,'users','GET','::1','{\"_sort\":{\"column\":\"full_name\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 11:38:30','2025-12-05 11:38:30'),(1301,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:38:31','2025-12-05 11:38:31'),(1302,1,'users','GET','::1','[]','2025-12-05 11:38:37','2025-12-05 11:38:37'),(1303,1,'users','GET','::1','[]','2025-12-05 11:38:55','2025-12-05 11:38:55'),(1304,1,'users','GET','::1','[]','2025-12-05 11:39:12','2025-12-05 11:39:12'),(1305,1,'users','GET','::1','[]','2025-12-05 11:39:24','2025-12-05 11:39:24'),(1306,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:40:21','2025-12-05 11:40:21'),(1307,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:40:25','2025-12-05 11:40:25'),(1308,1,'users','GET','::1','[]','2025-12-05 11:41:46','2025-12-05 11:41:46'),(1309,1,'user-hierarchy/180','GET','::1','[]','2025-12-05 11:41:56','2025-12-05 11:41:56'),(1310,1,'user-hierarchy/6','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:42:02','2025-12-05 11:42:02'),(1311,1,'user-hierarchy/180','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:42:04','2025-12-05 11:42:04'),(1312,1,'users','GET','::1','[]','2025-12-05 11:42:31','2025-12-05 11:42:31'),(1313,1,'user-hierarchy/180','GET','::1','[]','2025-12-05 11:42:46','2025-12-05 11:42:46'),(1314,1,'users/187/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:43:06','2025-12-05 11:43:06'),(1315,1,'users/187','PUT','::1','{\"first_name\":\"Hoyt\",\"last_name\":\"Fletcher\",\"phone_number\":\"+1 (749) 688-1182\",\"sex\":\"Female\",\"sponsor_id\":\"DTEHM001\",\"is_dip_member\":\"No\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[null],\"password\":null,\"password_confirmation\":null,\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-12-05 11:43:15','2025-12-05 11:43:15'),(1316,1,'users/187/edit','GET','::1','[]','2025-12-05 11:43:15','2025-12-05 11:43:15'),(1317,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:43:18','2025-12-05 11:43:18'),(1318,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:43:23','2025-12-05 11:43:23'),(1319,1,'users/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:44:08','2025-12-05 11:44:08'),(1320,1,'users','POST','::1','{\"first_name\":\"Carl\",\"last_name\":\"Edwards\",\"sex\":\"Female\",\"phone_number\":\"+1 (188) 176-7236\",\"is_dtehm_member\":\"Yes\",\"is_dip_member\":\"No\",\"sponsor_id\":\"DTEHM001\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-12-05 11:44:17','2025-12-05 11:44:17'),(1321,1,'users','GET','::1','[]','2025-12-05 11:44:17','2025-12-05 11:44:17'),(1322,1,'users/188/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:44:29','2025-12-05 11:44:29'),(1323,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:44:30','2025-12-05 11:44:30'),(1324,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:44:45','2025-12-05 11:44:45'),(1325,1,'insurance-programs','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:44:49','2025-12-05 11:44:49'),(1326,1,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:44:55','2025-12-05 11:44:55'),(1327,1,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:44:57','2025-12-05 11:44:57'),(1328,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:45:02','2025-12-05 11:45:02'),(1329,1,'auth/menu','GET','::1','[]','2025-12-05 11:45:10','2025-12-05 11:45:10'),(1330,1,'auth/menu/27/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:45:13','2025-12-05 11:45:13'),(1331,1,'auth/menu/27','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Network Hierarchy\",\"icon\":\"fa-adjust\",\"uri\":\"user-hierarchy\",\"roles\":[null],\"permission\":null,\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-12-05 11:45:16','2025-12-05 11:45:16'),(1332,1,'auth/menu','GET','::1','[]','2025-12-05 11:45:16','2025-12-05 11:45:16'),(1333,1,'auth/menu/27/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:45:18','2025-12-05 11:45:18'),(1334,1,'auth/menu/27','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Network Hierarchy\",\"icon\":\"fa-sitemap\",\"uri\":\"user-hierarchy\",\"roles\":[null],\"permission\":null,\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-12-05 11:45:38','2025-12-05 11:45:38'),(1335,1,'auth/menu','GET','::1','[]','2025-12-05 11:45:39','2025-12-05 11:45:39'),(1336,1,'auth/menu','GET','::1','[]','2025-12-05 11:45:41','2025-12-05 11:45:41'),(1337,1,'auth/menu/27/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:45:44','2025-12-05 11:45:44'),(1338,1,'auth/menu/27','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Network Hierarchy\",\"icon\":\"fa-sitemap\",\"uri\":\"user-hierarchy\",\"roles\":[\"1\",null],\"permission\":null,\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-12-05 11:45:51','2025-12-05 11:45:51'),(1339,1,'auth/menu','GET','::1','[]','2025-12-05 11:45:51','2025-12-05 11:45:51'),(1340,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:45:55','2025-12-05 11:45:55'),(1341,1,'user-hierarchy','GET','::1','[]','2025-12-05 11:45:56','2025-12-05 11:45:56'),(1342,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:46:02','2025-12-05 11:46:02'),(1343,1,'auth/menu/39/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:46:07','2025-12-05 11:46:07'),(1344,1,'auth/menu/39','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Members\",\"icon\":\"fa-users\",\"uri\":\"users\",\"roles\":[null],\"permission\":null,\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-12-05 11:46:12','2025-12-05 11:46:12'),(1345,1,'auth/menu','GET','::1','[]','2025-12-05 11:46:12','2025-12-05 11:46:12'),(1346,1,'auth/menu','GET','::1','[]','2025-12-05 11:46:14','2025-12-05 11:46:14'),(1347,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:46:16','2025-12-05 11:46:16'),(1348,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:46:18','2025-12-05 11:46:18'),(1349,1,'user-hierarchy','GET','::1','[]','2025-12-05 11:46:58','2025-12-05 11:46:58'),(1350,1,'user-hierarchy/188','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:47:18','2025-12-05 11:47:18'),(1351,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:47:19','2025-12-05 11:47:19'),(1352,1,'user-hierarchy','GET','::1','[]','2025-12-05 11:48:39','2025-12-05 11:48:39'),(1353,1,'user-hierarchy/188','GET','::1','[]','2025-12-05 11:48:42','2025-12-05 11:48:42'),(1354,1,'delivery-addresses','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:48:50','2025-12-05 11:48:50'),(1355,1,'auth/menu','GET','::1','[]','2025-12-05 11:48:54','2025-12-05 11:48:54'),(1356,1,'auth/menu/30','DELETE','::1','{\"_method\":\"delete\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\"}','2025-12-05 11:48:58','2025-12-05 11:48:58'),(1357,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:48:58','2025-12-05 11:48:58'),(1358,1,'auth/menu','GET','::1','[]','2025-12-05 11:49:01','2025-12-05 11:49:01'),(1359,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:49:03','2025-12-05 11:49:03'),(1360,1,'product-categories','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:49:05','2025-12-05 11:49:05'),(1361,1,'product-categories/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:49:28','2025-12-05 11:49:28'),(1362,1,'product-categories/1','PUT','::1','{\"category\":\"MAIN CATEGORY\",\"icon\":\"fa fa-wheelchair\",\"is_parent\":\"Yes\",\"specifications\":{\"1\":{\"name\":\"Brand\",\"is_required\":\"Yes\",\"id\":\"1\",\"_remove_\":\"0\"},\"2\":{\"name\":\"Material\",\"is_required\":\"Yes\",\"id\":\"2\",\"_remove_\":\"0\"},\"3\":{\"name\":\"Weight Capacity\",\"is_required\":\"Yes\",\"id\":\"3\",\"_remove_\":\"0\"},\"4\":{\"name\":\"Warranty Period\",\"is_required\":\"No\",\"id\":\"4\",\"_remove_\":\"0\"},\"5\":{\"name\":\"Color\",\"is_required\":\"No\",\"id\":\"5\",\"_remove_\":\"0\"}},\"show_in_banner\":\"Yes\",\"show_in_categories\":\"Yes\",\"is_first_banner\":\"No\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/product-categories\"}','2025-12-05 11:49:34','2025-12-05 11:49:34'),(1363,1,'product-categories','GET','::1','[]','2025-12-05 11:49:34','2025-12-05 11:49:34'),(1364,1,'auth/menu','GET','::1','[]','2025-12-05 11:49:40','2025-12-05 11:49:40'),(1365,1,'auth/menu/29','DELETE','::1','{\"_method\":\"delete\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\"}','2025-12-05 11:49:44','2025-12-05 11:49:44'),(1366,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:49:44','2025-12-05 11:49:44'),(1367,1,'auth/menu/32','DELETE','::1','{\"_method\":\"delete\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\"}','2025-12-05 11:49:50','2025-12-05 11:49:50'),(1368,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:49:50','2025-12-05 11:49:50'),(1369,1,'auth/menu','GET','::1','[]','2025-12-05 11:49:54','2025-12-05 11:49:54'),(1370,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:49:57','2025-12-05 11:49:57'),(1371,1,'products/21/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:50:03','2025-12-05 11:50:03'),(1372,1,'products/21','PUT','::1','{\"local_id\":\"1764880700-185886-699370\",\"currency\":\"UGX\",\"has_colors\":\"No\",\"has_sizes\":\"No\",\"home_section_1\":\"No\",\"home_section_2\":\"No\",\"home_section_3\":\"No\",\"status\":\"active\",\"in_stock\":\"Yes\",\"p_type\":\"product\",\"name\":\"Gavin Shepherd\",\"price_1\":\"100000\",\"description\":\"<p>Mollitia inventore r...<\\/p>\",\"category\":\"3\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/products\"}','2025-12-05 11:50:10','2025-12-05 11:50:10'),(1373,1,'products/21/edit','GET','::1','[]','2025-12-05 11:50:10','2025-12-05 11:50:10'),(1374,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:50:13','2025-12-05 11:50:13'),(1375,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:50:18','2025-12-05 11:50:18'),(1376,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:50:20','2025-12-05 11:50:20'),(1377,1,'ordered-items','POST','::1','{\"product\":\"21\",\"sponsor_id\":\"DIP0001\",\"stockist_id\":\"DTEHM20250001\",\"qty\":\"1\",\"unit_price\":\"100000\",\"subtotal\":\"100000\",\"amount\":\"100000\",\"sponsor_user_id\":\"2\",\"stockist_user_id\":\"3\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 11:50:31','2025-12-05 11:50:31'),(1378,1,'ordered-items','GET','::1','[]','2025-12-05 11:50:31','2025-12-05 11:50:31'),(1379,1,'ordered-items','GET','::1','[]','2025-12-05 11:50:34','2025-12-05 11:50:34'),(1380,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:50:56','2025-12-05 11:50:56'),(1381,1,'ordered-items','GET','::1','[]','2025-12-05 11:53:40','2025-12-05 11:53:40'),(1382,1,'ordered-items','GET','::1','[]','2025-12-05 11:55:07','2025-12-05 11:55:07'),(1383,1,'ordered-items','GET','::1','[]','2025-12-05 11:55:13','2025-12-05 11:55:13'),(1384,1,'ordered-items','GET','::1','[]','2025-12-05 11:55:46','2025-12-05 11:55:46'),(1385,1,'ordered-items','GET','::1','[]','2025-12-05 11:56:37','2025-12-05 11:56:37'),(1386,1,'ordered-items/12','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 11:57:03','2025-12-05 11:57:03'),(1387,1,'ordered-items/12','GET','::1','[]','2025-12-05 11:57:03','2025-12-05 11:57:03'),(1388,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:04:16','2025-12-05 12:04:16'),(1389,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 12:04:52','2025-12-05 12:04:52'),(1390,1,'ordered-items','GET','::1','[]','2025-12-05 12:06:03','2025-12-05 12:06:03'),(1391,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 12:06:19','2025-12-05 12:06:19'),(1392,1,'ordered-items/12','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 12:06:25','2025-12-05 12:06:25'),(1393,1,'ordered-items','GET','::1','[]','2025-12-05 12:06:25','2025-12-05 12:06:25'),(1394,1,'ordered-items','GET','::1','[]','2025-12-05 12:07:10','2025-12-05 12:07:10'),(1395,1,'ordered-items/12','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 12:07:13','2025-12-05 12:07:13'),(1396,1,'ordered-items','GET','::1','[]','2025-12-05 12:07:13','2025-12-05 12:07:13'),(1397,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:07:18','2025-12-05 12:07:18'),(1398,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:07:18','2025-12-05 12:07:18'),(1399,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:08:17','2025-12-05 12:08:17'),(1400,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:08:18','2025-12-05 12:08:18'),(1401,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:09:18','2025-12-05 12:09:18'),(1402,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:09:19','2025-12-05 12:09:19'),(1403,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:10:26','2025-12-05 12:10:26'),(1404,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:10:26','2025-12-05 12:10:26'),(1405,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:10:46','2025-12-05 12:10:46'),(1406,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:10:46','2025-12-05 12:10:46'),(1407,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:13:17','2025-12-05 12:13:17'),(1408,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:13:27','2025-12-05 12:13:27'),(1409,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:14:58','2025-12-05 12:14:58'),(1410,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:16:24','2025-12-05 12:16:24'),(1411,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:38:47','2025-12-05 12:38:47'),(1412,1,'ordered-items/12','GET','::1','[]','2025-12-05 12:42:04','2025-12-05 12:42:04'),(1413,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 12:42:13','2025-12-05 12:42:13'),(1414,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 12:42:19','2025-12-05 12:42:19'),(1415,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 12:42:30','2025-12-05 12:42:30'),(1416,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 12:42:33','2025-12-05 12:42:33'),(1417,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 12:42:39','2025-12-05 12:42:39'),(1418,1,'ordered-items','GET','::1','[]','2025-12-05 12:50:57','2025-12-05 12:50:57'),(1419,1,'ordered-items','GET','::1','[]','2025-12-05 12:51:07','2025-12-05 12:51:07'),(1420,1,'ordered-items','GET','::1','[]','2025-12-05 12:52:22','2025-12-05 12:52:22'),(1421,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 12:52:25','2025-12-05 12:52:25'),(1422,1,'ordered-items','GET','::1','[]','2025-12-05 12:52:37','2025-12-05 12:52:37'),(1423,1,'ordered-items/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 12:52:46','2025-12-05 12:52:46'),(1424,1,'ordered-items/create','GET','::1','[]','2025-12-05 13:07:54','2025-12-05 13:07:54'),(1425,1,'ordered-items','POST','::1','{\"product\":\"18\",\"sponsor_user_id\":\"5\",\"stockist_user_id\":\"5\",\"qty\":\"1\",\"unit_price\":\"35000\",\"subtotal\":\"35000\",\"amount\":\"35000\",\"sponsor_id\":\"DTEHM20250002\",\"stockist_id\":\"DTEHM20250002\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/ordered-items\"}','2025-12-05 13:08:08','2025-12-05 13:08:08'),(1426,1,'ordered-items','GET','::1','[]','2025-12-05 13:08:08','2025-12-05 13:08:08'),(1427,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:09:43','2025-12-05 13:09:43'),(1428,1,'auth/menu','GET','::1','[]','2025-12-05 13:11:06','2025-12-05 13:11:06'),(1429,1,'auth/menu/7/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:11:09','2025-12-05 13:11:09'),(1430,1,'auth/menu/7','PUT','::1','{\"parent_id\":\"22\",\"title\":\"Account Transactions\",\"icon\":\"fa-diamond\",\"uri\":\"account-transactions\",\"roles\":[\"1\",null],\"permission\":null,\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-12-05 13:11:13','2025-12-05 13:11:13'),(1431,1,'auth/menu','GET','::1','[]','2025-12-05 13:11:13','2025-12-05 13:11:13'),(1432,1,'auth/menu','POST','::1','{\"parent_id\":\"28\",\"title\":\"Commisions\",\"icon\":\"fa-diamond\",\"uri\":\"account-transactions\",\"roles\":[\"1\",null],\"permission\":null,\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\"}','2025-12-05 13:11:37','2025-12-05 13:11:37'),(1433,1,'auth/menu','GET','::1','[]','2025-12-05 13:11:37','2025-12-05 13:11:37'),(1434,1,'auth/menu','POST','::1','{\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":28,\\\"children\\\":[{\\\"id\\\":39},{\\\"id\\\":27},{\\\"id\\\":31},{\\\"id\\\":33},{\\\"id\\\":40}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":26},{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":24},{\\\"id\\\":13}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6}]},{\\\"id\\\":22,\\\"children\\\":[{\\\"id\\\":25},{\\\"id\\\":34},{\\\"id\\\":21},{\\\"id\\\":23},{\\\"id\\\":7}]},{\\\"id\\\":37,\\\"children\\\":[{\\\"id\\\":38},{\\\"id\\\":18}]}]\"}','2025-12-05 13:12:42','2025-12-05 13:12:42'),(1435,1,'auth/menu','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:12:43','2025-12-05 13:12:43'),(1436,1,'auth/menu','GET','::1','[]','2025-12-05 13:12:44','2025-12-05 13:12:44'),(1437,1,'auth/menu/40/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:12:46','2025-12-05 13:12:46'),(1438,1,'auth/menu/40','PUT','::1','{\"parent_id\":\"28\",\"title\":\"Commisions\",\"icon\":\"fa-money\",\"uri\":\"account-transactions\",\"roles\":[\"1\",null],\"permission\":null,\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-12-05 13:12:53','2025-12-05 13:12:53'),(1439,1,'auth/menu','GET','::1','[]','2025-12-05 13:12:53','2025-12-05 13:12:53'),(1440,1,'auth/menu','GET','::1','[]','2025-12-05 13:12:55','2025-12-05 13:12:55'),(1441,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:12:57','2025-12-05 13:12:57'),(1442,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"source\",\"type\":\"desc\"}}','2025-12-05 13:13:23','2025-12-05 13:13:23'),(1443,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"source\",\"type\":\"asc\"}}','2025-12-05 13:13:24','2025-12-05 13:13:24'),(1444,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"source\",\"type\":\"desc\"}}','2025-12-05 13:13:25','2025-12-05 13:13:25'),(1445,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"source\",\"type\":\"asc\"}}','2025-12-05 13:13:26','2025-12-05 13:13:26'),(1446,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:13:29','2025-12-05 13:13:29'),(1447,1,'account-transactions','GET','::1','[]','2025-12-05 13:13:46','2025-12-05 13:13:46'),(1448,1,'account-transactions','GET','::1','[]','2025-12-05 13:15:02','2025-12-05 13:15:02'),(1449,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"amount\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 13:15:14','2025-12-05 13:15:14'),(1450,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"amount\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 13:15:15','2025-12-05 13:15:15'),(1451,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"amount\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 13:15:17','2025-12-05 13:15:17'),(1452,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"amount\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 13:15:18','2025-12-05 13:15:18'),(1453,1,'account-transactions/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:15:24','2025-12-05 13:15:24'),(1454,1,'account-transactions','POST','::1','{\"user_id\":\"30\",\"amount\":\"10000\",\"source\":\"withdrawal\",\"description\":\"some message\",\"transaction_date\":\"2025-12-05\",\"created_by_id\":\"1\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/account-transactions?_sort%5Bcolumn%5D=amount&_sort%5Btype%5D=asc\"}','2025-12-05 13:15:38','2025-12-05 13:15:38'),(1455,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"amount\",\"type\":\"asc\"}}','2025-12-05 13:15:38','2025-12-05 13:15:38'),(1456,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"amount\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 13:15:40','2025-12-05 13:15:40'),(1457,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"amount\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 13:15:41','2025-12-05 13:15:41'),(1458,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"amount\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 13:15:42','2025-12-05 13:15:42'),(1459,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"amount\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 13:15:43','2025-12-05 13:15:43'),(1460,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"source\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 13:15:47','2025-12-05 13:15:47'),(1461,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"source\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 13:15:49','2025-12-05 13:15:49'),(1462,1,'_handle_action_','POST','::1','{\"_key\":\"56\",\"_model\":\"App_Models_AccountTransaction\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_action\":\"Encore_Admin_Grid_Actions_Delete\",\"_input\":\"true\"}','2025-12-05 13:15:59','2025-12-05 13:15:59'),(1463,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"source\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 13:16:00','2025-12-05 13:16:00'),(1464,1,'account-transactions/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:16:06','2025-12-05 13:16:06'),(1465,1,'account-transactions','POST','::1','{\"user_id\":\"2\",\"amount\":\"-1000\",\"source\":\"withdrawal\",\"description\":\"ikj\",\"transaction_date\":\"2025-12-05\",\"created_by_id\":\"1\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/account-transactions?_sort%5Bcolumn%5D=source&_sort%5Btype%5D=asc\"}','2025-12-05 13:16:28','2025-12-05 13:16:28'),(1466,1,'account-transactions','GET','::1','{\"_sort\":{\"column\":\"source\",\"type\":\"asc\"}}','2025-12-05 13:16:28','2025-12-05 13:16:28'),(1467,1,'membership-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:16:52','2025-12-05 13:16:52'),(1468,1,'auth/menu','GET','::1','[]','2025-12-05 13:16:58','2025-12-05 13:16:58'),(1469,1,'auth/menu/21/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:17:00','2025-12-05 13:17:00'),(1470,1,'auth/menu/21','PUT','::1','{\"parent_id\":\"22\",\"title\":\"DIP Memberships\",\"icon\":\"fa-cc-paypal\",\"uri\":\"membership-payments\",\"roles\":[null],\"permission\":null,\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/auth\\/menu\"}','2025-12-05 13:17:15','2025-12-05 13:17:15'),(1471,1,'auth/menu','GET','::1','[]','2025-12-05 13:17:15','2025-12-05 13:17:15'),(1472,1,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:17:22','2025-12-05 13:17:22'),(1473,1,'/','GET','::1','[]','2025-12-05 13:17:24','2025-12-05 13:17:24'),(1474,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:17:32','2025-12-05 13:17:32'),(1475,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:17:40','2025-12-05 13:17:40'),(1476,1,'insurance-programs','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:17:42','2025-12-05 13:17:42'),(1477,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:17:44','2025-12-05 13:17:44'),(1478,1,'insurance-programs','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:17:48','2025-12-05 13:17:48'),(1479,1,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:17:49','2025-12-05 13:17:49'),(1480,1,'insurance-subscription-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:17:52','2025-12-05 13:17:52'),(1481,1,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:17:57','2025-12-05 13:17:57'),(1482,1,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:18:00','2025-12-05 13:18:00'),(1483,1,'insurance-subscriptions/create','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:18:08','2025-12-05 13:18:08'),(1484,1,'insurance-subscriptions','POST','::1','{\"user_id\":\"2\",\"insurance_program_id\":\"1\",\"start_date\":\"2025-12-05\",\"beneficiaries\":\"some maessage\",\"notes\":\"some notes\",\"end_date\":\"2026-12-05\",\"coverage_start_date\":\"2025-12-05\",\"coverage_end_date\":\"2026-12-05\",\"premium_amount\":\"0\",\"next_billing_date\":\"2025-12-05\",\"status\":\"Active\",\"payment_status\":\"Current\",\"coverage_status\":\"Active\",\"total_expected\":\"0\",\"total_paid\":\"0\",\"total_balance\":\"0\",\"payments_completed\":\"0\",\"payments_pending\":\"0\",\"prepared\":\"No\",\"created_by\":\"1\",\"updated_by\":\"1\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/insurance-subscriptions\"}','2025-12-05 13:18:38','2025-12-05 13:18:38'),(1485,1,'insurance-subscriptions','GET','::1','[]','2025-12-05 13:18:39','2025-12-05 13:18:39'),(1486,1,'insurance-subscription-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:19:18','2025-12-05 13:19:18'),(1487,1,'insurance-subscription-payments/14/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:19:25','2025-12-05 13:19:25'),(1488,1,'insurance-subscription-payments/14','PUT','::1','{\"payment_status\":\"Paid\",\"paid_amount\":\"0\",\"payment_date\":null,\"payment_method\":null,\"payment_reference\":null,\"notes\":null,\"updated_by\":\"1\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/insurance-subscription-payments\"}','2025-12-05 13:19:33','2025-12-05 13:19:33'),(1489,1,'insurance-subscription-payments','GET','::1','[]','2025-12-05 13:19:33','2025-12-05 13:19:33'),(1490,1,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:19:42','2025-12-05 13:19:42'),(1491,1,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:19:45','2025-12-05 13:19:45'),(1492,1,'insurance-subscription-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:19:46','2025-12-05 13:19:46'),(1493,1,'insurance-subscription-payments','GET','::1','{\"_pjax\":\"#pjax-container\",\"user\":{\"name\":\"abel\"},\"insurance_program_id\":null,\"payment_status\":null,\"due_date\":{\"start\":null,\"end\":null},\"payment_date\":{\"start\":null,\"end\":null}}','2025-12-05 13:19:51','2025-12-05 13:19:51'),(1494,1,'insurance-subscription-payments','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:19:53','2025-12-05 13:19:53'),(1495,1,'insurance-subscription-payments/13/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:20:10','2025-12-05 13:20:10'),(1496,1,'insurance-subscription-payments/13','PUT','::1','{\"payment_status\":\"Paid\",\"paid_amount\":\"0\",\"payment_date\":null,\"payment_method\":null,\"payment_reference\":null,\"notes\":null,\"updated_by\":\"1\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/insurance-subscription-payments\"}','2025-12-05 13:20:16','2025-12-05 13:20:16'),(1497,1,'insurance-subscription-payments','GET','::1','[]','2025-12-05 13:20:16','2025-12-05 13:20:16'),(1498,1,'insurance-subscriptions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:20:21','2025-12-05 13:20:21'),(1499,1,'insurance-subscriptions/2/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:20:28','2025-12-05 13:20:28'),(1500,1,'disbursements','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:20:42','2025-12-05 13:20:42'),(1501,1,'projects','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:20:43','2025-12-05 13:20:43'),(1502,1,'/','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:21:03','2025-12-05 13:21:03'),(1503,1,'/','GET','::1','[]','2025-12-05 13:21:06','2025-12-05 13:21:06'),(1504,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 13:21:51','2025-12-05 13:21:51'),(1505,1,'auth/setting','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:38:53','2025-12-05 14:38:53'),(1506,1,'dtehm-memberships','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:39:00','2025-12-05 14:39:00'),(1507,1,'dtehm-memberships','GET','::1','[]','2025-12-05 14:39:06','2025-12-05 14:39:06'),(1508,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:39:09','2025-12-05 14:39:09'),(1509,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-12-05 14:39:16','2025-12-05 14:39:16'),(1510,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-12-05 14:39:17','2025-12-05 14:39:17'),(1511,1,'users/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:39:20','2025-12-05 14:39:20'),(1512,1,'users/1','PUT','::1','{\"first_name\":\"Admin\",\"last_name\":\"User\",\"phone_number\":\"+256783204665\",\"sex\":\"Male\",\"sponsor_id\":null,\"is_dip_member\":\"No\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"No\",\"user_type\":\"Admin\",\"status\":\"Active\",\"roles\":[\"1\",null],\"password\":\"$2y$10$Ll.ayiYJPROPCTIAhHOc4.17cBD2fdqGl0qr6CR.j77I3Jw92Z3rS\",\"password_confirmation\":null,\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users?&_sort%5Bcolumn%5D=id&_sort%5Btype%5D=asc\"}','2025-12-05 14:39:25','2025-12-05 14:39:25'),(1513,1,'users/1/edit','GET','::1','[]','2025-12-05 14:39:25','2025-12-05 14:39:25'),(1514,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:39:29','2025-12-05 14:39:29'),(1515,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-12-05 14:39:35','2025-12-05 14:39:35'),(1516,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-12-05 14:39:36','2025-12-05 14:39:36'),(1517,1,'users/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:39:39','2025-12-05 14:39:39'),(1518,1,'users/1','PUT','::1','{\"first_name\":\"Admin\",\"last_name\":\"User\",\"phone_number\":\"+256783204665\",\"sex\":\"Male\",\"sponsor_id\":null,\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"No\",\"user_type\":\"Admin\",\"status\":\"Active\",\"roles\":[\"1\",null],\"password\":\"111111\",\"password_confirmation\":\"111111\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users?&_sort%5Bcolumn%5D=id&_sort%5Btype%5D=asc\"}','2025-12-05 14:39:50','2025-12-05 14:39:50'),(1519,1,'users/1/edit','GET','::1','[]','2025-12-05 14:39:50','2025-12-05 14:39:50'),(1520,1,'users/1/edit','GET','::1','[]','2025-12-05 14:39:54','2025-12-05 14:39:54'),(1521,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:40:14','2025-12-05 14:40:14'),(1522,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-12-05 14:40:16','2025-12-05 14:40:16'),(1523,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-12-05 14:40:17','2025-12-05 14:40:17'),(1524,1,'users/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:40:55','2025-12-05 14:40:55'),(1525,1,'users/1','PUT','::1','{\"first_name\":\"Admin\",\"last_name\":\"User\",\"phone_number\":\"+256783204665\",\"sex\":\"Male\",\"sponsor_id\":null,\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"user_type\":\"Admin\",\"status\":\"Active\",\"roles\":[\"1\",null],\"password\":\"111111\",\"password_confirmation\":\"111111\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users?&_sort%5Bcolumn%5D=id&_sort%5Btype%5D=asc\"}','2025-12-05 14:41:03','2025-12-05 14:41:03'),(1526,1,'users/1/edit','GET','::1','[]','2025-12-05 14:41:03','2025-12-05 14:41:03'),(1527,1,'users/1','PUT','::1','{\"first_name\":\"Admin\",\"last_name\":\"User\",\"phone_number\":\"+256783204665\",\"sex\":\"Male\",\"sponsor_id\":null,\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"user_type\":\"Admin\",\"status\":\"Active\",\"roles\":[\"1\",null],\"password\":\"1111111\",\"password_confirmation\":\"1111111\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-12-05 14:42:09','2025-12-05 14:42:09'),(1528,1,'users/1/edit','GET','::1','[]','2025-12-05 14:42:09','2025-12-05 14:42:09'),(1529,1,'users/1','PUT','::1','{\"first_name\":\"Admin\",\"last_name\":\"User\",\"phone_number\":\"+256783204665\",\"sex\":\"Male\",\"sponsor_id\":null,\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"user_type\":\"Admin\",\"status\":\"Active\",\"roles\":[\"1\",null],\"password\":\"111111\",\"password_confirmation\":\"111111\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-12-05 14:42:18','2025-12-05 14:42:18'),(1530,1,'users/1/edit','GET','::1','[]','2025-12-05 14:42:18','2025-12-05 14:42:18'),(1531,1,'/','GET','::1','[]','2025-12-05 14:42:34','2025-12-05 14:42:34'),(1532,1,'auth/logout','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:42:37','2025-12-05 14:42:37'),(1533,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:47:35','2025-12-05 14:47:35'),(1534,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-12-05 14:47:39','2025-12-05 14:47:39'),(1535,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"id\",\"type\":\"asc\"}}','2025-12-05 14:47:40','2025-12-05 14:47:40'),(1536,1,'/','GET','::1','[]','2025-12-05 14:47:48','2025-12-05 14:47:48'),(1537,1,'auth/logout','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:47:51','2025-12-05 14:47:51'),(1538,1,'users/1/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:47:54','2025-12-05 14:47:54'),(1539,1,'/','GET','::1','[]','2025-12-05 14:50:21','2025-12-05 14:50:21'),(1540,1,'/','GET','::1','[]','2025-12-05 14:51:52','2025-12-05 14:51:52'),(1541,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:51:54','2025-12-05 14:51:54'),(1542,1,'users/188/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:51:59','2025-12-05 14:51:59'),(1543,1,'users/188','PUT','::1','{\"first_name\":\"Carl\",\"last_name\":\"Edwards\",\"phone_number\":\"+1 (188) 176-7236\",\"sex\":\"Female\",\"sponsor_id\":\"DTEHM001\",\"is_dip_member\":\"No\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[\"2\",null],\"_token\":\"ogoFSYKWJ8R0Aehn012sSL0Mxx63jdz4XbM00Znc\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-12-05 14:52:11','2025-12-05 14:52:11'),(1544,1,'users','GET','::1','[]','2025-12-05 14:52:11','2025-12-05 14:52:11'),(1545,1,'auth/users','GET','::1','[]','2025-12-05 14:52:25','2025-12-05 14:52:25'),(1546,1,'auth/users','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 14:52:30','2025-12-05 14:52:30'),(1547,1,'auth/users/188/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:52:33','2025-12-05 14:52:33'),(1548,1,'auth/users','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 14:52:39','2025-12-05 14:52:39'),(1549,1,'users','GET','::1','[]','2025-12-05 14:54:18','2025-12-05 14:54:18'),(1550,1,'users/188/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:54:21','2025-12-05 14:54:21'),(1551,1,'users/188/edit','GET','::1','[]','2025-12-05 14:56:14','2025-12-05 14:56:14'),(1552,1,'users/188','PUT','::1','{\"first_name\":\"Carl\",\"last_name\":\"Edwards\",\"phone_number\":\"+1 (188) 176-7236\",\"sex\":\"Female\",\"sponsor_id\":\"DTEHM001\",\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[\"2\",null],\"password\":null,\"password_confirmation\":null,\"change_password_toggle\":\"No\",\"_token\":\"ogoFSYKWJ8R0Aehn012sSL0Mxx63jdz4XbM00Znc\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-12-05 14:56:18','2025-12-05 14:56:18'),(1553,1,'users/188/edit','GET','::1','[]','2025-12-05 14:56:18','2025-12-05 14:56:18'),(1554,1,'users/188','PUT','::1','{\"first_name\":\"Carl\",\"last_name\":\"Edwards\",\"phone_number\":\"+1 (188) 176-7236\",\"sex\":\"Female\",\"sponsor_id\":\"DTEHM001\",\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[\"2\",null],\"password\":null,\"password_confirmation\":null,\"change_password_toggle\":\"No\",\"_token\":\"ogoFSYKWJ8R0Aehn012sSL0Mxx63jdz4XbM00Znc\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-12-05 14:57:57','2025-12-05 14:57:57'),(1555,1,'users/188/edit','GET','::1','[]','2025-12-05 14:57:57','2025-12-05 14:57:57'),(1556,1,'users/188','PUT','::1','{\"first_name\":\"Carl\",\"last_name\":\"Edwards.\",\"phone_number\":\"+1 (188) 176-7236\",\"sex\":\"Female\",\"sponsor_id\":\"DTEHM001\",\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[\"2\",null],\"password\":null,\"password_confirmation\":null,\"change_password_toggle\":\"No\",\"_token\":\"ogoFSYKWJ8R0Aehn012sSL0Mxx63jdz4XbM00Znc\",\"after-save\":\"1\",\"_method\":\"PUT\"}','2025-12-05 14:58:03','2025-12-05 14:58:03'),(1557,1,'users/188/edit','GET','::1','[]','2025-12-05 14:58:03','2025-12-05 14:58:03'),(1558,1,'auth/logout','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:58:21','2025-12-05 14:58:21'),(1559,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:58:28','2025-12-05 14:58:28'),(1560,1,'users/188/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:58:32','2025-12-05 14:58:32'),(1561,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:58:44','2025-12-05 14:58:44'),(1562,1,'users/188/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:59:01','2025-12-05 14:59:01'),(1563,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:59:12','2025-12-05 14:59:12'),(1564,1,'users/188/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:59:15','2025-12-05 14:59:15'),(1565,1,'users/188','PUT','::1','{\"first_name\":\"Carl\",\"last_name\":\"Edwards.\",\"phone_number\":\"+1 (188) 176-7236\",\"sex\":\"Female\",\"sponsor_id\":\"DTEHM001\",\"is_dip_member\":\"Yes\",\"is_dtehm_member\":\"Yes\",\"dtehm_membership_is_paid\":\"Yes\",\"user_type\":\"Customer\",\"status\":\"Active\",\"roles\":[\"2\",null],\"password\":\"111111\",\"password_confirmation\":\"111111\",\"change_password_toggle\":\"No\",\"_token\":\"Q64zXT8cA9RuShvd706JxgUAu2kkTwtcuZoSRcNd\",\"after-save\":\"1\",\"_method\":\"PUT\",\"_previous_\":\"http:\\/\\/localhost:8888\\/dtehm-insurance-api\\/users\"}','2025-12-05 14:59:26','2025-12-05 14:59:26'),(1566,1,'users/188/edit','GET','::1','[]','2025-12-05 14:59:27','2025-12-05 14:59:27'),(1567,188,'/','GET','::1','[]','2025-12-05 14:59:31','2025-12-05 14:59:31'),(1568,188,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:59:46','2025-12-05 14:59:46'),(1569,188,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:59:47','2025-12-05 14:59:47'),(1570,188,'users/188/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:59:49','2025-12-05 14:59:49'),(1571,188,'users/188/edit','GET','::1','[]','2025-12-05 14:59:49','2025-12-05 14:59:49'),(1572,188,'users','GET','::1','[]','2025-12-05 14:59:49','2025-12-05 14:59:49'),(1573,188,'users/188/edit','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 14:59:59','2025-12-05 14:59:59'),(1574,188,'users/188/edit','GET','::1','[]','2025-12-05 15:00:00','2025-12-05 15:00:00'),(1575,188,'users','GET','::1','[]','2025-12-05 15:00:00','2025-12-05 15:00:00'),(1576,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 15:00:15','2025-12-05 15:00:15'),(1577,1,'user-hierarchy','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 15:00:49','2025-12-05 15:00:49'),(1578,1,'products','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 15:00:55','2025-12-05 15:00:55'),(1579,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 15:00:57','2025-12-05 15:00:57'),(1580,1,'account-transactions','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 15:01:04','2025-12-05 15:01:04'),(1581,1,'ordered-items','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 15:01:08','2025-12-05 15:01:08'),(1582,188,'users','GET','::1','[]','2025-12-05 15:13:30','2025-12-05 15:13:30'),(1583,1,'/','GET','::1','[]','2025-12-05 16:01:04','2025-12-05 16:01:04'),(1584,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 16:01:09','2025-12-05 16:01:09'),(1585,1,'users','GET','::1','[]','2025-12-05 16:01:19','2025-12-05 16:01:19'),(1586,1,'users','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:01:23','2025-12-05 16:01:23'),(1587,1,'users','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:01:24','2025-12-05 16:01:24'),(1588,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:01:26','2025-12-05 16:01:26'),(1589,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:01:28','2025-12-05 16:01:28'),(1590,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:01:29','2025-12-05 16:01:29'),(1591,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"}}','2025-12-05 16:03:11','2025-12-05 16:03:11'),(1592,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"}}','2025-12-05 16:03:13','2025-12-05 16:03:13'),(1593,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:03:14','2025-12-05 16:03:14'),(1594,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:03:15','2025-12-05 16:03:15'),(1595,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:03:16','2025-12-05 16:03:16'),(1596,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:03:26','2025-12-05 16:03:26'),(1597,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:03:27','2025-12-05 16:03:27'),(1598,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:03:28','2025-12-05 16:03:28'),(1599,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"asc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:03:29','2025-12-05 16:03:29'),(1600,1,'auth/users','GET','::1','{\"_sort\":{\"column\":\"id\",\"type\":\"desc\"}}','2025-12-05 16:04:04','2025-12-05 16:04:04'),(1601,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\"}','2025-12-05 16:04:06','2025-12-05 16:04:06'),(1602,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"}}','2025-12-05 16:04:09','2025-12-05 16:04:09'),(1603,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"asc\"}}','2025-12-05 16:04:10','2025-12-05 16:04:10'),(1604,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"}}','2025-12-05 16:04:11','2025-12-05 16:04:11'),(1605,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"asc\"}}','2025-12-05 16:04:12','2025-12-05 16:04:12'),(1606,1,'users','GET','::1','{\"_pjax\":\"#pjax-container\",\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"}}','2025-12-05 16:04:13','2025-12-05 16:04:13'),(1607,1,'users','GET','::1','{\"_sort\":{\"column\":\"sponsor_id\",\"type\":\"desc\"},\"_pjax\":\"#pjax-container\"}','2025-12-05 16:10:20','2025-12-05 16:10:20');
/*!40000 ALTER TABLE `admin_operation_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_permissions`
--

DROP TABLE IF EXISTS `admin_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `http_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `http_path` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_permissions_name_unique` (`name`),
  UNIQUE KEY `admin_permissions_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_permissions`
--

LOCK TABLES `admin_permissions` WRITE;
/*!40000 ALTER TABLE `admin_permissions` DISABLE KEYS */;
INSERT INTO `admin_permissions` VALUES (1,'All permission','*','','*','2025-10-29 07:41:44','2025-10-29 07:41:44');
/*!40000 ALTER TABLE `admin_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_role_menu`
--

DROP TABLE IF EXISTS `admin_role_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_role_menu` (
  `role_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `admin_role_menu_role_id_menu_id_index` (`role_id`,`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_role_menu`
--

LOCK TABLES `admin_role_menu` WRITE;
/*!40000 ALTER TABLE `admin_role_menu` DISABLE KEYS */;
INSERT INTO `admin_role_menu` VALUES (1,9,NULL,NULL),(1,27,NULL,NULL),(1,7,NULL,NULL),(1,40,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_role_permissions`
--

DROP TABLE IF EXISTS `admin_role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `admin_role_permissions_role_id_permission_id_index` (`role_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_role_permissions`
--

LOCK TABLES `admin_role_permissions` WRITE;
/*!40000 ALTER TABLE `admin_role_permissions` DISABLE KEYS */;
INSERT INTO `admin_role_permissions` VALUES (1,1,'2025-10-29 07:41:44','2025-10-29 07:41:44'),(2,1,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_role_users`
--

DROP TABLE IF EXISTS `admin_role_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_role_users` (
  `role_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `admin_role_users_role_id_user_id_index` (`role_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_role_users`
--

LOCK TABLES `admin_role_users` WRITE;
/*!40000 ALTER TABLE `admin_role_users` DISABLE KEYS */;
INSERT INTO `admin_role_users` VALUES (1,1,NULL,NULL),(2,2,NULL,NULL),(2,151,NULL,NULL),(2,150,NULL,NULL),(1,6,NULL,NULL),(2,181,NULL,NULL),(2,183,NULL,NULL),(2,188,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_roles`
--

DROP TABLE IF EXISTS `admin_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_roles_name_unique` (`name`),
  UNIQUE KEY `admin_roles_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_roles`
--

LOCK TABLES `admin_roles` WRITE;
/*!40000 ALTER TABLE `admin_roles` DISABLE KEYS */;
INSERT INTO `admin_roles` VALUES (1,'Super Admin','admin',NULL,'2025-11-12 04:23:39'),(2,'System Manager','manager','2025-11-12 04:23:19','2025-11-12 04:23:19');
/*!40000 ALTER TABLE `admin_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_user_permissions`
--

DROP TABLE IF EXISTS `admin_user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `admin_user_permissions_user_id_permission_id_index` (`user_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_user_permissions`
--

LOCK TABLES `admin_user_permissions` WRITE;
/*!40000 ALTER TABLE `admin_user_permissions` DISABLE KEYS */;
INSERT INTO `admin_user_permissions` VALUES (1,1,NULL,NULL);
/*!40000 ALTER TABLE `admin_user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `affiliate_commissions`
--

DROP TABLE IF EXISTS `affiliate_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `affiliate_commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commission` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'comission in percentage',
  `date_added` date NOT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `affiliate_commissions`
--

LOCK TABLES `affiliate_commissions` WRITE;
/*!40000 ALTER TABLE `affiliate_commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `affiliate_commissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `affiliate_transactions`
--

DROP TABLE IF EXISTS `affiliate_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `affiliate_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) DEFAULT NULL,
  `affiliate` int(11) NOT NULL,
  `discount` int(11) NOT NULL,
  `temporary_id` int(11) DEFAULT NULL,
  `date_added` date NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='affiliate_transactions';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `affiliate_transactions`
--

LOCK TABLES `affiliate_transactions` WRITE;
/*!40000 ALTER TABLE `affiliate_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `affiliate_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `affiliate_withdraws`
--

DROP TABLE IF EXISTS `affiliate_withdraws`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `affiliate_withdraws` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `date_of_request` datetime NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` smallint(6) NOT NULL DEFAULT '0' COMMENT '0 is pending, 1 is approved, 3 is paid',
  `comment` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `affiliate_withdraws`
--

LOCK TABLES `affiliate_withdraws` WRITE;
/*!40000 ALTER TABLE `affiliate_withdraws` DISABLE KEYS */;
/*!40000 ALTER TABLE `affiliate_withdraws` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_version`
--

DROP TABLE IF EXISTS `app_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_version` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` int(11) NOT NULL DEFAULT '0',
  `date_added` date NOT NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_version`
--

LOCK TABLES `app_version` WRITE;
/*!40000 ALTER TABLE `app_version` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_version` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `basic_info`
--

DROP TABLE IF EXISTS `basic_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `basic_info` (
  `names` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dob` date NOT NULL,
  `theme` tinyint(1) NOT NULL,
  `gender` tinyint(1) NOT NULL,
  `continent` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timezone` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 is admin, 0 is client',
  `mail` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 is when welcome mail is not sent. 1 is sent welcome mail',
  `boo_cash` tinyint(1) NOT NULL DEFAULT '0',
  `affiliate_discount` tinyint(4) NOT NULL DEFAULT '0',
  `supplier` tinyint(1) NOT NULL DEFAULT '0',
  `user` int(11) NOT NULL AUTO_INCREMENT,
  `password` char(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified` char(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `_timstamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `basic_info`
--

LOCK TABLES `basic_info` WRITE;
/*!40000 ALTER TABLE `basic_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `basic_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_heads`
--

DROP TABLE IF EXISTS `chat_heads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_heads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_name` text COLLATE utf8mb4_unicode_ci,
  `product_photo` text COLLATE utf8mb4_unicode_ci,
  `product_owner_id` int(11) DEFAULT NULL,
  `product_owner_name` text COLLATE utf8mb4_unicode_ci,
  `product_owner_photo` text COLLATE utf8mb4_unicode_ci,
  `product_owner_last_seen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` text COLLATE utf8mb4_unicode_ci,
  `customer_photo` text COLLATE utf8mb4_unicode_ci,
  `customer_last_seen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_message_body` text COLLATE utf8mb4_unicode_ci,
  `last_message_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_message_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_heads`
--

LOCK TABLES `chat_heads` WRITE;
/*!40000 ALTER TABLE `chat_heads` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_heads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `chat_head_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned NOT NULL,
  `receiver_id` bigint(20) unsigned NOT NULL,
  `sender_name` text COLLATE utf8mb4_unicode_ci,
  `sender_photo` text COLLATE utf8mb4_unicode_ci,
  `receiver_name` text COLLATE utf8mb4_unicode_ci,
  `receiver_photo` text COLLATE utf8mb4_unicode_ci,
  `body` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audio` text COLLATE utf8mb4_unicode_ci,
  `video` text COLLATE utf8mb4_unicode_ci,
  `document` text COLLATE utf8mb4_unicode_ci,
  `photo` text COLLATE utf8mb4_unicode_ci,
  `longitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_addresses`
--

DROP TABLE IF EXISTS `delivery_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `shipping_cost` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_addresses`
--

LOCK TABLES `delivery_addresses` WRITE;
/*!40000 ALTER TABLE `delivery_addresses` DISABLE KEYS */;
INSERT INTO `delivery_addresses` VALUES (1,'2025-11-14 18:49:30','2025-11-14 18:49:41','Kasese',NULL,NULL,10000.00);
/*!40000 ALTER TABLE `delivery_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disbursements`
--

DROP TABLE IF EXISTS `disbursements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disbursements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL COMMENT 'Total amount to be disbursed to investors',
  `disbursement_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by_id` bigint(20) unsigned NOT NULL COMMENT 'Admin user who created the disbursement',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disbursements_project_id_index` (`project_id`),
  KEY `disbursements_created_by_id_index` (`created_by_id`),
  KEY `disbursements_disbursement_date_index` (`disbursement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disbursements`
--

LOCK TABLES `disbursements` WRITE;
/*!40000 ALTER TABLE `disbursements` DISABLE KEYS */;
/*!40000 ALTER TABLE `disbursements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `districts`
--

DROP TABLE IF EXISTS `districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `districts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country` int(11) NOT NULL DEFAULT '1',
  `district` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `region` tinyint(4) NOT NULL DEFAULT '4',
  `delivery_amount` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `districts`
--

LOCK TABLES `districts` WRITE;
/*!40000 ALTER TABLE `districts` DISABLE KEYS */;
/*!40000 ALTER TABLE `districts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dtehm_memberships`
--

DROP TABLE IF EXISTS `dtehm_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dtehm_memberships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '76000.00',
  `status` enum('PENDING','CONFIRMED','FAILED','REFUNDED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `payment_method` enum('CASH','MOBILE_MONEY','BANK_TRANSFER','PESAPAL') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_account_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `membership_type` enum('DTEHM') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DTEHM',
  `expiry_date` date DEFAULT NULL,
  `receipt_photo` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `pesapal_merchant_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_tracking_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_payment_status_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_payment_status_description` text COLLATE utf8mb4_unicode_ci,
  `confirmation_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `universal_payment_id` bigint(20) DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  `confirmed_by` bigint(20) DEFAULT NULL,
  `registered_by_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dtehm_memberships_payment_reference_unique` (`payment_reference`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dtehm_memberships`
--

LOCK TABLES `dtehm_memberships` WRITE;
/*!40000 ALTER TABLE `dtehm_memberships` DISABLE KEYS */;
INSERT INTO `dtehm_memberships` VALUES (1,154,'DTEHM-6B7079E6-73',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18 18:28:45','2025-11-18 18:28:45','DTEHM',NULL,NULL,'Manually created for testing',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-18 18:28:45','2025-11-18 18:28:45',NULL),(2,156,'DTEHM-687A9CEF-72',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18 18:38:05','2025-11-18 18:38:05','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-18 18:38:05','2025-11-18 18:38:05',NULL),(3,155,'DTEHM-1665AFFD-30',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18 18:45:01','2025-11-18 18:45:01','DTEHM',NULL,NULL,'Auto-created by admin  via web portal during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-18 18:45:01','2025-11-18 18:45:01',NULL),(4,157,'DTEHM-CCEB0603-64',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18 18:49:36','2025-11-18 18:49:36','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-18 18:49:36','2025-11-18 18:49:36',NULL),(5,152,'DTEHM-18866864-18',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18 18:54:12','2025-11-18 18:54:12','DTEHM',NULL,NULL,'Auto-created by admin admin@gmail.com via web portal during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-18 18:54:12','2025-11-18 18:54:12',NULL),(6,151,'DTEHM-E7B732E2-21',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18 18:54:12','2025-11-18 18:54:12','DTEHM',NULL,NULL,'Auto-created by admin admin@gmail.com via web portal during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-18 18:54:12','2025-11-18 18:54:12',NULL),(7,153,'DTEHM-3E8AC16B-14',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18 18:55:37','2025-11-18 18:55:37','DTEHM',NULL,NULL,'Auto-created by admin admin@gmail.com via web portal during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-18 18:55:37','2025-11-18 18:55:37',NULL),(8,162,'DTEHM-CE4C8B2E-32',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-23 16:56:29','2025-11-23 16:56:29','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-23 16:56:29','2025-11-23 16:56:29',NULL),(9,3,'DTEHM-2C153F47-29',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-23 17:20:01','2025-11-23 17:20:01','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-23 17:20:01','2025-11-23 17:23:47','2025-11-23 17:23:47'),(10,3,'DTEHM-517777A7-53',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-23 17:23:49','2025-11-23 17:23:49','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-23 17:23:49','2025-11-23 17:30:12','2025-11-23 17:30:12'),(11,3,'DTEHM-F23A2FDE-94',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-23 17:30:13','2025-11-23 17:30:13','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-23 17:30:13','2025-11-23 17:30:13',NULL),(12,2,'UNI-PAY-1764010658-UIXVVF',300.00,'CONFIRMED','PESAPAL','+256706638484',NULL,'2025-11-24 15:57:38','2025-11-24 15:57:38','DTEHM',NULL,NULL,NULL,'DTEHM Membership Payment',NULL,NULL,NULL,NULL,NULL,5,2,NULL,2,NULL,'2025-11-24 15:57:38','2025-11-24 15:57:38',NULL),(13,2,'UNI-PAY-1764011319-CQUMRX',300.00,'CONFIRMED','PESAPAL','+256706638484',NULL,'2025-11-24 16:08:39','2025-11-24 16:08:39','DTEHM',NULL,NULL,NULL,'DTEHM Membership Payment',NULL,NULL,NULL,NULL,NULL,6,2,NULL,2,NULL,'2025-11-24 16:08:39','2025-11-24 16:08:39',NULL),(14,2,'UNI-PAY-1764011610-BKIE14',300.00,'CONFIRMED','PESAPAL','+256706638484',NULL,'2025-11-24 16:13:30','2025-11-24 16:13:30','DTEHM',NULL,NULL,NULL,'DTEHM Membership Payment',NULL,NULL,NULL,NULL,NULL,7,2,NULL,2,NULL,'2025-11-24 16:13:30','2025-11-24 16:13:30',NULL),(15,2,'UNI-PAY-1764011925-JFW8SR',300.00,'CONFIRMED','PESAPAL','+256706638484',NULL,'2025-11-24 16:18:45','2025-11-24 16:18:45','DTEHM',NULL,NULL,NULL,'DTEHM Membership Payment',NULL,NULL,NULL,NULL,NULL,8,2,NULL,2,NULL,'2025-11-24 16:18:45','2025-11-24 16:18:45',NULL),(16,2,'UNI-PAY-1764012031-SPPIZM',300.00,'CONFIRMED','PESAPAL','+256706638484',NULL,'2025-11-24 16:20:31','2025-11-24 16:20:31','DTEHM',NULL,NULL,NULL,'DTEHM Membership Payment',NULL,NULL,NULL,NULL,NULL,9,2,NULL,2,NULL,'2025-11-24 16:20:31','2025-11-24 16:20:31',NULL),(19,2,'UNI-PAY-1764012325-QY0LJ7',300.00,'CONFIRMED','PESAPAL','+256706638484',NULL,'2025-11-24 16:25:25','2025-11-24 16:25:25','DTEHM',NULL,NULL,NULL,'DTEHM Membership Payment',NULL,NULL,NULL,NULL,NULL,12,2,NULL,2,NULL,'2025-11-24 16:25:25','2025-11-24 16:25:25',NULL),(20,2,'UNI-PAY-1764012343-TPIYVT',200.00,'CONFIRMED','PESAPAL','+256706638484',NULL,'2025-11-24 16:25:43','2025-11-24 16:25:43','DTEHM',NULL,NULL,NULL,'DIP Membership Payment',NULL,NULL,NULL,NULL,NULL,13,2,NULL,2,NULL,'2025-11-24 16:25:43','2025-11-24 16:25:43',NULL),(21,2,'UNI-PAY-1764012453-8AMWQR',500.00,'CONFIRMED','PESAPAL','+256706638484',NULL,'2025-11-24 16:27:33','2025-11-24 16:27:33','DTEHM',NULL,NULL,NULL,'DTEHM + DIP Membership Payment',NULL,NULL,NULL,NULL,NULL,14,2,NULL,2,NULL,'2025-11-24 16:27:33','2025-11-24 16:27:33',NULL),(22,180,'DTEHM-C411921A-37',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-26 16:30:08','2025-11-26 16:30:08','DTEHM',NULL,NULL,'Auto-created by admin admin@gmail.com via web portal during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-26 16:30:08','2025-11-26 16:30:08',NULL),(23,6,'DTEHM-BAD2DA38-31',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-26 16:53:12','2025-11-26 16:53:12','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-11-26 16:53:12','2025-11-26 16:53:12',NULL),(24,181,'DTEHM-2C9EF87A-45',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-01 18:52:50','2025-12-01 18:52:50','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-12-01 18:52:50','2025-12-01 18:52:50',NULL),(25,181,'DTEHM-451FD0D4-6',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-04 20:19:11','2025-12-04 20:19:11','DTEHM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,NULL,'2025-12-04 20:19:11','2025-12-04 20:19:11',NULL),(26,180,'DTEHM-EE52DC89-93',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-04 20:20:13',NULL,'DTEHM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,'2025-12-04 20:20:13','2025-12-04 20:20:13',NULL),(27,179,'DTEHM-A559C075-92',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-04 20:21:34',NULL,'DTEHM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-04 20:21:34','2025-12-04 20:21:34',NULL),(28,181,'DTEHM-6F96AAD4-66',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-04 20:22:38',NULL,'DTEHM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-04 20:22:38','2025-12-04 20:22:38',NULL),(29,183,'DTEHM-D7A39DBC-36',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-05 07:55:09','2025-12-05 07:55:09','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-12-05 07:55:09','2025-12-05 07:55:09',NULL),(30,185,'DTEHM-F39556B4-41',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-05 11:09:56','2025-12-05 11:09:56','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-12-05 11:09:56','2025-12-05 11:09:56',NULL),(31,186,'DTEHM-C406E778-57',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-05 11:26:54','2025-12-05 11:26:54','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-12-05 11:26:54','2025-12-05 11:26:54',NULL),(32,187,'DTEHM-EA998D7C-57',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-05 11:35:05','2025-12-05 11:35:05','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-12-05 11:35:05','2025-12-05 11:35:05',NULL),(33,188,'DTEHM-89326C6C-43',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-05 11:44:17','2025-12-05 11:44:17','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-12-05 11:44:17','2025-12-05 11:44:17',NULL),(34,1,'DTEHM-D1563D7E-63',76000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-05 14:39:50','2025-12-05 14:39:50','DTEHM',NULL,NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,1,'2025-12-05 14:39:50','2025-12-05 14:39:50',NULL);
/*!40000 ALTER TABLE `dtehm_memberships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fcm_tokens`
--

DROP TABLE IF EXISTS `fcm_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fcm_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_added` date NOT NULL,
  `device` mediumtext COLLATE utf8mb4_unicode_ci,
  `user` int(11) DEFAULT NULL,
  `auth_token` char(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fcm_tokens`
--

LOCK TABLES `fcm_tokens` WRITE;
/*!40000 ALTER TABLE `fcm_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `fcm_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forgot_password`
--

DROP TABLE IF EXISTS `forgot_password`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forgot_password` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `password` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `password` (`password`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forgot_password`
--

LOCK TABLES `forgot_password` WRITE;
/*!40000 ALTER TABLE `forgot_password` DISABLE KEYS */;
/*!40000 ALTER TABLE `forgot_password` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gens`
--

DROP TABLE IF EXISTS `gens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `class_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `use_db_table` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `table_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fields` text COLLATE utf8mb4_unicode_ci,
  `file_id` text COLLATE utf8mb4_unicode_ci,
  `end_point` varchar(355) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gens`
--

LOCK TABLES `gens` WRITE;
/*!40000 ALTER TABLE `gens` DISABLE KEYS */;
/*!40000 ALTER TABLE `gens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `administrator_id` bigint(20) unsigned DEFAULT NULL,
  `src` text COLLATE utf8mb4_unicode_ci,
  `thumbnail` text COLLATE utf8mb4_unicode_ci,
  `parent_id` int(11) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `deleted_at` date DEFAULT NULL,
  `type` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `parent_endpoint` text COLLATE utf8mb4_unicode_ci,
  `note` text COLLATE utf8mb4_unicode_ci,
  `is_processed` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'No',
  `parent_local_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `images`
--

LOCK TABLES `images` WRITE;
/*!40000 ALTER TABLE `images` DISABLE KEYS */;
/*!40000 ALTER TABLE `images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insurance_programs`
--

DROP TABLE IF EXISTS `insurance_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insurance_programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `coverage_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Maximum coverage/benefit amount',
  `premium_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Amount to pay per billing cycle',
  `billing_frequency` enum('Weekly','Monthly','Quarterly','Annually') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Monthly',
  `billing_day` int(11) NOT NULL DEFAULT '1' COMMENT 'Day of week (1-7) or month (1-31) for billing',
  `duration_months` int(11) NOT NULL DEFAULT '12' COMMENT 'Program duration in months',
  `grace_period_days` int(11) NOT NULL DEFAULT '7' COMMENT 'Days allowed for late payment',
  `late_payment_penalty` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Penalty for late payment',
  `penalty_type` enum('Fixed','Percentage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Fixed',
  `min_age` int(11) NOT NULL DEFAULT '18',
  `max_age` int(11) NOT NULL DEFAULT '70',
  `requirements` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of requirements',
  `benefits` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of benefits',
  `status` enum('Active','Inactive','Suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `start_date` date DEFAULT NULL COMMENT 'Program availability start date',
  `end_date` date DEFAULT NULL COMMENT 'Program availability end date',
  `total_subscribers` int(11) NOT NULL DEFAULT '0',
  `total_premiums_collected` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_premiums_expected` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_premiums_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `terms_and_conditions` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Icon identifier for UI',
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Color code for UI',
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `insurance_programs_status_index` (`status`),
  KEY `insurance_programs_billing_frequency_index` (`billing_frequency`),
  KEY `insurance_programs_start_date_index` (`start_date`),
  KEY `insurance_programs_end_date_index` (`end_date`),
  KEY `insurance_programs_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insurance_programs`
--

LOCK TABLES `insurance_programs` WRITE;
/*!40000 ALTER TABLE `insurance_programs` DISABLE KEYS */;
INSERT INTO `insurance_programs` VALUES (1,'Comprehensive Health Insurance','Complete medical coverage for critical health conditions including heart disease, cancer, stroke, epilepsy, and accidents. Includes innovative investment benefits through events management equipment portfolio with profit sharing and free equipment access for members.',50000000.00,16000.00,'Monthly',1,12,7,2000.00,'Fixed',18,70,'\"[\\\"Valid national identification (ID or Passport)\\\",\\\"Completed health questionnaire\\\",\\\"Age between 18 and 70 years\\\",\\\"Ugandan resident or valid work permit\\\",\\\"Mobile phone number for notifications\\\",\\\"Ability to pay UGX 16,000 monthly premium\\\"]\"','\"[\\\"Medical Coverage - Heart Diseases (coronary artery disease, heart attacks, hypertension)\\\",\\\"Medical Coverage - Cancer (screening, treatment, chemotherapy, radiation therapy)\\\",\\\"Medical Coverage - Stroke (emergency response, rehabilitation, recovery support)\\\",\\\"Medical Coverage - Epilepsy (seizure management, medication, neurological care)\\\",\\\"Medical Coverage - Accidents & Injuries (emergency care, surgery, hospitalization)\\\",\\\"FREE Events Equipment Access (sound systems, chairs, tents, decorations)\\\",\\\"Bi-annual Profit Sharing (from equipment rentals to non-members)\\\",\\\"Priority Equipment Booking (weddings, meetings, ceremonies)\\\",\\\"Community Investment Returns (UGX 8,000\\\\\\/month invested in events portfolio)\\\",\\\"No Rental Fees (unlimited equipment usage during subscription)\\\",\\\"Financial Protection (coverage up to UGX 50M for medical expenses)\\\",\\\"Monthly Payment Flexibility (affordable UGX 16,000\\\\\\/month)\\\"]\"','Active','2025-07-01','2026-06-30',2,32000.00,384000.00,352000.00,'This Health Insurance program provides comprehensive medical coverage for specified conditions including heart diseases, cancer, stroke, epilepsy, and accidents/injuries. Monthly premium of UGX 16,000 includes UGX 8,000 for medical coverage and UGX 8,000 invested in DTEHM events management portfolio. Active subscribers receive FREE access to all events equipment (sound systems, chairs, tents, publicity materials) for personal use. Equipment is provided on first-come, first-served basis subject to availability. Non-members pay standard rental rates, with all profits distributed to insurance members bi-annually or annually. Coverage activates upon enrollment and first premium payment. Minimum subscription period is 12 months. Late payments subject to UGX 2,000 penalty after 7-day grace period. Medical coverage subject to program terms, conditions, and exclusions. Pre-existing conditions may have waiting periods. Claims require proper documentation and medical verification. Profit distribution based on actual business performance and is not guaranteed. Equipment usage subject to booking policies and availability. Subscriber responsible for equipment damage or loss. Program runs from July 2025 through June 2026. Early cancellation may result in forfeiture of benefits. Full terms available in subscription agreement.','insurance/health-insurance-icon.png','#05179F','1','1','2025-11-12 06:54:58','2025-12-05 13:20:16',NULL);
/*!40000 ALTER TABLE `insurance_programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insurance_subscription_payments`
--

DROP TABLE IF EXISTS `insurance_subscription_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insurance_subscription_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `insurance_subscription_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `insurance_program_id` bigint(20) unsigned NOT NULL,
  `period_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., OCTOBER-2025, 2025-10-27',
  `period_start_date` date NOT NULL,
  `period_end_date` date NOT NULL,
  `year` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `month_number` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `week_number` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_frequency` enum('Weekly','Monthly','Quarterly','Annually') COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Expected payment amount',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Actual amount paid',
  `penalty_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Late payment penalty',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Amount + Penalty',
  `payment_status` enum('Pending','Paid','Partial','Overdue','Waived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `payment_date` date DEFAULT NULL,
  `overdue_date` date DEFAULT NULL COMMENT 'Date when payment became overdue',
  `days_overdue` int(11) NOT NULL DEFAULT '0',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coverage_affected` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No' COMMENT 'Did non-payment affect coverage',
  `coverage_suspended_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User who processed payment',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_subscription_period` (`insurance_subscription_id`,`period_name`),
  KEY `insurance_subscription_payments_insurance_subscription_id_index` (`insurance_subscription_id`),
  KEY `insurance_subscription_payments_insurance_user_id_index` (`user_id`),
  KEY `insurance_subscription_payments_insurance_program_id_index` (`insurance_program_id`),
  KEY `insurance_subscription_payments_payment_status_index` (`payment_status`),
  KEY `insurance_subscription_payments_due_date_index` (`due_date`),
  KEY `insurance_subscription_payments_payment_date_index` (`payment_date`),
  KEY `insurance_subscription_payments_period_name_index` (`period_name`),
  KEY `insurance_subscription_payments_year_index` (`year`),
  KEY `insurance_subscription_payments_month_number_index` (`month_number`),
  KEY `insurance_subscription_payments_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insurance_subscription_payments`
--

LOCK TABLES `insurance_subscription_payments` WRITE;
/*!40000 ALTER TABLE `insurance_subscription_payments` DISABLE KEYS */;
INSERT INTO `insurance_subscription_payments` VALUES (1,1,180,1,'NOVEMBER-2025','2025-11-24','2025-12-23','2025','11','48','Monthly','2025-11-24',16000.00,0.00,0.00,16000.00,'Overdue',NULL,'2025-11-25',0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - NOVEMBER-2025',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(2,1,180,1,'DECEMBER-2025','2025-12-24','2026-01-23','2025','12','52','Monthly','2025-12-24',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - DECEMBER-2025',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(3,1,180,1,'JANUARY-2026','2026-01-24','2026-02-23','2026','01','04','Monthly','2026-01-24',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - JANUARY-2026',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(4,1,180,1,'FEBRUARY-2026','2026-02-24','2026-03-23','2026','02','09','Monthly','2026-02-24',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - FEBRUARY-2026',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(5,1,180,1,'MARCH-2026','2026-03-24','2026-04-23','2026','03','13','Monthly','2026-03-24',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - MARCH-2026',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(6,1,180,1,'APRIL-2026','2026-04-24','2026-05-23','2026','04','17','Monthly','2026-04-24',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - APRIL-2026',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(7,1,180,1,'MAY-2026','2026-05-24','2026-06-23','2026','05','21','Monthly','2026-05-24',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - MAY-2026',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(8,1,180,1,'JUNE-2026','2026-06-24','2026-07-23','2026','06','26','Monthly','2026-06-24',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - JUNE-2026',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(9,1,180,1,'JULY-2026','2026-07-24','2026-08-23','2026','07','30','Monthly','2026-07-24',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - JULY-2026',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(10,1,180,1,'AUGUST-2026','2026-08-24','2026-09-23','2026','08','35','Monthly','2026-08-24',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - AUGUST-2026',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(11,1,180,1,'SEPTEMBER-2026','2026-09-24','2026-10-23','2026','09','39','Monthly','2026-09-24',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - SEPTEMBER-2026',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(12,1,180,1,'OCTOBER-2026','2026-10-24','2026-11-23','2026','10','43','Monthly','2026-10-24',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - OCTOBER-2026',NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(13,2,2,1,'DECEMBER-2025','2025-12-05','2026-01-04','2025','12','49','Monthly','2025-12-05',16000.00,16000.00,0.00,16000.00,'Paid','2025-12-05','2025-12-06',0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - DECEMBER-2025',NULL,NULL,'1',NULL,'2025-12-05 13:18:38','2025-12-05 13:20:16',NULL),(14,2,2,1,'JANUARY-2026','2026-01-05','2026-02-04','2026','01','02','Monthly','2026-01-05',16000.00,16000.00,0.00,16000.00,'Paid','2025-12-05',NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - JANUARY-2026',NULL,NULL,'1',NULL,'2025-12-05 13:18:38','2025-12-05 13:19:33',NULL),(15,2,2,1,'FEBRUARY-2026','2026-02-05','2026-03-04','2026','02','06','Monthly','2026-02-05',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - FEBRUARY-2026',NULL,NULL,NULL,NULL,'2025-12-05 13:18:38','2025-12-05 13:18:38',NULL),(16,2,2,1,'MARCH-2026','2026-03-05','2026-04-04','2026','03','10','Monthly','2026-03-05',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - MARCH-2026',NULL,NULL,NULL,NULL,'2025-12-05 13:18:38','2025-12-05 13:18:38',NULL),(17,2,2,1,'APRIL-2026','2026-04-05','2026-05-04','2026','04','14','Monthly','2026-04-05',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - APRIL-2026',NULL,NULL,NULL,NULL,'2025-12-05 13:18:38','2025-12-05 13:18:38',NULL),(18,2,2,1,'MAY-2026','2026-05-05','2026-06-04','2026','05','19','Monthly','2026-05-05',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - MAY-2026',NULL,NULL,NULL,NULL,'2025-12-05 13:18:38','2025-12-05 13:18:38',NULL),(19,2,2,1,'JUNE-2026','2026-06-05','2026-07-04','2026','06','23','Monthly','2026-06-05',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - JUNE-2026',NULL,NULL,NULL,NULL,'2025-12-05 13:18:38','2025-12-05 13:18:38',NULL),(20,2,2,1,'JULY-2026','2026-07-05','2026-08-04','2026','07','27','Monthly','2026-07-05',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - JULY-2026',NULL,NULL,NULL,NULL,'2025-12-05 13:18:38','2025-12-05 13:18:38',NULL),(21,2,2,1,'AUGUST-2026','2026-08-05','2026-09-04','2026','08','32','Monthly','2026-08-05',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - AUGUST-2026',NULL,NULL,NULL,NULL,'2025-12-05 13:18:38','2025-12-05 13:18:38',NULL),(22,2,2,1,'SEPTEMBER-2026','2026-09-05','2026-10-04','2026','09','36','Monthly','2026-09-05',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - SEPTEMBER-2026',NULL,NULL,NULL,NULL,'2025-12-05 13:18:38','2025-12-05 13:18:38',NULL),(23,2,2,1,'OCTOBER-2026','2026-10-05','2026-11-04','2026','10','41','Monthly','2026-10-05',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - OCTOBER-2026',NULL,NULL,NULL,NULL,'2025-12-05 13:18:38','2025-12-05 13:18:38',NULL),(24,2,2,1,'NOVEMBER-2026','2026-11-05','2026-12-04','2026','11','45','Monthly','2026-11-05',16000.00,0.00,0.00,16000.00,'Pending',NULL,NULL,0,NULL,NULL,NULL,'No',NULL,'Comprehensive Health Insurance - NOVEMBER-2026',NULL,NULL,NULL,NULL,'2025-12-05 13:18:39','2025-12-05 13:18:39',NULL);
/*!40000 ALTER TABLE `insurance_subscription_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insurance_subscriptions`
--

DROP TABLE IF EXISTS `insurance_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insurance_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `insurance_program_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `next_billing_date` date DEFAULT NULL,
  `status` enum('Active','Suspended','Cancelled','Expired','Pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `payment_status` enum('Current','Late','Defaulted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Current',
  `coverage_status` enum('Active','Suspended','Terminated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `coverage_start_date` date DEFAULT NULL,
  `coverage_end_date` date DEFAULT NULL,
  `premium_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Subscription premium per cycle',
  `total_expected` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Total expected premium payments',
  `total_paid` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Total amount paid',
  `total_balance` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Outstanding balance',
  `payments_completed` int(11) NOT NULL DEFAULT '0',
  `payments_pending` int(11) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `beneficiaries` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of beneficiaries',
  `policy_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Generated policy number',
  `prepared` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No' COMMENT 'Payment records generated',
  `suspended_date` date DEFAULT NULL,
  `cancelled_date` date DEFAULT NULL,
  `suspension_reason` text COLLATE utf8mb4_unicode_ci,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `insurance_subscriptions_policy_number_unique` (`policy_number`),
  KEY `insurance_subscriptions_insurance_user_id_index` (`user_id`),
  KEY `insurance_subscriptions_insurance_program_id_index` (`insurance_program_id`),
  KEY `insurance_subscriptions_status_index` (`status`),
  KEY `insurance_subscriptions_payment_status_index` (`payment_status`),
  KEY `insurance_subscriptions_coverage_status_index` (`coverage_status`),
  KEY `insurance_subscriptions_start_date_index` (`start_date`),
  KEY `insurance_subscriptions_end_date_index` (`end_date`),
  KEY `insurance_subscriptions_next_billing_date_index` (`next_billing_date`),
  KEY `insurance_subscriptions_policy_number_index` (`policy_number`),
  KEY `insurance_subscriptions_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insurance_subscriptions`
--

LOCK TABLES `insurance_subscriptions` WRITE;
/*!40000 ALTER TABLE `insurance_subscriptions` DISABLE KEYS */;
INSERT INTO `insurance_subscriptions` VALUES (1,180,1,'2025-11-24','2026-11-24',NULL,'Active','Current','Active','2025-11-24','2026-11-24',16000.00,192000.00,0.00,192000.00,0,12,'Enrolled via mobile app',NULL,'POL-6924BB025C015','Yes',NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:07:30','2025-11-24 17:07:30',NULL),(2,2,1,'2025-12-05','2026-12-05','2026-02-05','Active','Late','Active','2025-12-05','2026-12-05',16000.00,192000.00,32000.00,160000.00,2,10,'some notes','\"some maessage\"','POL-693305DED3486','Yes',NULL,NULL,NULL,NULL,'1','1','2025-12-05 13:18:38','2025-12-05 13:20:16',NULL);
/*!40000 ALTER TABLE `insurance_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mail_subscription`
--

DROP TABLE IF EXISTS `mail_subscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mail_subscription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` char(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mail_subscription`
--

LOCK TABLES `mail_subscription` WRITE;
/*!40000 ALTER TABLE `mail_subscription` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_subscription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medical_service_requests`
--

DROP TABLE IF EXISTS `medical_service_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medical_service_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `insurance_subscription_id` bigint(20) unsigned DEFAULT NULL,
  `service_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urgency_level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `symptoms_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `additional_notes` text COLLATE utf8mb4_unicode_ci,
  `preferred_hospital` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_doctor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` time DEFAULT NULL,
  `contact_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_address` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_feedback` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `assigned_hospital` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_doctor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `appointment_details` text COLLATE utf8mb4_unicode_ci,
  `estimated_cost` decimal(15,2) DEFAULT NULL,
  `insurance_coverage` decimal(15,2) DEFAULT NULL,
  `patient_payment` decimal(15,2) DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medical_service_requests_reference_number_unique` (`reference_number`),
  KEY `medical_service_requests_user_id_index` (`user_id`),
  KEY `medical_service_requests_insurance_subscription_id_index` (`insurance_subscription_id`),
  KEY `medical_service_requests_reviewed_by_index` (`reviewed_by`),
  KEY `medical_service_requests_status_index` (`status`),
  KEY `medical_service_requests_service_type_index` (`service_type`),
  KEY `medical_service_requests_urgency_level_index` (`urgency_level`),
  KEY `medical_service_requests_reference_number_index` (`reference_number`),
  KEY `medical_service_requests_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medical_service_requests`
--

LOCK TABLES `medical_service_requests` WRITE;
/*!40000 ALTER TABLE `medical_service_requests` DISABLE KEYS */;
INSERT INTO `medical_service_requests` VALUES (1,180,NULL,'prescription',NULL,'urgent','some mesage will go here','some massage will go here','Test hosp',NULL,'2025-11-26','15:09:00','07783204665',NULL,NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'MSR-4FAODVYXFL','127.0.0.1','Dart/3.8 (dart:io)',NULL,'2025-11-24 17:10:36','2025-11-24 17:10:36');
/*!40000 ALTER TABLE `medical_service_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membership_payments`
--

DROP TABLE IF EXISTS `membership_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membership_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '20000.00',
  `status` enum('PENDING','CONFIRMED','FAILED','REFUNDED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_account_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `membership_type` enum('LIFE','ANNUAL','MONTHLY') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LIFE',
  `expiry_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `receipt_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_order_tracking_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_merchant_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_response` text COLLATE utf8mb4_unicode_ci,
  `confirmation_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `universal_payment_id` bigint(20) DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  `confirmed_by` bigint(20) DEFAULT NULL,
  `registered_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `membership_payments_payment_reference_unique` (`payment_reference`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership_payments`
--

LOCK TABLES `membership_payments` WRITE;
/*!40000 ALTER TABLE `membership_payments` DISABLE KEYS */;
INSERT INTO `membership_payments` VALUES (1,155,'MEM-691CE8DD36D14-155',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18',NULL,'LIFE',NULL,'Auto-created by admin  via web portal during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-11-18 18:45:01','2025-11-18 19:14:02',NULL),(2,157,'MEM-691CE9F084EA0-157',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18',NULL,'LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,1,'2025-11-18 18:49:36','2025-11-18 19:14:02',NULL),(3,154,'MEM-691CEAA74F2A2-154',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18',NULL,'LIFE',NULL,'Auto-created by admin admin@gmail.com via web portal during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-11-18 18:52:39','2025-11-18 19:14:02',NULL),(4,152,'MEM-691CEB04F16FC-152',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18',NULL,'LIFE',NULL,'Auto-created by admin admin@gmail.com via web portal during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-11-18 18:54:12','2025-11-18 19:14:02',NULL),(5,151,'MEM-691CEB04F3225-151',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18',NULL,'LIFE',NULL,'Auto-created by admin admin@gmail.com via web portal during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-11-18 18:54:12','2025-11-18 19:14:02',NULL),(6,158,'MEM-691CEBB81208E-158',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18','2025-11-18 18:57:23','LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,1,1,'2025-11-18 18:57:12','2025-11-18 19:14:02',NULL),(7,159,'MEM-691CEC4691FA5-159',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18',NULL,'LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,1,'2025-11-18 18:59:34','2025-11-18 19:14:02',NULL),(8,160,'MEM-691CEF1039F27-160',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18',NULL,'LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,1,'2025-11-18 19:11:28','2025-11-18 19:14:02',NULL),(9,161,'MEM-691CF0085E6D5-161',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-18',NULL,'LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,1,'2025-11-18 19:15:36','2025-11-18 19:15:36',NULL),(10,3,'MEM-69236C70CACAB-3',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-11-23',NULL,'LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,1,'2025-11-23 17:20:00','2025-11-23 17:20:00',NULL),(11,180,'UNI-PAY-1764009692-GMVKLJ',500.00,'CONFIRMED','PESAPAL','0772111117',NULL,'2025-11-24','2025-11-24 15:43:15','LIFE',NULL,'Membership payment for Kule Swaleh - LIFE','DTEHM + DIP Membership Fee - One-time payment',NULL,'df7b8ed0-912b-4592-ae3e-db0d33e2bd50','PAYMENT_3_1764009692',NULL,NULL,3,180,NULL,180,NULL,'2025-11-24 15:43:15','2025-11-24 15:43:15',NULL),(12,2,'MEM-692E0602487AD-2',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-01',NULL,'LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,1,'2025-12-01 18:17:54','2025-12-01 18:17:54',NULL),(13,13,'MEM-692E06209B393-13',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-01',NULL,'LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,1,'2025-12-01 18:18:24','2025-12-01 18:18:24',NULL),(14,183,'MEM-6932BA0D785D5-183',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-05',NULL,'LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,1,'2025-12-05 07:55:09','2025-12-05 07:55:09',NULL),(15,185,'MEM-6932E7B42F9E9-185',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-05',NULL,'LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,1,'2025-12-05 11:09:56','2025-12-05 11:09:56',NULL),(16,1,'MEM-693318E6A9203-1',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-05',NULL,'LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,1,'2025-12-05 14:39:50','2025-12-05 14:39:50',NULL),(17,188,'MEM-69331D252C47C-188',20000.00,'CONFIRMED','CASH',NULL,NULL,'2025-12-05',NULL,'LIFE',NULL,'Auto-created by Observer during user registration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,1,'2025-12-05 14:57:57','2025-12-05 14:57:57',NULL);
/*!40000 ALTER TABLE `membership_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2019_12_14_000001_create_personal_access_tokens_table',1),(2,'2025_11_14_072856_add_parents_to_users',1),(3,'2025_11_14_192145_add_dtehm_membership_fields_to_users',1),(4,'2025_11_14_194652_add_sponsor_id_to_users_table',1),(5,'2025_11_14_221232_add_unit_price_and_subtotal_to_ordered_items_table',2),(6,'2025_11_14_221319_add_receipt_number_and_notes_to_orders_table',3),(7,'2025_11_14_222837_add_missing_delivery_columns_to_orders_table',4),(8,'2025_11_15_061159_add_commission_fields_to_orders_table',5),(9,'2025_11_15_061214_add_commission_fields_to_ordered_items_table',5),(10,'2025_11_18_202836_create_dtehm_memberships_table',6),(11,'2025_11_18_202846_add_registered_by_id_to_users_table',7),(12,'2025_11_18_220757_add_registered_by_id_to_membership_payments_table',8),(13,'2025_11_23_210629_add_sponsor_and_stockist_to_ordered_items_table',9),(14,'2025_12_01_214554_add_product_commission_to_account_transactions_source_enum',10),(15,'2025_12_05_095202_add_commission_tracking_columns_to_account_transactions',11),(16,'2025_12_05_125017_add_points_to_products_table',12),(17,'2025_12_05_125044_add_points_earned_to_ordered_items_table',12),(18,'2025_12_05_125101_add_total_points_to_users_table',12);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_models`
--

DROP TABLE IF EXISTS `notification_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_models` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `target_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `template` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_users` json DEFAULT NULL,
  `target_segments` json DEFAULT NULL,
  `target_devices` json DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `onesignal_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipients` int(11) NOT NULL DEFAULT '0',
  `status` enum('pending','sent','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `delivery_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'immediate',
  `scheduled_at` datetime DEFAULT NULL,
  `recurring_pattern` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `data` json DEFAULT NULL,
  `send_after_time_passed` tinyint(1) NOT NULL DEFAULT '0',
  `ttl` int(11) DEFAULT NULL,
  `priority_countries` json DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `picture_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `large_icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `large_icon_upload` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `big_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `big_picture_upload` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `click_count` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_models`
--

LOCK TABLES `notification_models` WRITE;
/*!40000 ALTER TABLE `notification_models` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onesignal_devices`
--

DROP TABLE IF EXISTS `onesignal_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `onesignal_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `player_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mobile',
  `app_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_active` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `onesignal_devices_player_id_unique` (`player_id`),
  KEY `onesignal_devices_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onesignal_devices`
--

LOCK TABLES `onesignal_devices` WRITE;
/*!40000 ALTER TABLE `onesignal_devices` DISABLE KEYS */;
INSERT INTO `onesignal_devices` VALUES (1,1,'fa78437f-fc7a-4865-8adb-78eb5c7edc5a','mobile','1.0.0','2025-11-26 18:22:47','2025-11-26 18:17:11','2025-11-26 18:22:47');
/*!40000 ALTER TABLE `onesignal_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_keys`
--

DROP TABLE IF EXISTS `order_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(11) NOT NULL,
  `order_ref` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user` int(11) NOT NULL,
  `date_created` date NOT NULL,
  `response_data` mediumtext COLLATE utf8mb4_unicode_ci,
  `temporary_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_keys`
--

LOCK TABLES `order_keys` WRITE;
/*!40000 ALTER TABLE `order_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ordered_items`
--

DROP TABLE IF EXISTS `ordered_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ordered_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `order` int(11) DEFAULT NULL,
  `product` mediumtext COLLATE utf8mb4_unicode_ci,
  `sponsor_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stockist_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` mediumtext COLLATE utf8mb4_unicode_ci,
  `amount` mediumtext COLLATE utf8mb4_unicode_ci,
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Unit price at time of order',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Quantity * Unit Price',
  `item_is_paid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `item_paid_date` timestamp NULL DEFAULT NULL,
  `item_paid_amount` decimal(15,2) DEFAULT NULL,
  `has_detehm_seller` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `dtehm_seller_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dtehm_user_id` bigint(20) unsigned DEFAULT NULL,
  `stockist_user_id` bigint(20) unsigned DEFAULT NULL,
  `sponsor_user_id` bigint(20) unsigned DEFAULT NULL,
  `commission_is_processed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `commission_processed_date` timestamp NULL DEFAULT NULL,
  `total_commission_amount` decimal(15,2) DEFAULT NULL,
  `balance_after_commission` decimal(15,2) DEFAULT NULL,
  `commission_seller` decimal(15,2) DEFAULT NULL,
  `commission_stockist` decimal(10,2) NOT NULL DEFAULT '0.00',
  `commission_parent_1` decimal(15,2) DEFAULT NULL,
  `commission_parent_2` decimal(15,2) DEFAULT NULL,
  `commission_parent_3` decimal(15,2) DEFAULT NULL,
  `commission_parent_4` decimal(15,2) DEFAULT NULL,
  `commission_parent_5` decimal(15,2) DEFAULT NULL,
  `commission_parent_6` decimal(15,2) DEFAULT NULL,
  `commission_parent_7` decimal(15,2) DEFAULT NULL,
  `commission_parent_8` decimal(15,2) DEFAULT NULL,
  `commission_parent_9` decimal(15,2) DEFAULT NULL,
  `commission_parent_10` decimal(15,2) DEFAULT NULL,
  `points_earned` int(11) DEFAULT '0' COMMENT 'Points earned by sponsor for selling this product',
  `parent_1_user_id` bigint(20) unsigned DEFAULT NULL,
  `parent_2_user_id` bigint(20) unsigned DEFAULT NULL,
  `parent_3_user_id` bigint(20) unsigned DEFAULT NULL,
  `parent_4_user_id` bigint(20) unsigned DEFAULT NULL,
  `parent_5_user_id` bigint(20) unsigned DEFAULT NULL,
  `parent_6_user_id` bigint(20) unsigned DEFAULT NULL,
  `parent_7_user_id` bigint(20) unsigned DEFAULT NULL,
  `parent_8_user_id` bigint(20) unsigned DEFAULT NULL,
  `parent_9_user_id` bigint(20) unsigned DEFAULT NULL,
  `parent_10_user_id` bigint(20) unsigned DEFAULT NULL,
  `color` mediumtext COLLATE utf8mb4_unicode_ci,
  `size` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ordered_items`
--

LOCK TABLES `ordered_items` WRITE;
/*!40000 ALTER TABLE `ordered_items` DISABLE KEYS */;
INSERT INTO `ordered_items` VALUES (9,'2025-12-05 07:32:44','2025-12-05 07:32:44',NULL,'18','DTEHM20250003','DTEHM20250003','1','35000',35000.00,35000.00,'No',NULL,NULL,'Yes',NULL,6,6,6,'Yes','2025-12-05 07:32:44',6300.00,28700.00,NULL,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,'2025-12-05 10:04:36','2025-12-05 10:04:36',NULL,'1','DTEHM20259018','DTEHM20250001','2','850000',850000.00,1700000.00,'Yes','2025-12-05 10:04:36',1700000.00,'Yes',NULL,2,3,2,'Yes','2025-12-05 10:04:36',255000.00,1445000.00,NULL,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,'2025-12-05 11:37:28','2025-12-05 11:37:28',NULL,'15','DTEHM002','DTEHM002','1','1800000',1800000.00,1800000.00,'No',NULL,NULL,'Yes',NULL,187,187,187,'Yes','2025-12-05 11:37:28',324000.00,1476000.00,NULL,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(12,'2025-12-05 11:50:31','2025-12-05 11:50:31',NULL,'21','DIP0001','DTEHM20250001','1','100000',100000.00,100000.00,'No',NULL,NULL,'Yes',NULL,2,3,2,'Yes','2025-12-05 11:50:31',15000.00,85000.00,NULL,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(13,'2025-12-05 13:08:08','2025-12-05 13:08:08',NULL,'18','DTEHM20250002','DTEHM20250002','1','35000',35000.00,35000.00,'No',NULL,NULL,'Yes',NULL,5,5,5,'Yes','2025-12-05 13:08:08',5250.00,29750.00,NULL,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `ordered_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Unique order receipt number',
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Invoice number',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `order_date` date DEFAULT NULL COMMENT 'Date of the order',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user` int(11) DEFAULT NULL,
  `order_state` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '0 is pending, 1 is submited, 2 pending_delivery, 3 is delivered, 4 is returned',
  `amount` mediumtext COLLATE utf8mb4_unicode_ci,
  `date_created` mediumtext COLLATE utf8mb4_unicode_ci,
  `payment_confirmation` mediumtext COLLATE utf8mb4_unicode_ci,
  `date_updated` mediumtext COLLATE utf8mb4_unicode_ci,
  `mail` mediumtext COLLATE utf8mb4_unicode_ci,
  `delivery_district` mediumtext COLLATE utf8mb4_unicode_ci,
  `temporary_id` int(11) DEFAULT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Admin notes about the order',
  `customer_name` mediumtext COLLATE utf8mb4_unicode_ci,
  `customer_phone_number_1` mediumtext COLLATE utf8mb4_unicode_ci,
  `customer_phone_number_2` mediumtext COLLATE utf8mb4_unicode_ci,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Primary phone number',
  `phone_number_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Phone number 1',
  `phone_number_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Phone number 2',
  `customer_address` mediumtext COLLATE utf8mb4_unicode_ci,
  `delivery_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Delivery or Pickup',
  `delivery_address_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Foreign key to delivery_addresses',
  `delivery_address_text` text COLLATE utf8mb4_unicode_ci COMMENT 'Delivery location text',
  `delivery_address_details` text COLLATE utf8mb4_unicode_ci COMMENT 'Specific delivery address details',
  `delivery_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Delivery fee amount',
  `order_total` mediumtext COLLATE utf8mb4_unicode_ci,
  `payable_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Total amount payable including delivery',
  `order_details` mediumtext COLLATE utf8mb4_unicode_ci,
  `items` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON items data for backward compatibility',
  `stripe_id` varchar(550) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_url` text COLLATE utf8mb4_unicode_ci,
  `stripe_paid` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'No',
  `pending_mail_sent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'No',
  `processing_mail_sent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'No',
  `completed_mail_sent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'No',
  `canceled_mail_sent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'No',
  `failed_mail_sent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'No',
  `sub_total` bigint(20) DEFAULT '0',
  `tax` bigint(20) DEFAULT '0',
  `discount` bigint(20) DEFAULT '0',
  `delivery_fee` bigint(20) DEFAULT '0',
  `payment_gateway` text COLLATE utf8mb4_unicode_ci,
  `pesapal_order_tracking_id` text COLLATE utf8mb4_unicode_ci,
  `pesapal_merchant_reference` text COLLATE utf8mb4_unicode_ci,
  `pesapal_status` text COLLATE utf8mb4_unicode_ci,
  `pesapal_payment_method` text COLLATE utf8mb4_unicode_ci,
  `pesapal_redirect_url` text COLLATE utf8mb4_unicode_ci,
  `payment_status` text COLLATE utf8mb4_unicode_ci,
  `pay_on_delivery` tinyint(1) NOT NULL DEFAULT '0',
  `payment_completed_at` text COLLATE utf8mb4_unicode_ci,
  `order_is_paid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `order_paid_date` timestamp NULL DEFAULT NULL,
  `order_paid_amount` decimal(15,2) DEFAULT NULL,
  `has_detehm_seller` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `dtehm_seller_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dtehm_user_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_receipt_number_unique` (`receipt_number`),
  UNIQUE KEY `orders_invoice_number_unique` (`invoice_number`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'ORD-20251114-000001','INV-20251114-000001','2025-11-14 19:30:46','2025-11-14','2025-11-14 19:30:46',1,'0','0',NULL,NULL,NULL,'mubahood360@gmail.com','some',NULL,NULL,NULL,'Muhindo Mubaraka','0783204665',NULL,NULL,NULL,NULL,'Ntinda, Kisaasi, Uganda','delivery',1,'Ntinda, Kisaasi, Uganda',NULL,0.00,'0',0.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',0,0,0,0,'manual',NULL,NULL,NULL,NULL,NULL,'PENDING_PAYMENT',0,NULL,'No',NULL,NULL,'No',NULL,NULL),(2,'ORD-20251114-000002','INV-20251114-000002','2025-11-14 19:41:56','2025-11-14','2025-11-14 19:41:56',13,'0','1800000',NULL,'Dolore cupidatat ape',NULL,'mubahood360@gmail.com','some',NULL,NULL,NULL,'Muhindo Mubaraka','0783204665','0783204665',NULL,NULL,NULL,'Ntinda, Kisaasi, Uganda','pickup',1,'Ntinda, Kisaasi, Uganda',NULL,0.00,'1800000',1800000.00,NULL,NULL,NULL,NULL,'No','Yes','No','No','No','No',1800000,0,0,0,'manual',NULL,NULL,NULL,NULL,NULL,'PENDING_PAYMENT',0,NULL,'No',NULL,NULL,'No',NULL,NULL),(3,'TEST-20251115-0001','INV-TEST-20251115-0001','2025-11-15 04:49:07','2025-10-23','2025-11-15 05:10:37',13,'2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 1','+256709619025',NULL,NULL,NULL,NULL,'Kampala, Uganda','delivery',NULL,NULL,NULL,280.00,'390280',390280.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',390000,0,0,0,'cash_on_delivery',NULL,NULL,NULL,NULL,NULL,'PAID',0,NULL,'Yes','2025-11-03 04:49:07',390280.00,'Yes','DTEHM20250902',138),(4,'TEST-20251115-0002','INV-TEST-20251115-0002','2025-11-15 04:49:07','2025-11-03','2025-11-15 04:49:07',NULL,'2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 2','+256702111045',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,5001.00,'825001',825001.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',820000,0,0,0,'manual',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes',NULL,825001.00,'Yes','DTEHM20250301',120),(5,'TEST-20251115-0003','INV-TEST-20251115-0003','2025-11-15 04:49:07','2025-11-04','2025-11-15 04:49:07',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 3','+256705618637',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,3168.00,'9153168',9153168.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',9150000,0,0,0,'cash_on_delivery',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes','2025-11-09 04:49:07',9153168.00,'Yes','DTEHM20259006',147),(6,'TEST-20251115-0004','INV-TEST-20251115-0004','2025-11-15 04:49:07','2025-11-14','2025-11-15 04:49:07',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 4','+256706997365',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,5565.00,'2565565',2565565.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',2560000,0,0,0,'cash_on_delivery',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes','2025-11-03 04:49:07',2565565.00,'Yes','DTEHM20250803',136),(7,'TEST-20251115-0005','INV-TEST-20251115-0005','2025-11-15 04:49:07','2025-10-25','2025-11-15 04:49:07',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 5','+256709816626',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,7541.00,'4357541',4357541.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',4350000,0,0,0,'manual',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'No','2025-11-02 04:49:07',NULL,'Yes','DTEHM20250302',121),(8,'TEST-20251115-0006','INV-TEST-20251115-0006','2025-11-15 04:49:07','2025-10-29','2025-11-15 04:49:07',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 6','+256704762094',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,4464.00,'6534464',6534464.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',6530000,0,0,0,'pesapal',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes','2025-11-08 04:49:07',6534464.00,'Yes','DTEHM20250801',134),(9,'TEST-20251115-0007','INV-TEST-20251115-0007','2025-11-15 04:49:07','2025-11-14','2025-11-15 04:49:07',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 7','+256704859302',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,9941.00,'469941',469941.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',460000,0,0,0,'manual',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes',NULL,469941.00,'Yes','DTEHM20250803',136),(10,'TEST-20251115-0008','INV-TEST-20251115-0008','2025-11-15 04:49:07','2025-10-18','2025-11-15 04:49:07',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 8','+256706086250',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,5168.00,'1175168',1175168.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',1170000,0,0,0,'manual',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes',NULL,1175168.00,'Yes','DTEHM20259007',148),(11,'TEST-20251115-0009','INV-TEST-20251115-0009','2025-11-15 04:49:07','2025-10-22','2025-11-15 04:49:07',NULL,'2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 9','+256701741674',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,5762.00,'465762',465762.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',460000,0,0,0,'pesapal',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'No',NULL,NULL,'Yes','DTEHM20250503',128),(12,'TEST-20251115-0010','INV-TEST-20251115-0010','2025-11-15 04:49:07','2025-11-03','2025-11-15 04:49:07',NULL,'2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 10','+256705984085',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,919.00,'1245919',1245919.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',1245000,0,0,0,'cash_on_delivery',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes','2025-11-14 04:49:07',1245919.00,'Yes','DTEHM20250502',127),(13,'TEST-20251115-0011','INV-TEST-20251115-0011','2025-11-15 04:49:07','2025-10-25','2025-11-15 04:49:07',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 11','+256702634453',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,2068.00,'5622068',5622068.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',5620000,0,0,0,'cash_on_delivery',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes','2025-10-31 04:49:07',5622068.00,'Yes','DTEHM20259010',151),(14,'TEST-20251115-0012','INV-TEST-20251115-0012','2025-11-15 04:49:07','2025-11-05','2025-11-15 04:49:07',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 12','+256706794423',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,1725.00,'1596725',1596725.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',1595000,0,0,0,'cash_on_delivery',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes','2025-11-08 04:49:07',1596725.00,'Yes','DTEHM20250203',119),(15,'TEST-20251115-0013','INV-TEST-20251115-0013','2025-11-15 04:49:07','2025-11-14','2025-11-15 04:49:07',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 13','+256704213851',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,3514.00,'3713514',3713514.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',3710000,0,0,0,'cash_on_delivery',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'No',NULL,NULL,'Yes','DTEHM20250403',125),(16,'TEST-20251115-0014','INV-TEST-20251115-0014','2025-11-15 04:49:07','2025-11-08','2025-11-15 04:49:07',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 14','+256703868406',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,5950.00,'290950',290950.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',285000,0,0,0,'manual',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes',NULL,290950.00,'Yes','DTEHM20259006',147),(17,'TEST-20251115-0015','INV-TEST-20251115-0015','2025-11-15 04:49:07','2025-11-14','2025-11-15 04:49:07',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 15','+256705538668',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,4655.00,'5434655',5434655.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',5430000,0,0,0,'manual',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'No','2025-11-05 04:49:07',NULL,'Yes','DTEHM20250101',115),(18,'TEST-20251115-0016','INV-TEST-20251115-0016','2025-11-15 04:49:07','2025-11-14','2025-11-15 04:49:07',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 16','+256709711075',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,6754.00,'6141754',6141754.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',6135000,0,0,0,'cash_on_delivery',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes',NULL,6141754.00,'Yes','DTEHM20250501',126),(19,'TEST-20251115-0017','INV-TEST-20251115-0017','2025-11-15 04:49:07','2025-11-02','2025-11-15 04:49:07',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 17','+256702943196',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,3000.00,'3678000',3678000.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',3675000,0,0,0,'cash_on_delivery',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes','2025-11-15 04:49:07',3678000.00,'Yes','DTEHM20251002',140),(20,'TEST-20251115-0018','INV-TEST-20251115-0018','2025-11-15 04:49:07','2025-11-03','2025-11-15 04:49:07',NULL,'2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 18','+256702265702',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,5628.00,'340628',340628.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',335000,0,0,0,'cash_on_delivery',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'No','2025-11-03 04:49:07',NULL,'Yes','DTEHM20250502',127),(21,'TEST-20251115-0019','INV-TEST-20251115-0019','2025-11-15 04:49:07','2025-10-26','2025-11-15 04:49:07',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 19','+256707769764',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,7980.00,'1447980',1447980.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',1440000,0,0,0,'manual',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'No','2025-11-07 04:49:07',NULL,'Yes','DTEHM20250303',122),(22,'TEST-20251115-0020','INV-TEST-20251115-0020','2025-11-15 04:49:07','2025-10-27','2025-11-15 04:49:07',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Test Customer 20','+256705848694',NULL,NULL,NULL,NULL,'Kampala, Uganda',NULL,NULL,NULL,NULL,7344.00,'477344',477344.00,NULL,NULL,NULL,NULL,'No','No','No','No','No','No',470000,0,0,0,'manual',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Yes',NULL,477344.00,'Yes','DTEHM20259004',145);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_keys`
--

DROP TABLE IF EXISTS `payment_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `testing` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `testing_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `encryption` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_keys`
--

LOCK TABLES `payment_keys` WRITE;
/*!40000 ALTER TABLE `payment_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uak` char(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `psid` char(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pesapal_ipn_logs`
--

DROP TABLE IF EXISTS `pesapal_ipn_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pesapal_ipn_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order_tracking_id` text COLLATE utf8mb4_unicode_ci,
  `merchant_reference` text COLLATE utf8mb4_unicode_ci,
  `notification_type` text COLLATE utf8mb4_unicode_ci,
  `request_method` text COLLATE utf8mb4_unicode_ci,
  `payload` text COLLATE utf8mb4_unicode_ci,
  `ip_address` text COLLATE utf8mb4_unicode_ci,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `status` text COLLATE utf8mb4_unicode_ci,
  `processed_at` text COLLATE utf8mb4_unicode_ci,
  `processing_notes` text COLLATE utf8mb4_unicode_ci,
  `response_sent` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pesapal_ipn_logs`
--

LOCK TABLES `pesapal_ipn_logs` WRITE;
/*!40000 ALTER TABLE `pesapal_ipn_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `pesapal_ipn_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pesapal_logs`
--

DROP TABLE IF EXISTS `pesapal_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pesapal_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'POST',
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `tracking_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merchant_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_data` json DEFAULT NULL,
  `request_headers` text COLLATE utf8mb4_unicode_ci,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `status_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `response_data` json DEFAULT NULL,
  `response_headers` text COLLATE utf8mb4_unicode_ci,
  `amount` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UGX',
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `response_time_ms` decimal(8,2) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `error_trace` text COLLATE utf8mb4_unicode_ci,
  `test_scenario` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `environment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sandbox',
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pesapal_logs_action_index` (`action`),
  KEY `pesapal_logs_tracking_id_index` (`tracking_id`),
  KEY `pesapal_logs_success_index` (`success`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pesapal_logs`
--

LOCK TABLES `pesapal_logs` WRITE;
/*!40000 ALTER TABLE `pesapal_logs` DISABLE KEYS */;
INSERT INTO `pesapal_logs` VALUES (1,'payment_initialization','submit_order_request','POST','https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest',1,NULL,'PAYMENT_1_1764004824','{\"ipn_url\": \"https://blitxpress.com/api/pesapal/ipn\", \"payload\": {\"id\": \"PAYMENT_1_1764004824\", \"amount\": 96000, \"currency\": \"UGX\", \"description\": \"DTEHM + DIP Membership Fee - One-time payment\", \"callback_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback\", \"billing_address\": {\"city\": \"\", \"state\": \"\", \"line_1\": \"\", \"line_2\": \"\", \"zip_code\": \"\", \"last_name\": \"\", \"first_name\": \"Kule Swaleh\", \"postal_code\": \"\", \"country_code\": \"UG\", \"phone_number\": \"0772111117\", \"email_address\": \"0772111117@dtehm.app\"}, \"notification_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\"}, \"callback_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback\", \"notification_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\"}',NULL,1,'200','Payment initialization successful','{\"error\": null, \"status\": \"200\", \"redirect_url\": \"https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\"}',NULL,96000.00,'UGX','Kule Swaleh','0772111117@dtehm.app','0772111117','DTEHM + DIP Membership Fee - One-time payment',1584.62,'2025-11-24 14:20:24','2025-11-24 14:20:27',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:20:24','2025-11-24 14:20:27'),(2,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,1,'200','Authentication successful','{\"error\": null, \"token\": \"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJodHRwOi8vc2NoZW1hcy5taWNyb3NvZnQuY29tL3dzLzIwMDgvMDYvaWRlbnRpdHkvY2xhaW1zL3VzZXJkYXRhIjoiMjg2ZGQ0NzEtZjRhOS00ZGZjLThjYTQtZGNjOThjY2FjYWE5IiwidWlkIjoiWVQ3b0laZ1NtdTVOLy9YTWxmYndsTDlndU4zNTRzOTkiLCJuYmYiOjE3NjQwMDQ4MjUsImV4cCI6MTc2NDAwNTEyNSwiaWF0IjoxNzY0MDA0ODI1LCJpc3MiOiJodHRwOi8vcGF5LnBlc2FwYWwuY29tLyIsImF1ZCI6Imh0dHA6Ly9wYXkucGVzYXBhbC5jb20vIn0.MnRQK3HS1PKfFqJGAXCdTl6gCwmt0nbBpp22X5bCjuw\", \"status\": \"200\", \"message\": \"Request processed successfully\", \"expiryDate\": \"2025-11-24T17:25:25.5665545Z\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,969.65,'2025-11-24 14:20:24','2025-11-24 14:20:25',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:20:24','2025-11-24 14:20:25'),(3,'api_call','register_ipn','POST','https://pay.pesapal.com/v3/api/URLSetup/RegisterIPN',NULL,NULL,NULL,NULL,NULL,1,'200','IPN registration successful','{\"url\": \"https://blitxpress.com/api/pesapal/ipn\", \"ipn_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\", \"status\": \"200\", \"message\": \"Request processed successfully\", \"ipn_status\": 1, \"created_date\": \"2025-10-29T12:18:42.05\", \"notification_type\": 0, \"ipn_status_decription\": \"Active\", \"ipn_notification_type_description\": \"GET\"}',NULL,NULL,'UGX',NULL,NULL,NULL,'Register IPN URL: https://blitxpress.com/api/pesapal/ipn',888.02,'2025-11-24 14:20:25','2025-11-24 14:20:26',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:20:25','2025-11-24 14:20:26'),(4,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:20:25',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:20:25','2025-11-24 14:20:25'),(5,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\"}, \"amount\": 96000, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T20:20:28.083\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,813.73,'2025-11-24 14:39:08','2025-11-24 14:39:09',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:39:08','2025-11-24 14:39:09'),(6,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,1,'200','Authentication successful','{\"error\": null, \"token\": \"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJodHRwOi8vc2NoZW1hcy5taWNyb3NvZnQuY29tL3dzLzIwMDgvMDYvaWRlbnRpdHkvY2xhaW1zL3VzZXJkYXRhIjoiMjg2ZGQ0NzEtZjRhOS00ZGZjLThjYTQtZGNjOThjY2FjYWE5IiwidWlkIjoiWVQ3b0laZ1NtdTVOLy9YTWxmYndsTDlndU4zNTRzOTkiLCJuYmYiOjE3NjQwMDU5NDksImV4cCI6MTc2NDAwNjI0OSwiaWF0IjoxNzY0MDA1OTQ5LCJpc3MiOiJodHRwOi8vcGF5LnBlc2FwYWwuY29tLyIsImF1ZCI6Imh0dHA6Ly9wYXkucGVzYXBhbC5jb20vIn0.l_KTguk46GgQFheVNarcsrZgB3DBfw2FvfPCS2EW72k\", \"status\": \"200\", \"message\": \"Request processed successfully\", \"expiryDate\": \"2025-11-24T17:44:09.1461864Z\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,968.86,'2025-11-24 14:39:08','2025-11-24 14:39:09',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:39:08','2025-11-24 14:39:09'),(7,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\"}, \"amount\": 96000, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T20:20:28.083\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,781.45,'2025-11-24 14:39:34','2025-11-24 14:39:35',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:39:34','2025-11-24 14:39:35'),(8,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:39:34',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:39:34','2025-11-24 14:39:34'),(9,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\"}, \"amount\": 96000, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T20:20:28.083\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,769.95,'2025-11-24 14:40:14','2025-11-24 14:40:15',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:14','2025-11-24 14:40:15'),(10,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:40:14',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:14','2025-11-24 14:40:14'),(11,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\"}, \"amount\": 96000, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T20:20:28.083\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,762.09,'2025-11-24 14:40:23','2025-11-24 14:40:24',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:23','2025-11-24 14:40:24'),(12,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:40:23',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:23','2025-11-24 14:40:23'),(13,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\"}, \"amount\": 96000, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T20:20:28.083\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,889.92,'2025-11-24 14:40:28','2025-11-24 14:40:29',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:28','2025-11-24 14:40:29'),(14,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:40:28',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:28','2025-11-24 14:40:28'),(15,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\"}, \"amount\": 96000, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T20:20:28.083\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,766.98,'2025-11-24 14:40:33','2025-11-24 14:40:34',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:33','2025-11-24 14:40:34'),(16,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:40:33',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:33','2025-11-24 14:40:33'),(17,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\"}, \"amount\": 96000, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T20:20:28.083\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,774.85,'2025-11-24 14:40:38','2025-11-24 14:40:39',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:38','2025-11-24 14:40:39'),(18,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:40:38',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:38','2025-11-24 14:40:38'),(19,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\"}, \"amount\": 96000, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T20:20:28.083\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,780.99,'2025-11-24 14:40:43','2025-11-24 14:40:44',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:43','2025-11-24 14:40:44'),(20,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:40:43',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:43','2025-11-24 14:40:43'),(21,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": null, \"message\": null, \"error_type\": null}, \"amount\": 96000, \"status\": \"200\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 2, \"created_date\": \"2025-11-24T20:40:44.927\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"MTNUG\", \"payment_account\": \"2567xxx38494\", \"confirmation_code\": \"22913A241125G\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"Failed\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,785.59,'2025-11-24 14:40:48','2025-11-24 14:40:49',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:48','2025-11-24 14:40:49'),(22,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:40:48',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:40:48','2025-11-24 14:40:48'),(23,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": null, \"message\": null, \"error_type\": null}, \"amount\": 96000, \"status\": \"200\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 1, \"created_date\": \"2025-11-24T20:41:18.263\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"AirtelUG\", \"payment_account\": \"706638494\", \"confirmation_code\": \"135422827128\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"Completed\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,958.60,'2025-11-24 14:41:39','2025-11-24 14:41:40',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:39','2025-11-24 14:41:40'),(24,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:41:39',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:39','2025-11-24 14:41:39'),(25,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": null, \"message\": null, \"error_type\": null}, \"amount\": 96000, \"status\": \"200\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 1, \"created_date\": \"2025-11-24T20:41:18.263\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"AirtelUG\", \"payment_account\": \"706638494\", \"confirmation_code\": \"135422827128\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"Completed\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,776.54,'2025-11-24 14:41:49','2025-11-24 14:41:50',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:49','2025-11-24 14:41:50'),(26,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:41:49',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:49','2025-11-24 14:41:49'),(27,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": null, \"message\": null, \"error_type\": null}, \"amount\": 96000, \"status\": \"200\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 1, \"created_date\": \"2025-11-24T20:41:18.263\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"AirtelUG\", \"payment_account\": \"706638494\", \"confirmation_code\": \"135422827128\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"Completed\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,797.47,'2025-11-24 14:41:52','2025-11-24 14:41:53',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:52','2025-11-24 14:41:53'),(28,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:41:52',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:52','2025-11-24 14:41:52'),(29,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'8502308a-4b03-4b23-97cf-db0d4cbfce61',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": null, \"message\": null, \"error_type\": null}, \"amount\": 96000, \"status\": \"200\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 1, \"created_date\": \"2025-11-24T20:41:18.263\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\", \"account_number\": null, \"payment_method\": \"AirtelUG\", \"payment_account\": \"706638494\", \"confirmation_code\": \"135422827128\", \"order_tracking_id\": \"8502308a-4b03-4b23-97cf-db0d4cbfce61\", \"merchant_reference\": \"PAYMENT_1_1764004824\", \"payment_status_code\": \"\", \"payment_status_description\": \"Completed\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,859.58,'2025-11-24 14:41:53','2025-11-24 14:41:54',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:53','2025-11-24 14:41:54'),(30,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:41:53',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:53','2025-11-24 14:41:53'),(31,'payment_initialization','submit_order_request','POST','https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest',2,NULL,'PAYMENT_2_1764006119','{\"ipn_url\": \"https://blitxpress.com/api/pesapal/ipn\", \"payload\": {\"id\": \"PAYMENT_2_1764006119\", \"amount\": 96000, \"currency\": \"UGX\", \"description\": \"DTEHM + DIP Membership Fee - One-time payment\", \"callback_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback\", \"billing_address\": {\"city\": \"\", \"state\": \"\", \"line_1\": \"\", \"line_2\": \"\", \"zip_code\": \"\", \"last_name\": \"\", \"first_name\": \"Kule Swaleh\", \"postal_code\": \"\", \"country_code\": \"UG\", \"phone_number\": \"0772111117\", \"email_address\": \"0772111117@dtehm.app\"}, \"notification_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\"}, \"callback_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback\", \"notification_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\"}',NULL,1,'200','Payment initialization successful','{\"error\": null, \"status\": \"200\", \"redirect_url\": \"https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=bf94c5dc-a7a5-4c91-9798-db0d622b9de1\", \"order_tracking_id\": \"bf94c5dc-a7a5-4c91-9798-db0d622b9de1\", \"merchant_reference\": \"PAYMENT_2_1764006119\"}',NULL,96000.00,'UGX','Kule Swaleh','0772111117@dtehm.app','0772111117','DTEHM + DIP Membership Fee - One-time payment',1442.49,'2025-11-24 14:41:59','2025-11-24 14:42:02',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:59','2025-11-24 14:42:02'),(32,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:41:59',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:59','2025-11-24 14:41:59'),(33,'api_call','register_ipn','POST','https://pay.pesapal.com/v3/api/URLSetup/RegisterIPN',NULL,NULL,NULL,NULL,NULL,1,'200','IPN registration successful','{\"url\": \"https://blitxpress.com/api/pesapal/ipn\", \"ipn_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\", \"status\": \"200\", \"message\": \"Request processed successfully\", \"ipn_status\": 1, \"created_date\": \"2025-10-29T12:18:42.05\", \"notification_type\": 0, \"ipn_status_decription\": \"Active\", \"ipn_notification_type_description\": \"GET\"}',NULL,NULL,'UGX',NULL,NULL,NULL,'Register IPN URL: https://blitxpress.com/api/pesapal/ipn',755.80,'2025-11-24 14:41:59','2025-11-24 14:42:00',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:59','2025-11-24 14:42:00'),(34,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:41:59',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:41:59','2025-11-24 14:41:59'),(35,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'bf94c5dc-a7a5-4c91-9798-db0d622b9de1',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=bf94c5dc-a7a5-4c91-9798-db0d622b9de1&OrderMerchantReference=PAYMENT_2_1764006119\"}, \"amount\": 96000, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T20:42:02.113\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=bf94c5dc-a7a5-4c91-9798-db0d622b9de1&OrderMerchantReference=PAYMENT_2_1764006119\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"bf94c5dc-a7a5-4c91-9798-db0d622b9de1\", \"merchant_reference\": \"PAYMENT_2_1764006119\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,1028.46,'2025-11-24 14:42:07','2025-11-24 14:42:08',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:42:07','2025-11-24 14:42:08'),(36,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 14:42:07',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 14:42:07','2025-11-24 14:42:07'),(37,'payment_initialization','submit_order_request','POST','https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest',3,NULL,'PAYMENT_3_1764009692','{\"ipn_url\": \"https://blitxpress.com/api/pesapal/ipn\", \"payload\": {\"id\": \"PAYMENT_3_1764009692\", \"amount\": 500, \"currency\": \"UGX\", \"description\": \"DTEHM + DIP Membership Fee - One-time payment\", \"callback_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback\", \"billing_address\": {\"city\": \"\", \"state\": \"\", \"line_1\": \"\", \"line_2\": \"\", \"zip_code\": \"\", \"last_name\": \"\", \"first_name\": \"Kule Swaleh\", \"postal_code\": \"\", \"country_code\": \"UG\", \"phone_number\": \"0772111117\", \"email_address\": \"0772111117@dtehm.app\"}, \"notification_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\"}, \"callback_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback\", \"notification_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\"}',NULL,1,'200','Payment initialization successful','{\"error\": null, \"status\": \"200\", \"redirect_url\": \"https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50\", \"order_tracking_id\": \"df7b8ed0-912b-4592-ae3e-db0d33e2bd50\", \"merchant_reference\": \"PAYMENT_3_1764009692\"}',NULL,500.00,'UGX','Kule Swaleh','0772111117@dtehm.app','0772111117','DTEHM + DIP Membership Fee - One-time payment',1514.44,'2025-11-24 15:41:32','2025-11-24 15:41:35',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:41:32','2025-11-24 15:41:35'),(38,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,1,'200','Authentication successful','{\"error\": null, \"token\": \"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJodHRwOi8vc2NoZW1hcy5taWNyb3NvZnQuY29tL3dzLzIwMDgvMDYvaWRlbnRpdHkvY2xhaW1zL3VzZXJkYXRhIjoiMjg2ZGQ0NzEtZjRhOS00ZGZjLThjYTQtZGNjOThjY2FjYWE5IiwidWlkIjoiWVQ3b0laZ1NtdTVOLy9YTWxmYndsTDlndU4zNTRzOTkiLCJuYmYiOjE3NjQwMDk2OTMsImV4cCI6MTc2NDAwOTk5MywiaWF0IjoxNzY0MDA5NjkzLCJpc3MiOiJodHRwOi8vcGF5LnBlc2FwYWwuY29tLyIsImF1ZCI6Imh0dHA6Ly9wYXkucGVzYXBhbC5jb20vIn0.DoBIQjoJCcmYRrtLKFLRgClbd2t2EYQso7Y7vTonmi4\", \"status\": \"200\", \"message\": \"Request processed successfully\", \"expiryDate\": \"2025-11-24T18:46:33.3912397Z\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,1106.72,'2025-11-24 15:41:32','2025-11-24 15:41:33',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:41:32','2025-11-24 15:41:33'),(39,'api_call','register_ipn','POST','https://pay.pesapal.com/v3/api/URLSetup/RegisterIPN',NULL,NULL,NULL,NULL,NULL,1,'200','IPN registration successful','{\"url\": \"https://blitxpress.com/api/pesapal/ipn\", \"ipn_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\", \"status\": \"200\", \"message\": \"Request processed successfully\", \"ipn_status\": 1, \"created_date\": \"2025-10-29T12:18:42.05\", \"notification_type\": 0, \"ipn_status_decription\": \"Active\", \"ipn_notification_type_description\": \"GET\"}',NULL,NULL,'UGX',NULL,NULL,NULL,'Register IPN URL: https://blitxpress.com/api/pesapal/ipn',783.84,'2025-11-24 15:41:33','2025-11-24 15:41:34',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:41:33','2025-11-24 15:41:34'),(40,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:41:33',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:41:33','2025-11-24 15:41:33'),(41,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'df7b8ed0-912b-4592-ae3e-db0d33e2bd50',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T21:41:35.747\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"df7b8ed0-912b-4592-ae3e-db0d33e2bd50\", \"merchant_reference\": \"PAYMENT_3_1764009692\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,834.38,'2025-11-24 15:41:45','2025-11-24 15:41:46',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:41:45','2025-11-24 15:41:46'),(42,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:41:45',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:41:45','2025-11-24 15:41:45'),(43,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'df7b8ed0-912b-4592-ae3e-db0d33e2bd50',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T21:41:35.747\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"df7b8ed0-912b-4592-ae3e-db0d33e2bd50\", \"merchant_reference\": \"PAYMENT_3_1764009692\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,826.77,'2025-11-24 15:41:50','2025-11-24 15:41:51',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:41:50','2025-11-24 15:41:51'),(44,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:41:50',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:41:50','2025-11-24 15:41:50'),(45,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'df7b8ed0-912b-4592-ae3e-db0d33e2bd50',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T21:41:35.747\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"df7b8ed0-912b-4592-ae3e-db0d33e2bd50\", \"merchant_reference\": \"PAYMENT_3_1764009692\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,1190.79,'2025-11-24 15:41:55','2025-11-24 15:41:56',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:41:55','2025-11-24 15:41:56'),(46,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:41:55',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:41:55','2025-11-24 15:41:55'),(47,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'df7b8ed0-912b-4592-ae3e-db0d33e2bd50',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T21:41:35.747\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"df7b8ed0-912b-4592-ae3e-db0d33e2bd50\", \"merchant_reference\": \"PAYMENT_3_1764009692\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,769.51,'2025-11-24 15:42:00','2025-11-24 15:42:01',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:42:00','2025-11-24 15:42:01'),(48,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:42:00',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:42:00','2025-11-24 15:42:00'),(49,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'df7b8ed0-912b-4592-ae3e-db0d33e2bd50',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T21:41:35.747\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"df7b8ed0-912b-4592-ae3e-db0d33e2bd50\", \"merchant_reference\": \"PAYMENT_3_1764009692\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,734.22,'2025-11-24 15:42:05','2025-11-24 15:42:06',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:42:05','2025-11-24 15:42:06'),(50,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:42:05',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:42:05','2025-11-24 15:42:05'),(51,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'df7b8ed0-912b-4592-ae3e-db0d33e2bd50',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T21:41:35.747\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"df7b8ed0-912b-4592-ae3e-db0d33e2bd50\", \"merchant_reference\": \"PAYMENT_3_1764009692\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,753.54,'2025-11-24 15:42:10','2025-11-24 15:42:10',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:42:10','2025-11-24 15:42:10'),(52,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:42:10',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:42:10','2025-11-24 15:42:10'),(53,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'df7b8ed0-912b-4592-ae3e-db0d33e2bd50',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": null, \"message\": null, \"error_type\": null}, \"amount\": 500, \"status\": \"200\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 2, \"created_date\": \"2025-11-24T21:42:13.333\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\", \"account_number\": null, \"payment_method\": \"MTNUG\", \"payment_account\": \"2567xxx38494\", \"confirmation_code\": \"22925B241125J\", \"order_tracking_id\": \"df7b8ed0-912b-4592-ae3e-db0d33e2bd50\", \"merchant_reference\": \"PAYMENT_3_1764009692\", \"payment_status_code\": \"\", \"payment_status_description\": \"Failed\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,753.74,'2025-11-24 15:42:15','2025-11-24 15:42:16',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:42:15','2025-11-24 15:42:16'),(54,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:42:15',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:42:15','2025-11-24 15:42:15'),(55,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'df7b8ed0-912b-4592-ae3e-db0d33e2bd50',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": null, \"message\": null, \"error_type\": null}, \"amount\": 500, \"status\": \"200\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 1, \"created_date\": \"2025-11-24T21:42:53.853\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\", \"account_number\": null, \"payment_method\": \"AirtelUG\", \"payment_account\": \"706638494\", \"confirmation_code\": \"135427865534\", \"order_tracking_id\": \"df7b8ed0-912b-4592-ae3e-db0d33e2bd50\", \"merchant_reference\": \"PAYMENT_3_1764009692\", \"payment_status_code\": \"\", \"payment_status_description\": \"Completed\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,927.75,'2025-11-24 15:43:14','2025-11-24 15:43:15',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:43:14','2025-11-24 15:43:15'),(56,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:43:14',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:43:14','2025-11-24 15:43:14'),(57,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'df7b8ed0-912b-4592-ae3e-db0d33e2bd50',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": null, \"message\": null, \"error_type\": null}, \"amount\": 500, \"status\": \"200\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 1, \"created_date\": \"2025-11-24T21:42:53.853\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\", \"account_number\": null, \"payment_method\": \"AirtelUG\", \"payment_account\": \"706638494\", \"confirmation_code\": \"135427865534\", \"order_tracking_id\": \"df7b8ed0-912b-4592-ae3e-db0d33e2bd50\", \"merchant_reference\": \"PAYMENT_3_1764009692\", \"payment_status_code\": \"\", \"payment_status_description\": \"Completed\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,1055.18,'2025-11-24 15:43:24','2025-11-24 15:43:25',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:43:24','2025-11-24 15:43:25'),(58,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:43:24',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 15:43:24','2025-11-24 15:43:24'),(59,'payment_initialization','submit_order_request','POST','https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest',16,NULL,'PAYMENT_16_1764015785','{\"ipn_url\": \"https://blitxpress.com/api/pesapal/ipn\", \"payload\": {\"id\": \"PAYMENT_16_1764015785\", \"amount\": 500, \"currency\": \"UGX\", \"description\": \"Purchase of 5 share(s) in Motorcycle Taxi Fleet\", \"callback_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback\", \"billing_address\": {\"city\": \"\", \"state\": \"\", \"line_1\": \"\", \"line_2\": \"\", \"zip_code\": \"\", \"last_name\": \"\", \"first_name\": \"Kule Swaleh\", \"postal_code\": \"\", \"country_code\": \"UG\", \"phone_number\": \"0772111117\", \"email_address\": \"0772111117@dtehm.app\"}, \"notification_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\"}, \"callback_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback\", \"notification_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\"}',NULL,1,'200','Payment initialization successful','{\"error\": null, \"status\": \"200\", \"redirect_url\": \"https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\"}',NULL,500.00,'UGX','Kule Swaleh','0772111117@dtehm.app','0772111117','Purchase of 5 share(s) in Motorcycle Taxi Fleet',1492.25,'2025-11-24 17:23:05','2025-11-24 17:23:09',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:05','2025-11-24 17:23:09'),(60,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,1,'200','Authentication successful','{\"error\": null, \"token\": \"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJodHRwOi8vc2NoZW1hcy5taWNyb3NvZnQuY29tL3dzLzIwMDgvMDYvaWRlbnRpdHkvY2xhaW1zL3VzZXJkYXRhIjoiMjg2ZGQ0NzEtZjRhOS00ZGZjLThjYTQtZGNjOThjY2FjYWE5IiwidWlkIjoiWVQ3b0laZ1NtdTVOLy9YTWxmYndsTDlndU4zNTRzOTkiLCJuYmYiOjE3NjQwMTU3ODcsImV4cCI6MTc2NDAxNjA4NywiaWF0IjoxNzY0MDE1Nzg3LCJpc3MiOiJodHRwOi8vcGF5LnBlc2FwYWwuY29tLyIsImF1ZCI6Imh0dHA6Ly9wYXkucGVzYXBhbC5jb20vIn0.KhoxHQwN4AnkBVcS7S39okM9MyGsIgRIFTK58DIpN7U\", \"status\": \"200\", \"message\": \"Request processed successfully\", \"expiryDate\": \"2025-11-24T20:28:07.0680527Z\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,1517.03,'2025-11-24 17:23:05','2025-11-24 17:23:07',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:05','2025-11-24 17:23:07'),(61,'api_call','register_ipn','POST','https://pay.pesapal.com/v3/api/URLSetup/RegisterIPN',NULL,NULL,NULL,NULL,NULL,1,'200','IPN registration successful','{\"url\": \"https://blitxpress.com/api/pesapal/ipn\", \"ipn_id\": \"860a782f-9d70-48ba-9cdd-db270b513234\", \"status\": \"200\", \"message\": \"Request processed successfully\", \"ipn_status\": 1, \"created_date\": \"2025-10-29T12:18:42.05\", \"notification_type\": 0, \"ipn_status_decription\": \"Active\", \"ipn_notification_type_description\": \"GET\"}',NULL,NULL,'UGX',NULL,NULL,NULL,'Register IPN URL: https://blitxpress.com/api/pesapal/ipn',848.50,'2025-11-24 17:23:07','2025-11-24 17:23:08',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:07','2025-11-24 17:23:08'),(62,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:23:07',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:07','2025-11-24 17:23:07'),(63,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,868.29,'2025-11-24 17:23:20','2025-11-24 17:23:21',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:20','2025-11-24 17:23:21'),(64,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:23:20',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:20','2025-11-24 17:23:20'),(65,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,797.56,'2025-11-24 17:23:25','2025-11-24 17:23:26',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:25','2025-11-24 17:23:26'),(66,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:23:25',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:25','2025-11-24 17:23:25'),(67,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,798.45,'2025-11-24 17:23:30','2025-11-24 17:23:31',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:30','2025-11-24 17:23:31'),(68,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:23:30',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:30','2025-11-24 17:23:30'),(69,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,907.69,'2025-11-24 17:23:35','2025-11-24 17:23:36',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:35','2025-11-24 17:23:36'),(70,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:23:35',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:35','2025-11-24 17:23:35'),(71,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,805.72,'2025-11-24 17:23:40','2025-11-24 17:23:41',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:40','2025-11-24 17:23:41'),(72,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:23:40',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:40','2025-11-24 17:23:40'),(73,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,791.12,'2025-11-24 17:23:45','2025-11-24 17:23:46',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:45','2025-11-24 17:23:46'),(74,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:23:45',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:45','2025-11-24 17:23:45'),(75,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,839.71,'2025-11-24 17:23:50','2025-11-24 17:23:51',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:50','2025-11-24 17:23:51'),(76,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:23:50',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:50','2025-11-24 17:23:50'),(77,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,912.86,'2025-11-24 17:23:55','2025-11-24 17:23:56',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:55','2025-11-24 17:23:56'),(78,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:23:55',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:23:55','2025-11-24 17:23:55'),(79,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,807.77,'2025-11-24 17:24:00','2025-11-24 17:24:01',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:00','2025-11-24 17:24:01'),(80,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:24:00',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:00','2025-11-24 17:24:00'),(81,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,1118.17,'2025-11-24 17:24:05','2025-11-24 17:24:06',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:05','2025-11-24 17:24:06'),(82,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:24:05',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:05','2025-11-24 17:24:05'),(83,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,1225.89,'2025-11-24 17:24:10','2025-11-24 17:24:11',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:10','2025-11-24 17:24:11'),(84,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:24:10',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:10','2025-11-24 17:24:10'),(85,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,839.88,'2025-11-24 17:24:15','2025-11-24 17:24:16',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:15','2025-11-24 17:24:16'),(86,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:24:15',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:15','2025-11-24 17:24:15'),(87,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,797.32,'2025-11-24 17:24:20','2025-11-24 17:24:21',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:20','2025-11-24 17:24:21'),(88,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:24:20',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:20','2025-11-24 17:24:20'),(89,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": \"payment_details_not_found\", \"message\": \"Pending Payment\", \"error_type\": \"api_error\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\"}, \"amount\": 500, \"status\": \"500\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 0, \"created_date\": \"2025-11-24T23:23:09.96\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"\", \"payment_account\": \"\", \"confirmation_code\": \"\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"INVALID\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,984.14,'2025-11-24 17:24:25','2025-11-24 17:24:26',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:25','2025-11-24 17:24:26'),(90,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:24:25',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:25','2025-11-24 17:24:25'),(91,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": null, \"message\": null, \"error_type\": null}, \"amount\": 500, \"status\": \"200\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 1, \"created_date\": \"2025-11-24T23:24:29.997\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"AirtelUG\", \"payment_account\": \"706638494\", \"confirmation_code\": \"135432480995\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"Completed\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,907.51,'2025-11-24 17:24:30','2025-11-24 17:24:31',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:30','2025-11-24 17:24:31'),(92,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:24:30',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:30','2025-11-24 17:24:30'),(93,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": null, \"message\": null, \"error_type\": null}, \"amount\": 500, \"status\": \"200\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 1, \"created_date\": \"2025-11-24T23:24:29.997\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"AirtelUG\", \"payment_account\": \"706638494\", \"confirmation_code\": \"135432480995\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"Completed\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,814.95,'2025-11-24 17:24:33','2025-11-24 17:24:34',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:33','2025-11-24 17:24:34'),(94,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:24:33',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:33','2025-11-24 17:24:33'),(95,'api_call','check_payment_status','GET','https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus',NULL,'938b596d-9d44-4f29-a50d-db0d7ecf9a04',NULL,NULL,NULL,1,'200','Status check successful','{\"error\": {\"code\": null, \"message\": null, \"error_type\": null}, \"amount\": 500, \"status\": \"200\", \"message\": \"Request processed successfully\", \"currency\": \"UGX\", \"description\": null, \"status_code\": 1, \"created_date\": \"2025-11-24T23:24:29.997\", \"call_back_url\": \"http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\", \"account_number\": null, \"payment_method\": \"AirtelUG\", \"payment_account\": \"706638494\", \"confirmation_code\": \"135432480995\", \"order_tracking_id\": \"938b596d-9d44-4f29-a50d-db0d7ecf9a04\", \"merchant_reference\": \"PAYMENT_16_1764015785\", \"payment_status_code\": \"\", \"payment_status_description\": \"Completed\"}',NULL,NULL,'UGX',NULL,NULL,NULL,NULL,830.79,'2025-11-24 17:24:36','2025-11-24 17:24:37',NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:36','2025-11-24 17:24:37'),(96,'api_call','authenticate','POST','https://pay.pesapal.com/v3/api/Auth/RequestToken',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,NULL,'2025-11-24 17:24:36',NULL,NULL,NULL,NULL,'local','Dart/3.8 (dart:io)','127.0.0.1','2025-11-24 17:24:36','2025-11-24 17:24:36');
/*!40000 ALTER TABLE `pesapal_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pesapal_transactions`
--

DROP TABLE IF EXISTS `pesapal_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pesapal_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order_id` text COLLATE utf8mb4_unicode_ci,
  `order_tracking_id` text COLLATE utf8mb4_unicode_ci,
  `merchant_reference` text COLLATE utf8mb4_unicode_ci,
  `amount` text COLLATE utf8mb4_unicode_ci,
  `currency` text COLLATE utf8mb4_unicode_ci,
  `status` text COLLATE utf8mb4_unicode_ci,
  `status_code` text COLLATE utf8mb4_unicode_ci,
  `payment_method` text COLLATE utf8mb4_unicode_ci,
  `confirmation_code` text COLLATE utf8mb4_unicode_ci,
  `payment_account` text COLLATE utf8mb4_unicode_ci,
  `redirect_url` text COLLATE utf8mb4_unicode_ci,
  `callback_url` text COLLATE utf8mb4_unicode_ci,
  `notification_id` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `pesapal_response` text COLLATE utf8mb4_unicode_ci,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pesapal_transactions`
--

LOCK TABLES `pesapal_transactions` WRITE;
/*!40000 ALTER TABLE `pesapal_transactions` DISABLE KEYS */;
INSERT INTO `pesapal_transactions` VALUES (1,'2025-11-24 14:20:27','2025-11-24 14:20:27','1','8502308a-4b03-4b23-97cf-db0d4cbfce61','PAYMENT_1_1764004824','96000','UGX','PENDING',NULL,NULL,NULL,NULL,'https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61','http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback','860a782f-9d70-48ba-9cdd-db270b513234','DTEHM + DIP Membership Fee - One-time payment',NULL,NULL),(2,'2025-11-24 14:42:02','2025-11-24 14:42:02','2','bf94c5dc-a7a5-4c91-9798-db0d622b9de1','PAYMENT_2_1764006119','96000','UGX','PENDING',NULL,NULL,NULL,NULL,'https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=bf94c5dc-a7a5-4c91-9798-db0d622b9de1','http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback','860a782f-9d70-48ba-9cdd-db270b513234','DTEHM + DIP Membership Fee - One-time payment',NULL,NULL),(3,'2025-11-24 15:41:35','2025-11-24 15:41:35','3','df7b8ed0-912b-4592-ae3e-db0d33e2bd50','PAYMENT_3_1764009692','500','UGX','PENDING',NULL,NULL,NULL,NULL,'https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50','http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback','860a782f-9d70-48ba-9cdd-db270b513234','DTEHM + DIP Membership Fee - One-time payment',NULL,NULL),(4,'2025-11-24 17:23:09','2025-11-24 17:23:09','16','938b596d-9d44-4f29-a50d-db0d7ecf9a04','PAYMENT_16_1764015785','500','UGX','PENDING',NULL,NULL,NULL,NULL,'https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04','http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback','860a782f-9d70-48ba-9cdd-db270b513234','Purchase of 5 share(s) in Motorcycle Taxi Fleet',NULL,NULL);
/*!40000 ALTER TABLE `pesapal_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` char(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'blank.png',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Icon class name for category representation',
  `banner_image` mediumtext COLLATE utf8mb4_unicode_ci,
  `show_in_banner` varchar(35) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_in_categories` varchar(35) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attributes` longtext COLLATE utf8mb4_unicode_ci,
  `is_parent` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT 'No',
  `parent_id` int(11) DEFAULT '1',
  `is_first_banner` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'No',
  `first_banner_image` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (1,'2025-11-14 19:00:22','2025-12-05 11:49:34','MAIN CATEGORY','images/categories/disability-aids.jpg','fa fa-wheelchair','images/banners/disability-aids-banner.jpg','Yes','Yes',NULL,'Yes',NULL,'No',NULL),(2,'2025-11-14 19:00:22','2025-11-14 19:00:22','Health & Wellness Products','images/categories/health-wellness.jpg','fa fa-heartbeat','images/banners/health-wellness-banner.jpg','Yes','Yes',NULL,'Yes',NULL,'Yes','images/banners/health-wellness-first.jpg'),(3,'2025-11-14 19:00:22','2025-11-14 19:00:22','Mobility Solutions','images/categories/mobility.jpg','fa fa-ambulance','images/banners/mobility-banner.jpg','Yes','Yes',NULL,'Yes',NULL,'No',NULL),(4,'2025-11-14 19:00:22','2025-11-14 19:00:22','Assistive Technology','images/categories/assistive-tech.jpg','fa fa-microphone','images/banners/assistive-tech-banner.jpg','Yes','Yes',NULL,'Yes',NULL,'No',NULL),(5,'2025-11-14 19:00:22','2025-11-14 19:00:22','Personal Care Items','images/categories/personal-care.jpg','fa fa-heart','images/banners/personal-care-banner.jpg','No','Yes',NULL,'Yes',NULL,'No',NULL);
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_category_specifications`
--

DROP TABLE IF EXISTS `product_category_specifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_category_specifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_category_id` bigint(20) unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci,
  `attribute_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `is_required` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'No',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_category_attributes_product_category_id_index` (`product_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_category_specifications`
--

LOCK TABLES `product_category_specifications` WRITE;
/*!40000 ALTER TABLE `product_category_specifications` DISABLE KEYS */;
INSERT INTO `product_category_specifications` VALUES (1,1,'Brand','text','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(2,1,'Material','text','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(3,1,'Weight Capacity','text','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(4,1,'Warranty Period','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(5,1,'Color','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(6,2,'Brand','text','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(7,2,'Type','text','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(8,2,'Expiry Date','date','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(9,2,'Dosage/Size','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(10,2,'Origin','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(11,3,'Brand','text','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(12,3,'Max Load','text','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(13,3,'Adjustable','text','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(14,3,'Folding','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(15,3,'Material','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(16,4,'Brand','text','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(17,4,'Battery Life','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(18,4,'Connectivity','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(19,4,'Warranty','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(20,4,'Model','text','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(21,5,'Brand','text','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(22,5,'Size','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(23,5,'Material','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(24,5,'Hypoallergenic','text','No','2025-11-14 19:00:22','2025-11-14 19:00:22');
/*!40000 ALTER TABLE `product_category_specifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_colors`
--

DROP TABLE IF EXISTS `product_colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_colors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_created` date NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_colors`
--

LOCK TABLES `product_colors` WRITE;
/*!40000 ALTER TABLE `product_colors` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_colors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_currency`
--

DROP TABLE IF EXISTS `product_currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_currency` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `currency` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `user` int(11) NOT NULL,
  `date_created` date NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `url` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_currency`
--

LOCK TABLES `product_currency` WRITE;
/*!40000 ALTER TABLE `product_currency` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_currency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_general_sizes`
--

DROP TABLE IF EXISTS `product_general_sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_general_sizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `size` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_created` date NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `user` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_general_sizes`
--

LOCK TABLES `product_general_sizes` WRITE;
/*!40000 ALTER TABLE `product_general_sizes` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_general_sizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_has_specifications`
--

DROP TABLE IF EXISTS `product_has_specifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_has_specifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_has_attributes_product_id_index` (`product_id`),
  KEY `product_has_attributes_product_id_name_index` (`product_id`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_has_specifications`
--

LOCK TABLES `product_has_specifications` WRITE;
/*!40000 ALTER TABLE `product_has_specifications` DISABLE KEYS */;
INSERT INTO `product_has_specifications` VALUES (1,1,'Brand','MobilityPro','2025-11-14 19:00:22','2025-11-14 19:00:22'),(2,1,'Material','Aluminum Alloy','2025-11-14 19:00:22','2025-11-14 19:00:22'),(3,1,'Weight Capacity','120kg','2025-11-14 19:00:22','2025-11-14 19:00:22'),(4,1,'Warranty Period','2 years','2025-11-14 19:00:22','2025-11-14 19:00:22'),(5,1,'Color','Black/Silver','2025-11-14 19:00:22','2025-11-14 19:00:22'),(6,2,'Brand','WalkEasy','2025-11-14 19:00:22','2025-11-14 19:00:22'),(7,2,'Material','Aluminum','2025-11-14 19:00:22','2025-11-14 19:00:22'),(8,2,'Weight Capacity','100kg','2025-11-14 19:00:22','2025-11-14 19:00:22'),(9,2,'Warranty Period','1 year','2025-11-14 19:00:22','2025-11-14 19:00:22'),(10,2,'Color','Gray','2025-11-14 19:00:22','2025-11-14 19:00:22'),(11,3,'Brand','StableWalk','2025-11-14 19:00:22','2025-11-14 19:00:22'),(12,3,'Material','Aluminum/Plastic','2025-11-14 19:00:22','2025-11-14 19:00:22'),(13,3,'Weight Capacity','130kg','2025-11-14 19:00:22','2025-11-14 19:00:22'),(14,3,'Warranty Period','1 year','2025-11-14 19:00:22','2025-11-14 19:00:22'),(15,3,'Color','Black','2025-11-14 19:00:22','2025-11-14 19:00:22'),(16,4,'Brand','MedCare','2025-11-14 19:00:22','2025-11-14 19:00:22'),(17,4,'Material','Steel Frame','2025-11-14 19:00:22','2025-11-14 19:00:22'),(18,4,'Weight Capacity','150kg','2025-11-14 19:00:22','2025-11-14 19:00:22'),(19,4,'Warranty Period','3 years','2025-11-14 19:00:22','2025-11-14 19:00:22'),(20,4,'Color','White','2025-11-14 19:00:22','2025-11-14 19:00:22'),(21,5,'Brand','ComfortCare','2025-11-14 19:00:22','2025-11-14 19:00:22'),(22,5,'Material','Steel/Plastic','2025-11-14 19:00:22','2025-11-14 19:00:22'),(23,5,'Weight Capacity','120kg','2025-11-14 19:00:22','2025-11-14 19:00:22'),(24,5,'Warranty Period','1 year','2025-11-14 19:00:22','2025-11-14 19:00:22'),(25,5,'Color','White/Blue','2025-11-14 19:00:22','2025-11-14 19:00:22'),(26,6,'Brand','HealthTrack','2025-11-14 19:00:22','2025-11-14 19:00:22'),(27,6,'Type','Digital BP Monitor','2025-11-14 19:00:22','2025-11-14 19:00:22'),(28,6,'Dosage/Size','Standard Cuff','2025-11-14 19:00:22','2025-11-14 19:00:22'),(29,6,'Origin','Japan','2025-11-14 19:00:22','2025-11-14 19:00:22'),(30,7,'Brand','GlucoCheck','2025-11-14 19:00:22','2025-11-14 19:00:22'),(31,7,'Type','Blood Glucose Meter','2025-11-14 19:00:22','2025-11-14 19:00:22'),(32,7,'Expiry Date','2026-12-31','2025-11-14 19:00:22','2025-11-14 19:00:22'),(33,7,'Dosage/Size','Starter Kit','2025-11-14 19:00:22','2025-11-14 19:00:22'),(34,7,'Origin','USA','2025-11-14 19:00:22','2025-11-14 19:00:22'),(35,8,'Brand','VitaHealth','2025-11-14 19:00:22','2025-11-14 19:00:22'),(36,8,'Type','Dietary Supplement','2025-11-14 19:00:22','2025-11-14 19:00:22'),(37,8,'Expiry Date','2026-06-30','2025-11-14 19:00:22','2025-11-14 19:00:22'),(38,8,'Dosage/Size','1000 IU','2025-11-14 19:00:22','2025-11-14 19:00:22'),(39,8,'Origin','Germany','2025-11-14 19:00:22','2025-11-14 19:00:22'),(40,9,'Brand','MediReady','2025-11-14 19:00:22','2025-11-14 19:00:22'),(41,9,'Type','First Aid Kit','2025-11-14 19:00:22','2025-11-14 19:00:22'),(42,9,'Dosage/Size','Large (100+ items)','2025-11-14 19:00:22','2025-11-14 19:00:22'),(43,9,'Origin','UK','2025-11-14 19:00:22','2025-11-14 19:00:22'),(44,10,'Brand','MobiScoot','2025-11-14 19:00:22','2025-11-14 19:00:22'),(45,10,'Max Load','150kg','2025-11-14 19:00:22','2025-11-14 19:00:22'),(46,10,'Adjustable','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(47,10,'Folding','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(48,10,'Material','Steel/Plastic','2025-11-14 19:00:22','2025-11-14 19:00:22'),(49,11,'Brand','WalkSafe','2025-11-14 19:00:22','2025-11-14 19:00:22'),(50,11,'Max Load','120kg','2025-11-14 19:00:22','2025-11-14 19:00:22'),(51,11,'Adjustable','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(52,11,'Folding','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(53,11,'Material','Aluminum','2025-11-14 19:00:22','2025-11-14 19:00:22'),(54,12,'Brand','SafeTransfer','2025-11-14 19:00:22','2025-11-14 19:00:22'),(55,12,'Max Load','180kg','2025-11-14 19:00:22','2025-11-14 19:00:22'),(56,12,'Adjustable','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(57,12,'Folding','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(58,12,'Material','Hardwood','2025-11-14 19:00:22','2025-11-14 19:00:22'),(59,13,'Brand','AccessEasy','2025-11-14 19:00:22','2025-11-14 19:00:22'),(60,13,'Max Load','300kg','2025-11-14 19:00:22','2025-11-14 19:00:22'),(61,13,'Adjustable','No','2025-11-14 19:00:22','2025-11-14 19:00:22'),(62,13,'Folding','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(63,13,'Material','Aluminum','2025-11-14 19:00:22','2025-11-14 19:00:22'),(64,14,'Brand','HearClear','2025-11-14 19:00:22','2025-11-14 19:00:22'),(65,14,'Battery Life','24 hours','2025-11-14 19:00:22','2025-11-14 19:00:22'),(66,14,'Connectivity','Bluetooth','2025-11-14 19:00:22','2025-11-14 19:00:22'),(67,14,'Warranty','2 years','2025-11-14 19:00:22','2025-11-14 19:00:22'),(68,14,'Model','HC-2024','2025-11-14 19:00:22','2025-11-14 19:00:22'),(69,15,'Brand','BrailleTech','2025-11-14 19:00:22','2025-11-14 19:00:22'),(70,15,'Battery Life','20 hours','2025-11-14 19:00:22','2025-11-14 19:00:22'),(71,15,'Connectivity','USB/Bluetooth','2025-11-14 19:00:22','2025-11-14 19:00:22'),(72,15,'Warranty','3 years','2025-11-14 19:00:22','2025-11-14 19:00:22'),(73,15,'Model','BT-40','2025-11-14 19:00:22','2025-11-14 19:00:22'),(74,16,'Brand','VoicePro','2025-11-14 19:00:22','2025-11-14 19:00:22'),(75,16,'Battery Life','12 hours','2025-11-14 19:00:22','2025-11-14 19:00:22'),(76,16,'Connectivity','Wired','2025-11-14 19:00:22','2025-11-14 19:00:22'),(77,16,'Warranty','1 year','2025-11-14 19:00:22','2025-11-14 19:00:22'),(78,16,'Model','VP-100','2025-11-14 19:00:22','2025-11-14 19:00:22'),(79,17,'Brand','EasyCall','2025-11-14 19:00:22','2025-11-14 19:00:22'),(80,17,'Battery Life','7 days standby','2025-11-14 19:00:22','2025-11-14 19:00:22'),(81,17,'Connectivity','2G/3G','2025-11-14 19:00:22','2025-11-14 19:00:22'),(82,17,'Warranty','1 year','2025-11-14 19:00:22','2025-11-14 19:00:22'),(83,17,'Model','EC-Senior','2025-11-14 19:00:22','2025-11-14 19:00:22'),(84,18,'Brand','ComfortCare','2025-11-14 19:00:22','2025-11-14 19:00:22'),(85,18,'Size','Large','2025-11-14 19:00:22','2025-11-14 19:00:22'),(86,18,'Material','Cotton blend','2025-11-14 19:00:22','2025-11-14 19:00:22'),(87,18,'Hypoallergenic','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22'),(88,19,'Brand','BathSafe','2025-11-14 19:00:22','2025-11-14 19:00:22'),(89,19,'Size','Standard','2025-11-14 19:00:22','2025-11-14 19:00:22'),(90,19,'Material','Aluminum/Plastic','2025-11-14 19:00:22','2025-11-14 19:00:22'),(91,19,'Hypoallergenic','N/A','2025-11-14 19:00:22','2025-11-14 19:00:22'),(92,20,'Brand','ComfortSeat','2025-11-14 19:00:22','2025-11-14 19:00:22'),(93,20,'Size','18\" x 16\"','2025-11-14 19:00:22','2025-11-14 19:00:22'),(94,20,'Material','Gel & Memory Foam','2025-11-14 19:00:22','2025-11-14 19:00:22'),(95,20,'Hypoallergenic','Yes','2025-11-14 19:00:22','2025-11-14 19:00:22');
/*!40000 ALTER TABLE `product_has_specifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `photo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `date_created` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `feature_photo` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_metrics`
--

DROP TABLE IF EXISTS `product_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metric` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `user` int(11) NOT NULL,
  `date_created` date NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `url` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_metrics`
--

LOCK TABLES `product_metrics` WRITE;
/*!40000 ALTER TABLE `product_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_rate`
--

DROP TABLE IF EXISTS `product_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_rate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `rate` tinyint(4) NOT NULL DEFAULT '0',
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_rate`
--

LOCK TABLES `product_rate` WRITE;
/*!40000 ALTER TABLE `product_rate` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_rate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_set_colors`
--

DROP TABLE IF EXISTS `product_set_colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_set_colors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_created` date NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user` int(11) NOT NULL,
  `product` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_set_colors`
--

LOCK TABLES `product_set_colors` WRITE;
/*!40000 ALTER TABLE `product_set_colors` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_set_colors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_sizes`
--

DROP TABLE IF EXISTS `product_sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_sizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `size` int(11) NOT NULL,
  `date_created` date NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `product` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_sizes`
--

LOCK TABLES `product_sizes` WRITE;
/*!40000 ALTER TABLE `product_sizes` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_sizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_sub_categories`
--

DROP TABLE IF EXISTS `product_sub_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_sub_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `user` int(11) NOT NULL,
  `date_created` date NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `url` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display` tinyint(1) NOT NULL DEFAULT '0',
  `priority` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_sub_categories`
--

LOCK TABLES `product_sub_categories` WRITE;
/*!40000 ALTER TABLE `product_sub_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_sub_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_supplier_images`
--

DROP TABLE IF EXISTS `product_supplier_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_supplier_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `photo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `date_created` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `feature_photo` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_supplier_images`
--

LOCK TABLES `product_supplier_images` WRITE;
/*!40000 ALTER TABLE `product_supplier_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_supplier_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_suppliers`
--

DROP TABLE IF EXISTS `product_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier` char(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contacts` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` char(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `user` int(11) NOT NULL,
  `date_created` date NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `url` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_suppliers`
--

LOCK TABLES `product_suppliers` WRITE;
/*!40000 ALTER TABLE `product_suppliers` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_views`
--

DROP TABLE IF EXISTS `product_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `comment` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_views`
--

LOCK TABLES `product_views` WRITE;
/*!40000 ALTER TABLE `product_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci,
  `metric` text COLLATE utf8mb4_unicode_ci,
  `currency` text COLLATE utf8mb4_unicode_ci,
  `description` mediumtext COLLATE utf8mb4_unicode_ci,
  `tags` longtext COLLATE utf8mb4_unicode_ci,
  `review_count` int(11) NOT NULL DEFAULT '0',
  `average_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `summary` text COLLATE utf8mb4_unicode_ci,
  `price_1` decimal(10,0) DEFAULT NULL,
  `points` int(11) DEFAULT '1' COMMENT 'Points earned by sponsor when this product is sold',
  `price_2` decimal(10,0) DEFAULT NULL,
  `feature_photo` text COLLATE utf8mb4_unicode_ci,
  `rates` text COLLATE utf8mb4_unicode_ci,
  `date_added` text COLLATE utf8mb4_unicode_ci,
  `date_updated` text COLLATE utf8mb4_unicode_ci,
  `user` int(11) DEFAULT NULL,
  `category` int(11) DEFAULT NULL,
  `sub_category` int(11) DEFAULT NULL,
  `supplier` int(11) DEFAULT NULL,
  `url` text COLLATE utf8mb4_unicode_ci,
  `status` text COLLATE utf8mb4_unicode_ci,
  `in_stock` text COLLATE utf8mb4_unicode_ci,
  `keywords` text COLLATE utf8mb4_unicode_ci,
  `p_type` text COLLATE utf8mb4_unicode_ci,
  `local_id` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `stripe_id` text COLLATE utf8mb4_unicode_ci,
  `stripe_price` text COLLATE utf8mb4_unicode_ci,
  `has_colors` text COLLATE utf8mb4_unicode_ci,
  `colors` text COLLATE utf8mb4_unicode_ci,
  `has_sizes` text COLLATE utf8mb4_unicode_ci,
  `sizes` text COLLATE utf8mb4_unicode_ci,
  `home_section_1` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No' COMMENT 'Flash Sales section - Yes/No',
  `home_section_2` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No' COMMENT 'Super Buyer section - Yes/No',
  `home_section_3` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No' COMMENT 'Top Products section - Yes/No',
  `is_compressed` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'no',
  `compress_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `compress_status_message` text COLLATE utf8mb4_unicode_ci,
  `original_size` decimal(15,2) DEFAULT '0.00',
  `compressed_size` decimal(15,2) DEFAULT '0.00',
  `compression_ratio` decimal(8,4) DEFAULT NULL,
  `compression_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_image_url` text COLLATE utf8mb4_unicode_ci,
  `compressed_image_url` text COLLATE utf8mb4_unicode_ci,
  `tinify_model_id` bigint(20) unsigned DEFAULT NULL,
  `compression_started_at` timestamp NULL DEFAULT NULL,
  `compression_completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_rating_index` (`average_rating`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Premium Manual Wheelchair',NULL,'UGX','<p>Lightweight aluminum wheelchair with comfortable padded seat and adjustable footrests. Perfect for daily mobility needs.</p><p><strong>Key Features:</strong></p><ul><li>Lightweight aluminum frame</li><li>Padded armrests</li><li>Adjustable footrests</li><li>Easy to fold</li><li>Anti-tip wheels</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,850000,5,977500,'images/products/premium-manual-wheelchair.jpg',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-1OJFFBIW','2025-12-05 13:04:36','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','Yes','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,'Adjustable Walking Crutches (Pair)',NULL,'UGX','<p>Height-adjustable aluminum crutches with comfortable grip handles and non-slip rubber tips.</p><p><strong>Key Features:</strong></p><ul><li>Height adjustable</li><li>Comfortable grip</li><li>Non-slip tips</li><li>Lightweight</li><li>Durable</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,45000,1,51750,'images/products/adjustable-walking-crutches-pair.jpg',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-N7O9DFP4','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,'Quad Walking Stick with Base',NULL,'UGX','<p>Stable quad-base walking stick providing maximum support and balance. Height adjustable.</p><p><strong>Key Features:</strong></p><ul><li>Four-point base</li><li>Height adjustable</li><li>Ergonomic handle</li><li>Lightweight</li><li>Maximum stability</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,35000,1,40250,'images/products/quad-walking-stick-with-base.jpg',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-AJREUWXL','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,'Hospital Bed with Side Rails',NULL,'UGX','<p>Adjustable hospital bed with manual crank system and safety side rails. Ideal for home care.</p><p><strong>Key Features:</strong></p><ul><li>Manual adjustment</li><li>Side safety rails</li><li>Sturdy steel frame</li><li>Mattress included</li><li>Easy assembly</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,1200000,1,1380000,'images/products/hospital-bed-with-side-rails.jpg',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-ISNGCOFX','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','Yes','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,'Commode Chair with Wheels',NULL,'UGX','<p>Mobile commode chair with removable bucket and wheels. Ideal for bedroom or bathroom use.</p><p><strong>Key Features:</strong></p><ul><li>Wheeled</li><li>Removable bucket</li><li>Padded seat</li><li>Armrests</li><li>Lockable wheels</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,180000,1,207000,'images/products/commode-chair-with-wheels.jpg',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-E6UD8CME','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'No','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,'Digital Blood Pressure Monitor',NULL,'UGX','<p>Automatic digital blood pressure monitor with large LCD display and memory function.</p><p><strong>Key Features:</strong></p><ul><li>Automatic measurement</li><li>Large LCD display</li><li>90 memory records</li><li>Irregular heartbeat detection</li><li>Portable</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,65000,1,74750,'images/products/digital-blood-pressure-monitor.jpg',NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-MV9CNFL7','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','Yes','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,'Glucose Monitoring Kit',NULL,'UGX','<p>Complete glucose monitoring system with meter, 50 test strips, and lancets.</p><p><strong>Key Features:</strong></p><ul><li>Fast results</li><li>50 test strips included</li><li>Memory storage</li><li>Easy to use</li><li>Carrying case</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,85000,1,97750,'images/products/glucose-monitoring-kit.jpg',NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-1V6SBCSK','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,'Vitamin D3 Supplements (60 Capsules)',NULL,'UGX','<p>High-quality Vitamin D3 supplements for bone health and immunity. 60 capsules.</p><p><strong>Key Features:</strong></p><ul><li>1000 IU per capsule</li><li>60 day supply</li><li>Supports bone health</li><li>Boosts immunity</li><li>Easy to swallow</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,25000,1,28750,'images/products/vitamin-d3-supplements-60-capsules.jpg',NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-FU2KYEDI','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'No','No','Yes','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,'First Aid Kit - Complete',NULL,'UGX','<p>Comprehensive first aid kit with bandages, antiseptics, and emergency supplies.</p><p><strong>Key Features:</strong></p><ul><li>100+ pieces</li><li>Bandages & gauze</li><li>Antiseptic wipes</li><li>Scissors & tweezers</li><li>Portable case</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,45000,1,51750,'images/products/first-aid-kit-complete.jpg',NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-BKRQYV2U','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,'Electric Mobility Scooter',NULL,'UGX','<p>4-wheel electric mobility scooter with long battery life and comfortable seat.</p><p><strong>Key Features:</strong></p><ul><li>4-wheel stability</li><li>30km range</li><li>Adjustable seat</li><li>LED headlights</li><li>Storage basket</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,2500000,1,2875000,'images/products/electric-mobility-scooter.jpg',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-NLBHKYPS','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,'Rollator Walker with Seat',NULL,'UGX','<p>Four-wheel rollator walker with built-in seat, hand brakes, and storage basket.</p><p><strong>Key Features:</strong></p><ul><li>Built-in seat</li><li>Hand brakes</li><li>Storage basket</li><li>Foldable</li><li>Height adjustable</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,220000,1,253000,'images/products/rollator-walker-with-seat.jpg',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-YNDUCNG8','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(12,'Transfer Board for Wheelchair',NULL,'UGX','<p>Smooth transfer board for safe wheelchair transfers. Non-slip surface.</p><p><strong>Key Features:</strong></p><ul><li>Non-slip surface</li><li>Smooth transfers</li><li>Durable hardwood</li><li>Ergonomic design</li><li>Easy to clean</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,55000,1,63250,'images/products/transfer-board-for-wheelchair.jpg',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-I4WDNIOH','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','Yes','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(13,'Portable Ramp - Wheelchair Access',NULL,'UGX','<p>Lightweight aluminum ramp for wheelchair access. Foldable and portable.</p><p><strong>Key Features:</strong></p><ul><li>Foldable design</li><li>Non-slip surface</li><li>Lightweight</li><li>6 feet length</li><li>Carrying handle</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,350000,1,402500,'images/products/portable-ramp-wheelchair-access.jpg',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-7W6AZHQB','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'No','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(14,'Digital Hearing Aid (Pair)',NULL,'UGX','<p>Advanced digital hearing aids with noise reduction and rechargeable battery.</p><p><strong>Key Features:</strong></p><ul><li>Digital sound processing</li><li>Noise reduction</li><li>Rechargeable</li><li>Bluetooth compatible</li><li>Discreet design</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,450000,1,517500,'images/products/digital-hearing-aid-pair.jpg',NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-LPFTJWXA','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','Yes','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(15,'Braille Display Device',NULL,'UGX','<p>Electronic braille display device with 40 cells. USB and Bluetooth connectivity.</p><p><strong>Key Features:</strong></p><ul><li>40 braille cells</li><li>USB & Bluetooth</li><li>Compatible with screen readers</li><li>Portable</li><li>Rechargeable</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,1800000,1,2070000,'images/products/braille-display-device.jpg',NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-O6I1YIKS','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'No','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(16,'Voice Amplifier for Speech Aid',NULL,'UGX','<p>Portable voice amplifier with headset microphone for clear communication.</p><p><strong>Key Features:</strong></p><ul><li>Clear amplification</li><li>Headset microphone</li><li>Rechargeable battery</li><li>Adjustable volume</li><li>Portable</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,95000,1,109250,'images/products/voice-amplifier-for-speech-aid.jpg',NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-DRLTRCAR','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(17,'Large Button Phone for Seniors',NULL,'UGX','<p>Easy-to-use phone with large buttons, loud speaker, and emergency button.</p><p><strong>Key Features:</strong></p><ul><li>Large buttons</li><li>Loud speaker</li><li>Emergency SOS button</li><li>Photo memory dial</li><li>Hearing aid compatible</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,75000,1,86250,'images/products/large-button-phone-for-seniors.jpg',NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-JILCOMHE','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'No','No','Yes','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(18,'Adult Diapers (Pack of 10)',NULL,'UGX','<p>Premium adult diapers with high absorbency and odor control. Pack of 10.</p><p><strong>Key Features:</strong></p><ul><li>High absorbency</li><li>Odor control</li><li>Comfortable fit</li><li>Leak-proof</li><li>Skin-friendly</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,35000,1,40250,'images/products/adult-diapers-pack-of-10.jpg',NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-KHXAJLWY','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'No','No','Yes','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(19,'Shower Chair with Back Support',NULL,'UGX','<p>Sturdy shower chair with comfortable back support and non-slip feet.</p><p><strong>Key Features:</strong></p><ul><li>Back support</li><li>Non-slip feet</li><li>Drainage holes</li><li>Height adjustable</li><li>Rust-resistant</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,95000,1,109250,'images/products/shower-chair-with-back-support.jpg',NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-BZICSYHC','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'No','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(20,'Pressure Relief Cushion',NULL,'UGX','<p>Medical-grade pressure relief cushion for wheelchair users. Prevents pressure sores.</p><p><strong>Key Features:</strong></p><ul><li>Pressure relief</li><li>Gel & foam</li><li>Breathable cover</li><li>Washable</li><li>Anti-slip bottom</li></ul>','disability,wellness,dtehm,medical,healthcare',0,0.00,NULL,65000,1,74750,'images/products/pressure-relief-cushion.jpg',NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'DTEHM-DGNRWBXS','2025-11-14 22:00:22','2025-11-14 22:00:22',NULL,NULL,'No',NULL,'No',NULL,'Yes','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(21,'Gavin Shepherd',NULL,'UGX','<p>Mollitia inventore r...</p>',NULL,0,0.00,NULL,100000,1,NULL,'images/dc1adb0f7c1c8564c14220031f7e086a.png',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,'active','Yes',NULL,'product','1764880700-185886-699370','2025-12-05 14:50:10','2025-12-04 20:39:17',NULL,NULL,'No',NULL,'No',NULL,'No','No','No','no',NULL,NULL,0.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products_best_deals`
--

DROP TABLE IF EXISTS `products_best_deals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products_best_deals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product` int(11) NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products_best_deals`
--

LOCK TABLES `products_best_deals` WRITE;
/*!40000 ALTER TABLE `products_best_deals` DISABLE KEYS */;
/*!40000 ALTER TABLE `products_best_deals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products_featured`
--

DROP TABLE IF EXISTS `products_featured`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products_featured` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product` int(11) NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products_featured`
--

LOCK TABLES `products_featured` WRITE;
/*!40000 ALTER TABLE `products_featured` DISABLE KEYS */;
/*!40000 ALTER TABLE `products_featured` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products_search_base`
--

DROP TABLE IF EXISTS `products_search_base`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products_search_base` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) DEFAULT NULL,
  `search_term` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products_search_base`
--

LOCK TABLES `products_search_base` WRITE;
/*!40000 ALTER TABLE `products_search_base` DISABLE KEYS */;
/*!40000 ALTER TABLE `products_search_base` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products_searched`
--

DROP TABLE IF EXISTS `products_searched`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products_searched` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) DEFAULT NULL,
  `product` int(11) NOT NULL,
  `search_query` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products_searched`
--

LOCK TABLES `products_searched` WRITE;
/*!40000 ALTER TABLE `products_searched` DISABLE KEYS */;
/*!40000 ALTER TABLE `products_searched` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products_special_offers`
--

DROP TABLE IF EXISTS `products_special_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products_special_offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product` int(11) NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products_special_offers`
--

LOCK TABLES `products_special_offers` WRITE;
/*!40000 ALTER TABLE `products_special_offers` DISABLE KEYS */;
/*!40000 ALTER TABLE `products_special_offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products_view_location`
--

DROP TABLE IF EXISTS `products_view_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products_view_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_view_id` int(11) NOT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products_view_location`
--

LOCK TABLES `products_view_location` WRITE;
/*!40000 ALTER TABLE `products_view_location` DISABLE KEYS */;
/*!40000 ALTER TABLE `products_view_location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products_viewed`
--

DROP TABLE IF EXISTS `products_viewed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products_viewed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product` int(11) NOT NULL,
  `user` int(11) DEFAULT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products_viewed`
--

LOCK TABLES `products_viewed` WRITE;
/*!40000 ALTER TABLE `products_viewed` DISABLE KEYS */;
/*!40000 ALTER TABLE `products_viewed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_shares`
--

DROP TABLE IF EXISTS `project_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_shares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `investor_id` bigint(20) unsigned NOT NULL COMMENT 'User who bought the shares',
  `purchase_date` date NOT NULL,
  `number_of_shares` int(11) NOT NULL COMMENT 'Shares bought in this transaction',
  `total_amount_paid` decimal(15,2) NOT NULL COMMENT 'number_of_shares * share_price at purchase time',
  `share_price_at_purchase` decimal(15,2) NOT NULL COMMENT 'Share price at time of purchase',
  `payment_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Link to universal_payments',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_shares_project_id_index` (`project_id`),
  KEY `project_shares_investor_id_index` (`investor_id`),
  KEY `project_shares_payment_id_index` (`payment_id`),
  KEY `project_shares_purchase_date_index` (`purchase_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_shares`
--

LOCK TABLES `project_shares` WRITE;
/*!40000 ALTER TABLE `project_shares` DISABLE KEYS */;
INSERT INTO `project_shares` VALUES (1,4,180,'2025-11-24',5,500.00,100.00,16,'2025-11-24 17:24:31','2025-11-24 17:24:31',NULL);
/*!40000 ALTER TABLE `project_shares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_transactions`
--

DROP TABLE IF EXISTS `project_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL COMMENT 'Positive for income, negative for expense',
  `transaction_date` date NOT NULL,
  `created_by_id` bigint(20) unsigned NOT NULL COMMENT 'Admin user who created the transaction',
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` enum('share_purchase','project_profit','project_expense','returns_distribution') COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_share_id` bigint(20) unsigned DEFAULT NULL COMMENT 'If source is share_purchase',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_transactions_project_id_index` (`project_id`),
  KEY `project_transactions_created_by_id_index` (`created_by_id`),
  KEY `project_transactions_type_index` (`type`),
  KEY `project_transactions_source_index` (`source`),
  KEY `project_transactions_transaction_date_index` (`transaction_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_transactions`
--

LOCK TABLES `project_transactions` WRITE;
/*!40000 ALTER TABLE `project_transactions` DISABLE KEYS */;
INSERT INTO `project_transactions` VALUES (1,4,500.00,'2025-11-24',180,'Share purchase by Kule Swaleh - 5 shares','income','share_purchase',1,'2025-11-24 17:24:31','2025-11-24 17:24:31',NULL);
/*!40000 ALTER TABLE `project_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('ongoing','completed','on_hold') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ongoing',
  `share_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_shares` int(11) NOT NULL DEFAULT '0' COMMENT 'Computed: total shares sold',
  `shares_sold` int(11) NOT NULL DEFAULT '0' COMMENT 'Computed: same as total_shares',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_investment` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Computed: sum of share purchases',
  `total_returns` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Computed: sum of returns distributed',
  `total_expenses` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Computed: sum of expenses',
  `total_profits` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Computed: sum of profits',
  `created_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `net_profit` bigint(20) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `projects_status_index` (`status`),
  KEY `projects_created_by_id_index` (`created_by_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,'Medicine Distribution Partnership','**Build Wealth While Healing Communities**\n\nBecome a DTEHM medicine distribution partner and earn returns while bringing holistic healthcare to patients in your community.\n\n**What You Get:**\n• DTEHM medicine stock worth UGX 20M for patient distribution\n• Comprehensive training on product knowledge & sales\n• Marketing support & ongoing business guidance\n• Direct supply chain from DTEHM Health Ministries\n• Flexible restocking & inventory management\n\n**Perfect for:** Entrepreneurs passionate about health and wellness who want to combine business with social impact.','2025-11-12',NULL,'ongoing',5000000.00,0,0,NULL,0.00,0.00,0.00,0.00,1,'2025-11-12 06:44:00','2025-11-12 06:44:00',NULL,0),(2,'Farm-to-Profit Initiative','**Learn, Grow, Earn with DTEHM Experts**\n\nMaster modern fish farming and poultry production with hands-on training from DTEHM agricultural specialists. We provide everything you need to start your profitable farm.\n\n**What You Get:**\n• Expert-led training in fish ponds & poultry management\n• Startup inputs & farming essentials provided\n• Ongoing technical support & farm visits\n• Market linkage for your produce\n• Proven farming techniques for maximum yield\n\n**Ideal for:** Aspiring farmers, rural development enthusiasts, and food security advocates ready to build sustainable income.','2025-11-12',NULL,'ongoing',1000000.00,0,0,NULL,0.00,0.00,0.00,0.00,1,'2025-11-12 06:44:00','2025-11-12 06:44:00',NULL,0),(3,'Property Wealth Builder','**Invest Small, Earn Big in Real Estate**\n\nAccess DTEHM\'s exclusive real estate portfolio with minimal entry capital. Build long-term wealth through property appreciation and rental income.\n\n**What You Get:**\n• Co-ownership in DTEHM real estate projects\n• Prime locations with high appreciation potential\n• Professional property management included\n• Transparent quarterly earnings reports\n• Low entry barrier for first-time investors\n\n**Perfect for:** Anyone wanting to enter real estate investment without massive capital requirements.','2025-11-12',NULL,'ongoing',10000.00,0,0,NULL,0.00,0.00,0.00,0.00,1,'2025-11-12 06:44:00','2025-11-12 06:44:00',NULL,0),(4,'Motorcycle Taxi Fleet','**Ride the Wave of Uganda\'s Transport Revolution**\r\n\r\nInvest in DTEHM\'s managed motorcycle taxi fleet and earn daily returns from Uganda\'s fastest-growing transport sector.\r\n\r\n**What You Get:**\r\n• Well-maintained motorcycles in high-demand areas\r\n• Vetted, trained riders with insurance coverage\r\n• Daily income tracking & transparent reporting\r\n• Fleet management & maintenance included\r\n• Rider accountability systems\r\n\r\n**Ideal for:** Investors seeking regular cash flow from Uganda\'s thriving boda boda transport industry.','2025-11-12','2025-11-24','ongoing',100.00,100,5,NULL,500.00,0.00,0.00,0.00,1,'2025-11-12 06:44:00','2025-11-24 17:24:31',NULL,0);
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `rating` int(11) NOT NULL COMMENT 'Rating between 1-5',
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_product_review` (`product_id`,`user_id`),
  KEY `product_rating_index` (`product_id`,`rating`),
  KEY `user_reviews_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_histories`
--

DROP TABLE IF EXISTS `search_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `search_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `search_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_ids` json DEFAULT NULL,
  `results_count` int(11) NOT NULL DEFAULT '0',
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `search_histories_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `search_histories_session_id_created_at_index` (`session_id`,`created_at`),
  KEY `search_histories_search_text_index` (`search_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_histories`
--

LOCK TABLES `search_histories` WRITE;
/*!40000 ALTER TABLE `search_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `search_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_requests`
--

DROP TABLE IF EXISTS `supplier_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `date_requested` date NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `business_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `items_sold` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `district` tinyint(4) NOT NULL,
  `country` tinyint(4) NOT NULL,
  `business_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Whole sale',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_requests`
--

LOCK TABLES `supplier_requests` WRITE;
/*!40000 ALTER TABLE `supplier_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_configurations`
--

DROP TABLE IF EXISTS `system_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `company_name` text COLLATE utf8mb4_unicode_ci,
  `company_email` text COLLATE utf8mb4_unicode_ci,
  `company_phone` text COLLATE utf8mb4_unicode_ci,
  `company_pobox` text COLLATE utf8mb4_unicode_ci,
  `company_address` text COLLATE utf8mb4_unicode_ci,
  `company_website` text COLLATE utf8mb4_unicode_ci,
  `company_logo` text COLLATE utf8mb4_unicode_ci,
  `company_details` text COLLATE utf8mb4_unicode_ci,
  `insurance_start_date` datetime DEFAULT NULL,
  `insurance_price` int(11) DEFAULT NULL,
  `dtehm_membership_fee` int(11) DEFAULT '76000',
  `dip_membership_fee` int(11) DEFAULT '20000',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'UGX',
  `minimum_investment_amount` int(11) DEFAULT '10000',
  `share_price` int(11) DEFAULT '50000',
  `referral_bonus_percentage` decimal(5,2) DEFAULT '5.00',
  `app_version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '1.0.0',
  `force_update` tinyint(1) DEFAULT '0',
  `maintenance_mode` tinyint(1) DEFAULT '0',
  `maintenance_message` text COLLATE utf8mb4_unicode_ci,
  `contact_phone` text COLLATE utf8mb4_unicode_ci,
  `contact_email` text COLLATE utf8mb4_unicode_ci,
  `contact_address` text COLLATE utf8mb4_unicode_ci,
  `social_facebook` text COLLATE utf8mb4_unicode_ci,
  `social_twitter` text COLLATE utf8mb4_unicode_ci,
  `social_instagram` text COLLATE utf8mb4_unicode_ci,
  `social_linkedin` text COLLATE utf8mb4_unicode_ci,
  `payment_gateway` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pesapal',
  `payment_callback_url` text COLLATE utf8mb4_unicode_ci,
  `terms_and_conditions` text COLLATE utf8mb4_unicode_ci,
  `privacy_policy` text COLLATE utf8mb4_unicode_ci,
  `about_us` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_configurations`
--

LOCK TABLES `system_configurations` WRITE;
/*!40000 ALTER TABLE `system_configurations` DISABLE KEYS */;
INSERT INTO `system_configurations` VALUES (1,'2025-11-24 18:12:20','2025-11-24 15:31:39','DTEHM Health Insurance','info@dtehmhealth.com','+256 700 000 000',NULL,NULL,NULL,NULL,NULL,'2025-11-24 18:31:19',0,300,200,'UGX',10000,50000,5.00,'1.0.0',0,0,'System is under maintenance. Please try again later.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pesapal',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `system_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tinify_models`
--

DROP TABLE IF EXISTS `tinify_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tinify_models` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `usage_count` int(11) NOT NULL DEFAULT '0',
  `monthly_limit` int(11) NOT NULL DEFAULT '500',
  `compressions_this_month` int(11) NOT NULL DEFAULT '0',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `last_reset_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tinify_models_api_key_unique` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tinify_models`
--

LOCK TABLES `tinify_models` WRITE;
/*!40000 ALTER TABLE `tinify_models` DISABLE KEYS */;
/*!40000 ALTER TABLE `tinify_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('DEPOSIT','WITHDRAWAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DEPOSIT',
  `description` text COLLATE utf8mb4_unicode_ci,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` enum('CASH','MOBILE_MONEY','BANK_TRANSFER','CHEQUE','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `payment_phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_account_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('PENDING','COMPLETED','FAILED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPLETED',
  `transaction_date` date DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `receipt_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_reference_number_unique` (`reference_number`),
  KEY `transactions_insurance_user_id_index` (`user_id`),
  KEY `transactions_type_index` (`type`),
  KEY `transactions_status_index` (`status`),
  KEY `transactions_transaction_date_index` (`transaction_date`),
  KEY `transactions_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tx_charge`
--

DROP TABLE IF EXISTS `tx_charge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tx_charge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `charge` float NOT NULL,
  `_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tx_charge`
--

LOCK TABLES `tx_charge` WRITE;
/*!40000 ALTER TABLE `tx_charge` DISABLE KEYS */;
/*!40000 ALTER TABLE `tx_charge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `universal_payments`
--

DROP TABLE IF EXISTS `universal_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `universal_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `number_of_shares` int(11) DEFAULT NULL COMMENT 'For share purchases',
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_items` json NOT NULL,
  `items_count` int(11) NOT NULL DEFAULT '1',
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UGX',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_gateway` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pesapal',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `payment_status_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_order_tracking_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_merchant_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_redirect_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_callback_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_notification_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesapal_status_code` int(11) DEFAULT NULL,
  `pesapal_response` text COLLATE utf8mb4_unicode_ci,
  `confirmation_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `items_processed` tinyint(1) NOT NULL DEFAULT '0',
  `items_processed_at` timestamp NULL DEFAULT NULL,
  `processing_notes` text COLLATE utf8mb4_unicode_ci,
  `processed_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_ipn_at` timestamp NULL DEFAULT NULL,
  `ipn_count` int(11) NOT NULL DEFAULT '0',
  `last_status_check` timestamp NULL DEFAULT NULL,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refund_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `retry_count` int(11) NOT NULL DEFAULT '0',
  `last_retry_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `universal_payments_payment_reference_unique` (`payment_reference`),
  UNIQUE KEY `universal_payments_pesapal_order_tracking_id_unique` (`pesapal_order_tracking_id`),
  UNIQUE KEY `universal_payments_pesapal_merchant_reference_unique` (`pesapal_merchant_reference`),
  KEY `universal_payments_payment_type_index` (`payment_type`),
  KEY `universal_payments_payment_category_index` (`payment_category`),
  KEY `universal_payments_user_id_index` (`user_id`),
  KEY `universal_payments_status_index` (`status`),
  KEY `universal_payments_payment_gateway_index` (`payment_gateway`),
  KEY `universal_payments_pesapal_order_tracking_id_index` (`pesapal_order_tracking_id`),
  KEY `universal_payments_pesapal_merchant_reference_index` (`pesapal_merchant_reference`),
  KEY `universal_payments_created_at_index` (`created_at`),
  KEY `universal_payments_payment_type_status_index` (`payment_type`,`status`),
  KEY `universal_payments_project_id_index` (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `universal_payments`
--

LOCK TABLES `universal_payments` WRITE;
/*!40000 ALTER TABLE `universal_payments` DISABLE KEYS */;
INSERT INTO `universal_payments` VALUES (1,'UNI-PAY-1764004824-BJ26C8','MEMBERSHIP','membership',180,NULL,NULL,'Kule Swaleh','0772111117@dtehm.app','0772111117',NULL,'[{\"amount\": 96000, \"description\": \"DTEHM + DIP Membership Fee\"}]',1,500.00,'UGX','DTEHM + DIP Membership Fee - One-time payment','pesapal',NULL,NULL,'COMPLETED','1','Completed','8502308a-4b03-4b23-97cf-db0d4cbfce61','PAYMENT_1_1764004824','https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61','http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback',NULL,1,'{\"payment_method\":\"AirtelUG\",\"amount\":96000,\"created_date\":\"2025-11-24T20:41:18.263\",\"confirmation_code\":\"135422827128\",\"order_tracking_id\":\"8502308a-4b03-4b23-97cf-db0d4cbfce61\",\"payment_status_description\":\"Completed\",\"description\":null,\"message\":\"Request processed successfully\",\"payment_account\":\"706638494\",\"call_back_url\":\"http:\\/\\/10.0.2.2:8888\\/dtehm-insurance-api\\/api\\/universal-payments\\/callback?OrderTrackingId=8502308a-4b03-4b23-97cf-db0d4cbfce61&OrderMerchantReference=PAYMENT_1_1764004824\",\"status_code\":1,\"merchant_reference\":\"PAYMENT_1_1764004824\",\"account_number\":null,\"payment_status_code\":\"\",\"currency\":\"UGX\",\"error\":{\"error_type\":null,\"code\":null,\"message\":null},\"status\":\"200\"}','135422827128','2025-11-24 14:41:54','2025-11-24 14:41:54',0,NULL,NULL,NULL,NULL,0,'2025-11-24 14:41:54',0.00,NULL,NULL,NULL,'127.0.0.1','Dart/3.8 (dart:io)',NULL,0,NULL,'180','Admin User',NULL,'2025-11-24 14:20:24','2025-11-24 14:41:54'),(2,'UNI-PAY-1764006119-UTILNP','membership','membership',180,NULL,NULL,'Kule Swaleh','0772111117@dtehm.app','0772111117',NULL,'[{\"id\": \"1\", \"type\": \"membership\", \"amount\": \"96000.0\", \"metadata\": null, \"description\": \"DTEHM + DIP Membership Fee\"}]',1,96000.00,'UGX','DTEHM + DIP Membership Fee - One-time payment','pesapal',NULL,NULL,'PENDING','0','INVALID','bf94c5dc-a7a5-4c91-9798-db0d622b9de1','PAYMENT_2_1764006119','https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=bf94c5dc-a7a5-4c91-9798-db0d622b9de1','http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback',NULL,0,'{\"payment_method\":\"\",\"amount\":96000,\"created_date\":\"2025-11-24T20:42:02.113\",\"confirmation_code\":\"\",\"order_tracking_id\":\"bf94c5dc-a7a5-4c91-9798-db0d622b9de1\",\"payment_status_description\":\"INVALID\",\"description\":null,\"message\":\"Request processed successfully\",\"payment_account\":\"\",\"call_back_url\":\"http:\\/\\/10.0.2.2:8888\\/dtehm-insurance-api\\/api\\/universal-payments\\/callback?OrderTrackingId=bf94c5dc-a7a5-4c91-9798-db0d622b9de1&OrderMerchantReference=PAYMENT_2_1764006119\",\"status_code\":0,\"merchant_reference\":\"PAYMENT_2_1764006119\",\"account_number\":null,\"payment_status_code\":\"\",\"currency\":\"UGX\",\"error\":{\"error_type\":\"api_error\",\"code\":\"payment_details_not_found\",\"message\":\"Pending Payment\",\"call_back_url\":\"http:\\/\\/10.0.2.2:8888\\/dtehm-insurance-api\\/api\\/universal-payments\\/callback?OrderTrackingId=bf94c5dc-a7a5-4c91-9798-db0d622b9de1&OrderMerchantReference=PAYMENT_2_1764006119\"},\"status\":\"500\"}','',NULL,NULL,0,NULL,NULL,NULL,NULL,0,'2025-11-24 14:42:08',0.00,NULL,NULL,NULL,'127.0.0.1','Dart/3.8 (dart:io)',NULL,0,NULL,'180',NULL,NULL,'2025-11-24 14:41:59','2025-11-24 14:42:08'),(3,'UNI-PAY-1764009692-GMVKLJ','membership','membership',180,NULL,NULL,'Kule Swaleh','0772111117@dtehm.app','0772111117',NULL,'[{\"id\": \"1\", \"type\": \"membership\", \"amount\": \"500.0\", \"metadata\": null, \"description\": \"DTEHM + DIP Membership Fee\"}]',1,500.00,'UGX','DTEHM + DIP Membership Fee - One-time payment','pesapal',NULL,NULL,'COMPLETED','1','Completed','df7b8ed0-912b-4592-ae3e-db0d33e2bd50','PAYMENT_3_1764009692','https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50','http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback',NULL,1,'{\"payment_method\":\"AirtelUG\",\"amount\":500,\"created_date\":\"2025-11-24T21:42:53.853\",\"confirmation_code\":\"135427865534\",\"order_tracking_id\":\"df7b8ed0-912b-4592-ae3e-db0d33e2bd50\",\"payment_status_description\":\"Completed\",\"description\":null,\"message\":\"Request processed successfully\",\"payment_account\":\"706638494\",\"call_back_url\":\"http:\\/\\/10.0.2.2:8888\\/dtehm-insurance-api\\/api\\/universal-payments\\/callback?OrderTrackingId=df7b8ed0-912b-4592-ae3e-db0d33e2bd50&OrderMerchantReference=PAYMENT_3_1764009692\",\"status_code\":1,\"merchant_reference\":\"PAYMENT_3_1764009692\",\"account_number\":null,\"payment_status_code\":\"\",\"currency\":\"UGX\",\"error\":{\"error_type\":null,\"code\":null,\"message\":null},\"status\":\"200\"}','135427865534','2025-11-24 15:43:25','2025-11-24 15:43:25',1,'2025-11-24 15:43:15','All 1 items processed successfully',NULL,NULL,0,'2025-11-24 15:43:25',0.00,NULL,NULL,NULL,'127.0.0.1','Dart/3.8 (dart:io)',NULL,0,NULL,'180',NULL,NULL,'2025-11-24 15:41:32','2025-11-24 15:43:25'),(4,'UNI-PAY-1764010523-J8LYSQ','MEMBERSHIP','membership_fee',2,NULL,NULL,'Abel Knowles','pefunuh@mailinator.com','+256706638484',NULL,'[{\"id\": 2, \"type\": \"membership\", \"amount\": 300, \"metadata\": {\"scenario\": \"dtehm_only\", \"is_dip_member\": \"No\", \"is_dtehm_member\": \"Yes\"}, \"description\": \"DTEHM Membership Payment\"}]',1,300.00,'UGX','DTEHM Membership Payment','test','test',NULL,'COMPLETED','1','Payment completed successfully (TEST)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:55:23','2025-11-24 15:55:23',0,NULL,NULL,NULL,NULL,0,NULL,0.00,NULL,NULL,NULL,'::1','curl/8.7.1',NULL,0,NULL,'2',NULL,NULL,'2025-11-24 15:55:23','2025-11-24 15:55:23'),(5,'UNI-PAY-1764010658-UIXVVF','MEMBERSHIP','membership_fee',2,NULL,NULL,'Abel Knowles','pefunuh@mailinator.com','+256706638484',NULL,'[{\"id\": 2, \"type\": \"membership\", \"amount\": 300, \"metadata\": {\"scenario\": \"dtehm_only\", \"is_dip_member\": \"No\", \"is_dtehm_member\": \"Yes\"}, \"description\": \"DTEHM Membership Payment\"}]',1,300.00,'UGX','DTEHM Membership Payment','test','test',NULL,'COMPLETED','1','Payment completed successfully (TEST)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 15:57:38','2025-11-24 15:57:38',1,'2025-11-24 15:57:38','All 1 items processed successfully',NULL,NULL,0,NULL,0.00,NULL,NULL,NULL,'::1','curl/8.7.1',NULL,0,NULL,'2',NULL,NULL,'2025-11-24 15:57:38','2025-11-24 15:57:38'),(6,'UNI-PAY-1764011319-CQUMRX','MEMBERSHIP','membership_fee',2,NULL,NULL,'Abel Knowles','pefunuh@mailinator.com','+256706638484',NULL,'[{\"id\": 2, \"type\": \"membership\", \"amount\": 300, \"metadata\": {\"scenario\": \"dtehm_only\", \"is_dip_member\": \"No\", \"is_dtehm_member\": \"Yes\"}, \"description\": \"DTEHM Membership Payment\"}]',1,300.00,'UGX','DTEHM Membership Payment','test','test',NULL,'COMPLETED','1','Payment completed successfully (TEST)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 16:08:39','2025-11-24 16:08:39',1,'2025-11-24 16:08:39','All 1 items processed successfully',NULL,NULL,0,NULL,0.00,NULL,NULL,NULL,'::1','curl/8.7.1',NULL,0,NULL,'2',NULL,NULL,'2025-11-24 16:08:39','2025-11-24 16:08:39'),(7,'UNI-PAY-1764011610-BKIE14','MEMBERSHIP','membership_fee',2,NULL,NULL,'Abel Knowles','pefunuh@mailinator.com','+256706638484',NULL,'[{\"id\": 2, \"type\": \"membership\", \"amount\": 300, \"metadata\": {\"scenario\": \"dtehm_only\", \"is_dip_member\": \"No\", \"is_dtehm_member\": \"Yes\"}, \"description\": \"DTEHM Membership Payment\"}]',1,300.00,'UGX','DTEHM Membership Payment','test','test',NULL,'COMPLETED','1','Payment completed successfully (TEST)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 16:13:30','2025-11-24 16:13:30',1,'2025-11-24 16:13:30','All 1 items processed successfully',NULL,NULL,0,NULL,0.00,NULL,NULL,NULL,'::1','curl/8.7.1',NULL,0,NULL,'2',NULL,NULL,'2025-11-24 16:13:30','2025-11-24 16:13:30'),(8,'UNI-PAY-1764011925-JFW8SR','MEMBERSHIP','membership_fee',2,NULL,NULL,'Abel Knowles','pefunuh@mailinator.com','+256706638484',NULL,'[{\"id\": 2, \"type\": \"membership\", \"amount\": 300, \"metadata\": {\"scenario\": \"dtehm_only\", \"is_dip_member\": \"No\", \"is_dtehm_member\": \"Yes\"}, \"description\": \"DTEHM Membership Payment\"}]',1,300.00,'UGX','DTEHM Membership Payment','test','test',NULL,'COMPLETED','1','Payment completed successfully (TEST)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 16:18:45','2025-11-24 16:18:45',1,'2025-11-24 16:18:45','All 1 items processed successfully',NULL,NULL,0,NULL,0.00,NULL,NULL,NULL,'::1','curl/8.7.1',NULL,0,NULL,'2',NULL,NULL,'2025-11-24 16:18:45','2025-11-24 16:18:45'),(9,'UNI-PAY-1764012031-SPPIZM','MEMBERSHIP','membership_fee',2,NULL,NULL,'Abel Knowles','pefunuh@mailinator.com','+256706638484',NULL,'[{\"id\": 2, \"type\": \"membership\", \"amount\": 300, \"metadata\": {\"scenario\": \"dtehm_only\", \"is_dip_member\": \"No\", \"is_dtehm_member\": \"Yes\"}, \"description\": \"DTEHM Membership Payment\"}]',1,300.00,'UGX','DTEHM Membership Payment','test','test',NULL,'COMPLETED','1','Payment completed successfully (TEST)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 16:20:31','2025-11-24 16:20:31',1,'2025-11-24 16:20:31','All 1 items processed successfully',NULL,NULL,0,NULL,0.00,NULL,NULL,NULL,'::1','curl/8.7.1',NULL,0,NULL,'2',NULL,NULL,'2025-11-24 16:20:31','2025-11-24 16:20:31'),(10,'UNI-PAY-1764012135-ZSCBTG','MEMBERSHIP','membership_fee',2,NULL,NULL,'Abel Knowles','pefunuh@mailinator.com','+256706638484',NULL,'[{\"id\": 2, \"type\": \"membership\", \"amount\": 300, \"metadata\": {\"scenario\": \"dtehm_only\", \"is_dip_member\": \"No\", \"is_dtehm_member\": \"Yes\"}, \"description\": \"DTEHM Membership Payment\"}]',1,300.00,'UGX','DTEHM Membership Payment','test','test',NULL,'COMPLETED','1','Payment completed successfully (TEST)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 16:22:15','2025-11-24 16:22:15',0,NULL,NULL,NULL,NULL,0,NULL,0.00,NULL,NULL,NULL,'::1','curl/8.7.1',NULL,0,NULL,'2',NULL,NULL,'2025-11-24 16:22:15','2025-11-24 16:22:15'),(11,'UNI-PAY-1764012193-6DJKXQ','MEMBERSHIP','membership_fee',2,NULL,NULL,'Abel Knowles','pefunuh@mailinator.com','+256706638484',NULL,'[{\"id\": 2, \"type\": \"membership\", \"amount\": 300, \"metadata\": {\"scenario\": \"dtehm_only\", \"is_dip_member\": \"No\", \"is_dtehm_member\": \"Yes\"}, \"description\": \"DTEHM Membership Payment\"}]',1,300.00,'UGX','DTEHM Membership Payment','test','test',NULL,'COMPLETED','1','Payment completed successfully (TEST)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 16:23:13','2025-11-24 16:23:13',0,NULL,NULL,NULL,NULL,0,NULL,0.00,NULL,NULL,NULL,'::1','curl/8.7.1',NULL,0,NULL,'2',NULL,NULL,'2025-11-24 16:23:13','2025-11-24 16:23:13'),(12,'UNI-PAY-1764012325-QY0LJ7','MEMBERSHIP','membership_fee',2,NULL,NULL,'Abel Knowles','pefunuh@mailinator.com','+256706638484',NULL,'[{\"id\": 2, \"type\": \"membership\", \"amount\": 300, \"metadata\": {\"scenario\": \"dtehm_only\", \"is_dip_member\": \"No\", \"is_dtehm_member\": \"Yes\"}, \"description\": \"DTEHM Membership Payment\"}]',1,300.00,'UGX','DTEHM Membership Payment','test','test',NULL,'COMPLETED','1','Payment completed successfully (TEST)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 16:25:25','2025-11-24 16:25:25',1,'2025-11-24 16:25:25','All 1 items processed successfully',NULL,NULL,0,NULL,0.00,NULL,NULL,NULL,'::1','curl/8.7.1',NULL,0,NULL,'2',NULL,NULL,'2025-11-24 16:25:25','2025-11-24 16:25:25'),(13,'UNI-PAY-1764012343-TPIYVT','MEMBERSHIP','membership_fee',2,NULL,NULL,'Abel Knowles','pefunuh@mailinator.com','+256706638484',NULL,'[{\"id\": 2, \"type\": \"membership\", \"amount\": 200, \"metadata\": {\"scenario\": \"dip_only\", \"is_dip_member\": \"Yes\", \"is_dtehm_member\": \"No\"}, \"description\": \"DIP Membership Payment\"}]',1,200.00,'UGX','DIP Membership Payment','test','test',NULL,'COMPLETED','1','Payment completed successfully (TEST)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 16:25:43','2025-11-24 16:25:43',1,'2025-11-24 16:25:43','All 1 items processed successfully',NULL,NULL,0,NULL,0.00,NULL,NULL,NULL,'::1','curl/8.7.1',NULL,0,NULL,'2',NULL,NULL,'2025-11-24 16:25:43','2025-11-24 16:25:43'),(14,'UNI-PAY-1764012453-8AMWQR','MEMBERSHIP','membership_fee',2,NULL,NULL,'Abel Knowles','pefunuh@mailinator.com','+256706638484',NULL,'[{\"id\": 2, \"type\": \"membership\", \"amount\": 500, \"metadata\": {\"scenario\": \"both\", \"is_dip_member\": \"Yes\", \"is_dtehm_member\": \"Yes\"}, \"description\": \"DTEHM + DIP Membership Payment\"}]',1,500.00,'UGX','DTEHM + DIP Membership Payment','test','test',NULL,'COMPLETED','1','Payment completed successfully (TEST)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 16:27:33','2025-11-24 16:27:33',1,'2025-11-24 16:27:33','All 1 items processed successfully',NULL,NULL,0,NULL,0.00,NULL,NULL,NULL,'::1','curl/8.7.1',NULL,0,NULL,'2',NULL,NULL,'2025-11-24 16:27:33','2025-11-24 16:27:33'),(16,'UNI-PAY-1764015785-HAI7P3','project_share_purchase','project_investment',180,NULL,NULL,'Kule Swaleh','0772111117@dtehm.app','0772111117',NULL,'[{\"id\": \"4\", \"type\": \"project_share_purchase\", \"amount\": \"500.0\", \"metadata\": null, \"description\": \"5 share(s) of Motorcycle Taxi Fleet\"}]',1,500.00,'UGX','Purchase of 5 share(s) in Motorcycle Taxi Fleet','pesapal',NULL,NULL,'COMPLETED','1','Completed','938b596d-9d44-4f29-a50d-db0d7ecf9a04','PAYMENT_16_1764015785','https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04','http://10.0.2.2:8888/dtehm-insurance-api/api/universal-payments/callback',NULL,1,'{\"payment_method\":\"AirtelUG\",\"amount\":500,\"created_date\":\"2025-11-24T23:24:29.997\",\"confirmation_code\":\"135432480995\",\"order_tracking_id\":\"938b596d-9d44-4f29-a50d-db0d7ecf9a04\",\"payment_status_description\":\"Completed\",\"description\":null,\"message\":\"Request processed successfully\",\"payment_account\":\"706638494\",\"call_back_url\":\"http:\\/\\/10.0.2.2:8888\\/dtehm-insurance-api\\/api\\/universal-payments\\/callback?OrderTrackingId=938b596d-9d44-4f29-a50d-db0d7ecf9a04&OrderMerchantReference=PAYMENT_16_1764015785\",\"status_code\":1,\"merchant_reference\":\"PAYMENT_16_1764015785\",\"account_number\":null,\"payment_status_code\":\"\",\"currency\":\"UGX\",\"error\":{\"error_type\":null,\"code\":null,\"message\":null},\"status\":\"200\"}','135432480995','2025-11-24 17:24:37','2025-11-24 17:24:37',1,'2025-11-24 17:24:31','All 1 items processed successfully',NULL,NULL,0,'2025-11-24 17:24:37',0.00,NULL,NULL,NULL,'127.0.0.1','Dart/3.8 (dart:io)',NULL,0,NULL,'180',NULL,NULL,'2025-11-24 17:23:05','2025-11-24 17:24:37');
/*!40000 ALTER TABLE `universal_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` text COLLATE utf8mb4_unicode_ci,
  `first_name` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reg_date` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_seen` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved` tinyint(4) DEFAULT NULL,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Customer',
  `registered_by_id` bigint(20) DEFAULT NULL,
  `is_membership_paid` tinyint(1) NOT NULL DEFAULT '0',
  `membership_paid_at` timestamp NULL DEFAULT NULL,
  `membership_amount` decimal(10,2) DEFAULT NULL,
  `membership_payment_id` bigint(20) DEFAULT NULL,
  `membership_type` enum('LIFE','ANNUAL','MONTHLY') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `membership_expiry_date` date DEFAULT NULL,
  `sex` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reg_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(35) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tribe` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `father_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `child_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'First biological child',
  `child_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Second biological child',
  `child_3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Third biological child',
  `child_4` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fourth biological child',
  `sponsor_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sponsor ID number',
  `occupation` varchar(225) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo_large` text COLLATE utf8mb4_unicode_ci,
  `phone_number` varchar(35) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_lat` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_long` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cv` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `about` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(325) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` text COLLATE utf8mb4_unicode_ci,
  `name` varchar(355) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `campus_id` bigint(20) NOT NULL DEFAULT '1',
  `complete_profile` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dob` timestamp NULL DEFAULT NULL,
  `intro` text COLLATE utf8mb4_unicode_ci,
  `business_name` text COLLATE utf8mb4_unicode_ci,
  `total_points` int(11) DEFAULT '0' COMMENT 'Total points earned by selling products',
  `business_license_number` text COLLATE utf8mb4_unicode_ci,
  `business_license_issue_authority` text COLLATE utf8mb4_unicode_ci,
  `business_license_issue_date` text COLLATE utf8mb4_unicode_ci,
  `business_license_validity` text COLLATE utf8mb4_unicode_ci,
  `business_address` text COLLATE utf8mb4_unicode_ci,
  `business_phone_number` text COLLATE utf8mb4_unicode_ci,
  `business_whatsapp` text COLLATE utf8mb4_unicode_ci,
  `business_email` text COLLATE utf8mb4_unicode_ci,
  `business_logo` text COLLATE utf8mb4_unicode_ci,
  `business_cover_photo` text COLLATE utf8mb4_unicode_ci,
  `business_cover_details` text COLLATE utf8mb4_unicode_ci,
  `nin` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(55) COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `parent_1` bigint(20) DEFAULT NULL,
  `parent_2` bigint(20) DEFAULT NULL,
  `parent_3` bigint(20) DEFAULT NULL,
  `parent_4` bigint(20) DEFAULT NULL,
  `parent_5` bigint(20) DEFAULT NULL,
  `parent_6` bigint(20) DEFAULT NULL,
  `parent_7` bigint(20) DEFAULT NULL,
  `parent_8` bigint(20) DEFAULT NULL,
  `parent_9` bigint(20) DEFAULT NULL,
  `parent_10` bigint(20) DEFAULT NULL,
  `is_dtehm_member` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `dtehm_membership_paid_at` timestamp NULL DEFAULT NULL,
  `dtehm_membership_amount` decimal(10,2) DEFAULT NULL,
  `dtehm_membership_payment_id` bigint(20) DEFAULT NULL,
  `is_dip_member` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `dtehm_member_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dtehm_member_membership_date` timestamp NULL DEFAULT NULL,
  `dtehm_membership_is_paid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `dtehm_membership_paid_date` timestamp NULL DEFAULT NULL,
  `dtehm_membership_paid_amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `watchlist`
--

DROP TABLE IF EXISTS `watchlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `watchlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `date_added` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `watchlist`
--

LOCK TABLES `watchlist` WRITE;
/*!40000 ALTER TABLE `watchlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `watchlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_name` text COLLATE utf8mb4_unicode_ci,
  `product_price` int(11) DEFAULT NULL,
  `product_sale_price` int(11) DEFAULT NULL,
  `product_photo` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlists`
--

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `withdraw_requests`
--

DROP TABLE IF EXISTS `withdraw_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `withdraw_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'User requesting withdrawal',
  `amount` decimal(15,2) NOT NULL COMMENT 'Amount to withdraw',
  `account_balance_before` decimal(15,2) NOT NULL COMMENT 'Account balance at time of request',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'User description/reason for withdrawal',
  `payment_method` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mobile money, bank, etc.',
  `payment_phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Phone number for mobile money',
  `admin_note` text COLLATE utf8mb4_unicode_ci COMMENT 'Admin note when approving/rejecting',
  `processed_by_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Admin who processed the request',
  `processed_at` timestamp NULL DEFAULT NULL COMMENT 'When request was approved/rejected',
  `account_transaction_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Related transaction if approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `withdraw_requests_user_id_index` (`user_id`),
  KEY `withdraw_requests_status_index` (`status`),
  KEY `withdraw_requests_processed_by_id_index` (`processed_by_id`),
  KEY `withdraw_requests_account_transaction_id_index` (`account_transaction_id`),
  KEY `withdraw_requests_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdraw_requests`
--

LOCK TABLES `withdraw_requests` WRITE;
/*!40000 ALTER TABLE `withdraw_requests` DISABLE KEYS */;
INSERT INTO `withdraw_requests` VALUES (1,180,8000.00,138000.00,'approved','For testin','mobile_money','0706638494',NULL,1,'2025-11-26 17:40:08',418,'2025-11-26 17:35:32','2025-11-26 17:40:08',NULL);
/*!40000 ALTER TABLE `withdraw_requests` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-05 22:59:46
