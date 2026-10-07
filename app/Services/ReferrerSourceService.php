<?php

namespace App\Services;

class ReferrerSourceService
{
    /**
     * Known sources matched by a domain label, e.g. "google" in www.google.co.uk (but not in notgoogle.com).
     *
     * @var array<string, string>
     */
    private const LABEL_SOURCES = [
        'google' => 'google',
        'googleusercontent' => 'google',
        'bing' => 'bing',
        'duckduckgo' => 'duckduckgo',
        'yahoo' => 'yahoo',
        'facebook' => 'facebook',
        'instagram' => 'instagram',
        'linkedin' => 'linkedin',
        'twitter' => 'twitter',
        'reddit' => 'reddit',
        'youtube' => 'youtube',
        'baidu' => 'baidu',
        'yandex' => 'yandex',
        'ecosia' => 'ecosia',
        'brave' => 'brave',
        'startpage' => 'startpage',
        'pinterest' => 'pinterest',
        'tiktok' => 'tiktok',
    ];

    /**
     * Known sources matched by the whole domain or its subdomains, e.g. t.co (but not reddit.com).
     *
     * @var array<string, string>
     */
    private const DOMAIN_SOURCES = [
        't.co' => 'twitter',
        'x.com' => 'twitter',
        'fb.com' => 'facebook',
        'lnkd.in' => 'linkedin',
        'youtu.be' => 'youtube',
    ];

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

        return ['source' => $this->knownSource($host) ?? $host, 'search_query' => $searchQuery];
    }

    private function knownSource(string $host): ?string
    {
        foreach (self::DOMAIN_SOURCES as $domain => $source) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return $source;
            }
        }

        // Skip the top-level domain so "bing" does not match a hypothetical ".bing" TLD.
        $labels = array_slice(explode('.', $host), 0, -1);
        foreach ($labels as $label) {
            if (isset(self::LABEL_SOURCES[$label])) {
                return self::LABEL_SOURCES[$label];
            }
        }

        return null;
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
