<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function store(ProjectRequest $request): RedirectResponse
    {
        $request->user()->projects()->create([
            ...$this->attributes($request),
            'position' => (int) $request->user()->projects()->max('position') + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Projekt gespeichert.']);

        return back();
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update($this->attributes($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Projekt gespeichert.']);

        return back();
    }

    /**
     * Die beiden Schalter eines Projekts: sichtbar im Lebenslauf und Teil der
     * Projektliste.
     */
    public function flags(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validate([
            'is_visible' => ['sometimes', 'boolean'],
            'in_project_list' => ['sometimes', 'boolean'],
        ]));

        return back();
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Projekt gelöscht.']);

        return back();
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $projects = $request->user()->projects()->get()->keyBy('id');

        foreach ($validated['ids'] as $position => $id) {
            $projects->get($id)?->update(['position' => $position]);
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ProjectRequest $request): array
    {
        return [
            'is_visible' => $request->boolean('is_visible', true),
            'in_project_list' => $request->boolean('in_project_list', true),
            'link' => $request->input('link') ?: null,
            'grade' => $request->input('grade') ?: null,
            'tags' => array_values(array_filter((array) $request->input('tags', []))),
            'client' => (string) $request->input('client', ''),
            'period' => (string) $request->input('period', ''),
            'team_size' => (string) $request->input('team_size', ''),
            'technologies' => array_values(array_filter((array) $request->input('technologies', []))),
            'translations' => $request->input('translations', []),
        ];
    }
}
