<?php

namespace App\Http\Controllers\Profile;

use App\Ai\AiTasks;
use App\Enums\AiTaskType;
use App\Enums\Language;
use App\Enums\ProfileSection;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Pdf\PdfText;
use App\Support\Translations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Import aus einer bestehenden PDF: Der Text wird extrahiert und per KI in die
 * Profilstruktur überführt — als Startpunkt statt eines leeren Formulars.
 *
 * Übernommen wird erst auf Zuruf, und immer ergänzend: vorhandene Einträge
 * bleiben unangetastet.
 */
class CvImportController extends Controller
{
    public function page(): Response
    {
        return Inertia::render('profile/import');
    }

    public function store(Request $request, PdfText $pdf, AiTasks $tasks): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'file.mimes' => 'Bitte eine PDF-Datei hochladen.',
            'file.max' => 'Die Datei ist größer als 10 MB.',
        ]);

        try {
            $text = $pdf->extract($request->file('file')->getRealPath());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $task = $tasks->dispatch($request->user(), AiTaskType::CvImport, ['text' => $text]);

        return response()->json(['task_id' => $task->id]);
    }

    /**
     * Übernimmt den geprüften Vorschlag ins Profil.
     */
    public function apply(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language' => ['required', 'string', 'in:de,en'],
            'contact' => ['array'],
            'experience' => ['array'],
            'education' => ['array'],
            'hard_skills' => ['array'],
            'soft_skills' => ['array'],
            'languages' => ['array'],
            'projects' => ['array'],
        ]);

        $user = $request->user();
        $language = Language::from($validated['language']);

        $counts = [
            'experience' => $this->importEntries($user, ProfileSection::Experience, $validated['experience'] ?? [], $language),
            'education' => $this->importEntries($user, ProfileSection::Education, $validated['education'] ?? [], $language),
            'skills' => $this->importSkills($user, ProfileSection::HardSkill, $validated['hard_skills'] ?? [], $language)
                + $this->importSkills($user, ProfileSection::SoftSkill, $validated['soft_skills'] ?? [], $language),
            'languages' => $this->importLanguages($user, $validated['languages'] ?? [], $language),
            'projects' => $this->importProjects($user, $validated['projects'] ?? [], $language),
        ];

        $this->fillEmptyContactFields($user, $validated['contact'] ?? []);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => sprintf(
                'Übernommen: %d Stationen, %d Ausbildungen, %d Skills, %d Sprachen, %d Projekte.',
                $counts['experience'],
                $counts['education'],
                $counts['skills'],
                $counts['languages'],
                $counts['projects'],
            ),
        ]);

        return to_route('profile.experience');
    }

    /**
     * @param  array<int, mixed>  $entries
     */
    private function importEntries(User $user, ProfileSection $section, array $entries, Language $language): int
    {
        $headline = $section->headlineField();
        $listField = $section === ProfileSection::Experience ? 'bullets' : 'details';
        $position = (int) $user->profileEntries()->section($section)->max('position');
        $created = 0;

        foreach ($entries as $entry) {
            if (! is_array($entry) || Translations::text($entry[$headline] ?? null) === '') {
                continue;
            }

            $user->profileEntries()->create([
                'section' => $section,
                'organization' => Translations::text($entry['organization'] ?? null),
                'start_month' => Translations::text($entry['start_month'] ?? null) ?: null,
                'end_month' => Translations::text($entry['end_month'] ?? null) ?: null,
                'is_current' => filter_var($entry['is_current'] ?? false, FILTER_VALIDATE_BOOL),
                'position' => ++$position,
                'translations' => [
                    // Nur die Sprache des Dokuments wird gefüllt; die andere
                    // bleibt leer und ist im Editor als fehlend markiert.
                    $language->value => [
                        $headline => Translations::text($entry[$headline] ?? null),
                        'location' => Translations::text($entry['location'] ?? null),
                        $listField => Translations::lines($entry[$listField] ?? []),
                    ],
                ],
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * @param  array<int, mixed>  $names
     */
    private function importSkills(User $user, ProfileSection $section, array $names, Language $language): int
    {
        $existing = $user->profileEntries()->section($section)->get()
            ->map(fn ($entry): string => mb_strtolower($entry->localizedText($language, 'name')))
            ->all();

        $position = (int) $user->profileEntries()->section($section)->max('position');
        $created = 0;

        foreach (Translations::lines($names) as $name) {
            if (in_array(mb_strtolower($name), $existing, true)) {
                continue;
            }

            $user->profileEntries()->create([
                'section' => $section,
                'position' => ++$position,
                'translations' => [$language->value => ['name' => $name]],
            ]);

            $existing[] = mb_strtolower($name);
            $created++;
        }

        return $created;
    }

    /**
     * @param  array<int, mixed>  $languages
     */
    private function importLanguages(User $user, array $languages, Language $language): int
    {
        $position = (int) $user->profileEntries()->section(ProfileSection::LanguageSkill)->max('position');
        $created = 0;

        foreach ($languages as $item) {
            $name = is_array($item) ? Translations::text($item['name'] ?? null) : Translations::text($item);
            if ($name === '') {
                continue;
            }

            $user->profileEntries()->create([
                'section' => ProfileSection::LanguageSkill,
                'position' => ++$position,
                'translations' => [$language->value => [
                    'name' => $name,
                    'level' => is_array($item) ? Translations::text($item['level'] ?? null) : '',
                ]],
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * @param  array<int, mixed>  $projects
     */
    private function importProjects(User $user, array $projects, Language $language): int
    {
        $position = (int) $user->projects()->max('position');
        $created = 0;

        foreach ($projects as $project) {
            if (! is_array($project) || Translations::text($project['title'] ?? null) === '') {
                continue;
            }

            $user->projects()->create([
                'position' => ++$position,
                'tags' => Translations::lines($project['tags'] ?? []),
                'translations' => [$language->value => [
                    'title' => Translations::text($project['title'] ?? null),
                    'summary' => Translations::text($project['summary'] ?? null),
                ]],
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Kontaktdaten werden nur ergänzt — was schon dasteht, bleibt stehen.
     *
     * @param  array<string, mixed>  $contact
     */
    private function fillEmptyContactFields(User $user, array $contact): void
    {
        $current = $user->contact();
        $fill = [];

        foreach (['full_name', 'street', 'postal_code', 'city', 'phone', 'email'] as $field) {
            $value = Translations::text($contact[$field] ?? null);

            if ($value !== '' && Translations::text($current->getAttribute($field)) === '') {
                $fill[$field] = $value;
            }
        }

        if ($fill !== []) {
            $current->fill($fill)->save();
        }
    }
}
