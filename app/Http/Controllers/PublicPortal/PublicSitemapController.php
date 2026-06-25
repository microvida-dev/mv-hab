<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Services\PublicPortal\PublicSitemapService;
use Illuminate\Http\Response;

class PublicSitemapController extends Controller
{
    public function sitemap(PublicSitemapService $sitemapService): Response
    {
        return response()
            ->view('public.sitemap', ['urls' => $sitemapService->urls()], 200, [
                'Content-Type' => 'application/xml; charset=UTF-8',
            ]);
    }

    public function robots(): Response
    {
        return response(implode("\n", [
            'User-agent: *',
            'Disallow: /backoffice',
            'Disallow: /area-candidato',
            'Disallow: /tenant',
            'Disallow: /dashboard',
            'Sitemap: '.route('public.sitemap'),
            '',
        ]), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
