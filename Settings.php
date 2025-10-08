<?php

class Settings
{
    /**
     * Restore Identity form
     */
    const FIELD_CLOUD_SHOP_ID = 'cloud_shop_id';
    const FIELD_OAUTH2_CLIENT_ID = 'oauth2_client_id';
    const FIELD_OAUTH2_CLIENT_SECRET = 'oauth2_client_secret';
    const FIELD_FORCE_VERIFY = 'force_verify';
    const FIELD_FORCE_MIGRATE = 'force_migrate';

    /**
     * Cleanup Identity form
     */
    const FIELD_CLEANUP_IDENTITY = 'cleanup_identity';

    /**
     * Settings form
     */
    const FIELD_LOGIN_WITH_PRESTASHOP = 'login_with_prestashop';
    const FIELD_VALIDATION_LEEWAY = 'validation_leeway';
    const FIELD_REFRESH_LEEWAY = 'refresh_leeway';

    /**
     * @var Ps_accounts
     */
    private $module;

    /**
     * @param Ps_accounts $module
     */
    public function __construct(Ps_accounts $module)
    {
        $this->module = $module;
    }

    /**
     * @return string|null
     */
    public function displayAdvancedSettings()
    {
        if (Tools::isSubmit('submitRestoreIdentity' . $this->name)) {
            return $this->storeRestoreIdentityForm();
        }

        if (Tools::isSubmit('submitCleanupIdentity' . $this->name)) {
            return $this->storeCleanupIdentityForm();
        }

        if (Tools::isSubmit('submitSettings' . $this->name)) {
            return $this->storeSettingsForm();
        }

        if (Tools::getValue('advanced')) {
            return $this->renderAdvancedSettingsForm();
        }

        return null;
    }

