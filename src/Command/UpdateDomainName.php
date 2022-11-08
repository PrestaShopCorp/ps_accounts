<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace PrestaShop\Module\PsAccounts\Command;

use PrestaShop\PrestaShop\Adapter\Meta\ShopUrlDataConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDomainName extends Command
{
    /** @var ShopUrlDataConfiguration ShopUrlDataConfiguration service */
    private $shopUrlDataConfiguration;

    /** @var string New domain to set */
    private $newDomain;

    /** @var string New domain ssl to set */
    private $newDomainSsl;

    /** @var string New physical uri to set */
    private $physicalUri;

    /**
     * Command return values
     */
    public const SUCCESS = 0;
    public const FAILURE = 1;

    public function __construct(ShopUrlDataConfiguration $shopUrlDataConfiguration)
    {
        parent::__construct();
        $this->shopUrlDataConfiguration = $shopUrlDataConfiguration;
    }

    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure()
    {
        // The name of the command (the part after "bin/console")
        $this->setName('smbedition:update-domain-name')
            ->setDescription('Update prestashop domain name')
            ->addOption(
                'domain',
                'd',
                InputOption::VALUE_REQUIRED,
                'New domain',
                null
            )
            ->addOption(
                'domain-ssl',
                's',
                InputOption::VALUE_REQUIRED,
                'New domain ssl',
                null
            )
            ->addOption(
                'uri',
                'u',
                InputOption::VALUE_REQUIRED,
                'Physical uri',
                null
            );
    }

    /**
     * Validate input options
     *
     * @param InputInterface $input Command line input
     * @param OutputInterface $output Command line output
     * @return boolean
     */
    private function validateInputOptions(InputInterface $input, OutputInterface $output): bool
    {
        // Verify and fill new domain
        $domain = $input->getOption('domain');
        if (!$domain) {
            $output->writeln('<error>Domain not provided. Specify it with the -d or the --domain option</error>');

            return false;
        }
        $this->newDomain = $domain;

        // Verify and fill new domain ssl
        $domainSsl = $input->getOption('domain-ssl');
        if (!$domainSsl) {
            $output->writeln('<error>Domain SSL not provided. Specify it with the -s or the --domain-ssl option</error>');

            return false;
        }
        $this->newDomainSsl = $domainSsl;

        // Verify and fill physical uri
        $physicalUri = $input->getOption('uri');
        if (!$physicalUri) {
            $output->writeln('<error>Physical URI not provided. Specify it with the -u or the --uri option</error>');

            return false;
        }
        $this->physicalUri = $physicalUri;

        return true;
    }

    /**
     * Get the name of the admin directory.
     *
     * @return string
     */
    private function getAdminDir(): string
    {
        $directories = glob('admin*', GLOB_ONLYDIR);
        return is_array($directories) ? $directories[0] : "";
    }

    /**
     * Execute the smbedition:update-domain-name command.
     *
     * @param InputInterface $input Command input
     * @param OutputInterface $output Command output
     * @return integer 0 on success, 1 on failure
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Validate input options on command line
        if (!$this->validateInputOptions($input, $output)) {
            return self::FAILURE;
        }

        $configuration = [
            "domain" => $this->newDomain,
            "domain_ssl" => $this->newDomainSsl,
            "physical_uri" => $this->physicalUri
        ];

        // /!\ In command line context, _PS_ADMIN_DIR_ is not defined
        if (!defined('_PS_ADMIN_DIR_')) {
            define('_PS_ADMIN_DIR_', $this->getAdminDir());
        }

        $res = $this->shopUrlDataConfiguration->updateConfiguration($configuration);

        return empty($res) ? self::SUCCESS : self::FAILURE;
    }
}
