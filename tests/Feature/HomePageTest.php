<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_renders_successfully(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Academic Journal', false);
        $response->assertSee('Current issue', false);
        $response->assertSee('Vol. 1, No. 1 — Forthcoming (June 2026)', false);
        $response->assertSee('For authors', false);
        $response->assertSee('All rights reserved.', false);
        $response->assertSee('<meta name="description"', false);
    }
}
