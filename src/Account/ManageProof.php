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

namespace PrestaShop\Module\PsAccounts\Account;

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use Ramsey\Uuid\Uuid;

class ManageProof
{
    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * ManageProof constructor.
     *
     * @param ConfigurationRepository $configuration
     */
    public function __construct(
        ConfigurationRepository $configuration
    ) {
        $this->configuration = $configuration;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function generateProof()
    {
        $proof = Uuid::uuid4()->toString();

        $this->configuration->updateProof($proof);

        return $proof;
    }

    /**
     * @return string|null
     */
    public function getProof()
    {
        return $this->configuration->getProof();
    }
}
