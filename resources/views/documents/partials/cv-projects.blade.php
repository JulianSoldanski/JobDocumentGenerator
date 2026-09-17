{{-- Nur die Projekte, die für dieses Dokument ausgewählt sind. --}}
@foreach ($projects as $project)
    <div class="entry">
        <div class="project-title">
            {{ $project['title'] }}@if ($project['grade'] ?? '') <span class="muted">({{ $fmt->label('project_list.grade') }} {{ $project['grade'] }})</span>@endif
        </div>
        @if ($project['summary'] ?? '')
            <div>{{ $project['summary'] }}</div>
        @endif
        @if ($project['link'] ?? '')
            <div class="muted"><a href="{{ $project['link'] }}">{{ $fmt->linkLabel($project['link']) }}</a></div>
        @endif
    </div>
@endforeach
