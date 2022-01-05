<?php

namespace PrestaShop\Module\PsAccounts\Api\Client;

abstract class AbstractGenericApiClient
{
    /**
     * Class Link in order to generate module link.
     *
     * @var \Link
     */
    protected $link;

    /**
     * Set how long guzzle will wait a response before end it up.
     *
     * @var int
     */
    protected $timeout = 10;

    /**
     * If set to false, you will not be able to catch the error
     * guzzle will show a different error message.
     *
     * @var bool
     */
    protected $catchExceptions = false;

    /**
     * @var AbstractGuzzleClient|null
     */
    protected $client;

    public function __construct()
    {
    }

    /**
     * @param AbstractGuzzleClient|null $client
     *
     * @return void
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return AbstractGuzzleClient|null
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Creater for client
     *
     * @param array $options
     *
     * @return AbstractGuzzleClient
     */
    protected function createClient($options)
    {
        $factory = new GuzzleFactory();

        return $factory->create($options);
    }

    /**
     * Getter for Link.
     *
     * @return \Link
     */
    protected function getLink()
    {
        return $this->link;
    }

    /**
     * Setter for link.
     *
     * @return void
     */
    protected function setLink(\Link $link)
    {
        $this->link = $link;
    }

    /**
     * Getter for timeout.
     *
     * @return int
     */
    protected function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Setter for timeout.
     *
     * @param int $timeout
     *
     * @return void
     */
    protected function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Getter for exceptions mode.
     *
     * @return bool
     */
    protected function getExceptionsMode()
    {
        return $this->catchExceptions;
    }

    /**
     * Setter for exceptions mode.
     *
     * @param bool $bool
     *
     * @return void
     */
    protected function setExceptionsMode($bool)
    {
        $this->catchExceptions = $bool;
    }
}
