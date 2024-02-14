<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Hook;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PrestaShop\Module\PsAccounts\Entity\EmployeeAccount;

class ActionObjectEmployeeDeleteAfter extends Hook
{
    /**
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function execute(array $params = [])
    {
        if (method_exists($this->module, 'getContainer') &&
            interface_exists('\Doctrine\ORM\EntityManagerInterface')) {
            /** @var \Employee $employee */
            $employee = $params['object'];

            /**
             * @phpstan-ignore-next-line
             *
             * @var EntityManagerInterface $entityManager
             */
            $entityManager = $this->module->getContainer()->get('doctrine.orm.entity_manager');

            $employeeAccountRepository = $entityManager->getRepository(EmployeeAccount::class);

            /**
             * @var EmployeeAccount $employeeAccount
             *
             * @phpstan-ignore-next-line
             */
            $employeeAccount = $employeeAccountRepository->findOneByEmployeeId($employee->id);

            /* @phpstan-ignore-next-line */
            if ($employeeAccount) {
                $entityManager->remove($employeeAccount);
                $entityManager->flush();
            }
        }
    }
}
