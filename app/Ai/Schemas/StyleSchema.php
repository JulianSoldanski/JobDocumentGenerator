<?php

namespace App\Ai\Schemas;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class StyleSchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'writing_style',
            description: 'Die aus einem Beispiel destillierten Stilregeln.',
            properties: [
                new ArraySchema(
                    name: 'rules',
                    description: 'Sechs bis zehn Regeln, je eine kurze Aussage.',
                    items: new StringSchema('rule', 'Eine einzelne Stilregel.'),
                ),
            ],
            requiredFields: ['rules'],
        );
    }
}
