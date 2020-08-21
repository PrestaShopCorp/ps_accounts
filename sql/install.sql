CREATE TABLE IF NOT EXISTS `PREFIX_accounts_sync_state` (
	  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `endpoint` varchar(50) NOT NULL,
	  `job_id` varchar(50) NOT NULL,
	  `sync_id` varchar(50) NOT NULL,
	  `limit` int(10) unsigned NOT NULL,
	  `offset` int(10) unsigned NOT NULL DEFAULT 0,
	  PRIMARY KEY (`id`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;
