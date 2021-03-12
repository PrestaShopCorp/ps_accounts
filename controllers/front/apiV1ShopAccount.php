<?php

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ps_AccountsApiV1ShopAccountModuleFrontRestController extends AbstractShopRestController
{
    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var Parser
     */
    private $jwtParser;

    /**
     * ps_AccountsApiV1ShopAccountModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = $this->module->getService(ConfigurationRepository::class);

        $this->jwtParser = new Parser();
    }

    /**
     * @param array $payload
     *
     * @return array
     *
     * @throws Exception
     */
    public function store(array $payload)
    {
        // TODO : verify tokens against Firebase
        // TODO : store BOTH user JWT & shop JWT
        // TODO : store PS_ACCOUNTS_FIREBASE_USER_ID_TOKEN_[user_id]
        // TODO : Entité "Account"
        // FIXME : prévoir plusieurs comptes utilisateur par shop

        $uuid = $this->jwtParser->parse((string) $payload['shop_token'])->getClaim('user_id');
        $this->configuration->updateShopUuid($uuid);

        $email = $this->jwtParser->parse((string) $payload['user_token'])->getClaim('email');
        $this->configuration->updateFirebaseEmail($email);

        $this->configuration->updateFirebaseIdAndRefreshTokens(
            $payload['shop_token'],
            $payload['shop_refresh_token']
        );

        return [
            'success' => true,
            'message' => 'Link Account stored successfully',
        ];
    }

    /**
     * @param mixed $id
     * @param array $payload
     *
     * @return array|void
     *
     * @throws Exception
     */
    public function show($id, array $payload)
    {
        return [
            'shop_uuid' => $this->configuration->getShopUuid(),
            'shop_token' => $this->configuration->getFirebaseIdToken(),
            'shop_refresh_token' => $this->configuration->getFirebaseRefreshToken(),
            'user_token' => null,
        ];
    }
}
