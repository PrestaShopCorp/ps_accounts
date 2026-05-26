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
    public function itShouldPromoteOrphanGroupRowToNullForTokenKeys()
    {
        if (!\Shop::isFeatureActive()) {
            $this->markTestSkipped('multishop branch requires Shop::isFeatureActive()');
        }

        /** @var ConfigurationRepository $repo */
        $repo = $this->module->getService(ConfigurationRepository::class);
        $idShop = $this->probeShopId();
        $idShopGroup = $this->probeShopGroupId($idShop);

        // orphan group row (no NULL counterpart) — must be promoted, not deleted
        $this->seedRow(self::KEY, 'populated-jwt-value', $idShop, $idShopGroup);

        $repo->fixMultiShopConfig(true);

        $this->assertNull($this->fetchValue(self::KEY, $idShop, $idShopGroup), 'group coordinate should be gone');
        $this->assertSame('populated-jwt-value', $this->fetchValue(self::KEY, $idShop, null), 'value preserved at NULL coordinate');
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
    public function itShouldDeleteShadowedGroupRowWhenNullRowIsNewer()
    {
        if (!\Shop::isFeatureActive()) {
            $this->markTestSkipped('multishop branch requires Shop::isFeatureActive()');
        }

        /** @var ConfigurationRepository $repo */
        $repo = $this->module->getService(ConfigurationRepository::class);
        $idShop = $this->probeShopId();
        $idShopGroup = $this->probeShopGroupId($idShop);
        $nonTokenKey = ConfigurationKeys::PS_ACCOUNTS_OAUTH2_CLIENT_ID;

        // NULL row is newer: its value must be preserved, group row deleted
        $this->seedRow($nonTokenKey, 'stale-client-id', $idShop, $idShopGroup, '2020-01-01 00:00:00');
        $this->seedRow($nonTokenKey, 'current-client-id', $idShop, null, '2021-01-01 00:00:00');

        $repo->fixMultiShopConfig(true);

        $this->assertSame('current-client-id', $this->fetchValue($nonTokenKey, $idShop, null), 'NULL row value preserved');
        $this->assertNull($this->fetchValue($nonTokenKey, $idShop, $idShopGroup), 'shadowed group row deleted');
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldPreserveGroupRowValueWhenGroupRowIsNewer()
    {
        if (!\Shop::isFeatureActive()) {
            $this->markTestSkipped('multishop branch requires Shop::isFeatureActive()');
        }

        /** @var ConfigurationRepository $repo */
        $repo = $this->module->getService(ConfigurationRepository::class);
        $idShop = $this->probeShopId();
        $idShopGroup = $this->probeShopGroupId($idShop);
        $nonTokenKey = ConfigurationKeys::PS_ACCOUNTS_OAUTH2_CLIENT_ID;

        // Group row is newer: its value must win, copied to NULL row before group row is deleted
        $this->seedRow($nonTokenKey, 'stale-client-id', $idShop, null, '2020-01-01 00:00:00');
        $this->seedRow($nonTokenKey, 'current-client-id', $idShop, $idShopGroup, '2021-01-01 00:00:00');

        $repo->fixMultiShopConfig(true);

        $this->assertSame('current-client-id', $this->fetchValue($nonTokenKey, $idShop, null), 'newer group value copied to NULL row');
        $this->assertNull($this->fetchValue($nonTokenKey, $idShop, $idShopGroup), 'group row deleted after value copied');
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldPromoteOrphanGroupRowForNonTokenModuleKeysInMultishop()
    {
        if (!\Shop::isFeatureActive()) {
            $this->markTestSkipped('multishop branch requires Shop::isFeatureActive()');
        }

        /** @var ConfigurationRepository $repo */
        $repo = $this->module->getService(ConfigurationRepository::class);
        $idShop = $this->probeShopId();
        $idShopGroup = $this->probeShopGroupId($idShop);
        $nonTokenKey = ConfigurationKeys::PS_ACCOUNTS_OAUTH2_CLIENT_ID;

        // orphan group row (no NULL counterpart) — must be promoted to NULL, not deleted
        $this->seedRow($nonTokenKey, 'client-id-value', $idShop, $idShopGroup);

        $repo->fixMultiShopConfig(true);

        $this->assertNull($this->fetchValue($nonTokenKey, $idShop, $idShopGroup), 'group coordinate gone after promotion');
        $this->assertSame('client-id-value', $this->fetchValue($nonTokenKey, $idShop, null), 'value preserved at NULL coordinate');
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

        // Shape the multishop helpers would otherwise clean up. The single-shop branch
        // does run the inherited UPDATE from #605 (rewriting matching rows toward NULL/NULL),
        // so we can't assert on (id_shop, id_shop_group) coordinates — we only assert that
        // no row is *deleted*, which is what cleanupShadowedConfigurationRows would do if
        // it ran.
        $countBefore = $this->countRowsForName(self::KEY);
        $this->seedRow(self::KEY, '', $idShop, null);
        $this->seedRow(self::KEY, 'populated', $idShop, 1);
        $this->assertSame($countBefore + 2, $this->countRowsForName(self::KEY), 'sanity: both rows are seeded');

        $repo->fixMultiShopConfig(true);

        $this->assertSame(
            $countBefore + 2,
            $this->countRowsForName(self::KEY),
            'cleanupShadowedConfigurationRows must not run in single-shop mode'
        );
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
     * @param string|null $dateUpd
     *
     * @return void
     */
    private function seedRow($name, $value, $idShop, $idShopGroup, $dateUpd = null)
    {
        $shopExpr = null === $idShop ? 'NULL' : (int) $idShop;
        $groupExpr = null === $idShopGroup ? 'NULL' : (int) $idShopGroup;
        $dateExpr = null === $dateUpd ? 'NOW()' : '"' . pSQL($dateUpd) . '"';
        \Db::getInstance()->execute(
            'INSERT INTO ' . _DB_PREFIX_ . 'configuration' .
            ' (name, value, id_shop, id_shop_group, date_add, date_upd)' .
            ' VALUES ("' . pSQL($name) . '", "' . pSQL($value) . '", ' . $shopExpr . ', ' . $groupExpr . ', NOW(), ' . $dateExpr . ')'
        );
    }

    /**
     * @param string $name
     *
     * @return int
     */
    private function countRowsForName($name)
    {
        return (int) \Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'configuration WHERE name = "' . pSQL($name) . '"'
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
