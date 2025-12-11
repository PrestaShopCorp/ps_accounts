<?php

namespace PrestaShop\Module\PsAccounts\Settings;

use AdminController;
use Configuration;
use Exception;
use HelperForm;
use PrestaShop\Module\PsAccounts\Account\Command\CleanupIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\Command\RestoreIdentityCommand;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Validator;
use Ps_accounts;
use Throwable;
use Tools;
use Validate;

class SettingsForm
{
    const FORM_ACCESS_PARAM = 'advanced';

    /**
     * Restore Identity form
     */
    const FIELD_CLOUD_SHOP_ID = 'PSX_UUID_V4';
    const FIELD_OAUTH2_CLIENT_ID = 'PS_ACCOUNTS_OAUTH2_CLIENT_ID';
    const FIELD_OAUTH2_CLIENT_SECRET = 'PS_ACCOUNTS_OAUTH2_CLIENT_SECRET';
    const FIELD_FORCE_VERIFY = 'force_verify';
    const FIELD_FORCE_MIGRATE = 'force_migrate';
    const FIELD_MIGRATE_FROM = 'migrate_from';

    /**
     * Cleanup Identity form
     */
    const FIELD_CLEANUP_IDENTITY = 'cleanup_identity';

    /**
     * Settings form
     */
    const FIELD_LOGIN_WITH_PRESTASHOP = 'PS_ACCOUNTS_LOGIN_ENABLED';
    const FIELD_VALIDATION_LEEWAY = 'PS_ACCOUNTS_VALIDATION_LEEWAY';
    const FIELD_REFRESH_LEEWAY = 'refresh_leeway';

    /**
     * @var Ps_accounts
     */
    private $module;

    /**
     * @var string
     */
    private $name;

    /**
     * @var ConfigurationRepository
     */
    private $repository;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @param Ps_accounts $module
     */
    public function __construct(Ps_accounts $module)
    {
        $this->module = $module;
        $this->name = (string) $module->name;
        $this->repository = $this->module->getService(ConfigurationRepository::class);
        $this->commandBus = $this->module->getService(CommandBus::class);
    }

    /**
     * @return string
     */
    public function getSubmitRestoreIdentity()
    {
        return 'submitRestoreIdentity' . $this->name;
    }

    /**
     * @return string
     */
    public function getSubmitCleanupIdentity()
    {
        return 'submitCleanupIdentity' . $this->name;
    }

    /**
     * @return string
     */
    public function getSubmitSettings()
    {
        return 'submitSettings' . $this->name;
    }

    /**
     * @param string $string String to translate
     *
     * @return string Translation
     */
    public function l($string)
    {
        return $this->module->l($string);
    }

    /**
     * @return string|null
     */
    public function render()
    {
        $res = null;

        if (Tools::isSubmit($this->getSubmitRestoreIdentity())) {
            $res = $this->storeRestoreIdentity();
        }

        if (Tools::isSubmit($this->getSubmitCleanupIdentity())) {
            $this->storeCleanupIdentity();
        }

        if (Tools::isSubmit($this->getSubmitSettings())) {
            $res = $this->storeSettings();
        }

        if (Tools::getValue(self::FORM_ACCESS_PARAM)) {
            $res = $this->generateForm();
        }

        /* @phpstan-ignore-next-line */
        return $res;
    }

