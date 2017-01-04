SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
CREATE TABLE `authTokens` (
  `id` int(11) NOT NULL,
  `selector` char(32) DEFAULT NULL,
  `token` char(64) DEFAULT NULL,
  `characterID` bigint(16) NOT NULL,
  `expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `backup_fcs` (
  `fleetID` int(11) NOT NULL,
  `characterID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `esisso` (
  `id` int(11) NOT NULL,
  `characterID` bigint(16) NOT NULL,
  `characterName` varchar(255) DEFAULT NULL,
  `refreshToken` varchar(255) NOT NULL,
  `accessToken` varchar(255) DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ownerHash` varchar(255) NOT NULL,
  `failcount` int(11) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `fleetmembers` (
  `characterID` bigint(16) NOT NULL,
  `fleetID` bigint(16) DEFAULT NULL,
  `backupfc` tinyint(1) NOT NULL DEFAULT '0',
  `wingID` bigint(16) NOT NULL,
  `squadID` bigint(16) NOT NULL,
  `role` varchar(32) DEFAULT NULL,
  `fleetWarp` tinyint(1) NOT NULL DEFAULT '1',
  `joined` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `fleets` (
  `fleetID` bigint(16) NOT NULL,
  `boss` bigint(16) NOT NULL,
  `fc` bigint(16) NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastFetch` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `pilots` (
  `characterID` bigint(16) NOT NULL,
  `characterName` varchar(255) NOT NULL,
  `locationID` bigint(16) NOT NULL,
  `shipTypeID` int(11) NOT NULL,
  `stationID` int(11) DEFAULT NULL,
  `structureID` bigint(16) DEFAULT NULL,
  `fitting` varchar(500) DEFAULT NULL,
  `lastFetch` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `structures` (
  `solarSystemID` int(11) NOT NULL,
  `structureID` bigint(16) NOT NULL,
  `structureName` varchar(255) DEFAULT NULL,
  `lastUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `authTokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `characterID` (`characterID`);
ALTER TABLE `esisso`
  ADD PRIMARY KEY (`characterID`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `characterID` (`characterID`);
ALTER TABLE `fleetmembers`
  ADD PRIMARY KEY (`characterID`),
  ADD UNIQUE KEY `characterID` (`characterID`);
ALTER TABLE `fleets`
  ADD PRIMARY KEY (`fleetID`),
  ADD UNIQUE KEY `fleetID` (`fleetID`);
ALTER TABLE `pilots`
  ADD PRIMARY KEY (`characterID`),
  ADD UNIQUE KEY `characterID` (`characterID`);
ALTER TABLE `structures`
  ADD PRIMARY KEY (`structureID`),
  ADD UNIQUE KEY `structureID` (`structureID`),
  ADD KEY `structureID_2` (`structureID`);
ALTER TABLE `authTokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;
ALTER TABLE `esisso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
