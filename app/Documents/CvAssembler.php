<?php

namespace App\Documents;

use App\Enums\Language;
use App\Enums\ProfileSection;
use App\Models\ProfileEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Setzt den Lebenslauf aus dem Profil zusammen.
 *
 * Prinzip 1: Jede Station, jedes Projekt, jeder Skill wird **wörtlich** aus dem
 * Profil übernommen. Von der KI kommen nur das Profil-Statement und die
 * Auswahl samt Reihenfolge — beides wird hier als IDs hereingereicht, die
 * bereits gegen die erlaubte Liste geprüft sind.
 */
class CvAssembler
{
    public function __construct(private readonly User $user, private readonly Language $language) {}

    /**
     * Was die KI zur Auswahl vorgelegt bekommt: je Abschnitt die IDs mit dem,
     * woran sie ein Projekt oder einen Skill erkennt.
     *
     * @return array{projects: array<int, array<string, mixed>>, hard_skills: array<int, array<string, string>>, soft_skills: array<int, array<string, string>>}
     */
    public function candidates(): array
    {
        return [
            'projects' => $this->projects()->map(fn (Project $project): array => [
                'id' => (string) $project->id,
                'title' => $project->localizedText($this->language, 'title'),
                'summary' => $project->localizedText($this->language, 'summary'),
                'tags' => $project->tags,
            ])->all(),
            'hard_skills' => $this->skillCandidates(ProfileSection::HardSkill),
            'soft_skills' => $this->skillCandidates(ProfileSection::SoftSkill),
        ];
    }

