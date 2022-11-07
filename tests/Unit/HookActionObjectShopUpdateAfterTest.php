<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\DTO\UpdateShop;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\PrestaShop\Adapter\Meta\ShopUrlDataConfiguration;
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
            ->willReturn('toto');

        // Replace with mocked version
        $this->module->getServiceContainer()->getContainer()->set(AccountsClient::class, $apiClient);

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

        // $this->module->install();

        global $kernel;
        if(!$kernel){
            require_once _PS_ROOT_DIR_.'/app/AppKernel.php';
            $kernel = new \AppKernel('dev', false);
            $kernel->boot();
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->module->getContainer()->get('doctrine.orm.entity_manager');

        // ShopUrlDataConfiguration::class

        $shopRepository = $entityManager->getRepository(Shop::class);

        /** @var Shop $shop */
        $shop = $shopRepository->findOneBy(['id' => 1]);

        // FIXME: update shop domain
        $shop->setName('Test PrestaShop');

        $entityManager->persist($shop);
        $entityManager->flush();

        $apiClient->expects($this->once())
            ->method('updateUserShop')
            ->willReturnCallback(function (UpdateShop $updateShopDto) {
                $this->assertEquals('toto', $updateShopDto->boBaseUrl);
                return false;
            });
    }
}
