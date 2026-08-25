<?php

namespace App\Http\Controllers;

use App\Models\WebsiteEvent;
use App\Models\WebsiteNewsPost;
use App\Models\WebsitePage;
use App\Support\PublicWebsiteData;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(PublicWebsiteData $site): Response
    {
        $origin = rtrim(request()->getSchemeAndHttpHost(), '/');
        $xml = $site->remember('sitemap:'.$origin, function () use ($origin): string {
            $staticRoutes = [
                'home' => ['home', '1.0'],
                'about' => ['website.about', '0.8'],
                'academics' => ['website.academics', '0.9'],
                'admissions' => ['website.admissions', '0.9'],
                'teachers' => ['website.teachers', '0.7'],
                'news' => ['website.news', '0.8'],
                'events' => ['website.events', '0.8'],
                'gallery' => ['website.gallery', '0.7'],
                'contact' => ['website.contact', '0.6'],
            ];
            $pages = WebsitePage::query()
                ->whereIn('slug', array_keys($staticRoutes))
                ->get(['slug', 'updated_at'])
                ->keyBy('slug');
            $entries = [];

            foreach ($staticRoutes as $slug => [$routeName, $priority]) {
                $entries[] = [
                    'url' => $origin.route($routeName, [], false),
                    'lastmod' => $pages->get($slug)?->updated_at?->toAtomString(),
                    'priority' => $priority,
                ];
            }

            foreach (WebsiteNewsPost::query()->published()->get(['slug', 'updated_at']) as $post) {
                $entries[] = [
                    'url' => $origin.route('website.news.show', $post->slug, false),
                    'lastmod' => $post->updated_at?->toAtomString(),
                    'priority' => '0.7',
                ];
            }

            foreach (WebsiteEvent::query()->published()->get(['slug', 'updated_at']) as $event) {
                $entries[] = [
                    'url' => $origin.route('website.events.show', $event->slug, false),
                    'lastmod' => $event->updated_at?->toAtomString(),
                    'priority' => '0.6',
                ];
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            foreach ($entries as $entry) {
                $xml .= '<url><loc>'.$this->escape($entry['url']).'</loc>';
                if ($entry['lastmod']) {
                    $xml .= '<lastmod>'.$this->escape($entry['lastmod']).'</lastmod>';
                }
                $xml .= '<priority>'.$entry['priority'].'</priority></url>';
            }

            return $xml.'</urlset>';
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900, stale-while-revalidate=60',
        ]);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