    /**
     * Builds the PSAccounts Advanced Settings form
     *
     * @return string HTML code
     */
    public function renderAdvancedSettingsForm()
    {
        $warning = ''; //$this->displayError($this->l('Warning! You should only modify those values according to the PrestaShop support.'));

        // Init Fields form array
        $formRestore = [
            'warning' => $this->l('Warning! You should only modify those values according to the PrestaShop support.'),
            'legend' => [
                'title' => $this->l('Recover Identity'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Cloud Shop Id'),
                    'name' => self::FIELD_CLOUD_SHOP_ID,
                    'size' => 20,
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Client Id'),
                    'name' => self::FIELD_OAUTH2_CLIENT_ID,
                    'size' => 20,
                    'required' => true,
                ],
                [
                    'type' => 'password',
                    'label' => $this->l('Client Secret'),
                    'name' => self::FIELD_OAUTH2_CLIENT_SECRET,
                    'size' => 20,
                    'required' => true,
                ],
                [
                    'type' => 'switch',
                    'label' => 'Force verification',
                    'desc' => 'Do you intend to verify shop with modified FrontendUrl OR BackOfficeUrl?',
                    'name' => self::FIELD_FORCE_VERIFY,
                    'required' => true,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'verify_on',
                            'value' => 1,
                            'label' => $this->module->l('Yes'),
                        ],
                        [
                            'id' => 'verify_off',
                            'value' => 0,
                            'label' => $this->module->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => 'Force migration',
                    'desc' => 'Do you intend to migrate shop?',
                    'name' => self::FIELD_FORCE_MIGRATE,
                    'required' => true,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'migrate_on',
                            'value' => 1,
                            'label' => $this->module->l('Yes'),
                        ],
                        [
                            'id' => 'migrate_off',
                            'value' => 0,
                            'label' => $this->module->l('No'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Restore Identity'),
                'class' => 'btn btn-default pull-right',
                'name' => 'submitRestoreIdentity' . $this->module->name,
            ],
        ];

        $formCleanup = [
            'warning' => $this->module->l('Warning! This will remove permanently store identity.'),
            'legend' => [
                'title' => $this->module->l('Cleanup Identity'),
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => 'Cleanup identity',
                    'desc' => 'Do you confirm clearing Identity information?',
                    'name' => self::FIELD_CLEANUP_IDENTITY,
                    'required' => true,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'cleanup_identity_on',
                            'value' => 1,
                            'label' => $this->module->l('Yes'),
                        ],
                        [
                            'id' => 'cleanup_identity_off',
                            'value' => 0,
                            'label' => $this->module->l('No'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Cleanup Identity'),
                'class' => 'btn btn-default pull-right',
                'name' => 'submitCleanupIdentity' . $this->module->name,
            ],
        ];

        $formSettings = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => 'Login with Prestashop',
                    'desc' => 'Do you want to activate Backoffice login with PrestaShop SSO?',
                    'name' => self::FIELD_LOGIN_WITH_PRESTASHOP,
                    'required' => true,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'login_with_prestashop_on',
                            'value' => 1,
                            'label' => $this->module->l('Enabled'),
                        ],
                        [
                            'id' => 'login_with_prestashop_off',
                            'value' => 0,
                            'label' => $this->module->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Validation leeway'),
                    'name' => self::FIELD_VALIDATION_LEEWAY,
                    'desc' => $this->module->l('Leeway seconds for token validation'),
                    'size' => 20,
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Refresh leeway'),
                    'name' => self::FIELD_REFRESH_LEEWAY,
                    'disabled' => 'disabled',
                    'size' => 20,
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Save'),
                'class' => 'btn btn-default pull-right',
                'name' => 'submitSettings' . $this->module->name,
            ],
        ];

        $form = [
            'restore' => ['form' => $formRestore],
            'cleanup' => ['form' => $formCleanup],
            'settings' => ['form' => $formSettings],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        if (version_compare(_PS_VERSION_, '1.7', '>')) {
            $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false, [], ['configure' => $this->name]);
        } else {
            $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        }

        // Default language
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        // Load current value into the form
        foreach ([
                     $cloudShopId,
                     $oAuth2ClientId,
                     $oAuth2ClientSecret,
                     $loginEnabled
                 ] as $cfg_key) {
            $helper->fields_value[$cfg_key] = Tools::getValue($cfg_key, Configuration::get($cfg_key));
        }

        $helper->fields_value['validation_leeway'] = $this->getParameter('ps_accounts.token_validator_leeway');

        return $warning . $helper->generateForm($form);
    }

    /**
     * @return string|void
     */
    public function storeRestoreIdentityForm()
    {
        $PSX_UUID_V4 = (string) Tools::getValue(
            \PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys::PSX_UUID_V4
        );
        $PS_ACCOUNTS_OAUTH2_CLIENT_ID = (string) Tools::getValue(
            \PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys::PS_ACCOUNTS_OAUTH2_CLIENT_ID
        );
        $PS_ACCOUNTS_OAUTH2_CLIENT_SECRET = (string) Tools::getValue(
            \PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys::PS_ACCOUNTS_OAUTH2_CLIENT_SECRET
        );
        $verify = (bool) Tools::getValue('verify');
        $migrate = (bool) Tools::getValue('migrate');

        $error = false;
        foreach ([$PSX_UUID_V4, $PS_ACCOUNTS_OAUTH2_CLIENT_ID] as $value) {
            if (empty($value) || !Validate::isGenericName($value)) {
                $error = true;
                break;
            }
        }
        foreach ([$PS_ACCOUNTS_OAUTH2_CLIENT_SECRET] as $value) {
            if (!empty($value) && !Validate::isPlaintextPassword($value)) {
                $error = true;
                break;
            }
        }

        if ($error) {
            return $this->displayError($this->l('The form contains incorrect values')) .
                $this->renderAdvancedSettingsForm();
        } else {
            /** @var \PrestaShop\Module\PsAccounts\Cqrs\CommandBus $commandBus */
            $commandBus = $this->getService(\PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class);
            $commandBus->handle(new \PrestaShop\Module\PsAccounts\Account\Command\RestoreIdentityCommand(
                $PSX_UUID_V4,
                $PS_ACCOUNTS_OAUTH2_CLIENT_ID,
                $PS_ACCOUNTS_OAUTH2_CLIENT_SECRET,
                $verify,
                $migrate,
                \PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService::ORIGIN_ADVANCED_SETTINGS,
                (string) $this->name
            ));
            // $output = $this->displayConfirmation($this->l('Identity recovered successfully'));

            $this->redirectSettingsPage();
        }
    }

    /**
     * @return string|void
     */
    public function storeCleanupIdentityForm()
    {
        $cleanup_identity = (bool) Tools::getValue(
            'cleanup_identity'
        );

        if ($cleanup_identity) {
            /** @var \PrestaShop\Module\PsAccounts\Cqrs\CommandBus $commandBus */
            $commandBus = $this->getService(\PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class);
            $commandBus->handle(new \PrestaShop\Module\PsAccounts\Account\Command\CleanupIdentityCommand());
            // $output = $this->displayConfirmation($this->l('Identity recovered successfully'));

            $this->redirectSettingsPage();
        }
    }

    /**
     * @return string|void
     */
    public function storeSettingsForm()
    {
        $PSX_ACCOUNTS_LOGIN_ENABLED = (bool) Tools::getValue(
            \PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys::PS_ACCOUNTS_LOGIN_ENABLED
        );
        $validation_leeway = Tools::getValue(
            'validation_leeway'
        );
        $refresh_leeway = Tools::getValue(
            'refresh_leeway'
        );

        $error = false;
        foreach ([$validation_leeway/*, $refresh_leeway*/] as $value) {
            if (empty($value) || !Validate::isInt($value)) {
                $error = true;
                break;
            }
        }

        if ($error) {
            return $this->displayError($this->l('The form contains incorrect values')) .
                $this->renderAdvancedSettingsForm();
        } else {
            /** @var \PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository $config */
            $config = $this->getService(\PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository::class);
            $config->updateLoginEnabled($PSX_ACCOUNTS_LOGIN_ENABLED);
            // TODO: update validation_leeway
            // TODO: update refresh_leeway
            // $output = $this->displayConfirmation($this->l('Identity recovered successfully'));

            $this->redirectSettingsPage();
        }
    }
}
