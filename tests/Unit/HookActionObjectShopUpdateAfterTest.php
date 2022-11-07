<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\DTO\UpdateShop;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShopBundle\Entity\Shop;

class HookActionObjectShopUpdateAfterTest extends TestCase
{
    /**
     * @test
     *
     * @throws Exception
     * @throws OptimisticLockException
     */
    public function itShouldUpdateBoBaseUrlInBoContext()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->module->get('doctrine.orm.entity_manager');

        $shopRepository = $entityManager->getRepository(Shop::class);

        /** @var Shop $shop */
        $shop = $shopRepository->findOneBy(['id' => 1]);

        // FIXME: update shop domain
        $shop->setName('foo');

        $entityManager->persist($shop);
        $entityManager->flush();

        // TODO: spy on update shop api call boBaseUrl
        ///** @var AccountsClient $tokenRepos */
        $apiClient = $this->getMockBuilder(AccountsClient::class)
            ->setConstructorArgs([
                $this->module->getParameter('ps_accounts.accounts_api_url'),
                $this->module->getService(ShopProvider::class)
            ])
            ->setMethods(['updateUserShop'])
            ->getMock();

        $apiClient->method('updateUserShop')
            ->willReturnCallback(function (UpdateShop $updateShopDto) {
                $this->assertEquals('', $updateShopDto->boBaseUrl);
            });
    }
}
