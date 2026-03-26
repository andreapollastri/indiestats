<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteExport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid',
        'user_id',
        'site_id',
        'status',
        'range',
        'filters_payload',
        'file_path',
        'error_message',
        'completed_at',
        'acknowledged_at',
        'expires_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'filters_payload' => 'array',
            'completed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReadyForDownload(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->file_path !== null
            && $this->file_path !== '';
    }
}
