<?php

namespace App\Http\Requests\Profile;

use App\Enums\Language;
use App\Support\Translations;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_visible' => ['boolean'],
            'in_project_list' => ['boolean'],
            'link' => ['nullable', 'url', 'max:500'],
            'grade' => ['nullable', 'string', 'max:50'],
            'tags' => ['array'],
            'tags.*' => ['string', 'max:50'],
            'client' => ['nullable', 'string', 'max:255'],
            'period' => ['nullable', 'string', 'max:100'],
            'team_size' => ['nullable', 'string', 'max:100'],
            'technologies' => ['array'],
            'technologies.*' => ['string', 'max:100'],
            'translations' => ['required', 'array'],
            'translations.de' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (['title', 'summary'] as $field) {
                $written = false;
                foreach (Language::cases() as $language) {
                    $block = $this->input('translations.'.$language->value, []);
                    if (is_array($block) && Translations::text($block[$field] ?? null) !== '') {
                        $written = true;
                    }
                }

                if (! $written) {
                    $label = $field === 'title' ? 'Der Titel' : 'Die Kurzbeschreibung';
                    $validator->errors()->add(
                        'translations.de.'.$field,
                        $label.' muss in mindestens einer Sprache ausgefüllt sein.'
                    );
                }
            }
        });
    }
}
