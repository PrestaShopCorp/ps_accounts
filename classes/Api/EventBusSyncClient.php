<?php

namespace PrestaShop\Module\PsAccounts\Api;

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use PrestaShop\AccountsAuth\Api\Client\GenericClient;

$accountsDir = _PS_MODULE_DIR_ . 'ps_accounts/';
if (file_exists($accountsDir . '.env')) {
    $dotenv = Dotenv::createImmutable($accountsDir);
    $dotenv->load();
}

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
