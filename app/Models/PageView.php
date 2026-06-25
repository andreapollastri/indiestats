<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'visitor_id',
        'path',
        'page_title',
        'page_query',
        'referrer_url',
        'referrer_source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'search_query',
        'browser',
        'browser_version',
        'is_bot',
        'os',
        'device_type',
        'browser_language',
        'timezone',
        'ip_address',
        'country_code',
        'asn',
        'as_organization',
        'duration_seconds',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
