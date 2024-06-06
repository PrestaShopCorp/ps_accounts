<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsEmailValidatedTest extends TestCase
{
    /**
     * @inject
     *
     * @var OwnerSession
     */
    protected $ownerSession;

    /**
     * @inject
     *
     * @var PsAccountsService
     */
    protected $service;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnTrue()
    {
        $token = $this->makeFirebaseToken(null, ['email_verified' => true]);

        $this->ownerSession->setToken($token);

        $this->assertTrue($this->service->isEmailValidated());
    }

    /** PHP Fatal error:  Cannot declare class PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Grant\ClientCredentials,
     *  because the name is already in use in
     *  /var/www/html/modules/ps_accounts/vendor/league/oauth2-client/src/Grant/ClientCredentials.php on line 22
     */

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        $this->ownerSession->setToken(
            $this->makeFirebaseToken(null, ['email_verified' => false])
        );

        $this->assertFalse($this->service->isEmailValidated());
    }
}