    /**
     * Builds the PSAccounts Advanced Settings form
     *
     * @param bool $displayWarning
     *
     * @return string HTML code
     */
    protected function generateForm($displayWarning = true)
    {
        $headerMessage = $this->getHeaderMessage();

        if (empty($headerMessage) && $displayWarning) {
            $headerMessage = $this->module->displayError($this->l(
                'Warning! You should only modify those values according to the PrestaShop support.'
            ));
        }

        // Init Fields form array
        $formRestore = [
            //'warning' => $this->l('Warning! You should only modify those values according to the PrestaShop support.'),
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
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'verify_off',
                            'value' => 0,
                            'label' => $this->l('No'),
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
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'migrate_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => 'Migrate from',
                    'name' => self::FIELD_MIGRATE_FROM,
                    'options' => [
                        'query' => [
                            [
                                'id' => '5.6.2',
                                'name' => '5.6.2',
                            ],
                            [
                                'id' => '6.3.2',
                                'name' => '6.3.2',
                            ],
                            [
                                'id' => '7.2.2',
                                'name' => '7.2.3',
                            ],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
            ],
            'buttons' => [
                $this->getBackButton(),
            ],
            'submit' => [
                'title' => $this->l('Restore Identity'),
                'class' => 'btn btn-default pull-right',
                'name' => $this->getSubmitRestoreIdentity(),
            ],
        ];

        $formCleanup = [
            //'warning' => $this->l('Warning! This will remove permanently store identity.'),
            'legend' => [
                'title' => $this->l('Cleanup Identity'),
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
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'cleanup_identity_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
            ],
            'buttons' => [
                $this->getBackButton(),
            ],
            'submit' => [
                'title' => $this->l('Cleanup Identity'),
                'class' => 'btn btn-default pull-right',
                'name' => $this->getSubmitCleanupIdentity(),
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
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id' => 'login_with_prestashop_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Validation leeway'),
                    'name' => self::FIELD_VALIDATION_LEEWAY,
                    'desc' => $this->l('Leeway seconds for token validation'),
                    'size' => 20,
                    'required' => true,
                ],
//                [
//                    'type' => 'text',
//                    'label' => $this->l('Refresh leeway'),
//                    'name' => self::FIELD_REFRESH_LEEWAY,
//                    'disabled' => 'disabled',
//                    'size' => 20,
//                    'required' => true,
//                ],
            ],
            'buttons' => [
                $this->getBackButton(),
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
                'name' => $this->getSubmitSettings(),
            ],
        ];

        $form = [
            'restore' => ['form' => $formRestore],
            'cleanup' => ['form' => $formCleanup],
            'settings' => ['form' => $formSettings],
        ];

        $helper = $this->buildHelperForm();

        // Load current value into the form
        foreach ([
                     self::FIELD_CLOUD_SHOP_ID,
                     self::FIELD_OAUTH2_CLIENT_ID,
                     self::FIELD_OAUTH2_CLIENT_SECRET,
                     self::FIELD_LOGIN_WITH_PRESTASHOP,
                 ] as $cfg_key) {
            $helper->fields_value[$cfg_key] = Tools::getValue($cfg_key, Configuration::get($cfg_key));
        }

        // Load current value into the form
        foreach ([
                     self::FIELD_FORCE_VERIFY,
                     self::FIELD_FORCE_MIGRATE,
                     self::FIELD_CLEANUP_IDENTITY,
                     self::FIELD_MIGRATE_FROM,
                 ] as $cfg_key) {
            $helper->fields_value[$cfg_key] = Tools::getValue($cfg_key, false);
        }

        /** @var Validator $validator */
        $validator = $this->module->getService(Validator::class);
        $helper->fields_value[self::FIELD_VALIDATION_LEEWAY] = Tools::getValue($cfg_key, $validator->getLeeway());

        return $headerMessage . $helper->generateForm($form);
    }

    /**
     * @return string|void
     */
    protected function storeRestoreIdentity()
    {
        $cloudShopId = (string) Tools::getValue(self::FIELD_CLOUD_SHOP_ID);
        $oAuth2ClientId = (string) Tools::getValue(self::FIELD_OAUTH2_CLIENT_ID);
        $oAuth2ClientSecret = (string) Tools::getValue(self::FIELD_OAUTH2_CLIENT_SECRET);
        $forceVerify = (bool) Tools::getValue(self::FIELD_FORCE_VERIFY);
        $forceMigrate = (bool) Tools::getValue(self::FIELD_FORCE_MIGRATE);
        $migrateFrom = (string) Tools::getValue(self::FIELD_MIGRATE_FROM);

        $error = false;
        foreach ([$cloudShopId, $oAuth2ClientId] as $value) {
            if (empty($value) || !Validate::isGenericName($value)) {
                $error = true;
                break;
            }
        }
        foreach ([$oAuth2ClientSecret] as $value) {
            if (!empty($value) && !$this->isPlaintextPassword($value)) {
                $error = true;
                break;
            }
        }

        if ($error) {
            return $this->module->displayError($this->l('The form contains incorrect values')) .
                $this->generateForm(false);
        } else {
            try {
                $this->commandBus->handle(new RestoreIdentityCommand(
                    $cloudShopId,
                    $oAuth2ClientId,
                    $oAuth2ClientSecret,
                    $forceVerify,
                    $forceMigrate,
                    $migrateFrom
                ));
            } catch (Exception $e) {
                return $this->module->displayError($this->l('An error occurred while restoring identity: ' . $e->getMessage())) .
                    $this->generateForm(false);
            } catch (Throwable $e) {
                return $this->module->displayError($this->l('An error occurred while restoring identity: ' . $e->getMessage())) .
                    $this->generateForm(false);
            }

            $this->module->redirectSettingsPage([
                self::FORM_ACCESS_PARAM => 1,
                'confirmation' => $this->l('Identity restored'),
            ]);
        }
    }

