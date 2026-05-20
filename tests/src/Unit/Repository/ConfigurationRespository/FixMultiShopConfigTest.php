<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\ConfigurationRespository;

use PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class FixMultiShopConfigTest extends TestCase
{
    const KEY = ConfigurationKeys::PS_ACCOUNTS_ACCESS_TOKEN;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSkipWhenNotForcedAndContextMatchesActualState()
    {
        /** @var ConfigurationRepository $repo */
        $repo = $this->module->getService(ConfigurationRepository::class);
        $idShop = $this->probeShopId();

        $this->seedRow(self::KEY, '', $idShop, null);

        $repo->fixMultiShopConfig(false);

        // Without force, when isFeatureActive() === isMultishopActive() the function returns early
        // and no UPDATE on id_shop_group nor DELETE of shadow rows is performed.
        $this->assertNotNull($this->fetchValue(self::KEY, $idShop, null));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldDeleteShadowRowsWithEmptyValueDuplicatingAPopulatedRow()
    {
        if (!\Shop::isFeatureActive()) {
            $this->markTestSkipped('multishop branch requires Shop::isFeatureActive()');
        }

        /** @var ConfigurationRepository $repo */
        $repo = $this->module->getService(ConfigurationRepository::class);
        $idShop = $this->probeShopId();
        $idShopGroup = $this->probeShopGroupId($idShop);

        // shadow row (empty value, id_shop_group NULL)
        $this->seedRow(self::KEY, '', $idShop, null);
        // populated row (same shop, id_shop_group set)
        $this->seedRow(self::KEY, 'populated-jwt-value', $idShop, $idShopGroup);

        $repo->fixMultiShopConfig(true);

        $this->assertNull($this->fetchValue(self::KEY, $idShop, null), 'shadow row should be deleted');
        $this->assertSame('populated-jwt-value', $this->fetchValue(self::KEY, $idShop, $idShopGroup), 'populated row remains intact');
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldNormalizeOrphanShopGroupIdToPsShopValue()
    {
        if (!\Shop::isFeatureActive()) {
            $this->markTestSkipped('multishop branch requires Shop::isFeatureActive()');
        }

        /** @var ConfigurationRepository $repo */
        $repo = $this->module->getService(ConfigurationRepository::class);
        $idShop = $this->probeShopId();
        $expectedGroupId = $this->probeShopGroupId($idShop);

        $this->seedRow(self::KEY, 'populated-jwt-value', $idShop, null);

        $repo->fixMultiShopConfig(true);

        $this->assertNull($this->fetchValue(self::KEY, $idShop, null), 'orphan row no longer matches id_shop_group=NULL');
        $this->assertSame('populated-jwt-value', $this->fetchValue(self::KEY, $idShop, $expectedGroupId), 'row was migrated to actual ps_shop.id_shop_group');
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldNotTouchRowsForKeysNotOwnedByTheModule()
    {
        if (!\Shop::isFeatureActive()) {
            $this->markTestSkipped('multishop branch requires Shop::isFeatureActive()');
        }

        /** @var ConfigurationRepository $repo */
        $repo = $this->module->getService(ConfigurationRepository::class);
        $idShop = $this->probeShopId();
        $foreignKey = 'PS_FOREIGN_TEST_KEY_' . $this->faker->numberBetween(10000, 99999);

        // identical pathological shape on a foreign key — must be left untouched
        $this->seedRow($foreignKey, '', $idShop, null);
        $this->seedRow($foreignKey, 'foreign-value', $idShop, $this->probeShopGroupId($idShop));

        $repo->fixMultiShopConfig(true);

        $this->assertSame('', (string) $this->fetchValue($foreignKey, $idShop, null), 'foreign shadow row should remain');
        $this->assertSame('foreign-value', $this->fetchValue($foreignKey, $idShop, $this->probeShopGroupId($idShop)), 'foreign populated row should remain');
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldNotRunMultishopHelpersWhenShopFeatureInactive()
    {
        if (\Shop::isFeatureActive()) {
            $this->markTestSkipped('single-shop branch requires !Shop::isFeatureActive()');
        }

        /** @var ConfigurationRepository $repo */
        $repo = $this->module->getService(ConfigurationRepository::class);
        $idShop = $this->probeShopId();

        // Shape that the multishop branch would normally fix — but it must not fire here.
        $this->seedRow(self::KEY, '', $idShop, null);
        $this->seedRow(self::KEY, 'populated', $idShop, 1);

        $repo->fixMultiShopConfig(true);

        // single-shop branch only touches rows with id_shop = defaultShop.id and resets them to NULL/NULL
        // it does NOT call cleanupShadowedConfigurationRows nor normalizeOrphanShopGroupRows
        $this->assertNotNull($this->fetchValue(self::KEY, $idShop, 1), 'shadow row should remain since the multishop helpers do not run');
    }

    /**
     * @return int
     */
    private function probeShopId()
    {
        $id = (int) \Shop::getContextShopID(true);
        if ($id <= 0) {
            $id = (int) (new \Shop(1))->id ?: 1;
        }
        return $id;
    }

    /**
     * @param int $idShop
     *
     * @return int
     */
    private function probeShopGroupId($idShop)
    {
        $group = (int) (new \Shop($idShop))->id_shop_group;
        return $group ?: 1;
    }

    /**
     * @param string $name
     * @param string $value
     * @param int|null $idShop
     * @param int|null $idShopGroup
     *
     * @return void
     */
    private function seedRow($name, $value, $idShop, $idShopGroup)
    {
        $shopExpr = null === $idShop ? 'NULL' : (int) $idShop;
        $groupExpr = null === $idShopGroup ? 'NULL' : (int) $idShopGroup;
        \Db::getInstance()->execute(
            'INSERT INTO ' . _DB_PREFIX_ . 'configuration' .
            ' (name, value, id_shop, id_shop_group, date_add, date_upd)' .
            ' VALUES ("' . pSQL($name) . '", "' . pSQL($value) . '", ' . $shopExpr . ', ' . $groupExpr . ', NOW(), NOW())'
        );
    }

    /**
     * @param string $name
     * @param int|null $idShop
     * @param int|null $idShopGroup
     *
     * @return string|null
     */
    private function fetchValue($name, $idShop, $idShopGroup)
    {
        $shopExpr = null === $idShop ? 'id_shop IS NULL' : 'id_shop = ' . (int) $idShop;
        $groupExpr = null === $idShopGroup ? 'id_shop_group IS NULL' : 'id_shop_group = ' . (int) $idShopGroup;
        $row = \Db::getInstance()->getRow(
            'SELECT value FROM ' . _DB_PREFIX_ . 'configuration' .
            ' WHERE name = "' . pSQL($name) . '"' .
            ' AND ' . $shopExpr .
            ' AND ' . $groupExpr
        );
        return is_array($row) && array_key_exists('value', $row) ? $row['value'] : null;
    }
}
