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

use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Cqrs\QueryBus;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Token;
use PrestaShop\Module\PsAccounts\Domain\Shop\Query\GetOrRefreshShopToken;

class ps_AccountsApiV1ShopTokenModuleFrontController extends AbstractShopRestController
{
    /**
     * @var QueryBus
     */
    private $queryBus;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->queryBus = $this->module->getService(QueryBus::class);
    }

    /**
     * @param Shop $shop
     * @param array $payload
     *
     * @return string[]
     *
     * @throws Exception
     */
    public function show(Shop $shop, array $payload): array
    {
        /** @var Token $token */
        $token = $this->queryBus->handle(new GetOrRefreshShopToken());

        return [
            'token' => (string) $token->getJwt(),
            'refresh_token' => (string) $token->getRefreshToken(),
        ];
    }
}
