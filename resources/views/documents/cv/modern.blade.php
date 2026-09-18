{{--
    Modern: jeder Abschnitt eine eigene Karte auf hellem Grund — das Design,
    mit dem die bisherigen Bewerbungen verschickt wurden.
--}}
@extends('documents.layout')

@section('document')
    <div class="sheet">
        <header class="card header">
            <h1>{{ $contact['full_name'] }}</h1>
            @include('documents.partials.cv-contact')
        </header>

        @if ($statement)
            <section class="card">
                <h2>{{ $fmt->label('cv.profile') }}</h2>
                <p>{{ $statement }}</p>
            </section>
        @endif

        @if ($experience)
            <section class="card">
                <h2>{{ $fmt->label('cv.experience') }}</h2>
                @foreach ($experience as $entry)
                    <div class="entry">
                        <div class="entry-title">{{ $entry['title'] }}</div>
                        <div class="entry-meta">{{ collect([
                            collect([$entry['organization'] ?? '', $entry['location'] ?? ''])->filter()->implode(', '),
                            $fmt->period($entry['start_month'] ?? null, $entry['end_month'] ?? null, $entry['is_current'] ?? false),
                        ])->filter()->implode(' · ') }}</div>
                        @if ($entry['bullets'] ?? [])
                            <ul>
                                @foreach ($entry['bullets'] as $bullet)
                                    <li>{{ $bullet }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        @if ($education)
            <section class="card">
                <h2>{{ $fmt->label('cv.education') }}</h2>
                @foreach ($education as $entry)
                    <div class="entry">
                        <div class="entry-title">{{ $entry['degree'] }}</div>
                        <div class="entry-meta">{{ collect([
                            $entry['organization'] ?? '',
                            $entry['location'] ?? '',
                            $fmt->period($entry['start_month'] ?? null, $entry['end_month'] ?? null, $entry['is_current'] ?? false),
                        ])->filter()->implode(' · ') }}</div>
                        @if ($entry['details'] ?? [])
                            <ul>
                                @foreach ($entry['details'] as $detail)
                                    <li>{{ $detail }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        @if ($projects)
            <section class="card">
                <h2>{{ $fmt->label('cv.projects') }}</h2>
                <ul class="projects">
                    @foreach ($projects as $project)
                        <li>
                            <strong>{{ $project['title'] }}@if ($project['grade'] ?? '') ({{ $fmt->label('project_list.grade') }}: {{ $project['grade'] }})@endif</strong>@if ($project['summary'] ?? ''): {{ $project['summary'] }}@endif
                            @if ($project['link'] ?? '')
                                (<a href="{{ $project['link'] }}">{{ $fmt->linkLabel($project['link']) }}</a>)
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($skills['hard'] || $skills['soft'] || $skills['languages'])
            <section class="card">
                <h2>{{ $fmt->label('cv.skills') }}</h2>
                @foreach ([
                    'cv.hard_skills' => collect($skills['hard'])->pluck('name')->implode(', '),
                    'cv.soft_skills' => collect($skills['soft'])->pluck('name')->implode(', '),
                    'cv.languages' => collect($skills['languages'])
                        ->map(fn (array $item): string => ($item['level'] ?? '') ? "{$item['name']} ({$item['level']})" : $item['name'])
                        ->implode(', '),
                ] as $label => $values)
                    @if ($values)
                        <div class="skills-row">
                            <strong>{{ $fmt->label($label) }}:</strong>
                            <span>{{ $values }}</span>
                        </div>
                    @endif
                @endforeach
            </section>
        @endif
    </div>
@endsection
