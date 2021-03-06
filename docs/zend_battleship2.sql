--
-- Table structure for table `fields`
--

CREATE TABLE IF NOT EXISTS `fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `size_x` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT 'Number of plates on the X axis.',
  `size_y` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT 'Number of plates on the Y axis.',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `field_plates`
--

CREATE TABLE IF NOT EXISTS `field_plates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(10) unsigned NOT NULL,
  `game_vessel_id` int(10) unsigned DEFAULT NULL,
  `coordinate_x` int(10) unsigned NOT NULL,
  `coordinate_y` char(1) NOT NULL COMMENT 'A through Z',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 - Initial state, 1 - strike, 2 - miss.',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FIELD_PLATES_FIELD_ID_FK_idx` (`field_id`),
  KEY `fk_field_plates_game_vessels_idx` (`game_vessel_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE IF NOT EXISTS `games` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned NOT NULL COMMENT 'The plaer_id that is playing this game.',
  `field_id` int(10) unsigned NOT NULL COMMENT 'The battle field of the current game.',
  `moves_cnt` int(10) unsigned NOT NULL COMMENT 'The moves count needed for the game to be finished.',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Status of the game.\n0 - New, 1 - In progress, 2 - Finished',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `GAMES_PLAYER_ID_IDX` (`player_id`),
  KEY `GAMES_FIELD_ID_IDX` (`field_id`),
  KEY `GAMES_STATUS_IDX` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `game_config`
--

CREATE TABLE IF NOT EXISTS `game_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `value` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `GAME_CONFING_NAME_IDX` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `game_config`
--

INSERT INTO `game_config` (`id`, `name`, `value`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'x', '10', '2015-01-06 08:44:47', NULL, NULL),
(2, 'y', '10', '2015-01-06 08:44:47', NULL, NULL),
(3, 'hit_sign', 'x', '2015-01-06 08:44:58', NULL, NULL),
(4, 'miss_sign', '-', '2015-01-06 08:44:58', NULL, NULL),
(5, 'no_shot_sign', '.', '2015-01-06 08:45:06', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `game_shots`
--

CREATE TABLE IF NOT EXISTS `game_shots` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `shot_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0 - None, 1 - Strike, 2 - Miss.',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `GAME_SHOTS_GAME_ID_FK_idx` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `game_vessels`
--

CREATE TABLE IF NOT EXISTS `game_vessels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `vessel_type_id` int(10) unsigned NOT NULL,
  `coordinate_x` int(10) unsigned NOT NULL,
  `coordinate_y` char(1) NOT NULL COMMENT 'A through Z',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 - Intact, 1 - Hit, 2 - Sunk.',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `GAME_VESSELS_GAME_ID_FK_idx` (`game_id`),
  KEY `GAME_VESSELS_COORDINATE_X_IDX` (`coordinate_x`),
  KEY `GAME_VESSELS_COORDINATE_Y_IDX` (`coordinate_y`),
  KEY `GAME_VESSELS_VESSEL_TYPE_ID_FK_idx` (`vessel_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE IF NOT EXISTS `players` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '0 - inactive, 1 - active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `PLAYERS_USERNAME_IDX` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `vessel_types`
--

CREATE TABLE IF NOT EXISTS `vessel_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `size` tinyint(4) NOT NULL COMMENT 'Field plates count.',
  `vessels_count` int(10) unsigned NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '0 - Inactive, 1 - Active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `GAME_VESSELS_NAME_IDX` (`name`),
  KEY `GAME_VESSELS_SIZE_IDX` (`size`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `vessel_types`
--

INSERT INTO `vessel_types` (`id`, `name`, `size`, `vessels_count`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Battleship', 5, 1, 1, '2015-01-06 08:45:24', NULL, NULL),
(2, 'Destroyer', 4, 2, 1, '2015-01-06 08:45:24', NULL, NULL);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `field_plates`
--
ALTER TABLE `field_plates`
  ADD CONSTRAINT `FIELD_PLATES_FIELD_ID_FK` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_field_plates_game_vessels` FOREIGN KEY (`game_vessel_id`) REFERENCES `game_vessels` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `GAMES_FIELD_ID_FK` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `GAMES_PLAYER_ID_FK` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `game_shots`
--
ALTER TABLE `game_shots`
  ADD CONSTRAINT `GAME_SHOTS_GAME_ID_FK` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `game_vessels`
--
ALTER TABLE `game_vessels`
  ADD CONSTRAINT `GAME_VESSELS_GAME_ID_FK` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `GAME_VESSELS_VESSEL_TYPE_ID_FK` FOREIGN KEY (`vessel_type_id`) REFERENCES `vessel_types` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
