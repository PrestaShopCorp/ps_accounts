<?php

namespace PrestaShop\Module\PsAccounts\Api;

use GuzzleHttp\Client;
use PrestaShop\AccountsAuth\Api\Client\GenericClient;
use PrestaShop\AccountsAuth\Service\PsAccountsService;

class EventBusSyncClient extends GenericClient
{
    public function __construct(\Link $link, Client $client = null)
    {
        parent::__construct();

        $this->setLink($link);
        $psAccountsService = new PsAccountsService();
        $token = $psAccountsService->getOrRefreshToken();

        if (null === $client) {
            $client = new Client([
                'base_url' => $_ENV['EVENT_BUS_SYNC_API_URL'],
                'defaults' => [
                    'timeout' => $this->timeout,
                    'exceptions' => $this->catchExceptions,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => "Bearer $token",
                    ],
                ],
            ]);
        }

        $this->setClient($client);
    }

    /**
     * @param string $jobId
     *
     * @return array|bool
     */
    public function validateJobId($jobId)
    {
        $this->setRoute($_ENV['EVENT_BUS_SYNC_API_URL'] . "/job/$jobId");

        return $this->get();
    }
}
