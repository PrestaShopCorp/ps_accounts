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

namespace PrestaShop\Module\PsAccounts\Account\Session;

use PrestaShop\Module\PsAccounts\Account\Command\UnlinkShopCommand;
use PrestaShop\Module\PsAccounts\Account\Exception\InconsistentAssociationStateException;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Hook\ActionShopAccessTokenRefreshAfter;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Grant\ClientCredentials;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Token\AccessToken;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Token\AccessTokenInterface;

class ShopSession extends Session implements SessionInterface
{
    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @var ShopProvider
     */
    protected $oauth2ClientProvider;

    /**
     * @var LinkShop
     */
    protected $linkShop;

    /**
     * @var int
     */
    protected $oauth2ClientReceiptTimeout = 60;

    /**
     * @param ConfigurationRepository $configurationRepository
     * @param ShopProvider $oauth2ClientProvider
     * @param CommandBus $commandBus
     */
    public function __construct(
        ConfigurationRepository $configurationRepository,
        ShopProvider $oauth2ClientProvider,
        LinkShop $linkShop,
        CommandBus $commandBus
    ) {
        $this->configurationRepository = $configurationRepository;
        $this->oauth2ClientProvider = $oauth2ClientProvider;
        $this->linkShop = $linkShop;
        $this->commandBus = $commandBus;
    }

    /**
     * @param string $refreshToken
     *
     * @return Token
     *
     * @throws RefreshTokenException
     */
    public function refreshToken($refreshToken = null)
    {
        try {
            $this->assertAssociationState($this->oauth2ClientReceiptTimeout);
            $shopUuid = $this->getShopUuid();
            $accessToken = $this->getAccessToken($shopUuid);

            //return new Token($accessToken->getToken(), $accessToken->getRefreshToken());
            $this->setToken(
                $accessToken->getToken(),
                $accessToken->getRefreshToken()
            );

            $token = $this->getToken();

            \Hook::exec(ActionShopAccessTokenRefreshAfter::getName(), ['token' => $token]);

            return $token;
        } catch (InconsistentAssociationStateException $e) {
            $this->commandBus->handle(new UnlinkShopCommand(
                $this->configurationRepository->getShopId(),
                $e->getMessage()
            ));
        } catch (IdentityProviderException $e) {
        } catch (\Throwable $e) {
            /* @phpstan-ignore-next-line */
        } catch (\Exception $e) {
        }
        throw new RefreshTokenException('Unable to refresh shop token : ' . $e->getMessage());
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        return new Token($this->configurationRepository->getAccessToken());
    }

    /**
     * @param string $token
     * @param string $refreshToken
     *
     * @return void
     */
    public function setToken($token, $refreshToken = null)
    {
        $this->configurationRepository->updateAccessToken($token);
    }

    /**
     * @return void
     */
    public function cleanup()
    {
        $this->configurationRepository->updateAccessToken('');
    }

    /**
     * @param int $oauth2ClientReceiptTimeout
     *
     * @return void
     */
    public function setOauth2ClientReceiptTimeout($oauth2ClientReceiptTimeout)
    {
        $this->oauth2ClientReceiptTimeout = $oauth2ClientReceiptTimeout;
    }

    /**
     * @param string $shopUid
     *
     * @return AccessToken|AccessTokenInterface
     *
     * @throws IdentityProviderException
     */
    protected function getAccessToken($shopUid)
    {
        $audience = [
            'shop_' . $shopUid,
            //'another.audience'
        ];
        $token = $this->oauth2ClientProvider->getAccessToken(new ClientCredentials(), [
            //'scope' => 'read.all write.all',
            'audience' => implode(' ', $audience),
        ]);
        Logger::getInstance()->debug(__METHOD__ . json_encode($token->jsonSerialize(), JSON_PRETTY_PRINT));

        return $token;
    }

    /**
     * @param int $oauth2ClientReceiptTimeout
     *
     * @return void
     *
     * @throws InconsistentAssociationStateException
     */
    protected function assertAssociationState($oauth2ClientReceiptTimeout = 60)
    {
        $linkedAtTs = $currentTs = time();
        if ($this->linkShop->linkedAt()) {
            $linkedAtTs = (new \DateTime($this->linkShop->linkedAt()))->getTimestamp();
        }

        if ($this->linkShop->exists() &&
            $currentTs - $linkedAtTs > $oauth2ClientReceiptTimeout &&
            !$this->oauth2ClientProvider->getOauth2Client()->exists()) {
            throw new InconsistentAssociationStateException('Invalid OAuth2 client');
        }
    }

    /**
     * @return string
     */
    private function getShopUuid()
    {
        return $this->linkShop->getShopUuid();
    }
}
