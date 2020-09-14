<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use Db;
use PrestaShop\PrestaShop\Core\Addon\Theme\ThemeManagerBuilder;
use Theme;

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
    public function getThemes()
    {
        if (version_compare(_PS_VERSION_, '1.7', '>')) {
            $themeRepository = (new ThemeManagerBuilder($this->context, $this->db))
                ->buildRepository($this->context->shop);

            $currentTheme = $this->context->shop->theme;
            $themes = $themeRepository->getList();
            $key = 0;

            return array_map(function ($theme) use ($currentTheme, &$key) {
                ++$key;

                return [
                    'id' => (string) $key,
                    'collection' => 'themes',
                    'properties' => [
                        'name' => $theme->getName(),
                        'version' => $theme->get('version'),
                        'active' => (int) $theme->getName() == $currentTheme->getName(),
                    ],
                ];
            }, $themes);
        } else {
            $themes = Theme::getAvailable();

            return array_map(function ($theme) use (&$key) {
                ++$key;
                $themeObj = Theme::getByDirectory($theme);

                $themeData = [
                    'id' => (string) $key,
                    'collection' => 'themes',
                    'properties' => [],
                ];

                if ($themeObj instanceof Theme) {
                    $themeInfo = Theme::getThemeInfo($themeObj->id);

                    $themeData['properties'] = [
                        'name' => $themeInfo['theme_name'],
                        'version' => $themeInfo['theme_version'],
                        'active' => (int) $this->context->theme->id == $themeInfo['theme_id'],
                    ];
                } else {
                    $themeData['properties'] = [
                        'name' => $theme,
                        'version' => '',
                        'active' => 0,
                    ];
                }

                return $themeData;
            }, $themes);
        }
    }
}
