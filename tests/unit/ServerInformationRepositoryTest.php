<?php

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\CurrencyRepository;
use PrestaShop\Module\PsAccounts\Repository\LanguageRepository;
use PrestaShop\Module\PsAccounts\Repository\ServerInformationRepository;

class ServerInformationRepositoryTest extends TestCase
{
    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;
    /**
     * @var LanguageRepository
     */
    private $languageRepository;
    /**
     * @var ServerInformationRepository
     */
    private $serverInformationRepository;
    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;
    /**
     * @var ArrayFormatter
     */
    private $arrayFormatter;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var Db
     */
    private $db;

    protected function setUp()
    {
        parent::setUp();
        $this->currencyRepository = $this->createMock(CurrencyRepository::class);
        $this->languageRepository = $this->createMock(LanguageRepository::class);
        $this->configurationRepository = $this->createMock(ConfigurationRepository::class);
        $this->arrayFormatter = $this->createMock(ArrayFormatter::class);
        $this->context = $this->createMock(Context::class);
        $this->db = $this->createMock(Db::class);

        $this->serverInformationRepository = new ServerInformationRepository(
            $this->context,
            $this->db,
            $this->currencyRepository,
            $this->languageRepository,
            $this->configurationRepository,
            $this->arrayFormatter
        );
    }

    public function testGetServerInformation()
    {
        $this->currencyRepository->method('getCurrenciesIsoCodes')->willReturn(['EUR', 'USD']);
        $this->currencyRepository->method('getDefaultCurrencyIsoCode')->willReturn('USD');

        $this->languageRepository->method('getLanguagesIsoCodes')->willReturn(['en', 'fr', 'lt']);
        $this->languageRepository->method('getDefaultLanguageIsoCode')->willReturn('en');

        $this->configurationRepository->expects($this->at(0))->method('get')->with('PS_REWRITING_SETTINGS')->willReturn(true);
        $this->configurationRepository->expects($this->at(1))->method('get')->with('PS_CART_FOLLOWING')->willReturn(true);
        $this->configurationRepository->expects($this->at(2))->method('get')->with('PS_TIMEZONE')->willReturn('GMT/Zulu');

        $this->assertTrue(is_array($this->serverInformationRepository->getServerInformation()));
    }
}
