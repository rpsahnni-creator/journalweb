<?php

namespace Tests\Unit\Support;

use App\Support\SafeRedirect;
use Tests\TestCase;

class SafeRedirectTest extends TestCase
{
    public function test_it_rejects_external_and_protocol_relative_urls(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000']);

        $fallback = 'http://127.0.0.1:8000/dashboard';

        $this->assertSame('/author', SafeRedirect::target('/author', $fallback));
        $this->assertSame($fallback, SafeRedirect::target('https://evil.example/phish', $fallback));
        $this->assertSame($fallback, SafeRedirect::target('//evil.example/phish', $fallback));
        $this->assertSame($fallback, SafeRedirect::target('javascript:alert(1)', $fallback));
        $this->assertSame(
            'http://127.0.0.1:8000/admin',
            SafeRedirect::target('http://127.0.0.1:8000/admin', $fallback)
        );
    }
}
