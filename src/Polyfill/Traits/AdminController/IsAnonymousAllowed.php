<?php

namespace PrestaShop\Module\PsAccounts\Polyfill\Traits\AdminController;

if (defined('_PS_VERSION_')
    && version_compare(_PS_VERSION_, '9.0', '>=')) {
    trait IsAnonymousAllowed
    {
        /**
         * @return bool
         */
        public function isAnonymousAllowed()
        {
            return true;
        }
    }
} else {
    trait IsAnonymousAllowed
    {
        /**
         * @return bool
         */
        protected function isAnonymousAllowed()
        {
            return true;
        }
    }
}
