{{--
    Das Gerüst jedes Dokuments.

    Das CSS wird eingebettet, damit die HTML-Datei für sich allein steht: Sie
    wird exportiert, verschickt und gedruckt, ohne dass irgendwo ein
    Stylesheet daneben liegen muss.
--}}
<!DOCTYPE html>
<html lang="{{ $language->value }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>{!! $styles !!}</style>
</head>
<body>
@yield('document')
</body>
</html>
