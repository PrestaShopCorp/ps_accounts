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

namespace PrestaShop\Module\PsAccounts\Service;

use Context;
use PrestaShop\Module\PsAccounts\Api\Client\ServicesBillingClient;
use PrestaShop\Module\PsAccounts\Exception\BillingException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;

/**
 * Construct the psbilling service.
 */
class PsBillingService
{
//    /**
//     * @var \Symfony\Component\DependencyInjection\ContainerInterface
//     */
//    protected $container;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var ShopTokenRepository
     */
    private $shopTokenRepository;

    /**
     * @var ServicesBillingClient
     */
    private $servicesBillingClient;

    /**
     * PsBillingService constructor.
     *
     * @param ServicesBillingClient $servicesBillingClient
     * @param ShopTokenRepository $shopTokenRepository
     * @param ConfigurationRepository $configuration
     */
    public function __construct(
        ServicesBillingClient $servicesBillingClient,
        ShopTokenRepository $shopTokenRepository,
        ConfigurationRepository $configuration
    ) {
        $this->servicesBillingClient = $servicesBillingClient;
        $this->shopTokenRepository = $shopTokenRepository;
        $this->configuration = $configuration;
    }

//    /**
//     * Override of native function to always retrieve Symfony container instead of legacy admin container on legacy context.
//     *
//     * @param string $serviceName
//     *
//     * @return mixed
//     */
//    public function get($serviceName)
//    {
//        if (null === $this->container) {
//            $this->container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
//        }
//
//        return $this->container->get($serviceName);
//    }

    /**
     * Create a Billing customer if needed, and subscribe to $planName.
     * The $module and $planName must exist in PrestaShop Billing.
     * The $ip parameter will help PS Billing to preselect a tax rate and a currency
     * from the geolocalized IP. This IP should be the browser IP displaying the backoffice.
     *
     * @param string $module the name of the module
     * @param string $planName The label of the existing plan for this module
     * @param mixed $shopId an optional shop ID in multishop context. If left false, the current shop will be selected.
     * @param mixed $customerIp an optional element to help Billing choosing the currency and tax rate (depending on the IP's country) for later paying plan
     *
     * @return mixed An array with subscription identifiers if succeed
     *
     * @throws \Exception in case of error
     */
    public function subscribeToFreePlan($module, $planName, $shopId = false, $customerIp = null)
    {
        if ($shopId !== false) {
            $this->configuration->setShopId($shopId);
        }

        $uuid = $this->configuration->getShopUuid();
        $toReturn = ['shopAccountId' => $uuid];

        if ($uuid && strlen($uuid) > 0) {
            $billingClient = $this->servicesBillingClient;

            $response = $billingClient->getBillingCustomer($uuid);

            if (!$response || !array_key_exists('httpCode', $response)) {
                throw new BillingException('Billing customer request failed.', 50);
            }
            if ($response['httpCode'] === 404) {
                $response = $billingClient->createBillingCustomer(
                    $uuid,
                    $customerIp ? ['created_from_ip' => $customerIp] : []
                );
                if (!$response || !array_key_exists('httpCode', $response) || $response['httpCode'] !== 201) {
                    throw new BillingException('Billing customer creation failed.', 60);
                }
            }
            $toReturn['customerId'] = $response['body']['customer']['id'];

            $response = $billingClient->getBillingSubscriptions($uuid, $module);
            if (!$response || !array_key_exists('httpCode', $response) || $response['httpCode'] >= 500) {
                throw new BillingException('Billing subscriptions request failed.', 51);
            }

            if ($response['httpCode'] === 404) {
                $response = $billingClient->createBillingSubscriptions($uuid, $module, ['plan_id' => $planName, 'module' => $module]);
                if (!$response || !array_key_exists('httpCode', $response) || $response['httpCode'] >= 400) {
                    if ($response && array_key_exists('body', $response)
                        && array_key_exists('message', $response['body'])
                        && array_key_exists(0, $response['body']['message'])
                    ) {
                        throw new BillingException($response['body']['message'][0]);
                    }
                    throw new BillingException('Billing subscription creation failed.', 65);
                }

                $toReturn['subscriptionId'] = $response['body']['subscription']['id'];

                return $toReturn;
            } else {
                // There is existing subscription. Testing if planName matches the right one.
                if (array_key_exists('body', $response) && $response['body']
                    && array_key_exists('subscription', $response['body'])
                    && array_key_exists('plan_id', $response['body']['subscription'])
                    && $response['body']['subscription']['plan_id'] === $planName
                ) {
                    $toReturn['subscriptionId'] = $response['body']['subscription']['id'];
                    $this->shopTokenRepository->getOrRefreshToken();

                    return $toReturn;
                } else {
                    throw new BillingException('Subscription plan name mismatch.', 20);
                }
            }
        }

        throw new \Exception('Shop account unknown.', 10);
    }
}
