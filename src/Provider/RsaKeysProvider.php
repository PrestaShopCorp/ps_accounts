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

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class RsaKeysProvider
{
    private $configuration;

    public function __construct(ConfigurationRepository $configuration)
    {
        $this->configuration = $configuration;
    }

    public function createPair()
    {
        $keyConfig = [
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($keyConfig);
        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)['key'];

        return [
            'privatekey' => $privateKey,
            'publickey' => $publicKey,
        ];
    }

    public function signData($privateKey, $data)
    {
        $privateKeyResource = openssl_pkey_get_private($privateKey);
        openssl_sign($data, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    public function verifySignature($publicKey, $signature, $data)
    {
        $publicKeyResource = openssl_pkey_get_public($publicKey);
        $signature = base64_decode($signature);
        return openssl_verify($data, $signature, $publicKeyResource, OPENSSL_ALGO_SHA256) === 1;
    }

    public function decrypt($encrypted)
    {
        $privateKey = $this->getPrivateKey();
        $privateKeyResource = openssl_pkey_get_private($privateKey);
        openssl_private_decrypt(base64_decode($encrypted), $decrypted, $privateKeyResource);
        return $decrypted;
    }

    public function encrypt($string)
    {
        $publicKey = $this->getPublicKey();
        $publicKeyResource = openssl_pkey_get_public($publicKey);
        openssl_public_encrypt($string, $encrypted, $publicKeyResource);
        return base64_encode($encrypted);
    }

    public function generateKeys($refresh = false)
    {
        if ($refresh || false === $this->hasKeys()) {
            $key = $this->createPair();
            $this->configuration->updateAccountsRsaPrivateKey($key['privatekey']);
            $this->configuration->updateAccountsRsaPublicKey($key['publickey']);

            if (false === $this->hasKeys()) {
                throw new SshKeysNotFoundException('No RSA keys found for the shop');
            }
        }
    }

    public function getOrGenerateAccountsRsaPublicKey()
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

    public function regenerateKeys()
    {
        $this->generateKeys(true);
    }

    public function hasKeys()
    {
        return false === empty($this->configuration->getAccountsRsaPublicKey());
    }

    public function getPublicKey()
    {
        return $this->configuration->getAccountsRsaPublicKey();
    }

    public function getPrivateKey()
    {
        return $this->configuration->getAccountsRsaPrivateKey();
    }

    public function cleanupKeys()
    {
        $this->configuration->updateAccountsRsaPrivateKey('');
        $this->configuration->updateAccountsRsaPublicKey('');
    }
}
