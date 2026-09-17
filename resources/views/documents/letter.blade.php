@extends('documents.layout')

@section('document')
    <div class="sheet">
        <div class="addresses">
            <div class="recipient">
                @if ($recipient['company'] ?? '')
                    <div class="company">{{ $recipient['company'] }}</div>
                @endif
                @if ($recipient['contact_person'] ?? '')
                    <div>{{ $recipient['contact_person'] }}</div>
                @endif
                @if ($recipient['address'] ?? '')
                    <div>{{ $recipient['address'] }}</div>
                @endif
            </div>

            <div class="sender">
                <div class="name">{{ $sender['full_name'] ?? '' }}</div>
                @foreach ($sender['address_lines'] ?? [] as $line)
                    <div>{{ $line }}</div>
                @endforeach
                @if ($sender['phone'] ?? '')
                    <div>{{ $sender['phone'] }}</div>
                @endif
                @if ($sender['email'] ?? '')
                    <div>{{ $sender['email'] }}</div>
                @endif
            </div>
        </div>

        <div class="date-line">{{ collect([$place, $fmt->date($date)])->filter()->implode(', ') }}</div>

        @if ($subject)
            <div class="subject">{{ $subject }}</div>
        @endif

        <div class="salutation">{{ $salutation }}</div>

        <div class="body">
            @foreach ($paragraphs as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>

        <div class="closing">
            <div>{{ $closing ?: $fmt->label('letter.closing') }}</div>
            <div class="signature">{{ $sender['full_name'] ?? '' }}</div>
        </div>
    </div>
@endsection
