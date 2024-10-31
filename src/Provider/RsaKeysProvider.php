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

 use OpenSSLAsymmetricKey;
 use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
 use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
 
 /**
  * Manage RSA
  */
 class RsaKeysProvider
 {
     private ConfigurationRepository $configuration;
 
     public function __construct(ConfigurationRepository $configuration)
     {
         $this->configuration = $configuration;
     }
 
     /**
      * @return array<string, string>|null
      */
     public function createPair(): ?array
     {
         $keyConfig = [
             'private_key_bits' => 2048,
             'private_key_type' => OPENSSL_KEYTYPE_RSA,
         ];
 
         $res = openssl_pkey_new($keyConfig);
         if ($res === false) {
             return null;
         }
 
         openssl_pkey_export($res, $privateKey);
         $publicKey = openssl_pkey_get_details($res)['key'] ?? null;
 
         return $publicKey && $privateKey ? ['privatekey' => $privateKey, 'publickey' => $publicKey] : null;
     }
 
     /**
      * @param string $privateKey
      * @param string $data
      * @return string|null
      */
     public function signData(string $privateKey, string $data): ?string
     {
         $privateKeyResource = openssl_pkey_get_private($privateKey);
         if ($privateKeyResource === false) {
             return null;
         }
 
         openssl_sign($data, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
         return base64_encode($signature);
     }
 
     /**
      * @param string $publicKey
      * @param string $signature
      * @param string $data
      * @return bool|null
      */
     public function verifySignature(string $publicKey, string $signature, string $data): ?bool
     {
         $publicKeyResource = openssl_pkey_get_public($publicKey);
         if ($publicKeyResource === false) {
             return null;
         }
 
         $signatureDecoded = base64_decode($signature);
         return openssl_verify($data, $signatureDecoded, $publicKeyResource, OPENSSL_ALGO_SHA256) === 1;
     }
 
     /**
      * @param string $encrypted
      * @return string|null
      */
     public function decrypt(string $encrypted): ?string
     {
         $privateKey = $this->getPrivateKey();
         $privateKeyResource = openssl_pkey_get_private($privateKey);
         if ($privateKeyResource === false) {
             return null;
         }
 
         openssl_private_decrypt(base64_decode($encrypted), $decrypted, $privateKeyResource);
         return $decrypted ?: null;
     }
 
     /**
      * @param string $string
      * @return string|null
      */
     public function encrypt(string $string): ?string
     {
         $publicKey = $this->getPublicKey();
         $publicKeyResource = openssl_pkey_get_public($publicKey);
         if ($publicKeyResource === false) {
             return null;
         }
 
         openssl_public_encrypt($string, $encrypted, $publicKeyResource);
         return base64_encode($encrypted);
     }
 
     /**
      * @param bool $refresh
      * @return void
      * @throws SshKeysNotFoundException
      */
     public function generateKeys(bool $refresh = false): void
     {
         if ($refresh || !$this->hasKeys()) {
             $key = $this->createPair();
             if ($key === null) {
                 throw new SshKeysNotFoundException('Failed to create RSA keys');
             }
 
             $this->configuration->updateAccountsRsaPrivateKey($key['privatekey']);
             $this->configuration->updateAccountsRsaPublicKey($key['publickey']);
 
             if (!$this->hasKeys()) {
                 throw new SshKeysNotFoundException('No RSA keys found for the shop');
             }
         }
     }
 
     /**
      * @return string|null
      */
     public function getOrGenerateAccountsRsaPublicKey(): ?string
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
      * @return void
      * @throws SshKeysNotFoundException
      */
     public function regenerateKeys(): void
     {
         $this->generateKeys(true);
     }
 
     /**
      * @return bool
      */
     public function hasKeys(): bool
     {
         return !empty($this->configuration->getAccountsRsaPublicKey());
     }
 
     /**
      * @return string|null
      */
     public function getPublicKey(): ?string
     {
         return $this->configuration->getAccountsRsaPublicKey() ?: null;
     }
 
     /**
      * @return string|null
      */
     public function getPrivateKey(): ?string
     {
         return $this->configuration->getAccountsRsaPrivateKey() ?: null;
     }
 
     /**
      * @return void
      */
     public function cleanupKeys(): void
     {
         $this->configuration->updateAccountsRsaPrivateKey('');
         $this->configuration->updateAccountsRsaPublicKey('');
     }
 }
