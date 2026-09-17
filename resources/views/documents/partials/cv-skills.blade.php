{{-- Die Skill-Zeilen: je Kategorie eine Zeile, in der gespeicherten Schreibweise. --}}
<dl class="skills">
    @if ($skills['hard'] ?? [])
        <dt>{{ $fmt->label('cv.hard_skills') }}</dt>
        <dd>{{ collect($skills['hard'])->pluck('name')->implode(', ') }}</dd>
    @endif
    @if ($skills['soft'] ?? [])
        <dt>{{ $fmt->label('cv.soft_skills') }}</dt>
        <dd>{{ collect($skills['soft'])->pluck('name')->implode(', ') }}</dd>
    @endif
    @if ($skills['languages'] ?? [])
        <dt>{{ $fmt->label('cv.languages') }}</dt>
        <dd>{{ collect($skills['languages'])->map(fn (array $item): string => $item['level'] ? "{$item['name']} ({$item['level']})" : $item['name'])->implode(', ') }}</dd>
    @endif
</dl>
