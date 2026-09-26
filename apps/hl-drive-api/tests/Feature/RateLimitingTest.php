<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Rate Limiting Tests for Public Authentication Endpoints.
 *
 * Verifies that brute-force and DoS protection is actively enforced:
 * - /api/auth/login (5 req/min)
 * - /api/auth/register (3 req/min)
 * - /api/auth/password-recovery/request (3 req/min)
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('login');
        RateLimiter::clear('register');
        RateLimiter::clear('password-recovery');
    }

    public function testLoginEndpointIsRateLimitedAfterFiveAttempts(): void
    {
        $payload = [
            'email' => 'invalid-login@example.com',
            'password' => 'wrong-password',
        ];

        // First 5 attempts should reach controller (e.g. 401 Unauthorized or 422)
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/auth/login', $payload);
            $this->assertNotEquals(429, $response->getStatusCode(), "Attempt {$i} should not be throttled");
        }

        // 6th attempt should be blocked by RateLimiter with 429 Too Many Requests
        $throttledResponse = $this->postJson('/api/auth/login', $payload);
        $throttledResponse->assertStatus(429);
        $this->assertTrue($throttledResponse->headers->has('Retry-After'));
    }

    public function testRegisterEndpointIsRateLimitedAfterThreeAttempts(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john.register@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        // First 3 attempts should not return 429
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson('/api/auth/register', array_merge($payload, [
                'email' => "user{$i}@example.com",
            ]));
            $this->assertNotEquals(429, $response->getStatusCode(), "Attempt {$i} should not be throttled");
        }

        // 4th attempt should be blocked with 429
        $throttledResponse = $this->postJson('/api/auth/register', array_merge($payload, [
            'email' => 'user4@example.com',
        ]));
        $throttledResponse->assertStatus(429);
        $this->assertTrue($throttledResponse->headers->has('Retry-After'));
    }

    public function testPasswordRecoveryEndpointIsRateLimitedAfterThreeAttempts(): void
    {
        User::factory()->create(['email' => 'recover@example.com']);

        $payload = ['email' => 'recover@example.com'];

        // First 3 attempts should pass rate limiter
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson('/api/auth/password-recovery/request', $payload);
            $this->assertNotEquals(429, $response->getStatusCode(), "Attempt {$i} should not be throttled");
        }

        // 4th attempt should be blocked with 429
        $throttledResponse = $this->postJson('/api/auth/password-recovery/request', $payload);
        $throttledResponse->assertStatus(429);
        $this->assertTrue($throttledResponse->headers->has('Retry-After'));
    }
}
