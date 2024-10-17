<?php

namespace PrestaShop\Module\PsAccounts\Identity\Domain;

interface IdentityManager
{
    /**
     * @return Identity
     */
	public function get();

    /**
     * @param Identity $identity
     *
     * @return void
     */
	public function save(Identity $identity);
}
