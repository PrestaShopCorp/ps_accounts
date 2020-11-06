<?php

function upgrade_module_2_3_2()
{
    $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'accounts_sync`;';
    $sql .= 'ALTER TABLE `' . _DB_PREFIX_ . 'accounts_sync` CHANGE `job_id` `job_id` VARCHAR(200) NOT NULL;';

    return Db::getInstance()->execute($sql);
}
