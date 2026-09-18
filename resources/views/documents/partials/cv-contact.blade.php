{{-- Die Kontaktzeile im Kopf des Lebenslaufs. --}}
<div class="contact">
    @if ($contact['address_line'] ?? '')
        <span class="address">{{ $contact['address_line'] }}</span>
    @endif
    @if ($contact['phone'] ?? '')
        <span class="phone">{{ $contact['phone'] }}</span>
    @endif
    @if ($contact['email'] ?? '')
        <span class="email"><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></span>
    @endif
</div>
