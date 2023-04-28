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

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Entity;

use phpseclib\Crypt\RSA;
use PrestaShop\Module\PsAccounts\Domain\Shop\Exception\PublicKeyException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

/**
 * Manage RSA
 */
class PublicKey
{
    /**
     * @var RSA
     */
    private $rsa;

    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    public function __construct(ConfigurationRepository $configuration)
    {
        $this->rsa = new RSA();
        $this->rsa->setHash('sha256');
        $this->rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);

        $this->configurationRepository = $configuration;
    }

    public function createPair(): array
    {
        $this->rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_PKCS1);
        $this->rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_PKCS1);

        return $this->rsa->createKey();
    }

    /**
     * @throws PublicKeyException
     */
    public function generateKeys(bool $refresh = false): void
    {
        if ($refresh || false === $this->hasKeys()) {
            $key = $this->createPair();
            $this->configurationRepository->updateAccountsRsaPrivateKey($key['privatekey']);
            $this->configurationRepository->updateAccountsRsaPublicKey($key['publickey']);
            if (false === $this->hasKeys()) {
                throw new PublicKeyException('Error while generating keys');
            }
        }
    }

    /**
     * @return string|bool|null
     */
    public function getOrGeneratePublicKey()
    {
        $publicKey = $this->getPublicKey();
        if ($publicKey) {
            return $publicKey;
        }

        try {
            $this->regenerateKeys();

            return $this->getPublicKey();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @throws PublicKeyException
     */
    public function regenerateKeys(): void
    {
        $this->generateKeys(true);
    }

    public function hasKeys(): bool
    {
        return false === empty($this->configurationRepository->getAccountsRsaPublicKey());
    }

    /**
     * @return string|bool
     */
    public function getPublicKey()
    {
        return $this->configurationRepository->getAccountsRsaPublicKey();
    }

    public function getPrivateKey(): string
    {
        return $this->configurationRepository->getAccountsRsaPrivateKey();
    }

    public function cleanupKeys(): void
    {
        $this->configurationRepository->updateAccountsRsaPrivateKey('');
        $this->configurationRepository->updateAccountsRsaPublicKey('');
    }
}
