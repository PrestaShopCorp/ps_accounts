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

    public function __construct(Context $context, Db $db)
    {
        $this->context = $context;
        $this->db = $db;
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

            return array_map(function ($key, $theme) use ($currentTheme) {
                return [
                    'id' => md5((string) $key),
                    'collection' => 'themes',
                    'properties' => [
                        'name' => $theme->getName(),
                        'theme_version' => $theme->get('version'),
                        'active' => $theme->getName() == $currentTheme->getName(),
                    ],
                ];
            }, array_keys($themes), $themes);
        } else {
            $themes = Theme::getAvailable(false);

            return array_map(function ($theme) {
                $themeObj = Theme::getByDirectory($theme);

                $themeData = [
                    'id' => md5($theme),
                    'collection' => 'themes',
                    'properties' => [],
                ];

                if ($themeObj instanceof Theme) {
                    $themeInfo = Theme::getThemeInfo($themeObj->id);

                    $themeData['properties'] = [
                        'name' => $themeInfo['theme_name'],
                        'theme_version' => $themeInfo['theme_version'],
                        'active' => (string) $this->context->theme->id == (string) $themeInfo['theme_id'],
                    ];
                } else {
                    $themeData['properties'] = [
                        'name' => $theme,
                        'theme_version' => '',
                        'active' => false,
                    ];
                }

                return $themeData;
            }, $themes);
        }
    }
}
