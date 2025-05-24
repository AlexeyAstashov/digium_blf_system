-- MariaDB dump 10.19  Distrib 10.11.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: blf_system
-- ------------------------------------------------------
-- Server version	10.11.6-MariaDB-0+deb12u1

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
-- Table structure for table `blf_default_settings`
--

DROP TABLE IF EXISTS `blf_default_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blf_default_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pickupcall` tinyint(1) DEFAULT 1,
  `myintercom` tinyint(1) DEFAULT 1,
  `idle_led_color` enum('green','amber','red') DEFAULT 'green',
  `idle_led_state` enum('on','off') DEFAULT 'on',
  `idle_ringtone` enum('Alarm','Chimes','Digium','GuitarStrum','Jingle','Office2','Office','RotaryPhone','SteelDrum','Techno','Theme','Tweedle','Twinkle','Vibe') DEFAULT 'Digium',
  `ringing_led_color` enum('green','amber','red') DEFAULT 'red',
  `ringing_led_state` enum('fast','slow','on','off') DEFAULT 'fast',
  `ringing_ringtone` enum('Alarm','Chimes','Digium','GuitarStrum','Jingle','Office2','Office','RotaryPhone','SteelDrum','Techno','Theme','Tweedle','Twinkle','Vibe') DEFAULT 'Techno',
  `busy_led_color` enum('green','amber','red') DEFAULT 'red',
  `busy_led_state` enum('on','off') DEFAULT 'on',
  `busy_ringtone` enum('Alarm','Chimes','Digium','GuitarStrum','Jingle','Office2','Office','RotaryPhone','SteelDrum','Techno','Theme','Tweedle','Twinkle','Vibe') DEFAULT 'Techno',
  `hold_led_color` enum('green','amber','red') DEFAULT 'amber',
  `hold_led_state` enum('fast','slow','on','off') DEFAULT 'slow',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `extension` varchar(10) DEFAULT NULL,
  `contact_id` varchar(20) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `second_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `suffix` enum('Mr','Ms','Mrs','none') DEFAULT 'none',
  `organization` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `pickupcall` tinyint(1) DEFAULT 1,
  `myintercom` tinyint(1) DEFAULT 1,
  `idle_led_color` enum('green','amber','red') DEFAULT 'green',
  `idle_led_state` enum('on','off') DEFAULT 'on',
  `idle_ringtone` enum('Alarm','Chimes','Digium','GuitarStrum','Jingle','Office2','Office','RotaryPhone','SteelDrum','Techno','Theme','Tweedle','Twinkle','Vibe') DEFAULT 'Digium',
  `ringing_led_color` enum('green','amber','red') DEFAULT 'red',
  `ringing_led_state` enum('fast','slow','on','off') DEFAULT 'fast',
  `ringing_ringtone` enum('Alarm','Chimes','Digium','GuitarStrum','Jingle','Office2','Office','RotaryPhone','SteelDrum','Techno','Theme','Tweedle','Twinkle','Vibe') DEFAULT 'Techno',
  `busy_led_color` enum('green','amber','red') DEFAULT 'red',
  `busy_led_state` enum('on','off') DEFAULT 'on',
  `busy_ringtone` enum('Alarm','Chimes','Digium','GuitarStrum','Jingle','Office2','Office','RotaryPhone','SteelDrum','Techno','Theme','Tweedle','Twinkle','Vibe') DEFAULT 'Techno',
  `hold_ringtone` enum('Alarm','Chimes','Digium','GuitarStrum','Jingle','Office2','Office','RotaryPhone','SteelDrum','Techno','Theme','Tweedle','Twinkle','Vibe') DEFAULT 'Techno',
  `hold_led_color` enum('green','amber','red') DEFAULT 'amber',
  `hold_led_state` enum('fast','slow','on','off') DEFAULT 'slow',
  PRIMARY KEY (`id`),
  KEY `extension` (`extension`),
  CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`extension`) REFERENCES `users` (`extension`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `extension` varchar(10) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`extension`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-24 16:48:17
