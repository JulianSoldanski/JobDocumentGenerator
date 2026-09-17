<?php

namespace App\Ai\Schemas;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class ProjectDraftSchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'project_draft',
            description: 'Die ausführliche Fassung eines Projekts, in beiden Sprachen.',
            properties: [
                new StringSchema('client', 'Auftraggeber, Arbeitgeber oder Hochschule; leer, wenn nicht genannt.'),
                new StringSchema('period', 'Zeitraum, etwa "03/2024 – 07/2024"; leer, wenn nicht genannt.'),
                new StringSchema('team_size', 'Teamgröße, etwa "4 Personen"; leer, wenn nicht genannt.'),
                new ArraySchema(
                    name: 'technologies',
                    description: 'Konkrete Technologien, Werkzeuge und Methoden.',
                    items: new StringSchema('technology', 'Eine Technologie.'),
                ),
                self::block('de', 'Deutsch'),
                self::block('en', 'Englisch'),
            ],
            requiredFields: ['client', 'period', 'team_size', 'technologies', 'de', 'en'],
        );
    }

    private static function block(string $name, string $language): ObjectSchema
    {
        return new ObjectSchema(
            name: $name,
            description: "Alle Texte dieses Blocks auf {$language}.",
            properties: [
                new StringSchema('title', 'Sachlicher Projekttitel.'),
                new StringSchema('summary', 'Ein bis zwei Sätze für den Lebenslauf.'),
                new StringSchema('role', 'Die eigene Rolle im Projekt.'),
                new StringSchema('situation', 'Ein Satz zur Ausgangslage.'),
                new ArraySchema(
                    name: 'contributions',
                    description: 'Zwei bis drei kurze Punkte zum eigenen Beitrag, je mit einem Verb beginnend.',
                    items: new StringSchema('contribution', 'Ein Beitrag.'),
                ),
                new StringSchema('result', 'Ein Satz zum Ergebnis.'),
            ],
            requiredFields: ['title', 'summary', 'role', 'situation', 'contributions', 'result'],
        );
    }
}
