<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['geoip_maxmind_license_key', 'geoip_database_updated_at'])]
class AppSetting extends Model
{
    /**
     * Singleton row for application-wide settings.
     */
    public static function instance(): self
    {
        $row = static::query()->first();
        if ($row === null) {
            $row = static::query()->create([]);
        }

        return $row;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'geoip_maxmind_license_key' => 'encrypted',
            'geoip_database_updated_at' => 'datetime',
        ];
    }
}
