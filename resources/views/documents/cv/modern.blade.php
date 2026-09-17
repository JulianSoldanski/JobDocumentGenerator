@extends('documents.layout')

@section('document')
    <div class="sheet">
        <header class="header">
            <h1>{{ $contact['full_name'] }}</h1>
            @include('documents.partials.cv-contact')
        </header>

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

        @if ($education)
            <section>
                <h2>{{ $fmt->label('cv.education') }}</h2>
                @foreach ($education as $entry)
                    <div class="entry">
                        <div class="entry-head">
                            <span class="entry-title">{{ $entry['degree'] }}</span>
                            <span class="entry-period">{{ $fmt->period($entry['start_month'] ?? null, $entry['end_month'] ?? null, $entry['is_current'] ?? false) }}</span>
                        </div>
                        <div class="entry-meta">{{ collect([$entry['organization'] ?? '', $entry['location'] ?? ''])->filter()->implode(' · ') }}</div>
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
            <section>
                <h2>{{ $fmt->label('cv.projects') }}</h2>
                @include('documents.partials.cv-projects')
            </section>
        @endif

        @if ($skills['hard'] || $skills['soft'] || $skills['languages'])
            <section>
                <h2>{{ $fmt->label('cv.skills') }}</h2>
                @include('documents.partials.cv-skills')
            </section>
        @endif
    </div>
@endsection
