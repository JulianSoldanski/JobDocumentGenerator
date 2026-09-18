<?php

namespace App\Ai\Schemas;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class CoverLetterSchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'cover_letter',
            description: 'Ein Anschreiben für genau eine Stelle.',
            properties: [
                new StringSchema('subject', 'Betreffzeile.'),
                new StringSchema('salutation', 'Anrede, mit Komma am Ende.'),
                new ArraySchema(
                    name: 'paragraphs',
                    description: 'Die Absätze des Brieftexts, ohne Anrede und Grußformel.',
                    items: new StringSchema('paragraph', 'Ein Absatz.'),
                ),
                new StringSchema('closing', 'Grußformel ohne Namen, etwa "Mit freundlichen Grüßen".'),
            ],
            requiredFields: ['subject', 'salutation', 'paragraphs', 'closing'],
        );
    }
}