    /**
     * @return string|void
     */
    protected function storeCleanupIdentity()
    {
        $cleanup_identity = (bool) Tools::getValue(self::FIELD_CLEANUP_IDENTITY);

        if ($cleanup_identity) {
            $this->commandBus->handle(new CleanupIdentityCommand());

            $this->module->redirectSettingsPage([
                self::FORM_ACCESS_PARAM => 1,
                'confirmation' => $this->l('Identity cleared'),
            ]);
        }
        $this->module->redirectSettingsPage([
            self::FORM_ACCESS_PARAM => 1,
            'information' => $this->l('Nothing to do'),
        ]);
    }

    /**
     * @return string|void
     */
    protected function storeSettings()
    {
        $loginWithPrestaShop = (bool) Tools::getValue(self::FIELD_LOGIN_WITH_PRESTASHOP);
        $validationLeeway = (int) Tools::getValue(self::FIELD_VALIDATION_LEEWAY);
        $refreshLeeway = Tools::getValue(self::FIELD_REFRESH_LEEWAY);

        $error = false;
        foreach ([$validationLeeway/*, $refresh_leeway*/] as $value) {
            if (!Validate::isInt($value)) {
                $error = true;
                break;
            }
        }

        if ($error) {
            return $this->module->displayError($this->l('The form contains incorrect values')) .
                $this->generateForm(false);
        } else {
            $this->repository->updateLoginEnabled($loginWithPrestaShop);
            $this->repository->updateValidationLeeway($validationLeeway);

            $this->module->redirectSettingsPage([
                self::FORM_ACCESS_PARAM => 1,
                'confirmation' => $this->l('Settings updated'),
            ]);
        }
    }

    /**
     * @return HelperForm
     */
    protected function buildHelperForm()
    {
        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->table = 'configuration'; //$this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        if (version_compare(_PS_VERSION_, '1.7', '>')) {
            $helper->currentIndex = $this->module->getContext()
                ->link->getAdminLink('AdminModules', false, [], ['configure' => $this->name]);
        } else {
            $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        }

        // Default language
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        return $helper;
    }

    /**
     * @return string
     */
    protected function getHeaderMessage()
    {
        $headerMessage = '';
        foreach (['information', 'confirmation', 'warning', 'error'] as $messageType) {
            if ($message = Tools::getValue($messageType)) {
                $methodName = 'display' . ucfirst($messageType);
                if (method_exists($this->module, $methodName)) {
                    $headerMessage .= $this->module->$methodName($message);
                }
            }
        }

        return $headerMessage;
    }

    /**
     * @return array
     */
    protected function getBackButton()
    {
        return [
            'href' => $this->module->getSettingsPageUrl(),          // If this is set, the button will be an <a> tag
//                    'js'   => 'someFunction()', // Javascript to execute on click
//                    'class' => '',              // CSS class to add
            'type' => 'button',         // Button type
//                    'id'   => 'mybutton',
//                    'name' => 'mybutton',       // If not defined, this will take the value of "submitOptions{$table}"
//                    'icon' => 'icon-foo',       // Icon to show, if any
            'title' => $this->l('Back'),      // Button label
        ];
    }

    /**
     * @param string $password
     *
     * @return bool
     */
    protected function isPlaintextPassword($password)
    {
        /* @phpstan-ignore-next-line */
        if (method_exists(Validate::class, 'isPlaintextPassword')) {
            /* @phpstan-ignore-next-line */
            return Validate::isPlaintextPassword($password);
        }
        /* @phpstan-ignore-next-line */
        if (method_exists(Validate::class, 'isPasswd')) {
            /* @phpstan-ignore-next-line */
            return Validate::isPasswd($password);
        }
        /* @phpstan-ignore-next-line */
        if (method_exists(Validate::class, 'isAcceptablePasswordScore')) {
            /* @phpstan-ignore-next-line */
            return Validate::isAcceptablePasswordScore($password);
        }

        return false;
    }
}
