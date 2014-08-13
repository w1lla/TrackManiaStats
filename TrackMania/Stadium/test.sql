	
CREATE TABLE IF NOT EXISTS `competitions` (
	`id` int(10) NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL,
	`description` text,
	`logo` varchar(255) DEFAULT NULL,
	`embed` text,
	`background` varchar(255) NOT NULL DEFAULT '/img/fond_eswc.png',
	`date_begin` datetime DEFAULT NULL,
	`date_end` datetime DEFAULT NULL,
	`show` tinyint(1) NOT NULL DEFAULT '0',
	`type` varchar(50) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
	) COLLATE='utf8_general_ci'
	ENGINE=InnoDB AUTO_INCREMENT=1 ;
	
	CREATE TABLE IF NOT EXISTS `matches` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`MatchName` varchar(50) NOT NULL DEFAULT '',
	`MatchType` varchar(50) NOT NULL DEFAULT '',
	`MatchStart` datetime DEFAULT '0000-00-00 00:00:00',
	`MatchEnd` datetime DEFAULT '0000-00-00 00:00:00',
	`matchServerLogin` VARCHAR(250) NOT NULL,
	`competition_id` INT(10) NOT NULL DEFAULT '1',
	`show` tinyint (1),
	`Replay` VARCHAR(100) DEFAULT NULL,
	`Restarted` tinyint (1),
	PRIMARY KEY (`id`),
	Index (`matchServerLogin`),
	CONSTRAINT `FK_Matches_competition_id` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE
	) COLLATE='utf8_general_ci'
	ENGINE=InnoDB AUTO_INCREMENT=1 ;
	
	CREATE TABLE IF NOT EXISTS `players` (
	`id` mediumint(9) NOT NULL AUTO_INCREMENT,
	`login` varchar(50) NOT NULL,
	`nation` varchar(50) NOT NULL,
	`updatedate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	UNIQUE KEY `login` (`login`)
	) COLLATE='utf8_general_ci'
	ENGINE=InnoDB AUTO_INCREMENT=1 ;
	
	CREATE TABLE IF NOT EXISTS `player_nicknames` (
	`player_id` mediumint(9) NOT NULL,  
	`nickname` varchar(100) DEFAULT NULL,
	`player_nickname_Clean` varchar(255) NOT NULL,
	`competition_id` INT(10) NOT NULL DEFAULT '1',
	INDEX id (player_id, competition_id)
	) COLLATE='utf8_general_ci'
	ENGINE=InnoDB  AUTO_INCREMENT=1 ;
	
	CREATE TABLE IF NOT EXISTS `maps` (
	`id` MEDIUMINT( 9 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
									`uid` VARCHAR( 27 ) NOT NULL ,
									`name` VARCHAR( 100 ) NOT NULL ,
				  `author` VARCHAR( 30 ) NOT NULL
	) COLLATE='utf8_general_ci'
	ENGINE=InnoDB AUTO_INCREMENT=1 ;
	
	CREATE TABLE IF NOT EXISTS `match_rounds` (
	`id` mediumint(9) NOT NULL AUTO_INCREMENT,
	`match_id` INT NOT NULL DEFAULT '0',
	`MatchType` varchar(50) NOT NULL DEFAULT '',
	`map_id` mediumint(9) NOT NULL DEFAULT '0',
	`player_id` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
	`map_round` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
	`player_rank` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
	`player_time` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
	`player_checkpoints` TEXT,
	`player_score` MEDIUMINT( 9 ) DEFAULT '0',
	`matchServerLogin` VARCHAR(250) NOT NULL,
	`mapbonus` tinyint (1),
	PRIMARY KEY (`id`),
	Index (`matchServerLogin`),
	CONSTRAINT `FK_Match_details_match_id` FOREIGN KEY (`match_id`) REFERENCES `Matches` (`id`) ON DELETE CASCADE,
	CONSTRAINT `FK_Match_details_team_id` FOREIGN KEY (`MatchType`) REFERENCES `Matches` (`MatchType`) ON DELETE CASCADE,
	CONSTRAINT `FK_Match_details_map_id` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE,
	CONSTRAINT `FK_Match_details_player_id` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE
	) COLLATE='utf8_general_ci'
	ENGINE=InnoDB AUTO_INCREMENT=1 ;