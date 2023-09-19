<?php

namespace PrestaShop\Module\PsAccounts\Api\Client\CircuitBreaker;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->circuitBreaker = $this->createCircuitBreaker(
            'FOO_BAR',
            $this->defaultResponse,
            $this->threshold,
            $this->resetTimeoutMs
        );
    }

    public function itShouldStartClosed(): void
    {
        $this->assertEquals(CircuitBreaker::CIRCUIT_BREAKER_STATE_CLOSED, $this->circuitBreaker->state());
    }

    /**
     * @test
     */
    public function itShouldOpenCircuitOnThreshold(): void
    {
        $circuitBreaker = $this->circuitBreaker;

        for ($i = 0; $i <= $circuitBreaker->getThreshold(); ++$i) {
            $response = $circuitBreaker->call(function () {
                throw new ConnectException('Test Timeout Reached', new Request('POST', '/test-route'));
            });
        }

        $this->assertEquals(CircuitBreaker::CIRCUIT_BREAKER_STATE_OPEN, $circuitBreaker->state());
        $this->assertFalse($response['status'] ?? null);
        $this->assertEquals(500, $response['httpCode'] ?? null);
        $this->assertEquals('Circuit Breaker Open', $response['body']['message'] ?? null);
    }

    /**
     * @test
     */
    public function itShouldHalfOpenCircuitOnResetTimeout(): void
    {
        $circuitBreaker = $this->circuitBreaker;

        for ($i = 0; $i <= $circuitBreaker->getThreshold(); ++$i) {
            $response = $circuitBreaker->call(function () {
                throw new ConnectException('Test Timeout Reached', new Request('POST', '/test-route'));
            });
        }

        sleep(1);

        $this->assertEquals(CircuitBreaker::CIRCUIT_BREAKER_STATE_HALF_OPEN, $circuitBreaker->state());
    }

    /**
     * @test
     */
    public function itShouldReOpenCircuitOnTimeoutAndHalfOpen(): void
    {
        $circuitBreaker = $this->circuitBreaker;

        for ($i = 0; $i <= $circuitBreaker->getThreshold(); ++$i) {
            $response = $circuitBreaker->call(function () {
                throw new ConnectException('Test Timeout Reached', new Request('POST', '/test-route'));
            });
        }

        sleep(1);

        $response = $circuitBreaker->call(function () {
            throw new ConnectException('Test Timeout Reached', new Request('POST', '/test-route'));
        });

        $this->assertEquals(CircuitBreaker::CIRCUIT_BREAKER_STATE_OPEN, $circuitBreaker->state());
        $this->assertFalse($response['status'] ?? null);
        $this->assertEquals(500, $response['httpCode'] ?? null);
        $this->assertEquals('Circuit Breaker Open', $response['body']['message'] ?? null);
    }

    /**
     * @test
     */
    public function itShouldCloseCircuitOnSuccess(): void
    {
        $circuitBreaker = $this->circuitBreaker;

        for ($i = 0; $i <= $circuitBreaker->getThreshold(); ++$i) {
            $response = $circuitBreaker->call(function () {
                throw new ConnectException('Test Timeout Reached', new Request('POST', '/test-route'));
            });
        }

        sleep(1);

        $response = $circuitBreaker->call(function () {
            return 'OK';
        });

        $this->assertEquals(CircuitBreaker::CIRCUIT_BREAKER_STATE_CLOSED, $circuitBreaker->state());
    }

    private function createCircuitBreaker(
        string $resourceId,
        array $defaultResponse,
        int $threshold,
        int $resetTimeoutMs
    ): CircuitBreaker {
        //$circuitBreaker = new InMemoryCircuitBreaker($resourceId);
        $circuitBreaker = CircuitBreakerFactory::create($resourceId);
        $circuitBreaker->setResetTimeoutMs($resetTimeoutMs);
        $circuitBreaker->setThreshold($threshold);
        $circuitBreaker->setDefaultFallbackResponse($defaultResponse);

        return $circuitBreaker;
    }
}
