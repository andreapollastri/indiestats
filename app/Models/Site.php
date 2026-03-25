<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Site extends Model
{
    public function getRouteKeyName(): string
    {
        return 'public_key';
    }

    protected $fillable = [
        'user_id',
        'name',
        'public_key',
        'allowed_domains',
    ];

    protected static function booted(): void
    {
        static::creating(function (Site $site): void {
            if (empty($site->public_key)) {
                $site->public_key = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class);
    }

    public function outboundClicks(): HasMany
    {
        return $this->hasMany(OutboundClick::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function isOriginAllowed(Request $request): bool
    {
        if (blank($this->allowed_domains)) {
            return false;
        }

        $origin = $request->header('Origin');
        if (! $origin) {
            $referer = $request->header('Referer');
            if ($referer) {
                $scheme = parse_url($referer, PHP_URL_SCHEME) ?: 'https';
                $host = parse_url($referer, PHP_URL_HOST);
                if ($host) {
                    $origin = $scheme.'://'.$host;
                }
            }
        }

        $targetHost = $origin ? parse_url($origin, PHP_URL_HOST) : null;
        if (! $targetHost) {
            return false;
        }

        $targetHost = strtolower(preg_replace('/^www\./', '', $targetHost));
        $hosts = array_merge(
            array_map('trim', explode(',', $this->allowed_domains)),
            config('analytics.tracking_extra_allowed_hosts', [])
        );

        foreach ($hosts as $raw) {
            $h = self::normalizeAllowedHostEntry($raw);
            if ($h === null || $h === '') {
                continue;
            }
            if ($targetHost === $h || str_ends_with($targetHost, '.'.$h)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Accetta host "puliti" o URL completi (es. https://web.ap.it.test/path) salvati per errore in allowed_domains.
     */
    protected static function normalizeAllowedHostEntry(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, '://')) {
            $host = parse_url($raw, PHP_URL_HOST);
        } else {
            $beforePath = strtok($raw, '/');
            $host = $beforePath !== false
                ? parse_url('http://'.$beforePath, PHP_URL_HOST)
                : null;
        }

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);

        return preg_replace('/^www\./', '', $host) ?: null;
    }
}
