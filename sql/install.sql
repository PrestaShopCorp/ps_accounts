CREATE TABLE IF NOT EXISTS `PREFIX_accounts_type_sync`
(
    `type`               VARCHAR(50)      NOT NULL,
    `offset`             INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `id_shop`            INT(10) UNSIGNED NOT NULL,
    `lang_iso`           VARCHAR(3),
    `full_sync_finished` TINYINT(1)       NOT NULL DEFAULT 0,
    `last_sync_date`     DATETIME         NOT NULL
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_accounts_sync`
(
    `job_id`     VARCHAR(200) NOT NULL,
    `created_at` DATETIME     NOT NULL
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_accounts_deleted_objects`
(
    `type`       VARCHAR(50)      NOT NULL,
    `id_object`  INT(10) UNSIGNED NOT NULL,
    `id_shop`    INT(10) UNSIGNED NOT NULL,
    `created_at` DATETIME         NOT NULL,
    PRIMARY KEY (`type`, `id_object`, `id_shop`)
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_accounts_incremental_sync`
(
    `type`       VARCHAR(50)      NOT NULL,
    `id_object`  INT(10) UNSIGNED NOT NULL,
    `id_shop`    INT(10) UNSIGNED NOT NULL,
    `lang_iso`   VARCHAR(3),
    `created_at` DATETIME         NOT NULL,
    PRIMARY KEY (`type`, `id_object`, `id_shop`, `lang_iso`)
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;
