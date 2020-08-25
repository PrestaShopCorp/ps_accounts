<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Configuration;
use Language;

class LanguageRepository
{
    public function getLanguagesIsoCodes()
    {
        $languages = Language::getLanguages();

        return array_map(function ($language) {
            return $language['iso_code'];
        }, $languages);
    }

    public function getDefaultLanguageIsoCode()
    {
        return Language::getLanguage(Configuration::get('PS_LANG_DEFAULT'))['iso_code'];
    }
}
