<?php
namespace PrestaShop\Module\PsAccounts\Tests\Unit\Http\Client\CircuitBreaker;

use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\CircuitBreakerException;
use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\Factory;
use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\CircuitBreaker;
use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\State;
use PrestaShop\Module\PsAccounts\Http\Client\ClientException;
use PrestaShop\Module\PsAccounts\Http\Client\ConnectException;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    /**
     * @var array
     */
    private $defaultResponse = [
        'status' => false,
        'httpCode' => 500,
        'body' => ['message' => 'Circuit Breaker Open'],
    ];

    /**
     * @var int
     */
    private $resetTimeoutMs = 500;

    /**
     * @var int
     */
    private $threshold = 2;

    /**
     * @var CircuitBreaker
     */
    private $circuitBreaker;

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function set_up()
    {
        parent::set_up();

        $this->circuitBreaker = $this->createCircuitBreaker(
            'FOO_BAR',
            $this->defaultResponse,
            $this->threshold,
            $this->resetTimeoutMs
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function itShouldStartClosed()
    {
        $this->assertEquals(State::CLOSED, $this->circuitBreaker->state());
    }

    /**
     * @test
     *
     * @return void
     */
    public function itShouldOpenCircuitOnThreshold()
    {
        $circuitBreaker = $this->circuitBreaker;

        for ($i = 0; $i <= $circuitBreaker->getThreshold(); ++$i) {
            $response = $circuitBreaker->call(function () {
                throw new ConnectException('Test Timeout Reached');
            });
        }

        $this->assertEquals(State::OPEN, $circuitBreaker->state(), (string) $this->circuitBreaker);
        $this->assertFalse(isset($response['status']) ? $response['status'] : null);
        $this->assertEquals(500, isset($response['httpCode']) ? $response['httpCode'] : null);
        $this->assertEquals('Circuit Breaker Open', isset($response['body']['message']) ? $response['body']['message'] : null);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itShouldNotOpenCircuitOnNonBreakingException()
    {
        $circuitBreaker = $this->circuitBreaker;

        for ($i = 0; $i <= $circuitBreaker->getThreshold(); ++$i) {
            $response = $circuitBreaker->call(function () {
                throw new ClientException('Test Timeout Reached');
            });
        }

        $this->assertEquals(State::CLOSED, $circuitBreaker->state(), (string) $this->circuitBreaker);
        $this->assertFalse(isset($response['status']) ? $response['status'] : null);
        $this->assertEquals(500, isset($response['httpCode']) ? $response['httpCode'] : null);
        $this->assertEquals('Circuit Breaker Open', isset($response['body']['message']) ? $response['body']['message'] : null);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itShouldHalfOpenCircuitOnResetTimeout()
    {
        $circuitBreaker = $this->circuitBreaker;

        for ($i = 0; $i <= $circuitBreaker->getThreshold(); ++$i) {
            $response = $circuitBreaker->call(function () {
                throw new ConnectException('Test Timeout Reached');
            });
        }

        sleep(1);

        $this->assertEquals(State::HALF_OPEN, $circuitBreaker->state(), (string) $this->circuitBreaker);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itShouldReOpenCircuitOnTimeoutAndHalfOpen()
    {
        $circuitBreaker = $this->circuitBreaker;

        for ($i = 0; $i <= $circuitBreaker->getThreshold(); ++$i) {
            $response = $circuitBreaker->call(function () {
                throw new ConnectException('Test Timeout Reached');
            });
        }

        sleep(1);

        $response = $circuitBreaker->call(function () {
            throw new ConnectException('Test Timeout Reached');
        });

        $this->assertEquals(State::OPEN, $circuitBreaker->state(), (string) $this->circuitBreaker);
        $this->assertFalse(isset($response['status']) ? $response['status'] : null);
        $this->assertEquals(500, isset($response['httpCode']) ? $response['httpCode'] : null);
        $this->assertEquals('Circuit Breaker Open', isset($response['body']['message']) ? $response['body']['message'] : null);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itShouldCloseCircuitOnSuccess()
    {
        $circuitBreaker = $this->circuitBreaker;

        for ($i = 0; $i <= $circuitBreaker->getThreshold(); ++$i) {
            $response = $circuitBreaker->call(function () {
                throw new ConnectException('Test Timeout Reached');
            });
        }

        sleep(1);

        $response = $circuitBreaker->call(function () {
            return 'OK';
        });

        $this->assertEquals(State::CLOSED, $circuitBreaker->state(), (string) $this->circuitBreaker);
    }

    /**
     * @param string $resourceId
     * @param array $defaultResponse
     * @param int $threshold
     * @param int $resetTimeoutMs
     *
     * @return CircuitBreaker
     *
     * @throws \Exception
     */
    private function createCircuitBreaker(
        $resourceId,
        array $defaultResponse,
        $threshold,
        $resetTimeoutMs
    ) {
        //$circuitBreaker = new InMemoryCircuitBreaker($resourceId);
        $circuitBreaker = Factory::create($resourceId);
        $circuitBreaker->setResetTimeoutMs($resetTimeoutMs);
        $circuitBreaker->setThreshold($threshold);
        $circuitBreaker->setFallbackResponse($defaultResponse);
        //$circuitBreaker->reset();

        return $circuitBreaker;
    }
}
