<?php

namespace PrestaShop\Module\PsAccounts\Service;

// TODO : OnboardingDataDTO

use PrestaShop\Module\PsAccounts\Api\Client\ServicesAccountsClient;
use PrestaShop\Module\PsAccounts\Exception\HmacException;
use PrestaShop\Module\PsAccounts\Exception\PsAccountsRsaSignDataEmptyException;
use PrestaShop\Module\PsAccounts\Exception\QueryParamsException;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;

class ShopLinkAccountService
{
    /**
     * @return string
     *
     * @throws \PrestaShopException
     */
    public function getLinkAccountUrl()
    {
        $callback = $this->replaceScheme(
            $this->link->getAdminLink('AdminModules', true) . '&configure=' . $this->psxName
        );

        $uiSvcBaseUrl = $this->accountsUiUrl;

        $protocol = $this->shopProvider->getShopContext()->getProtocol();
        $currentShop = $this->shopProvider->getCurrentShop($this->psxName);
        $domainName = $this->shopProvider->getShopContext()->sslEnabled() ? $currentShop['domainSsl'] :$currentShop['domain'];

        $queryParams = [
            'bo' => $callback,
            'pubKey' => $this->shopKeysService->getPublicKey(),
            'next' => $this->replaceScheme(
                $this->link->getAdminLink('AdminConfigureHmacPsAccounts')
            ),
            'name' => $currentShop['name'],
            'lang' => $this->context->language->iso_code,
        ];

        $queryParamsArray = [];
        foreach ($queryParams as $key => $value) {
            $queryParamsArray[] = $key . '=' . urlencode($value);
        }
        $strQueryParams = implode('&', $queryParamsArray);

        return  $uiSvcBaseUrl . '/shop/account/link/' . $protocol . '/' . $domainName
            . '/' . $protocol . '/' . $domainName . '/' . $this->psxName . '?' . $strQueryParams;
    }

    /**
     * @param array $queryParams
     * @param string $rootDir
     *
     * @return string
     *
     * @throws HmacException
     * @throws QueryParamsException
     * @throws SshKeysNotFoundException
     * @throws PsAccountsRsaSignDataEmptyException
     */
    public function getVerifyAccountUrl(array $queryParams, $rootDir)
    {
        $this->shopKeysService->generateKeys();

        $hmacPath = $rootDir . '/upload/';

        // FIXME: need some kind of DTO
        foreach (
            [
                'hmac' => '/[a-zA-Z0-9]{8,64}/',
                'uid' => '/[a-zA-Z0-9]{8,64}/',
                'slug' => '/[-_a-zA-Z0-9]{8,255}/'
            ] as $key => $value
        ) {
            if (!array_key_exists($key, $queryParams)) {
                throw new QueryParamsException('Missing query params', 500);
            }

            if (!preg_match($value, $queryParams[$key])) {
                throw new QueryParamsException('Invalide query params', 500);
            }
        }

        if (!is_dir($hmacPath)) {
            mkdir($hmacPath);
        }

        if (!is_writable($hmacPath)) {
            throw new HmacException('Directory isn\'t writable', 500);
        }

        file_put_contents($hmacPath . $queryParams['uid'] . '.txt', $queryParams['hmac']);

        $url = $this->accountsUiUrl;

        if ('/' === substr($url, -1)) {
            $url = substr($url, 0, -1);
        }

        if (empty($this->shopKeysService->getSignature())) {
            throw new PsAccountsRsaSignDataEmptyException('PsAccounts RsaSignData couldn\'t be empty', 500);
        }

        return $url . '/shop/account/verify/' . $queryParams['uid']
            . '?shopKey=' . urlencode($this->shopKeysService->getSignature());
    }

    /**
     * @return array
     */
    public function unlinkShop()
    {
        /** @var ServicesAccountsClient $servicesAccountsClient */
        $servicesAccountsClient = $this->module->getService(ServicesAccountsClient::class);

        $response = $servicesAccountsClient->deleteShop((string) $this->getShopUuidV4());

        // Réponse: 200: Shop supprimé avec payload contenant un message de confirmation
        // Réponse: 404: La shop n'existe pas (not found)
        // Réponse: 401: L'utilisateur n'est pas autorisé à supprimer cette shop

        if ($response['status'] && $response['httpCode'] === 200) {
            $this->resetOnboardingData();
        }

        return $response;
    }

    /**
     * Empty onboarding configuration values
     *
     * @return void
     */
    public function resetOnboardingData()
    {
        $this->configuration->updateAccountsRsaPrivateKey('');
        $this->configuration->updateAccountsRsaPublicKey('');
        $this->configuration->updateAccountsRsaSignData('');

        $this->configuration->updateFirebaseIdAndRefreshTokens('', '');
        $this->configuration->updateFirebaseEmail('');
        $this->configuration->updateFirebaseEmailIsVerified(false);

        $this->configuration->updateShopUuid('');
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function manageOnboarding()
    {
        $this->shopKeysService->generateKeys();
        $this->updateOnboardingData();
    }

    /**
     * Only callable during onboarding
     *
     * Prepare onboarding data
     *
     * @return void
     *
     * @throws \Exception
     */
    public function updateOnboardingData()
    {
        $email = \Tools::getValue('email');
        $emailVerified = \Tools::getValue('emailVerified');
        $customToken = \Tools::getValue('adminToken');

        if (false === $this->shopKeysService->hasKeys()) {
            throw new \Exception('SSH keys were not found');
        }

        if (!$this->shopTokenService->exchangeCustomTokenForIdAndRefreshToken($customToken)) {
            throw new \Exception('Unable to get Firebase token');
        }

        if (!empty($email)) {
            $this->configuration->updateFirebaseEmail($email);

            if (!empty($emailVerified)) {
                $this->configuration->updateFirebaseEmailIsVerified('true' === $emailVerified);
            }
        }
    }
}
