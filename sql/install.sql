CREATE TABLE IF NOT EXISTS `PREFIX_accounts_type_sync`
(
    `type`               VARCHAR(50)      NOT NULL,
    `offset`             INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `id_shop`            INT(10) UNSIGNED NOT NULL,
    `lang_iso`           VARCHAR(3),
    `full_sync_finished` TINYINT(1) NOT NULL DEFAULT 0,
    `last_sync_date`     DATETIME         NOT NULL
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_accounts_sync`
(
    `job_id`     VARCHAR(200) NOT NULL,
    `created_at` DATETIME     NOT NULL
) ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8;
