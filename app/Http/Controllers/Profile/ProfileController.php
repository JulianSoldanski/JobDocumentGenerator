<?php

namespace App\Http\Controllers\Profile;

use App\Enums\ProfileSection;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileEntryResource;
use App\Http\Resources\ProjectResource;
use App\Models\ProfileEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Das Profil ist die Datenbasis, aus der alles gespeist wird. Jeder Abschnitt
 * hat eine eigene Adresse, damit ein Neuladen dort bleibt, wo der Nutzer war.
 */
class ProfileController extends Controller
{
    public function experience(Request $request): Response
    {
        return Inertia::render('profile/experience', [
            'entries' => $this->entries($request->user(), ProfileSection::Experience),
        ]);
    }

    public function education(Request $request): Response
    {
        return Inertia::render('profile/education', [
            'entries' => $this->entries($request->user(), ProfileSection::Education),
        ]);
    }

    public function skills(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('profile/skills', [
            'hardSkills' => $this->entries($user, ProfileSection::HardSkill),
            'softSkills' => $this->entries($user, ProfileSection::SoftSkill),
            'languages' => $this->entries($user, ProfileSection::LanguageSkill),
        ]);
    }

    public function projects(Request $request): Response
    {
        return Inertia::render('profile/projects', [
            'projects' => ProjectResource::collection(
                $request->user()->projects()->orderBy('position')->orderBy('id')->get()
            )->resolve(),
        ]);
    }

    public function style(Request $request): Response
    {
        $style = $request->user()->style();

        return Inertia::render('profile/style', [
            'style' => [
                'example' => $style->example ?? '',
                'rules' => $style->rules,
            ],
        ]);
    }

    public function contact(Request $request): Response
    {
        $contact = $request->user()->contact();

        return Inertia::render('profile/contact', [
            'contact' => [
                'full_name' => $contact->full_name,
                'street' => $contact->street,
                'postal_code' => $contact->postal_code,
                'city' => $contact->city,
                'phone' => $contact->phone,
                'email' => $contact->email,
            ],
        ]);
    }

    /**
     * Die Einträge eines Abschnitts in der Reihenfolge, in der sie später auf
     * dem Dokument stehen.
     *
     * @return array<int, array<string, mixed>>
     */
    private function entries(User $user, ProfileSection $section): array
    {
        $entries = $user->profileEntries()->section($section)->get();

        $entries = $section->isDated()
            // Laufende Stationen zuerst, dann nach Enddatum absteigend.
            ? $entries->sortByDesc(fn (ProfileEntry $entry): string => $entry->sortKey())->values()
            : $entries->sortBy([['position', 'asc'], ['id', 'asc']])->values();

        return ProfileEntryResource::collection($entries)->resolve();
    }
}
