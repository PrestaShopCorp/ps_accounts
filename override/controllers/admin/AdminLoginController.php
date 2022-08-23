<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
class AdminLoginController extends AdminLoginControllerCore
{
    /** @var string */
    public $override_folder;

    /** @var string */
    public $template = 'content.tpl';

    /** @var string */
    private $psAccountsTemplateDir;

    public function __construct()
    {
        parent::__construct();

        $this->psAccountsTemplateDir = _PS_MODULE_DIR_ .
            DIRECTORY_SEPARATOR . 'ps_accounts' .
            DIRECTORY_SEPARATOR . 'views' .
            DIRECTORY_SEPARATOR . 'templates' .
            DIRECTORY_SEPARATOR . 'override' .
            DIRECTORY_SEPARATOR . 'controllers' .
            DIRECTORY_SEPARATOR . 'login' .
            DIRECTORY_SEPARATOR;

        $this->layout = $this->psAccountsTemplateDir . 'layout.tpl';
    }

    /**
     * Create a template from the override file, else from the base file.
     *
     * @param string $tpl_name filename
     *
     * @return Smarty_Internal_Template
     *
     * @throws SmartyException
     */
    public function createTemplate($tpl_name)
    {
        if ($tpl_name === $this->template) {
            return $this->context->smarty->createTemplate(
                $this->psAccountsTemplateDir . $tpl_name, $this->context->smarty
            );
        }

        return parent::createTemplate($tpl_name);
    }
}
