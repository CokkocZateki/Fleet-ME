SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `esisso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `characterID` bigint(16) NOT NULL,
  `characterName` varchar(255) DEFAULT NULL,
  `refreshToken` varchar(255) NOT NULL,
  `accessToken` varchar(255) DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ownerHash` varchar(255) NOT NULL,
  `failcount` int(11) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`characterID`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `characterID` (`characterID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pilots` (
  `characterID` bigint(16) NOT NULL,
  `locationID` bigint(16) NOT NULL,
  `shipTypeID` int(11) NOT NULL,
  `fleetID` bigint(16) NOT NULL,
  `stationID` int(11) DEFAULT NULL,
  `structureID` bigint(16) DEFAULT NULL,
  `fitting` varchar(500) DEFAULT NULL,
  `backupfc` tinyint(1) NOT NULL DEFAULT '0',
  `lastFetch` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`characterID`),
  UNIQUE KEY `characterID` (`characterID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `structures` (
  `solarSystemID` int(11) NOT NULL,
  `structureID` bigint(16) NOT NULL,
  `structureName` varchar(255) DEFAULT NULL,
  `lastUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`structureID`),
  UNIQUE KEY `structureID` (`structureID`),
  KEY `structureID_2` (`structureID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
