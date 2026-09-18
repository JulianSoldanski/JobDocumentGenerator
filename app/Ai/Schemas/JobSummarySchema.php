<?php

namespace App\Ai\Schemas;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class JobSummarySchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'job_summary',
            description: 'Die Anzeige auf einen Blick, während rechts am Dokument gearbeitet wird.',
            properties: [
                new StringSchema('company', 'Was das Unternehmen macht: zwei bis drei Sätze.'),
                new StringSchema('role', 'Wen es sucht: zwei bis drei Sätze zu Aufgaben und Erwartungen.'),
                new ArraySchema(
                    name: 'technologies',
                    description: 'Genannte Technologien, Werkzeuge und Methoden.',
                    items: new StringSchema('technology', 'Eine Technologie.'),
                ),
            ],
            requiredFields: ['company', 'role', 'technologies'],
        );
    }
}
