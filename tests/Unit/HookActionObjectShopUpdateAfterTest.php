<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Api\Client\UpdateShopDto;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\PrestaShop\Adapter\Meta\ShopUrlDataConfiguration;
use PrestaShopBundle\Entity\Shop;

class HookActionObjectShopUpdateAfterTest extends TestCase
{
    /**
     * @not_a_test
     *
     * @throws Exception
     * @throws OptimisticLockException
     */
    public function itShouldUpdateBoBaseUrlInBoContext()
    {
        // TODO: spy on update shop api call boBaseUrl
        ///** @var AccountsClient $tokenRepos */
        $apiClient = $this->getMockBuilder(AccountsClient::class)
            ->setConstructorArgs([
                $this->module->getParameter('ps_accounts.accounts_api_url'),
                $this->module->getService(ShopProvider::class)
            ])
            ->setMethods(['updateUserShop'])
            ->getMock();

        // Replace with mocked version
        //$this->module->getServiceContainer()->getContainer()->set(AccountsClient::class, $apiClient);

//        $this->assertEquals('toto',
//            $this->module->getService(AccountsClient::class)
//                ->updateUserShop(new UpdateShop([
//                    'shopId' => 1,
//                    'name' => 'foo',
//                    'domain' => 'my.shop',
//                    'sslDomain' => 'my.secure.shop',
//                    'virtualUri' => 'bar',
//                    'physicalUri' => 'baz',
//                    'boBaseUrl' => 'base.url',
//                ]))
//        );

        $apiClient->expects($this->once())
            ->method('updateUserShop')
            ->willReturnCallback(function (UpdateShopDto $updateShopDto) {
                $boBaseUrl = $updateShopDto->boBaseUrl;
                error_log('############### ' . $boBaseUrl);
            });

        /** @var ShopUrlDataConfiguration $shopUrlData */
        $shopUrlData = $this->module->getContainer()->get('prestashop.adapter.meta.shop_url.configuration');

        $shopUrlData->updateConfiguration([
            "domain" => 'foo.com',
            "domain_ssl" => 'foo.secure.com',
            "physical_uri" => '/',
        ]);

//        /** @var EntityManagerInterface $entityManager */
//        $entityManager = $this->module->getContainer()->get('doctrine.orm.entity_manager');
//
//        $shopRepository = $entityManager->getRepository(Shop::class);
//
//        /** @var Shop $shop */
//        $shop = $shopRepository->findOneBy(['id' => 1]);
//
//        // FIXME: update shop domain
//        $shop->setName('Test PrestaShop');
//
//        $entityManager->persist($shop);
//        $entityManager->flush();
    }
}
