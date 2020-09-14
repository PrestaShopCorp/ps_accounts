<?php

namespace PrestaShop\Module\PsAccounts\Api\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use PrestaShop\AccountsAuth\Api\Client\GenericClient;

class EventBusSyncClient extends GenericClient
{
    public function __construct(\Link $link, Client $client = null)
    {
        parent::__construct();

        $this->setLink($link);

        if (null === $client) {
            $client = new Client([
                'base_url' => $_ENV['EVENT_BUS_SYNC_API_URL'],
                'defaults' => [
                    'timeout' => $this->timeout,
                    'exceptions' => $this->catchExceptions,
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            ]);
        }

        $this->setClient($client);
    }

    /**
     * @param string $jobId
     *
     * @return bool
     */
    public function validateJobId($jobId)
    {
        $this->setRoute($_ENV['EVENT_BUS_SYNC_API_URL'] . "/job/$jobId");

        try {
            $response = $this->get();

            return $response['httpCode'] == 201;
        } catch (ConnectException $exception) {
            return false;
        }
    }
}
