<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Site extends Model
{
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

    public function isOriginAllowed(Request $request): bool
    {
        if (blank($this->allowed_domains)) {
            return true;
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
        $hosts = array_map('trim', explode(',', $this->allowed_domains));

        foreach ($hosts as $h) {
            $h = strtolower(preg_replace('/^www\./', '', $h));
            if ($h === '') {
                continue;
            }
            if ($targetHost === $h || str_ends_with($targetHost, '.'.$h)) {
                return true;
            }
        }

        return false;
    }
}
