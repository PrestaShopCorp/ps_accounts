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

namespace PrestaShop\Module\PsAccounts\Repository;

use PrestaShop\Module\PsAccounts\Entity\EmployeeAccount;

class EmployeeAccountRepository
{
    /**
     * @var mixed
     */
    private $entityManager;

    /**
     * @var mixed
     */
    private $repository;

    public function __construct()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');
        /* @phpstan-ignore-next-line */
        if (method_exists($module, 'getContainer') &&
            interface_exists('\Doctrine\ORM\EntityManagerInterface')) {
            /* @phpstan-ignore-next-line */
            $this->entityManager = $module->getContainer()->get('doctrine.orm.entity_manager');
            /* @phpstan-ignore-next-line */
            $this->repository = $this->entityManager->getRepository(EmployeeAccount::class);
        }
    }

    /**
     * @deprecated
     *
     * @return bool
     */
    public function isCompatPs16()
    {
        return isset($this->repository);
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function assertCompatible()
    {
        if (!isset($this->repository)) {
            throw new \Exception('Employee accounts repository has not been set');
        }
    }

    /**
     * @param int $employeeId
     *
     * @return EmployeeAccount|null
     */
    public function findByEmployeeId($employeeId)
    {
        $this->assertCompatible();
        //return $this->repository->findOneByEmployeeId($employeeId);
        return $this->repository->findOneBy(['employeeId' => $employeeId]);
    }

    /**
     * @param string $uuid
     *
     * @return EmployeeAccount|null
     *
     * @throws \Exception
     */
    public function findByUid($uuid)
    {
        $this->assertCompatible();

        return $this->repository->findOneBy(['uid' => $uuid]);
    }

    /**
     * @param EmployeeAccount $employeeAccount
     *
     * @return void
     *
     * @throws \Exception
     */
    public function delete(EmployeeAccount $employeeAccount)
    {
        $this->assertCompatible();
        $this->entityManager->remove($employeeAccount);
        $this->entityManager->flush();
    }

    /**
     * @param EmployeeAccount $employeeAccount
     *
     * @return void
     *
     * @throws \Exception
     */
    public function upsert(EmployeeAccount $employeeAccount)
    {
        $this->assertCompatible();
        $this->entityManager->persist($employeeAccount);
        $this->entityManager->flush();
    }
}
