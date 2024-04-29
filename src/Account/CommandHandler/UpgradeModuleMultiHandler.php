<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\UpgradeModuleCommand;
use PrestaShop\Module\PsAccounts\Account\Command\UpgradeModuleMultiCommand;
use PrestaShop\Module\PsAccounts\Account\Dto\UpgradeModule;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShopDatabaseException;

class UpgradeModuleMultiHandler
{
    /**
     * @var ConfigurationRepository
     */
    private $configRepo;

    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(
        CommandBus $commandBus,
        ConfigurationRepository $configRepo
    ) {
        $this->commandBus = $commandBus;
        $this->configRepo = $configRepo;
    }

    /**
     * @param UpgradeModuleMultiCommand $command
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     */
    public function handle(UpgradeModuleMultiCommand $command)
    {
        foreach ($this->getShops($this->configRepo->isMultishopActive()) as $id) {
            $this->commandBus->handle(new UpgradeModuleCommand(new UpgradeModule([
                'shopId' => $id,
                // FIXME: should be part of the command payload
                'version' => \Ps_accounts::VERSION,
            ])));
        }
    }

    /**
     * @param bool $multishop
     *
     * @return array|null[]
     *
     * @throws PrestaShopDatabaseException
     */
    private function getShops($multishop)
    {
        $shops = [null];
        if ($multishop) {
            $shops = [];
            $db = \Db::getInstance();
            $result = $db->query('SELECT id_shop FROM ' . _DB_PREFIX_ . 'shop');
            while ($row = $db->nextRow($result)) {
                /* @phpstan-ignore-next-line */
                $shops[] = $row['id_shop'];
            }
        }

        return $shops;
    }
}
