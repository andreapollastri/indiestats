<?php

namespace App\Support;

use Illuminate\Http\Request;

final class AnalyticsFilters
{
    public function __construct(
        public readonly ?string $source = null,
        public readonly ?string $path = null,
        public readonly ?string $utm = null,
        public readonly ?string $event = null,
        public readonly ?string $device = null,
        public readonly ?string $country = null,
        public readonly ?string $searchQuery = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
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

        return new self(
            source: $s('filter_source', 64),
            path: $s('filter_path', 2048),
            utm: $s('filter_utm', 255),
            event: $s('filter_event', 128),
            device: $s('filter_device', 32),
            country: $s('filter_country', 2),
            searchQuery: $s('filter_q', 512),
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
        if ($this->utm !== null) {
            $o['filter_utm'] = $this->utm;
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
            || $this->utm !== null
            || $this->device !== null
            || $this->country !== null
            || $this->searchQuery !== null;
    }

    public function withoutEvent(): self
    {
        return new self(
            source: $this->source,
            path: $this->path,
            utm: $this->utm,
            event: null,
            device: $this->device,
            country: $this->country,
            searchQuery: $this->searchQuery,
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
