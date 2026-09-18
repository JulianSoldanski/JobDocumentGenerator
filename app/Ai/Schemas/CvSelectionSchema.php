<?php

namespace App\Ai\Schemas;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class CvSelectionSchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'cv_selection',
            description: 'Das Profil-Statement und die Auswahl für einen Lebenslauf.',
            properties: [
                new StringSchema('statement', 'Zwei bis drei Sätze, auf die Stelle bezogen.'),
                self::ids('projects', 'IDs der Projekte, die auf diesen Lebenslauf gehören, wichtigstes zuerst.'),
                self::ids('hard_skills', 'IDs der fachlichen Skills, relevanteste zuerst.'),
                self::ids('soft_skills', 'IDs der überfachlichen Skills, relevanteste zuerst.'),
            ],
            requiredFields: ['statement', 'projects', 'hard_skills', 'soft_skills'],
        );
    }

    private static function ids(string $name, string $description): ArraySchema
    {
        return new ArraySchema(
            name: $name,
            description: $description,
            items: new StringSchema('id', 'Eine ID aus der vorgelegten Liste.'),
        );
    }
}
