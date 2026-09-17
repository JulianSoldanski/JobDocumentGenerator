{{-- Die Kontaktzeile im Kopf des Lebenslaufs. --}}
<div class="contact">
    @if ($contact['address_line'] ?? '')
        <span>{{ $contact['address_line'] }}</span>
    @endif
    @if ($contact['phone'] ?? '')
        <span>{{ $contact['phone'] }}</span>
    @endif
    @if ($contact['email'] ?? '')
        <span><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></span>
    @endif
</div>
