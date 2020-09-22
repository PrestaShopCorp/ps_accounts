<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use DbQuery;
use Language;

class CountryRepository
{
    /**
     * @var Db
     */
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param $isoCode
     *
     * @return int
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function getCountryIdByLanguageIsoCode($isoCode)
    {
        $lang = new Language(Language::getIdByIso($isoCode));

        $query = new DbQuery();
        $query->select('c.id_country')
            ->from('country', 'c')
            ->where('c.iso_code = "' . substr($lang->locale, 3, 2) . '"');

        return (int) $this->db->getValue($query);
    }
}
