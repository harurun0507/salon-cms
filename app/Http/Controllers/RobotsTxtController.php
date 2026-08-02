<?php

namespace App\Http\Controllers;

use App\Models\SalonSetting;
use Illuminate\Http\Response;
use Throwable;

class RobotsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $body = $this->buildBody();

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function buildBody(): string
    {
        if (! app()->environment('production')) {
            return "User-agent: *\nDisallow: /\n";
        }

        $noindex = false;

        try {
            $noindex = (bool) SalonSetting::current()->noindex;
        } catch (Throwable) {
            $noindex = true;
        }

        if ($noindex) {
            return "User-agent: *\nDisallow: /\n";
        }

        $sitemapUrl = url('/sitemap.xml');

        return "User-agent: *\nAllow: /\n\nSitemap: {$sitemapUrl}\n";
    }
}
