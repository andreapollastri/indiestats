<?php

namespace App\Support;

use Illuminate\Http\Request;

final class AnalyticsFilters
{
    public function __construct(
        public readonly ?string $source = null,
        public readonly ?string $path = null,
        public readonly ?string $utmSource = null,
        public readonly ?string $utmMedium = null,
        public readonly ?string $utmCampaign = null,
        public readonly ?string $utmTerm = null,
        public readonly ?string $utmContent = null,
        public readonly ?string $event = null,
        public readonly ?string $device = null,
        public readonly ?string $country = null,
        public readonly ?string $searchQuery = null,
        public readonly ?string $browser = null,
        public readonly ?string $os = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return self::fromQueryArray($request->query());
    }

    /**
     * Rebuild filters from query parameters (e.g. stored in site_exports.filters_payload).
     *
     * @param  array<string, mixed>  $query
     */
    public static function fromQueryArray(array $query): self
    {
        $request = Request::create('/', 'GET', $query);

        $s = function (string $key, int $maxLen) use ($request): ?string {
            $v = $request->input($key);
            if (! is_string($v)) {
                return null;
            }
            $v = trim($v);
            if ($v === '') {
                return null;
            }

            return mb_substr($v, 0, $maxLen);
        };

        $utmSource = $s('filter_utm_source', 255);
        if ($utmSource === null) {
            $utmSource = $s('filter_utm', 255);
        }

        return new self(
            source: $s('filter_source', 64),
            path: $s('filter_path', 2048),
            utmSource: $utmSource,
            utmMedium: $s('filter_utm_medium', 255),
            utmCampaign: $s('filter_utm_campaign', 255),
            utmTerm: $s('filter_utm_term', 255),
            utmContent: $s('filter_utm_content', 255),
            event: $s('filter_event', 128),
            device: $s('filter_device', 32),
            country: $s('filter_country', 2),
            searchQuery: $s('filter_q', 512),
            browser: $s('filter_browser', 64),
            os: $s('filter_os', 64),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQueryArray(): array
    {
        $o = [];
        if ($this->source !== null) {
            $o['filter_source'] = $this->source;
        }
        if ($this->path !== null) {
            $o['filter_path'] = $this->path;
        }
        if ($this->utmSource !== null) {
            $o['filter_utm_source'] = $this->utmSource;
        }
        if ($this->utmMedium !== null) {
            $o['filter_utm_medium'] = $this->utmMedium;
        }
        if ($this->utmCampaign !== null) {
            $o['filter_utm_campaign'] = $this->utmCampaign;
        }
        if ($this->utmTerm !== null) {
            $o['filter_utm_term'] = $this->utmTerm;
        }
        if ($this->utmContent !== null) {
            $o['filter_utm_content'] = $this->utmContent;
        }
        if ($this->event !== null) {
            $o['filter_event'] = $this->event;
        }
        if ($this->device !== null) {
            $o['filter_device'] = $this->device;
        }
        if ($this->country !== null) {
            $o['filter_country'] = $this->country;
        }
        if ($this->searchQuery !== null) {
            $o['filter_q'] = $this->searchQuery;
        }
        if ($this->browser !== null) {
            $o['filter_browser'] = $this->browser;
        }
        if ($this->os !== null) {
            $o['filter_os'] = $this->os;
        }

        return $o;
    }

    public function hasAny(): bool
    {
        return $this->hasPageViewRowFilters() || $this->event !== null;
    }

    public function hasPageViewRowFilters(): bool
    {
        return $this->source !== null
            || $this->path !== null
            || $this->utmSource !== null
            || $this->utmMedium !== null
            || $this->utmCampaign !== null
            || $this->utmTerm !== null
            || $this->utmContent !== null
            || $this->device !== null
            || $this->country !== null
            || $this->searchQuery !== null
            || $this->browser !== null
            || $this->os !== null;
    }

    public function withoutEvent(): self
    {
        return new self(
            source: $this->source,
            path: $this->path,
            utmSource: $this->utmSource,
            utmMedium: $this->utmMedium,
            utmCampaign: $this->utmCampaign,
            utmTerm: $this->utmTerm,
            utmContent: $this->utmContent,
            event: null,
            device: $this->device,
            country: $this->country,
            searchQuery: $this->searchQuery,
            browser: $this->browser,
            os: $this->os,
        );
    }

    /**
     * For outbound_clicks queries: path and source map to from_path / referrer_source;
     * they must not be repeated in the page_views visitor_id subquery.
     */
    public function withoutPathAndSource(): self
    {
        return new self(
            source: null,
            path: null,
            utmSource: $this->utmSource,
            utmMedium: $this->utmMedium,
            utmCampaign: $this->utmCampaign,
            utmTerm: $this->utmTerm,
            utmContent: $this->utmContent,
            event: $this->event,
            device: $this->device,
            country: $this->country,
            searchQuery: $this->searchQuery,
            browser: $this->browser,
            os: $this->os,
        );
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    public function mergeQuery(array $base): array
    {
        return array_merge($base, $this->toQueryArray());
    }
}
