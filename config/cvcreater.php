<?php

return [

    /*
    |--------------------------------------------------------------------------
    | KI
    |--------------------------------------------------------------------------
    |
    | Der Anbieter ist austauschbar: alle Aufrufe laufen über Prism und den
    | AiClient. "provider_options" schaltet den Denkmodus des jeweiligen
    | Modells ab — bleibt er an, verbraucht das Modell sein Token-Budget
    | unsichtbar, bevor der eigentliche Text beginnt, und lange Prompts
    | kommen leer zurück.
    |
    */

    'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
        'model' => env('AI_MODEL', 'gemini-2.5-flash'),

        'provider_options' => [
            'gemini' => ['thinkingBudget' => 0],
            'anthropic' => ['thinking' => ['enabled' => false]],
            'openai' => ['reasoning' => ['effort' => 'minimal']],
        ],

        // KI-Aufrufe pro Minute und Nutzer.
        'rate_limit' => (int) env('AI_RATE_LIMIT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stellenanzeigen laden
    |--------------------------------------------------------------------------
    */

    'job_posting' => [
        'fetch_timeout' => 15,
        'max_length' => 20000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dokumente
    |--------------------------------------------------------------------------
    |
    | Die Layouts nutzen modernes CSS, deshalb rendert eine Browser-Engine das
    | PDF. CHROME_PATH zeigt auf eine vorhandene Chrome-Installation.
    |
    */

    'chrome' => [
        'path' => env('CHROME_PATH'),
        'timeout' => (int) env('CHROME_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Zeitmessung
    |--------------------------------------------------------------------------
    |
    | Obergrenze für einen einzelnen Messabschnitt, damit ein tagelang offener
    | Tab der Bewerbung keine absurde Recherchezeit gutschreibt.
    |
    */

    'research_timer' => [
        'max_segment_seconds' => 8 * 3600,
    ],

];
