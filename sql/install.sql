CREATE TABLE IF NOT EXISTS `PREFIX_accounts_type_sync` (
	  `type` varchar(50) NOT NULL,
	  `offset` int(10) unsigned NOT NULL DEFAULT 0,
	  `last_sync_date` DATETIME NOT NULL
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_accounts_sync` (
	  `job_id` varchar(50) NOT NULL,
	  `created_at` DATETIME NOT NULL
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;
