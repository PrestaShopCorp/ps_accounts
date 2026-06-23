<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\AccountLogin;

use PrestaShop\Module\PsAccounts\AccountLogin\OAuth2Session;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OAuth2LoginTraitTest extends TestCase
{
    public function set_up()
    {
        parent::set_up();

        // OAuth2Controller (and the Symfony session it relies on) only exists on PS 1.7+
        if (false === version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->markTestSkipped('OAuth2 controller / Symfony session only available on PS 1.7+');
        }
    }

    public function tear_down()
    {
        // Tools::getValue reads $_POST + $_GET: clean up so other tests aren't polluted
        unset($_GET['shop_id'], $_GET['code'], $_GET['state'], $_GET['__ps_oauth_retry']);
        unset($_POST['shop_id'], $_POST['code'], $_POST['state'], $_POST['__ps_oauth_retry']);
        unset($_SERVER['REQUEST_URI']);

        parent::tear_down();
    }

    /**
     * A callback carrying a valid code + state but landing on a request whose session lost
     * the stored oauth2state is the PS_COOKIE_SAMESITE=Strict symptom: instead of failing,
     * the flow must emit a same-site bounce (re-navigation) to recover the cookie, without
     * ever attempting the token exchange (which would lack the pkceCode).
     *
     * @test
     */
    public function itShouldBounceWhenSessionIsMissing()
    {
        $session = $this->createSessionDouble(false, null);

        $this->whenCallbackReceives('somevalidcode', 'state-from-callback');

        $instance = $this->createInstance($session, $this->createOAuth2ServiceNeverCalled());

        $result = $instance->oauth2Login();

        $this->assertSame('same-site-bounce', $result);
        $this->assertNotNull($instance->bouncedUrl);
        $this->assertStringContainsString('code=somevalidcode', $instance->bouncedUrl);
        $this->assertStringContainsString('state=state-from-callback', $instance->bouncedUrl);
        $this->assertStringContainsString('__ps_oauth_retry=1', $instance->bouncedUrl);
    }

    /**
     * If the session is still missing after a bounce already happened (__ps_oauth_retry=1),
     * this is a genuine session loss: reject before any token exchange instead of looping.
     *
     * @test
     */
    public function itShouldRejectCallbackWhenSessionStillMissingAfterBounce()
    {
        $session = $this->createSessionDouble(false, null);

        $this->whenCallbackReceives('somevalidcode', 'state-from-callback');
        $_GET['__ps_oauth_retry'] = '1';

        $instance = $this->createInstance($session, $this->createOAuth2ServiceNeverCalled());

        $thrown = null;
        try {
            $instance->oauth2Login();
        } catch (\Exception $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(\Exception::class, $thrown);
        $this->assertSame('Invalid state', $thrown->getMessage());
        // No second bounce was emitted: the guard turned the retry into a clean failure.
        $this->assertNull($instance->bouncedUrl);
    }

    /**
     * A callback whose state does not match the one stored in session (CSRF mitigation) must
     * be rejected before any token exchange.
     *
     * @test
     */
    public function itShouldRejectCallbackWhenStateDoesNotMatch()
    {
        $session = $this->createSessionDouble(true, 'a-different-state');

        $this->whenCallbackReceives('somevalidcode', 'state-from-callback');

        $instance = $this->createInstance($session, $this->createOAuth2ServiceNeverCalled());

        $session->expects($this->once())->method('remove')->with('oauth2state');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid state');

        $instance->oauth2Login();
    }

    /**
     * @param string $code
     * @param string $state
     *
     * @return void
     */
    private function whenCallbackReceives($code, $state)
    {
        $_GET['shop_id'] = \Context::getContext()->shop->id;
        $_GET['code'] = $code;
        $_GET['state'] = $state;

        // buildBounceUrl() rebuilds the current callback URL from REQUEST_URI + $_GET
        $_SERVER['REQUEST_URI'] = '/admin/index.php?controller=AdminOAuth2PsAccounts';
    }

    /**
     * @param bool $hasState whether the session holds an oauth2state key
     * @param string|null $storedState value returned for get('oauth2state')
     *
     * @return SessionInterface
     */
    private function createSessionDouble($hasState, $storedState)
    {
        $session = $this->createMock(SessionInterface::class);

        $session->method('has')->willReturnCallback(function ($key) use ($hasState) {
            return 'oauth2state' === $key ? $hasState : false;
        });

        // getShopId() calls get('shopId') eagerly (default arg of Tools::getValue) — answer null
        $session->method('get')->willReturnCallback(function ($key, $default = null) use ($storedState) {
            return 'oauth2state' === $key ? $storedState : $default;
        });

        return $session;
    }

    /**
     * The discriminating assertion: the token exchange (with the missing pkceCode) is the
     * exact call the fix prevents, so it must never be reached on a rejected callback.
     *
     * @return OAuth2Service
     */
    private function createOAuth2ServiceNeverCalled()
    {
        $oauth2Service = $this->createMock(OAuth2Service::class);
        $oauth2Service->expects($this->never())->method('getAccessTokenByAuthorizationCode');

        return $oauth2Service;
    }

    /**
     * @param SessionInterface $session
     * @param OAuth2Service $oauth2Service
     *
     * @return OAuth2LoginTraitTestClass
     */
    private function createInstance($session, $oauth2Service)
    {
        return new OAuth2LoginTraitTestClass(
            $this->module,
            $session,
            $oauth2Service,
            $this->createMock(OAuth2Session::class)
        );
    }
}
