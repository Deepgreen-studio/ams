<?php

namespace Tests\Feature\Authentication;

use Tests\TestCase;

class CorsConfigurationTest extends TestCase
{
    public function test_cors_allows_the_production_spa_origin(): void
    {
        $origin = 'https://ams.eh.studio';

        $response = $this->withHeaders([
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type,authorization,x-requested-with',
        ])->options('/api/v1/auth/login');

        $response->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', $origin)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_cors_allows_the_easycare_origin(): void
    {
        $origin = 'https://easycare.eh.studio';

        $response = $this->withHeaders([
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type,authorization',
        ])->options('/api/v1/auth/login');

        $response->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', $origin)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_cors_allows_the_configured_frontend_origin(): void
    {
        $origin = 'http://localhost:5173';

        $response = $this->withHeaders([
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'GET',
        ])->options('/sanctum/csrf-cookie');

        $response->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', $origin)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_cors_merges_frontend_url_into_allowed_origins(): void
    {
        $this->assertContains('http://localhost:5173', config('cors.allowed_origins'));
    }

    public function test_cors_allows_local_vite_ports_outside_production(): void
    {
        $origin = 'http://localhost:5175';

        $response = $this->withHeaders([
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type,authorization,x-requested-with',
        ])->options('/api/v1/auth/login');

        $response->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', $origin)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }
}
