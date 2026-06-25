<?php

namespace App\Support;

use Illuminate\Http\Request;

final class AnalyticsFilters
{
    /** @var list<string> */
    private const REQUEST_PARAM_KEYS = [
        'filter_source',
        'filter_path',
        'filter_page_title',
        'filter_page_query',
        'filter_utm',
        'filter_utm_source',
        'filter_utm_medium',
        'filter_utm_campaign',
        'filter_utm_term',
        'filter_utm_content',
        'filter_gclid',
        'filter_fbclid',
        'filter_msclkid',
        'filter_event',
        'filter_device',
        'filter_country',
        'filter_q',
        'filter_browser',
        'filter_browser_version',
        'filter_os',
        'filter_language',
        'filter_timezone',
        'filter_session_id',
        'filter_is_bot',
        'filter_asn',
    ];

    public function __construct(
        public readonly ?string $source = null,
        public readonly ?string $path = null,
        public readonly ?string $pageTitle = null,
        public readonly ?string $pageQuery = null,
        public readonly ?string $utmSource = null,
        public readonly ?string $utmMedium = null,
        public readonly ?string $utmCampaign = null,
        public readonly ?string $utmTerm = null,
        public readonly ?string $utmContent = null,
        public readonly ?string $gclid = null,
        public readonly ?string $fbclid = null,
        public readonly ?string $msclkid = null,
        public readonly ?string $event = null,
        public readonly ?string $device = null,
        public readonly ?string $country = null,
        public readonly ?string $searchQuery = null,
        public readonly ?string $browser = null,
        public readonly ?string $browserVersion = null,
        public readonly ?string $os = null,
        public readonly ?string $language = null,
        public readonly ?string $timezone = null,
        public readonly ?string $sessionId = null,
        public readonly ?bool $isBot = null,
        public readonly ?int $asn = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return self::fromQueryArray(array_merge(
            $request->query(),
            $request->only(self::REQUEST_PARAM_KEYS),
        ));
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
            pageTitle: $s('filter_page_title', 512),
            pageQuery: $s('filter_page_query', 2048),
            utmSource: $utmSource,
            utmMedium: $s('filter_utm_medium', 255),
            utmCampaign: $s('filter_utm_campaign', 255),
            utmTerm: $s('filter_utm_term', 255),
            utmContent: $s('filter_utm_content', 255),
            gclid: $s('filter_gclid', 255),
            fbclid: $s('filter_fbclid', 255),
            msclkid: $s('filter_msclkid', 255),
            event: $s('filter_event', 128),
            device: $s('filter_device', 32),
            country: $s('filter_country', 2),
            searchQuery: $s('filter_q', 512),
            browser: $s('filter_browser', 64),
            browserVersion: $s('filter_browser_version', 32),
            os: $s('filter_os', 64),
            language: $s('filter_language', 16),
            timezone: $s('filter_timezone', 64),
            sessionId: $s('filter_session_id', 64),
            isBot: self::parseIsBot($request->input('filter_is_bot')),
            asn: self::parseAsn($request->input('filter_asn')),
        );
    }

    private static function parseIsBot(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '1' || $value === 'true') {
            return true;
        }
        if ($value === '0' || $value === 'false') {
            return false;
        }

        return null;
    }

    private static function parseAsn(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || ! ctype_digit($value)) {
            return null;
        }

        $asn = (int) $value;

        return $asn > 0 ? $asn : null;
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
        if ($this->pageTitle !== null) {
            $o['filter_page_title'] = $this->pageTitle;
        }
        if ($this->pageQuery !== null) {
            $o['filter_page_query'] = $this->pageQuery;
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
        if ($this->gclid !== null) {
            $o['filter_gclid'] = $this->gclid;
        }
        if ($this->fbclid !== null) {
            $o['filter_fbclid'] = $this->fbclid;
        }
        if ($this->msclkid !== null) {
            $o['filter_msclkid'] = $this->msclkid;
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
        if ($this->browserVersion !== null) {
            $o['filter_browser_version'] = $this->browserVersion;
        }
        if ($this->os !== null) {
            $o['filter_os'] = $this->os;
        }
        if ($this->language !== null) {
            $o['filter_language'] = $this->language;
        }
        if ($this->timezone !== null) {
            $o['filter_timezone'] = $this->timezone;
        }
        if ($this->sessionId !== null) {
            $o['filter_session_id'] = $this->sessionId;
        }
        if ($this->isBot !== null) {
            $o['filter_is_bot'] = $this->isBot ? '1' : '0';
        }
        if ($this->asn !== null) {
            $o['filter_asn'] = (string) $this->asn;
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
            || $this->pageTitle !== null
            || $this->pageQuery !== null
            || $this->utmSource !== null
            || $this->utmMedium !== null
            || $this->utmCampaign !== null
            || $this->utmTerm !== null
            || $this->utmContent !== null
            || $this->gclid !== null
            || $this->fbclid !== null
            || $this->msclkid !== null
            || $this->device !== null
            || $this->country !== null
            || $this->searchQuery !== null
            || $this->browser !== null
            || $this->browserVersion !== null
            || $this->os !== null
            || $this->language !== null
            || $this->timezone !== null
            || $this->sessionId !== null
            || $this->isBot !== null
            || $this->asn !== null;
    }

    public function withoutEvent(): self
    {
        return new self(
            source: $this->source,
            path: $this->path,
            pageTitle: $this->pageTitle,
            pageQuery: $this->pageQuery,
            utmSource: $this->utmSource,
            utmMedium: $this->utmMedium,
            utmCampaign: $this->utmCampaign,
            utmTerm: $this->utmTerm,
            utmContent: $this->utmContent,
            gclid: $this->gclid,
            fbclid: $this->fbclid,
            msclkid: $this->msclkid,
            event: null,
            device: $this->device,
            country: $this->country,
            searchQuery: $this->searchQuery,
            browser: $this->browser,
            browserVersion: $this->browserVersion,
            os: $this->os,
            language: $this->language,
            timezone: $this->timezone,
            sessionId: $this->sessionId,
            isBot: $this->isBot,
            asn: $this->asn,
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
            pageTitle: $this->pageTitle,
            pageQuery: $this->pageQuery,
            utmSource: $this->utmSource,
            utmMedium: $this->utmMedium,
            utmCampaign: $this->utmCampaign,
            utmTerm: $this->utmTerm,
            utmContent: $this->utmContent,
            gclid: $this->gclid,
            fbclid: $this->fbclid,
            msclkid: $this->msclkid,
            event: $this->event,
            device: $this->device,
            country: $this->country,
            searchQuery: $this->searchQuery,
            browser: $this->browser,
            browserVersion: $this->browserVersion,
            os: $this->os,
            language: $this->language,
            timezone: $this->timezone,
            sessionId: $this->sessionId,
            isBot: $this->isBot,
            asn: $this->asn,
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
