<?php

namespace App\Ai\Schemas;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class ExtractFieldsSchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'job_fields',
            description: 'Die Eckdaten einer Stellenanzeige, wörtlich aus dem Text.',
            properties: [
                new StringSchema('company', 'Name des Unternehmens, das die Stelle ausschreibt.'),
                new StringSchema('position', 'Bezeichnung der Stelle, so wie sie ausgeschrieben ist.'),
                new StringSchema('contact_person', 'Name der Ansprechperson samt Anrede, etwa "Frau Dr. Meyer".'),
                new StringSchema('city', 'Ort des Arbeitsplatzes.'),
                new StringSchema('company_address', 'Postanschrift für das Anschreiben, mehrzeilig.'),
            ],
            requiredFields: ['company', 'position', 'contact_person', 'city', 'company_address'],
        );
    }
}
