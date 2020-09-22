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
     * @return int
     */
    public function getLanguageIdByIsoCode($isoCode)
    {
        return (int) Language::getIdByIso($isoCode);
    }

    /**
     * @return array
     */
    public function getLanguages()
    {
        return Language::getLanguages();
    }
}
