<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_the_health_check_route_returns_ok(): void
    {
        $response = $this->getJson(route('health'));

        $response->assertOk();
        $response->assertJson([
            'status' => 'ok',
            'database' => 'ok',
        ]);
        $this->assertArrayNotHasKey('environment', $response->json());
        $this->assertArrayNotHasKey('php', $response->json());
        $this->assertArrayNotHasKey('app', $response->json());
    }
}
