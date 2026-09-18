{{-- Das kleine Fenster des Bookmarklets: kurz bestätigen, dann schließen. --}}
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ $message }}</title>
    <style>
        body { font-family: system-ui, sans-serif; display: grid; place-items: center; height: 100vh; margin: 0; background: #0a0a0a; color: #fafafa; }
        p { font-size: 15px; text-align: center; padding: 0 16px; }
        .ok::before { content: '✓ '; color: #3987e5; }
    </style>
</head>
<body>
    <p class="{{ $ok ? 'ok' : '' }}">{{ $message }}</p>
    @if ($ok)
        <script>setTimeout(() => window.close(), 1200);</script>
    @endif
</body>
</html>
