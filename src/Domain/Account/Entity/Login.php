<?php

namespace PrestaShop\Module\PsAccounts\Domain\Account\Entity;

use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\Module\PsAccounts\Entity\EmployeeAccount;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\PrestaShop\Adapter\Entity\Module;
use PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException;

class Login
{
    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    /**
     * @var Oauth2Client
     */
    private $oauth2Client;

    /**
     * @param ConfigurationRepository $configurationRepository
     * @param Oauth2Client $oauth2Client
     */
    public function __construct(
        ConfigurationRepository $configurationRepository,
        Oauth2Client $oauth2Client
    ) {
        $this->configurationRepository = $configurationRepository;
        $this->oauth2Client = $oauth2Client;
    }

    public function isEnabled(): bool
    {
        return $this->configurationRepository->getLoginEnabled() &&
            $this->oauth2Client->getClientId() &&
            $this->oauth2Client->getClientSecret();
    }

    public function enable(): void
    {
        $this->configurationRepository->updateLoginEnabled(true);
    }

    public function disable(): void
    {
        $this->configurationRepository->updateLoginEnabled(false);
    }

    /**
     * @throws ContainerNotFoundException
     */
    public function getLoggedInEmployeeAccount(): ?EmployeeAccount
    {
        /** @var \Ps_accounts $module */
        $module = Module::getInstanceByName('ps_accounts');

        $employeeId = $module->getContext()->employee->id;

        if (!empty($employeeId)) {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $module->getContainer()->get('doctrine.orm.entity_manager');

            $employeeAccountRepository = $entityManager->getRepository(EmployeeAccount::class);

            /**
             * @var EmployeeAccount $employeeAccount
             * @phpstan-ignore-next-line
             */
            $employeeAccount = $employeeAccountRepository->findOneBy(['employeeId' => $employeeId]);
            // $employeeAccount = $employeeAccountRepository->findOneByUid($uid);
            return $employeeAccount;
        }

        return null;
    }
}
