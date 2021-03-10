<?php

namespace PrestaShop\Module\PsAccounts\Controller;

interface RestControllerInterface
{
    /**
     * @param array $payload
     *
     * @return array
     *
     * @throws \Exception
     */
    public function index(array $payload);

    /**
     * @param array $payload
     *
     * @return array
     *
     * @throws \Exception
     */
    public function store(array $payload);

    /**
     * @param mixed $id
     * @param array $payload
     *
     * @return array
     *
     * @throws \Exception
     */
    public function show($id, array $payload);

    /**
     * @param mixed $id
     * @param array $payload
     *
     * @return array
     *
     * @throws \Exception
     */
    public function update($id, array $payload);

    /**
     * @param mixed $id
     * @param array $payload
     *
     * @return array
     *
     * @throws \Exception
     */
    public function delete($id, array $payload);
}
