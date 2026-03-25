<?php

namespace App\Services;

class ReferrerSourceService
{
    /**
     * @return array{source: string, search_query: ?string}
     */
    public function analyze(?string $referrerUrl): array
    {
        if ($referrerUrl === null || $referrerUrl === '') {
            return ['source' => 'direct', 'search_query' => null];
        }

        $host = parse_url($referrerUrl, PHP_URL_HOST);
        if (! $host) {
            return ['source' => 'other', 'search_query' => null];
        }

        $host = strtolower(preg_replace('/^www\./', '', $host));

        $searchQuery = $this->extractSearchQuery($referrerUrl, $host);

        $rules = [
            ['google.', 'google'],
            ['googleusercontent.', 'google'],
            ['bing.', 'bing'],
            ['duckduckgo.', 'duckduckgo'],
            ['yahoo.', 'yahoo'],
            ['facebook.', 'facebook'],
            ['fb.com', 'facebook'],
            ['instagram.', 'instagram'],
            ['linkedin.', 'linkedin'],
            ['twitter.', 'twitter'],
            ['t.co', 'twitter'],
            ['x.com', 'twitter'],
            ['reddit.', 'reddit'],
            ['youtube.', 'youtube'],
            ['baidu.', 'baidu'],
            ['yandex.', 'yandex'],
            ['ecosia.', 'ecosia'],
            ['brave.', 'brave'],
            ['startpage.', 'startpage'],
            ['pinterest.', 'pinterest'],
            ['tiktok.', 'tiktok'],
        ];

        foreach ($rules as [$needle, $label]) {
            if (str_contains($host, $needle)) {
                return ['source' => $label, 'search_query' => $searchQuery];
            }
        }

        return ['source' => $host, 'search_query' => $searchQuery];
    }

    private function extractSearchQuery(string $referrerUrl, string $host): ?string
    {
        $query = parse_url($referrerUrl, PHP_URL_QUERY);
        if (! $query) {
            return null;
        }

        parse_str($query, $params);

        if (str_contains($host, 'yahoo')) {
            return isset($params['p']) ? $this->truncate($params['p']) : null;
        }

        if (isset($params['q'])) {
            return $this->truncate($params['q']);
        }

        if (isset($params['query'])) {
            return $this->truncate($params['query']);
        }

        return null;
    }

    private function truncate(string $value): string
    {
        return mb_substr($value, 0, 512);
    }
}