    /**
     * Die Stationen, an denen sich Statement und Anschreiben orientieren.
     *
     * @return array<int, array<string, mixed>>
     */
    public function experienceOutline(): array
    {
        return $this->dated(ProfileSection::Experience)->map(fn (ProfileEntry $entry): array => [
            'title' => $entry->localizedText($this->language, 'title'),
            'organization' => $entry->organization,
            'period' => trim(($entry->start_month ?? '').' – '.($entry->is_current ? 'heute' : ($entry->end_month ?? '')), ' –'),
            'bullets' => $entry->localizedList($this->language, 'bullets'),
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function educationOutline(): array
    {
        return $this->dated(ProfileSection::Education)->map(fn (ProfileEntry $entry): array => [
            'degree' => $entry->localizedText($this->language, 'degree'),
            'organization' => $entry->organization,
        ])->all();
    }

    /**
     * Ob überhaupt etwas zum Zusammensetzen da ist. Ein leeres Profil ergibt
     * einen leeren Lebenslauf — und ein Anschreiben, in dem die KI
     * Qualifikationen erfinden müsste.
     */
    public function hasContent(): bool
    {
        return $this->user->profileEntries()->visible()->exists()
            || $this->user->projects()->where('is_visible', true)->exists();
    }

    /**
     * Der fertige Inhalt in der Form, die der DocumentRenderer erwartet.
     *
     * Alle sichtbaren Projekte kommen mit: die ausgewählten in der Reihenfolge
     * der KI und eingeschaltet, die übrigen ausgeschaltet dahinter — so lässt
     * sich im Editor jedes Projekt per Schalter zurückholen.
     *
     * @param  array<int, string>  $projectIds
     * @param  array<int, string>  $hardSkillIds
     * @param  array<int, string>  $softSkillIds
     * @return array<string, mixed>
     */
    public function assemble(string $statement, array $projectIds, array $hardSkillIds, array $softSkillIds): array
    {
        return [
            'contact' => $this->contact(),
            'statement' => trim($statement),
            'experience' => $this->dated(ProfileSection::Experience)->map(fn (ProfileEntry $entry): array => [
                'id' => (string) $entry->id,
                'title' => $entry->localizedText($this->language, 'title'),
                'organization' => $entry->organization,
                'location' => $entry->localizedText($this->language, 'location'),
                'start_month' => $entry->start_month,
                'end_month' => $entry->end_month,
                'is_current' => $entry->is_current,
                'subtle' => $entry->is_subtle,
                'bullets' => $entry->localizedList($this->language, 'bullets'),
            ])->all(),
            'education' => $this->dated(ProfileSection::Education)->map(fn (ProfileEntry $entry): array => [
                'id' => (string) $entry->id,
                'degree' => $entry->localizedText($this->language, 'degree'),
                'organization' => $entry->organization,
                'location' => $entry->localizedText($this->language, 'location'),
                'start_month' => $entry->start_month,
                'end_month' => $entry->end_month,
                'is_current' => $entry->is_current,
                'details' => $entry->localizedList($this->language, 'details'),
            ])->all(),
            'projects' => $this->orderedProjects($projectIds),
            'skills' => [
                'hard' => $this->pickSkills(ProfileSection::HardSkill, $hardSkillIds),
                'soft' => $this->pickSkills(ProfileSection::SoftSkill, $softSkillIds),
                // Sprachen sind Fakten, keine Auswahl — sie stehen immer drin.
                'languages' => $this->ordered(ProfileSection::LanguageSkill)->map(fn (ProfileEntry $entry): array => [
                    'id' => (string) $entry->id,
                    'name' => $entry->localizedText($this->language, 'name'),
                    'level' => $entry->localizedText($this->language, 'level'),
                ])->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function contact(): array
    {
        $contact = $this->user->contact();

        return [
            'full_name' => $contact->full_name,
            'address_lines' => $contact->addressLines(),
            'phone' => $contact->phone,
            'email' => $contact->email,
        ];
    }

    /**
     * @param  array<int, string>  $selected
     * @return array<int, array<string, mixed>>
     */
    private function orderedProjects(array $selected): array
    {
        $projects = $this->projects()->keyBy(fn (Project $project): string => (string) $project->id);

        $order = array_merge(
            $selected,
            array_values(array_diff($projects->keys()->all(), $selected)),
        );

        return array_map(function (string $id) use ($projects, $selected): array {
            /** @var Project $project */
            $project = $projects[$id];

            return [
                'id' => $id,
                'title' => $project->localizedText($this->language, 'title'),
                'summary' => $project->localizedText($this->language, 'summary'),
                'grade' => (string) $project->grade,
                'link' => (string) $project->link,
                'included' => in_array($id, $selected, true),
            ];
        }, $order);
    }

    /**
     * @param  array<int, string>  $selected
     * @return array<int, array<string, string>>
     */
    private function pickSkills(ProfileSection $section, array $selected): array
    {
        $skills = $this->ordered($section)->keyBy(fn (ProfileEntry $entry): string => (string) $entry->id);

        return array_values(array_map(
            fn (string $id): array => [
                'id' => $id,
                'name' => $skills[$id]->localizedText($this->language, 'name'),
            ],
            array_filter($selected, fn (string $id): bool => $skills->has($id)),
        ));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function skillCandidates(ProfileSection $section): array
    {
        return $this->ordered($section)->map(fn (ProfileEntry $entry): array => [
            'id' => (string) $entry->id,
            'name' => $entry->localizedText($this->language, 'name'),
        ])->all();
    }

    /**
     * Stationen so, wie sie auf dem Lebenslauf stehen: laufende zuerst, dann
     * nach Enddatum absteigend.
     *
     * @return Collection<int, ProfileEntry>
     */
    private function dated(ProfileSection $section): Collection
    {
        return $this->user->profileEntries()->section($section)->visible()->get()
            ->sortByDesc(fn (ProfileEntry $entry): string => $entry->sortKey())
            ->values();
    }

    /**
     * @return Collection<int, ProfileEntry>
     */
    private function ordered(ProfileSection $section): Collection
    {
        return $this->user->profileEntries()->section($section)->visible()
            ->orderBy('position')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, Project>
     */
    private function projects(): Collection
    {
        return $this->user->projects()->where('is_visible', true)
            ->orderBy('position')->orderBy('id')->get();
    }
}
