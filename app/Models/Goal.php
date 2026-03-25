<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    protected $fillable = [
        'site_id',
        'label',
        'event_name',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
