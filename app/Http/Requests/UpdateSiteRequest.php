<?php

namespace App\Http\Requests;

use App\Concerns\SiteValidationRules;
use App\Models\Site;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\Attributes\RedirectToRoute;
use Illuminate\Foundation\Http\FormRequest;

#[RedirectToRoute('sites.index')]
class UpdateSiteRequest extends FormRequest
{
    use SiteValidationRules;

    public function authorize(): bool
    {
        $site = $this->route('site');

        return $site instanceof Site
            && $this->user()?->can('update', $site);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->siteValidationRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->siteValidationMessages();
    }

    protected function prepareForValidation(): void
    {
        $allowedDomains = $this->input('allowed_domains');
        if (! is_string($allowedDomains)) {
            return;
        }

        $this->merge([
            'allowed_domains' => trim($allowedDomains),
        ]);
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $allowedDomains = $this->input('allowed_domains');
                if (is_string($allowedDomains) && trim($allowedDomains) === '') {
                    $validator->errors()->add(
                        'allowed_domains',
                        __('Indica almeno un dominio consentito.'),
                    );
                }
            },
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $site = $this->route('site');
        if ($site instanceof Site) {
            $this->session()->flash('edit_site_public_key', $site->public_key);
        }

        parent::failedValidation($validator);
    }
}
