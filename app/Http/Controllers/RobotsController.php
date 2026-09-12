<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /submissions',
            'Disallow: /reviews',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
            '# OAI-PMH: '.url('/oai?verb=Identify'),
            '',
        ]);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
