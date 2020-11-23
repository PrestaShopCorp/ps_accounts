CREATE TABLE IF NOT EXISTS `PREFIX_accounts_type_sync` (
	  `type` varchar(50) NOT NULL,
	  `offset` int(10) unsigned NOT NULL DEFAULT 0,
	  `lang_iso` varchar(3),
	  `last_sync_date` DATETIME NOT NULL
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_accounts_sync` (
	  `job_id` varchar(200) NOT NULL,
	  `created_at` DATETIME NOT NULL
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_accounts_deleted_objects` (
    `type`       VARCHAR(50)      NOT NULL,
    `id_object`  INT(10) UNSIGNED NOT NULL,
    `id_shop`    INT(10) UNSIGNED NOT NULL,
    `created_at` DATETIME         NOT NULL,
    PRIMARY KEY (`type`, `id_object`, `id_shop`)
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;
