@extends('documents.layout')

@section('document')
    <div class="sheet">
        <aside class="aside">
            <h1>{{ $contact['full_name'] }}</h1>

            <h2>{{ $fmt->label('cv.contact') }}</h2>
            @foreach ($contact['address_lines'] ?? [] as $line)
                <p>{{ $line }}</p>
            @endforeach
            @if ($contact['phone'] ?? '')
                <p>{{ $contact['phone'] }}</p>
            @endif
            @if ($contact['email'] ?? '')
                <p><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></p>
            @endif

            @if ($skills['hard'] || $skills['soft'])
                <h2>{{ $fmt->label('cv.skills') }}</h2>
                <div>
                    @foreach (array_merge($skills['hard'], $skills['soft']) as $skill)
                        <span class="tag">{{ $skill['name'] }}</span>
                    @endforeach
                </div>
            @endif

            @if ($skills['languages'])
                <h2>{{ $fmt->label('cv.languages') }}</h2>
                <ul>
                    @foreach ($skills['languages'] as $language)
                        <li>{{ $language['level'] ? "{$language['name']} ({$language['level']})" : $language['name'] }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($education)
                <h2>{{ $fmt->label('cv.education') }}</h2>
                @foreach ($education as $entry)
                    <div class="entry">
                        <div class="entry-title">{{ $entry['degree'] }}</div>
                        <div class="entry-meta">{{ $entry['organization'] ?? '' }}</div>
                        <div class="entry-period">{{ $fmt->period($entry['start_month'] ?? null, $entry['end_month'] ?? null, $entry['is_current'] ?? false) }}</div>
                    </div>
                @endforeach
            @endif
        </aside>

        <main class="main">
            @if ($statement)
                <section>
                    <h2>{{ $fmt->label('cv.profile') }}</h2>
                    <p>{{ $statement }}</p>
                </section>
            @endif

            @if ($experience)
                <section>
                    <h2>{{ $fmt->label('cv.experience') }}</h2>
                    @foreach ($experience as $entry)
                        <div class="entry">
                            <div class="entry-head">
                                <span class="entry-title">{{ $entry['title'] }}</span>
                                <span class="entry-period">{{ $fmt->period($entry['start_month'] ?? null, $entry['end_month'] ?? null, $entry['is_current'] ?? false) }}</span>
                            </div>
                            <div class="entry-meta">{{ collect([$entry['organization'] ?? '', $entry['location'] ?? ''])->filter()->implode(' · ') }}</div>
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

            @if ($projects)
                <section>
                    <h2>{{ $fmt->label('cv.projects') }}</h2>
                    @include('documents.partials.cv-projects')
                </section>
            @endif
        </main>
    </div>
@endsection
