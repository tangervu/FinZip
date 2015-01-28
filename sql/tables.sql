-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 29, 2015 at 12:32 AM
-- Server version: 5.5.41-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `geo`
--

-- --------------------------------------------------------

--
-- Table structure for table `data`
--

CREATE TABLE IF NOT EXISTS `data` (
  `zip` varchar(5) COLLATE utf8_swedish_ci NOT NULL,
  `locality` varchar(30) COLLATE utf8_swedish_ci NOT NULL,
  `locality_short` varchar(12) COLLATE utf8_swedish_ci DEFAULT NULL,
  `street` varchar(30) COLLATE utf8_swedish_ci DEFAULT NULL,
  `streetnumber_type` enum('odd','even','none') COLLATE utf8_swedish_ci DEFAULT NULL,
  `streetnumber_min` int(5) DEFAULT NULL,
  `streetnumber_max` int(5) DEFAULT NULL,
  `municipality_code` varchar(3) COLLATE utf8_swedish_ci NOT NULL,
  `municipality_name` varchar(20) COLLATE utf8_swedish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `localities`
--

CREATE TABLE IF NOT EXISTS `localities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8_swedish_ci NOT NULL,
  `short` varchar(12) COLLATE utf8_swedish_ci DEFAULT NULL,
  `municipality` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`municipality`),
  KEY `municipality` (`municipality`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `municipalities`
--

CREATE TABLE IF NOT EXISTS `municipalities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_swedish_ci NOT NULL,
  `code` varchar(3) COLLATE utf8_swedish_ci DEFAULT NULL,
  `location` point DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locationUpdated` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`),
  KEY `point` (`location`(25)),
  KEY `active` (`active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `streetnames`
--

CREATE TABLE IF NOT EXISTS `streetnames` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8_swedish_ci NOT NULL,
  `location` point DEFAULT NULL,
  `locality` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locationUpdated` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`locality`),
  KEY `locality` (`locality`),
  KEY `location` (`location`(25))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `streetnumbers`
--

CREATE TABLE IF NOT EXISTS `streetnumbers` (
  `street` int(11) NOT NULL,
  `type` enum('odd','even','none') COLLATE utf8_swedish_ci NOT NULL,
  `min` int(5) DEFAULT NULL,
  `max` int(5) DEFAULT NULL,
  `zip` varchar(5) COLLATE utf8_swedish_ci DEFAULT NULL,
  KEY `street` (`street`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `localities`
--
ALTER TABLE `localities`
  ADD CONSTRAINT `localities_ibfk_1` FOREIGN KEY (`municipality`) REFERENCES `municipalities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `streetnames`
--
ALTER TABLE `streetnames`
  ADD CONSTRAINT `streetnames_ibfk_1` FOREIGN KEY (`locality`) REFERENCES `localities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `streetnumbers`
--
ALTER TABLE `streetnumbers`
  ADD CONSTRAINT `streetnumbers_ibfk_1` FOREIGN KEY (`street`) REFERENCES `streetnames` (`id`) ON DELETE CASCADE;
