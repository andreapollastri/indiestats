<?php

namespace App\Concerns;

trait SiteValidationRules
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function siteValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'allowed_domains' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function siteValidationMessages(): array
    {
        return [
            'allowed_domains.required' => __('Indica almeno un dominio consentito.'),
        ];
    }
}
