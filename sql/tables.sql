--
-- Database: `geo`
--

-- --------------------------------------------------------

--
-- Table structure for table `localities`
--

CREATE TABLE IF NOT EXISTS `localities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_swedish_ci NOT NULL,
  `short` varchar(5) COLLATE utf8_swedish_ci DEFAULT NULL,
  `type` int(1) NOT NULL,
  `location` point DEFAULT NULL,
  `municipality` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locationUpdated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`municipality`),
  KEY `municipality` (`municipality`),
  KEY `location` (`location`(25))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `municipalities`
--

CREATE TABLE IF NOT EXISTS `municipalities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_swedish_ci NOT NULL,
  `code` varchar(5) COLLATE utf8_swedish_ci DEFAULT NULL,
  `location` point DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locationUpdated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`),
  KEY `point` (`location`(25))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `streetnames`
--

CREATE TABLE IF NOT EXISTS `streetnames` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_swedish_ci NOT NULL,
  `location` point DEFAULT NULL,
  `locality` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locationUpdated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`locality`),
  KEY `locality` (`locality`),
  KEY `location` (`location`(25))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `streetnumbers`
--

CREATE TABLE IF NOT EXISTS `streetnumbers` (
  `street` int(11) NOT NULL,
  `type` int(1) NOT NULL,
  `min` int(11) DEFAULT NULL,
  `max` int(11) DEFAULT NULL,
  `zip` varchar(20) COLLATE utf8_swedish_ci DEFAULT NULL,
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
