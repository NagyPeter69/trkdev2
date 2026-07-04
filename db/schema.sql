/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `type` varchar(40) NOT NULL DEFAULT '',
  `publisher` varchar(11) NOT NULL,
  `email` varchar(100) NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(100) NOT NULL DEFAULT '',
  `group` int(11) NOT NULL,
  `landing` varchar(30) NOT NULL DEFAULT 'create_magazine',
  `advanced_publishers` varchar(255) NOT NULL DEFAULT '',
  `actual` varchar(50) NOT NULL DEFAULT '',
  `logtime` int(11) NOT NULL DEFAULT 15,
  `fpFilter` varchar(20) NOT NULL DEFAULT 'all',
  `fpPages` varchar(10) NOT NULL DEFAULT 'pair',
  `cutBox` varchar(20) NOT NULL DEFAULT 'trimbox',
  `unit` varchar(10) NOT NULL DEFAULT 'mm',
  `lang` varchar(10) NOT NULL DEFAULT 'en',
  `logged_in` int(11) NOT NULL DEFAULT 0,
  `lastOpt` varchar(20) NOT NULL DEFAULT '',
  `lastlogin` int(11) NOT NULL DEFAULT 0,
  `showMagazines` text NOT NULL DEFAULT '',
  `last_response` int(11) NOT NULL DEFAULT 0,
  `showJobs` text NOT NULL DEFAULT '',
  `safety_int` float NOT NULL DEFAULT 5,
  `safety_on` int(11) NOT NULL DEFAULT 0,
  `prepresstools` int(11) NOT NULL DEFAULT 0,
  `multiplelogin` int(11) NOT NULL DEFAULT 0,
  `calendarGroup` int(11) NOT NULL DEFAULT 0,
  `showDays` varchar(10) NOT NULL DEFAULT 'both',
  `color` varchar(10) NOT NULL DEFAULT '#FFFFFF',
  `chat_pos` varchar(255) NOT NULL DEFAULT 'bottom',
  `chat_size` varchar(255) NOT NULL DEFAULT 'max',
  `clientSelect` varchar(10) NOT NULL DEFAULT '',
  `plannerpub` int(11) NOT NULL DEFAULT 0,
  `uploadsettings` text DEFAULT NULL,
  `usertype` varchar(10) NOT NULL DEFAULT 'Perma',
  `temppubid` int(11) NOT NULL DEFAULT 0,
  `uploadparams` int(11) NOT NULL DEFAULT 1,
  `downloadsettings` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=441 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `action_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `action_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` varchar(255) NOT NULL DEFAULT '0',
  `action` varchar(255) NOT NULL,
  `publisher` int(11) NOT NULL,
  `magazine` varchar(30) NOT NULL,
  `issue` varchar(30) NOT NULL,
  `target` varchar(255) NOT NULL,
  `date` int(11) NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT '',
  `info` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=436034 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ad_hoc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ad_hoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `settings` text NOT NULL,
  `gen_name` varchar(100) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `pic_num` int(11) NOT NULL DEFAULT 0,
  `mask_num` int(11) NOT NULL DEFAULT 0,
  `worktime` int(11) NOT NULL DEFAULT 0,
  `deleted` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ad_hoc_infobox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ad_hoc_infobox` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `info_showed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ad_hoc_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ad_hoc_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `client` int(11) NOT NULL,
  `creator` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ad_sizes_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ad_sizes_old` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magazine_id` int(11) NOT NULL,
  `size` varchar(50) NOT NULL,
  `orient` varchar(50) NOT NULL,
  `cover` varchar(50) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=329 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `adhoc_hotlinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `adhoc_hotlinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `hash` varchar(255) NOT NULL,
  `magazine_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `redirecto` varchar(255) DEFAULT NULL,
  `pubid` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pub_id` int(11) NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `size` varchar(10) NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 1,
  `publisher` int(11) NOT NULL,
  `uploaded` varchar(20) NOT NULL DEFAULT '',
  `reason` varchar(255) NOT NULL DEFAULT '',
  `proofCounter` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `article_colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_colors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `color` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pub_id` int(11) NOT NULL,
  `fp` varchar(10) NOT NULL DEFAULT '',
  `parent` int(11) NOT NULL DEFAULT 0,
  `name` text NOT NULL,
  `type` varchar(100) NOT NULL,
  `time` int(11) NOT NULL,
  `stripped_name` text NOT NULL,
  `hide` text DEFAULT NULL,
  `color` varchar(100) NOT NULL DEFAULT '',
  `origname` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_counters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publisher_id` int(11) NOT NULL,
  `counter` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publisher_id` int(11) NOT NULL,
  `magazine_id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `start` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `end` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `publisher` int(11) NOT NULL,
  `plannerView` int(11) NOT NULL DEFAULT 0,
  `plannerModify` int(11) NOT NULL DEFAULT 0,
  `dayShift` decimal(10,0) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magazine_id` int(11) NOT NULL,
  `salesDay` varchar(30) NOT NULL,
  `printDay` varchar(30) NOT NULL,
  `code` varchar(50) NOT NULL,
  `specificName` varchar(255) NOT NULL,
  `magCode` varchar(100) NOT NULL,
  `publisher_id` int(11) NOT NULL,
  `numofpages` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_reminder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_reminder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `postID` int(11) NOT NULL,
  `data` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publisher_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_hungarian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comment_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL DEFAULT 0,
  `action` varchar(255) NOT NULL,
  `publisher` int(11) NOT NULL,
  `magazine` varchar(30) NOT NULL,
  `issue` varchar(30) NOT NULL,
  `target` varchar(255) NOT NULL,
  `date` int(11) NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT '',
  `info` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pub_id` int(11) NOT NULL,
  `parent` int(11) NOT NULL,
  `left` double NOT NULL,
  `top` double NOT NULL,
  `width` double NOT NULL,
  `height` double NOT NULL,
  `page` int(11) NOT NULL,
  `text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp(),
  `user` varchar(255) NOT NULL,
  `shape` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT '',
  `pageType` varchar(10) NOT NULL DEFAULT 'NOR',
  `pageVersion` varchar(10) NOT NULL DEFAULT '',
  `modify` int(11) NOT NULL DEFAULT 0,
  `part` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliver_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `deliver_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pub_id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL DEFAULT 0,
  `action` varchar(255) NOT NULL,
  `publisher` int(11) NOT NULL,
  `magazine` varchar(30) NOT NULL,
  `issue` varchar(30) NOT NULL,
  `target` varchar(255) NOT NULL,
  `date` int(11) NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT '',
  `info` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `filetransfer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `filetransfer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jobid` int(11) NOT NULL,
  `path` varchar(100) NOT NULL,
  `time` int(11) NOT NULL,
  `expire` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `filetransfer_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `filetransfer_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `publisher` int(11) NOT NULL,
  `jobname` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `time` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `jtype` varchar(20) NOT NULL DEFAULT '',
  `userid` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flatplan_articletypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `flatplan_articletypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pub_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(30) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flatplan_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `flatplan_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `pubid` int(11) NOT NULL,
  `articlename` varchar(255) NOT NULL,
  `date` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `origname` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flatplan_handout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `flatplan_handout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `pub_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `date` int(11) NOT NULL,
  `downloaded` int(11) NOT NULL DEFAULT 0,
  `arrived` int(11) NOT NULL DEFAULT 0,
  `changed` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flatplan_handout_hotlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `flatplan_handout_hotlink` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `handoutid` int(11) NOT NULL,
  `hash` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flatplan_planner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `flatplan_planner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pub_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `pos` int(11) NOT NULL,
  `ownColor` varchar(10) NOT NULL DEFAULT '',
  `workerName` varchar(255) NOT NULL,
  `workerID` int(11) NOT NULL,
  `atype` int(11) NOT NULL,
  `tspent` int(11) NOT NULL DEFAULT 0,
  `remark` text NOT NULL,
  `text` int(11) NOT NULL DEFAULT 0,
  `have_text` int(11) NOT NULL DEFAULT 0,
  `image` int(11) NOT NULL DEFAULT 0,
  `have_image` int(11) NOT NULL DEFAULT 0,
  `other` int(11) NOT NULL DEFAULT 0,
  `have_other` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'defined',
  `template` varchar(100) NOT NULL DEFAULT '',
  `position` varchar(100) NOT NULL DEFAULT '',
  `mixed` int(11) NOT NULL DEFAULT 0,
  `x` int(11) NOT NULL DEFAULT 0,
  `y` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `handout_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `handout_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `issue` varchar(255) NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hotlinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotlinks` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `publication` int(11) NOT NULL,
  `hashtag` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `pages` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `comment` int(11) NOT NULL,
  `accept` int(11) NOT NULL,
  `time_expire` int(11) NOT NULL,
  `visited` int(11) NOT NULL DEFAULT 0,
  `spread` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `note` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `email` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL DEFAULT '',
  `lang` varchar(10) NOT NULL DEFAULT 'en',
  `creator` int(11) NOT NULL,
  `fp_type` varchar(10) NOT NULL DEFAULT 'FIN',
  `state` varchar(20) NOT NULL,
  `downloadable` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hotlinks_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotlinks_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `job_id` bigint(20) NOT NULL,
  `action` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `info` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `time` int(11) NOT NULL,
  `hotlink_id` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `image_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `image_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pub_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `retus` int(11) NOT NULL DEFAULT 0,
  `maszk` int(11) NOT NULL DEFAULT 0,
  `pack` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publisher` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `deadline` varchar(50) NOT NULL,
  `emails` text NOT NULL,
  `workflow` varchar(50) NOT NULL,
  `size` varchar(20) NOT NULL,
  `parts` text NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs_pageinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs_pageinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `page` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `view` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `magazines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `magazines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publisher_id` varchar(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `removing` int(11) NOT NULL DEFAULT 0,
  `color` varchar(10) NOT NULL DEFAULT '000000',
  `salePeriod` int(11) NOT NULL DEFAULT 8,
  `finishtype` varchar(50) NOT NULL DEFAULT 'sales',
  `dayshift` int(11) NOT NULL DEFAULT 2,
  `calendarGroup` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(50) NOT NULL DEFAULT '',
  `clientChange` int(11) NOT NULL DEFAULT 0,
  `clientChangeResult` varchar(255) NOT NULL DEFAULT '',
  `pubName` varchar(255) NOT NULL DEFAULT '',
  `HideApprovedComments` varchar(10) NOT NULL DEFAULT 'No',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=372 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `marquard_calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marquard_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `magazine_id` int(11) NOT NULL,
  `release` varchar(255) NOT NULL DEFAULT '',
  `designation` varchar(10) NOT NULL DEFAULT '',
  `codeSeparator` varchar(5) NOT NULL DEFAULT '',
  `customName` varchar(255) NOT NULL DEFAULT '',
  `printOrder` varchar(255) NOT NULL DEFAULT '',
  `salesDay` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `package_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `package_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `event` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `starting_page` varchar(15) NOT NULL DEFAULT '0',
  `directory` varchar(100) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 0,
  `status_changed` int(11) NOT NULL DEFAULT 0,
  `acquired_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pageinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pageinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pack_id` int(11) NOT NULL,
  `issue` varchar(20) NOT NULL,
  `version` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `page` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `code` varchar(10) NOT NULL,
  `state` varchar(20) NOT NULL DEFAULT '',
  `width` int(11) NOT NULL DEFAULT 1,
  `view` text DEFAULT NULL,
  `fin` int(11) NOT NULL DEFAULT 0,
  `lastdifference` varchar(255) NOT NULL DEFAULT '',
  `proofCounter` int(11) NOT NULL DEFAULT 0,
  `boxes` text DEFAULT NULL,
  `colors` text DEFAULT NULL,
  `part` varchar(255) NOT NULL DEFAULT '',
  `origname` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `partial_ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partial_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ads_id` int(11) NOT NULL,
  `orient` varchar(50) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pub_id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `place` varchar(255) NOT NULL,
  `color` varchar(30) NOT NULL,
  `size` varchar(20) NOT NULL DEFAULT '',
  `mag_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `publications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `publications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publisher_id` int(11) NOT NULL,
  `magazine_id` int(11) NOT NULL,
  `internal` text DEFAULT NULL,
  `upload` text DEFAULT NULL,
  `output` text DEFAULT NULL,
  `pages` int(11) NOT NULL DEFAULT 0,
  `parts` text DEFAULT NULL,
  `uploadable` varchar(10) NOT NULL DEFAULT '',
  `precounter` int(11) NOT NULL DEFAULT 0,
  `code` varchar(255) NOT NULL DEFAULT '',
  `deadline` varchar(255) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'created',
  `current` int(11) NOT NULL DEFAULT 0,
  `article` varchar(200) NOT NULL DEFAULT '',
  `bleedBox` int(11) NOT NULL DEFAULT 5,
  `safetyBox` int(11) NOT NULL DEFAULT 3,
  `specificName` varchar(20) NOT NULL DEFAULT '',
  `removing` int(11) NOT NULL DEFAULT 0,
  `created` int(11) NOT NULL DEFAULT 0,
  `enhance` varchar(100) NOT NULL DEFAULT '',
  `clientChange` int(11) NOT NULL DEFAULT 0,
  `clientChangeResult` varchar(255) NOT NULL DEFAULT '',
  `owner` int(11) NOT NULL DEFAULT 0,
  `user` int(11) NOT NULL DEFAULT 0,
  `clientType` varchar(1000) NOT NULL DEFAULT 'known',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `publishers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `publishers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `switch_flows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `switch_flows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `flowid` int(11) NOT NULL,
  `objectid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `switch_sync_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `switch_sync_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_type` varchar(50) NOT NULL,
  `payload` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `next_attempt_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_next` (`status`,`next_attempt_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL DEFAULT 0,
  `action` varchar(255) NOT NULL,
  `publisher` int(11) NOT NULL,
  `magazine` varchar(30) NOT NULL,
  `issue` varchar(30) NOT NULL,
  `target` varchar(255) NOT NULL,
  `date` int(11) NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT '',
  `info` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=31266 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tasklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tracker_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tracker_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `userLogSettings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `userLogSettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `uploadAD` varchar(5) NOT NULL DEFAULT '1',
  `newArticle` int(11) NOT NULL DEFAULT 0,
  `backArticle` int(11) NOT NULL DEFAULT 1,
  `approveComment` int(11) NOT NULL DEFAULT 1,
  `newCReply` int(11) NOT NULL DEFAULT 1,
  `newComment` int(11) NOT NULL DEFAULT 1,
  `revertedPage` int(11) NOT NULL DEFAULT 1,
  `approvePage` int(11) NOT NULL DEFAULT 1,
  `updatePage` int(11) NOT NULL DEFAULT 1,
  `newPage` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=313 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `publisher` int(11) NOT NULL,
  `ad_view` int(11) NOT NULL DEFAULT 0,
  `ad_send` int(11) NOT NULL DEFAULT 0,
  `ad_upload` int(11) NOT NULL DEFAULT 0,
  `ad_delete` int(11) NOT NULL DEFAULT 0,
  `ad_sizes` int(11) NOT NULL DEFAULT 0,
  `magazine_upload` int(11) NOT NULL DEFAULT 0,
  `magazine_itemlist` int(11) NOT NULL DEFAULT 0,
  `magazine_flatplan` int(11) NOT NULL DEFAULT 0,
  `magazine_delete` int(11) NOT NULL DEFAULT 0,
  `magazine_add` int(11) NOT NULL DEFAULT 0,
  `magazine_settings` int(11) NOT NULL DEFAULT 0,
  `magazine_download` int(11) NOT NULL DEFAULT 0,
  `ad-hoc_send_pic` int(11) NOT NULL DEFAULT 0,
  `ad-hoc_send_pack` int(11) NOT NULL DEFAULT 0,
  `ad-hoc_send_pdf` int(11) NOT NULL DEFAULT 0,
  `ad-hoc_proof` int(11) NOT NULL DEFAULT 0,
  `ftp_create` int(11) NOT NULL DEFAULT 0,
  `ftp_modify` int(11) NOT NULL DEFAULT 0,
  `ftp_delete` int(11) NOT NULL DEFAULT 0,
  `pmdallmodify` int(11) NOT NULL DEFAULT 0,
  `acceptPage` int(11) NOT NULL DEFAULT 0,
  `newIssue` int(11) NOT NULL DEFAULT 0,
  `delIssue` int(11) NOT NULL DEFAULT 0,
  `acceptIssue` int(11) NOT NULL DEFAULT 0,
  `archiveIssue` int(11) NOT NULL DEFAULT 0,
  `reArchive` int(11) NOT NULL DEFAULT 0,
  `stopIssue` int(11) NOT NULL DEFAULT 0,
  `modIssue` int(11) NOT NULL DEFAULT 0,
  `modDdIssue` int(11) NOT NULL DEFAULT 0,
  `lengthIssue` int(11) NOT NULL DEFAULT 0,
  `trimIssue` int(11) NOT NULL DEFAULT 0,
  `partsIssue` int(11) NOT NULL DEFAULT 0,
  `proof` int(11) NOT NULL DEFAULT 0,
  `viewComment` int(11) NOT NULL DEFAULT 0,
  `createComment` int(11) NOT NULL DEFAULT 0,
  `replyComment` int(11) NOT NULL DEFAULT 0,
  `deleteComment` int(11) NOT NULL DEFAULT 0,
  `accounts_addMember` int(11) NOT NULL DEFAULT 0,
  `accounts_modifyMember` int(11) NOT NULL DEFAULT 0,
  `accounts_removeMember` int(11) NOT NULL DEFAULT 0,
  `accounts_addGroup` int(11) NOT NULL DEFAULT 0,
  `accounts_modifyGroup` int(11) NOT NULL DEFAULT 0,
  `accounts_removeGroup` int(11) NOT NULL DEFAULT 0,
  `cancelApprove` int(11) NOT NULL DEFAULT 0,
  `manageFP` int(11) NOT NULL DEFAULT 0,
  `jobs_menu` int(11) NOT NULL DEFAULT 0,
  `jobs_create` int(11) NOT NULL DEFAULT 0,
  `jobs_modify` int(11) NOT NULL DEFAULT 0,
  `jobs_delete` int(11) NOT NULL DEFAULT 0,
  `jobs_upload` int(11) NOT NULL DEFAULT 0,
  `jobs_accept` int(11) NOT NULL DEFAULT 0,
  `jobs_archive` int(11) NOT NULL DEFAULT 0,
  `jobs_stop` int(11) NOT NULL DEFAULT 0,
  `jobs_ftp` int(11) NOT NULL DEFAULT 0,
  `sys_log` int(11) NOT NULL DEFAULT 0,
  `client_create` int(11) NOT NULL DEFAULT 0,
  `client_modify` int(11) NOT NULL DEFAULT 0,
  `client_delete` int(11) NOT NULL DEFAULT 0,
  `accounts_addAdhoc` int(11) NOT NULL DEFAULT 0,
  `accounts_modAdhoc` int(11) NOT NULL DEFAULT 0,
  `accounts_delAdhoc` int(11) NOT NULL DEFAULT 0,
  `sendHotlink` int(11) NOT NULL DEFAULT 0,
  `accounts_addPlannerGroup` int(11) NOT NULL DEFAULT 0,
  `accounts_removePlannerGroup` int(11) NOT NULL DEFAULT 0,
  `calendar_realdates` int(11) NOT NULL DEFAULT 0,
  `task_lists` int(11) NOT NULL DEFAULT 0,
  `handouts` int(11) NOT NULL DEFAULT 0,
  `accounts_userStat` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL DEFAULT '',
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=65238 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

