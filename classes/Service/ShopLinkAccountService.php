<?php

namespace PrestaShop\Module\PsAccounts\Service;

// TODO : OnboardingDataDTO

use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Api\Client\ServicesAccountsClient;
use PrestaShop\Module\PsAccounts\Exception\HmacException;
use PrestaShop\Module\PsAccounts\Exception\PsAccountsRsaSignDataEmptyException;
use PrestaShop\Module\PsAccounts\Exception\QueryParamsException;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ShopLinkAccountService
{
    /**
     * @var ShopKeysService
     */
    private $shopKeysService;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var ShopTokenService
     */
    private $shopTokenService;

    /**
     * @var ServicesAccountsClient
     */
    private $servicesAccountsClient;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var Link
     */
    private $link;

    /**
     * @var string | null
     */
    private $psxName = null;

    /**
     * ShopLinkAccountService constructor.
     */
    public function __construct(
        ShopProvider $shopProvider,
        ShopKeysService $shopKeysService,
        ShopTokenService $shopTokenService,
        ConfigurationRepository $configuration,
        Link $link
    ) {
        $this->shopProvider = $shopProvider;
        $this->shopKeysService = $shopKeysService;
        $this->shopTokenService = $shopTokenService;
        $this->configuration = $configuration;
        $this->link = $link;
    }

    /**
     * @param string $psxName
     *
     * @return void
     */
    public function setPsxName($psxName)
    {
        $this->psxName = $psxName;
    }

    /**
     * @return string | null
     */
    public function getPsxName()
    {
        return $this->psxName;
    }

    /**
     * @param array $bodyHttp
     * @param string $trigger
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function updateShopUrl($bodyHttp, $trigger)
    {
        if (array_key_exists('shop_id', $bodyHttp)) {
            // id for multishop
            $this->configuration->setShopId($bodyHttp['shop_id']);
        }

        $sslEnabled = $this->shopProvider->getShopContext()->sslEnabled();
        $protocol = $this->shopProvider->getShopContext()->getProtocol();
        $domain = $sslEnabled ? $bodyHttp['domain_ssl'] : $bodyHttp['domain'];

        $uuid = $this->configuration->getShopUuid();

        $response = false;
        $boUrl = $this->replaceScheme(
            $this->link->getAdminLink('AdminModules', true),
            $protocol . '://' . $domain
        );

        if ($uuid && strlen($uuid) > 0) {
            $response = $this->servicesAccountsClient->updateShopUrl(
                $uuid,
                [
                    'protocol' => $protocol,
                    'domain' => $domain,
                    'boUrl' => $boUrl,
                    'trigger' => $trigger,
                ]
            );
        }

        return $response;
    }

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
            'lang' => $this->shopProvider->getShopContext()->getContext()->language->iso_code,
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
        $response = $this->servicesAccountsClient->deleteShop((string) $this->getShopUuidV4());

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

    /**
     * @param string $url
     * @param string $replacement
     *
     * @return string
     */
    private function replaceScheme($url, $replacement = '')
    {
        return preg_replace('/^https?:\/\/[^\/]+/', $replacement, $url);
    }
}
