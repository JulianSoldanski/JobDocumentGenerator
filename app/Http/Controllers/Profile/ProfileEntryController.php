<?php

namespace App\Http\Controllers\Profile;

use App\Enums\ProfileSection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProfileEntryRequest;
use App\Models\ProfileEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileEntryController extends Controller
{
    public function store(ProfileEntryRequest $request): RedirectResponse
    {
        $section = $request->section();

        $request->user()->profileEntries()->create([
            ...$this->attributes($request),
            'position' => (int) $request->user()->profileEntries()->section($section)->max('position') + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Eintrag gespeichert.']);

        return back();
    }

    public function update(ProfileEntryRequest $request, ProfileEntry $entry): RedirectResponse
    {
        $this->authorize('update', $entry);

        $entry->update($this->attributes($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Eintrag gespeichert.']);

        return back();
    }

    /**
     * Der Sichtbarkeitsschalter: ältere Nebenjobs ausblenden, ohne sie zu
     * löschen.
     */
    public function visibility(Request $request, ProfileEntry $entry): RedirectResponse
    {
        $this->authorize('update', $entry);

        $entry->update(['is_visible' => $request->boolean('is_visible')]);

        return back();
    }

    public function destroy(ProfileEntry $entry): RedirectResponse
    {
        $this->authorize('delete', $entry);

        $entry->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Eintrag gelöscht.']);

        return back();
    }

    /**
     * Reihenfolge einer Liste (Skills, Sprachen) neu setzen.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'section' => ['required', 'string'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $section = ProfileSection::from($validated['section']);
        $entries = $request->user()->profileEntries()->section($section)->get()->keyBy('id');

        foreach ($validated['ids'] as $position => $id) {
            $entry = $entries->get($id);
            $entry?->update(['position' => $position]);
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ProfileEntryRequest $request): array
    {
        $section = $request->section();

        return [
            'section' => $section,
            'organization' => (string) $request->input('organization', ''),
            'start_month' => $section->isDated() ? $request->input('start_month') : null,
            'end_month' => $section->isDated() && ! $request->boolean('is_current')
                ? $request->input('end_month')
                : null,
            'is_current' => $section->isDated() && $request->boolean('is_current'),
            'is_visible' => $request->boolean('is_visible', true),
            'is_subtle' => $section === ProfileSection::Experience && $request->boolean('is_subtle'),
            'translations' => $request->input('translations', []),
        ];
    }
}
