<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Configuration;
use Language;

class LanguageRepository
{
    /**
     * @return array
     */
    public function getLanguagesIsoCodes()
    {
        $languages = Language::getLanguages();

        return array_map(function ($language) {
            return $language['iso_code'];
        }, $languages);
    }

    /**
     * @return string
     */
    public function getDefaultLanguageIsoCode()
    {
        return Language::getLanguage(Configuration::get('PS_LANG_DEFAULT'))['iso_code'];
    }

    /**
     * @param string $isoCode
     *
     * @return false|string|null
     */
    public function getLanguageIdByIsoCode($isoCode)
    {
        return Language::getIdByIso($isoCode);
    }
}
