<?php

namespace PrestaShop\Module\PsAccounts\Api\Client\CircuitBreaker;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Message\Request;
use PHPUnit\Framework\TestCase;

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
    public function setUp()
    {
        parent::setUp();

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
        $this->assertEquals(CircuitBreaker::CIRCUIT_BREAKER_STATE_CLOSED, $this->circuitBreaker->state());
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
                /* @phpstan-ignore-next-line  */
                throw new ConnectException('Test Timeout Reached', new Request('POST', '/test-route'));
            });
        }

        $this->assertEquals(CircuitBreaker::CIRCUIT_BREAKER_STATE_OPEN, $circuitBreaker->state());
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
                /* @phpstan-ignore-next-line  */
                throw new ConnectException('Test Timeout Reached', new Request('POST', '/test-route'));
            });
        }

        sleep(1);

        $this->assertEquals(CircuitBreaker::CIRCUIT_BREAKER_STATE_HALF_OPEN, $circuitBreaker->state());
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
                /* @phpstan-ignore-next-line  */
                throw new ConnectException('Test Timeout Reached', new Request('POST', '/test-route'));
            });
        }

        sleep(1);

        $response = $circuitBreaker->call(function () {
            /* @phpstan-ignore-next-line  */
            throw new ConnectException('Test Timeout Reached', new Request('POST', '/test-route'));
        });

        $this->assertEquals(CircuitBreaker::CIRCUIT_BREAKER_STATE_OPEN, $circuitBreaker->state());
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
                /* @phpstan-ignore-next-line  */
                throw new ConnectException('Test Timeout Reached', new Request('POST', '/test-route'));
            });
        }

        sleep(1);

        $response = $circuitBreaker->call(function () {
            return 'OK';
        });

        $this->assertEquals(CircuitBreaker::CIRCUIT_BREAKER_STATE_CLOSED, $circuitBreaker->state());
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
        $circuitBreaker = CircuitBreakerFactory::create($resourceId);
        $circuitBreaker->setResetTimeoutMs($resetTimeoutMs);
        $circuitBreaker->setThreshold($threshold);
        $circuitBreaker->setDefaultFallbackResponse($defaultResponse);

        return $circuitBreaker;
    }
}
