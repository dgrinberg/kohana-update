CREATE TABLE IF NOT EXISTS `db_version` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `version` varchar(25) NOT NULL,
    `filename` varchar(255) NOT NULL,
    `stamp_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `stamp_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
