<?php
/**
 * 2007-2019 PrestaShop and Contributors.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Service;

use phpseclib\Crypt\RSA;

class SshKey
{
    public function __construct()
    {
        $this->rsa = new RSA();
        $this->rsa->setHash('sha256');
        $this->rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);
    }

    public function generate()
    {
        $this->rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_PKCS1);
        $this->rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_PKCS1);

        return $this->rsa->createKey();
    }

    public function signData($privateKey, $data)
    {
        $this->rsa->loadKey($privateKey);

        return base64_encode($this->rsa->sign($data));
    }

    public function verifySignature($publicKey, $signature, $data)
    {
        $this->rsa->loadKey($publicKey);

        return  $this->rsa->verify($data, base64_decode($signature));
    }
}
