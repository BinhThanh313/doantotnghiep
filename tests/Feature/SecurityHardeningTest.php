<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test các header bảo mật (SecurityHeaders middleware) và rate limit cho
 * route công khai (public-api).
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_include_basic_security_headers()
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_public_flash_sale_endpoint_gets_rate_limited_after_too_many_requests()
    {
        // RateLimiter 'public-api' giới hạn 60 req/phút theo IP
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/flash-sales/current')->assertOk();
        }

        $this->getJson('/api/flash-sales/current')->assertStatus(429);
    }
}