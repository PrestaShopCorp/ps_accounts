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
use PrestaShop\Module\PsAccounts\Vendor\Ramsey\Uuid\Uuid;

class ProofManager
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
     */
    public function generateProof()
    {
        // FIXME: best way to generate a unique proof ?
        //$proof = base64_encode(\hash_hmac('sha512', Uuid::uuid4()->toString(), uniqid()));
        $proof = Uuid::uuid4()->toString();

        $this->configuration->updateShopProof($proof);

        return $proof;
    }

    /**
     * @return string|null
     */
    public function getProof()
    {
        return $this->configuration->getShopProof();
    }

    /**
     * @return void
     */
    public function deleteProof()
    {
        $this->configuration->updateShopProof(null);
    }
}
