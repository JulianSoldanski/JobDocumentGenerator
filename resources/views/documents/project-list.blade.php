@extends('documents.layout')

@section('document')
    <div class="sheet">
        <header class="header">
            <h1>{{ $contact['full_name'] }}</h1>
            <div class="doc-title">{{ $fmt->label('project_list.title') }}</div>
            @include('documents.partials.cv-contact')
        </header>

        <p class="intro">{{ $fmt->label('project_list.intro') }}</p>

        @foreach ($projects as $index => $project)
            <div class="project">
                <div class="project-head">
                    <h2><span class="number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>{{ $project['title'] }}</h2>
                    @if ($project['grade'] ?? '')
                        <span class="badge">{{ $fmt->label('project_list.grade') }} {{ $project['grade'] }}</span>
                    @endif
                </div>

                @php
                    $meta = collect([
                        'client' => $project['client'] ?? '',
                        'period' => $project['period'] ?? '',
                        'role' => $project['role'] ?? '',
                        'team' => $project['team_size'] ?? '',
                    ])->filter();
                @endphp

                @if ($meta->isNotEmpty())
                    <div class="project-meta">
                        @foreach ($meta as $key => $value)
                            <span><strong>{{ $fmt->label("project_list.{$key}") }}:</strong> {{ $value }}</span>
                        @endforeach
                    </div>
                @endif

                <dl class="fields">
                    {{-- Ohne ausführliche Felder trägt die Kurzbeschreibung den Eintrag. --}}
                    @if (($project['situation'] ?? '') === '' && ($project['contributions'] ?? []) === [])
                        @if ($project['summary'] ?? '')
                            <dt>{{ $fmt->label('project_list.summary') }}</dt>
                            <dd>{{ $project['summary'] }}</dd>
                        @endif
                    @else
                        @if ($project['situation'] ?? '')
                            <dt>{{ $fmt->label('project_list.situation') }}</dt>
                            <dd>{{ $project['situation'] }}</dd>
                        @endif
                        @if ($project['contributions'] ?? [])
                            <dt>{{ $fmt->label('project_list.contribution') }}</dt>
                            <dd>
                                <ul>
                                    @foreach ($project['contributions'] as $contribution)
                                        <li>{{ $contribution }}</li>
                                    @endforeach
                                </ul>
                            </dd>
                        @endif
                    @endif

                    @if ($project['technologies'] ?? [])
                        <dt>{{ $fmt->label('project_list.technologies') }}</dt>
                        <dd class="tech">
                            @foreach ($project['technologies'] as $technology)
                                <span>{{ $technology }}</span>
                            @endforeach
                        </dd>
                    @endif

                    @if ($project['result'] ?? '')
                        <dt>{{ $fmt->label('project_list.result') }}</dt>
                        <dd class="result">{{ $project['result'] }}</dd>
                    @endif

                    @if ($project['link'] ?? '')
                        <dt>{{ $fmt->label('project_list.link') }}</dt>
                        <dd><a href="{{ $project['link'] }}">{{ $fmt->linkLabel($project['link']) }}</a></dd>
                    @endif
                </dl>
            </div>
        @endforeach
    </div>
@endsection
