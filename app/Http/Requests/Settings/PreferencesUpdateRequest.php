<?php

namespace App\Http\Requests\Settings;

use App\Support\UserPreferences;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class PreferencesUpdateRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string|In>>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(UserPreferences::allowedLocales())],
            'timezone' => ['required', 'string', 'timezone:all'],
        ];
    }
}
