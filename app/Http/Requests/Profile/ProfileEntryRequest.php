<?php

namespace App\Http\Requests\Profile;

use App\Enums\Language;
use App\Enums\ProfileSection;
use App\Support\Translations;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ein Eintrag ist gültig, sobald er in **einer** Sprache geschrieben ist — die
 * zweite darf später folgen. Das ist der Normalfall beim Erfassen.
 */
class ProfileEntryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section' => ['required', Rule::enum(ProfileSection::class)],
            'organization' => ['nullable', 'string', 'max:255'],
            'start_month' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'end_month' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'is_current' => ['boolean'],
            'is_visible' => ['boolean'],
            'translations' => ['required', 'array'],
            'translations.de' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_month.regex' => 'Bitte einen Monat im Format JJJJ-MM angeben.',
            'end_month.regex' => 'Bitte einen Monat im Format JJJJ-MM angeben.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $section = $this->section();

            if ($section->isDated() && trim((string) $this->input('organization')) === '') {
                $validator->errors()->add('organization', $section === ProfileSection::Experience
                    ? 'Unternehmen ist Pflicht.'
                    : 'Institution ist Pflicht.');
            }

            $headline = $section->headlineField();
            $written = false;
            foreach (Language::cases() as $language) {
                $block = $this->input('translations.'.$language->value, []);
                if (is_array($block) && Translations::text($block[$headline] ?? null) !== '') {
                    $written = true;
                }
            }

            if (! $written) {
                $validator->errors()->add(
                    'translations.de.'.$headline,
                    $this->headlineLabel($section).' muss in mindestens einer Sprache ausgefüllt sein.'
                );
            }
        });
    }

    public function section(): ProfileSection
    {
        return ProfileSection::from((string) $this->input('section'));
    }

    private function headlineLabel(ProfileSection $section): string
    {
        return match ($section) {
            ProfileSection::Experience => 'Die Berufsbezeichnung',
            ProfileSection::Education => 'Der Abschluss',
            default => 'Der Name',
        };
    }
}
