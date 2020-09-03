<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use Db;
use PrestaShop\PrestaShop\Core\Addon\Theme\ThemeManagerBuilder;

class ThemeRepository
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var Db
     */
    private $db;

    public function __construct()
    {
        $this->context = Context::getContext();
        $this->db = Db::getInstance();
    }

    /**
     * @return array|mixed|null
     */
    public function getThemes() {
        if (version_compare(_PS_VERSION_, '1.6', '>')) {
            $themeRepository = (new ThemeManagerBuilder($this->context, $this->db))
                ->buildRepository($this->context->shop);

            $currentTheme = $this->context->shop->theme;
            $themes = $themeRepository->getList();
            $key = 0;

            return array_map(function ($theme) use ($currentTheme, &$key) {
                $key += 1;
                return [
                    'id' => (string) $key,
                    'collection' => 'themes',
                    'properties' => [
                        'name' => $theme->getName(),
                        'version' => $theme->get('version'),
                        'active' => $theme->getName() == $currentTheme->getName()
                    ],
                ];
            }, $themes);
        } else {
            return [];
        }
    }

}
