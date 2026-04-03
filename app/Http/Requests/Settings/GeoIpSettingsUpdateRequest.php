<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class GeoIpSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'geoip_maxmind_license_key' => ['nullable', 'string', 'max:255'],
            'clear_geoip_license_key' => ['sometimes', 'boolean'],
        ];
    }
}
