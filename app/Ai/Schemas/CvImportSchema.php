<?php

namespace App\Ai\Schemas;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

/**
 * Der Import liest einen vorhandenen Lebenslauf in die Profilstruktur — als
 * Startpunkt statt eines leeren Formulars. Übersetzt wird dabei nichts: die
 * Texte bleiben in der Sprache, in der sie im PDF stehen.
 */
class CvImportSchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'cv_import',
            description: 'Der Inhalt eines vorhandenen Lebenslaufs in Profilstruktur.',
            properties: [
                new StringSchema('language', 'Die Sprache des Lebenslaufs: "de" oder "en".'),
                new ObjectSchema(
                    name: 'contact',
                    description: 'Kontaktdaten aus dem Kopf des Dokuments.',
                    properties: [
                        new StringSchema('full_name', 'Vollständiger Name.'),
                        new StringSchema('street', 'Straße und Hausnummer.'),
                        new StringSchema('postal_code', 'Postleitzahl.'),
                        new StringSchema('city', 'Ort.'),
                        new StringSchema('phone', 'Telefonnummer.'),
                        new StringSchema('email', 'E-Mail-Adresse.'),
                    ],
                    requiredFields: ['full_name'],
                ),
                new ArraySchema(
                    name: 'experience',
                    description: 'Berufserfahrung, neueste zuerst.',
                    items: new ObjectSchema(
                        name: 'position',
                        description: 'Eine Station.',
                        properties: [
                            new StringSchema('title', 'Berufsbezeichnung.'),
                            new StringSchema('organization', 'Unternehmen.'),
                            new StringSchema('location', 'Ort.'),
                            new StringSchema('start_month', 'Beginn als JJJJ-MM, sonst leer.'),
                            new StringSchema('end_month', 'Ende als JJJJ-MM, leer wenn laufend.'),
                            new StringSchema('is_current', '"true", wenn die Station noch läuft, sonst "false".'),
                            new ArraySchema(
                                name: 'bullets',
                                description: 'Die Aufzählungspunkte, wörtlich übernommen.',
                                items: new StringSchema('bullet', 'Ein Punkt.'),
                            ),
                        ],
                        requiredFields: ['title', 'organization'],
                    ),
                ),
                new ArraySchema(
                    name: 'education',
                    description: 'Ausbildung, neueste zuerst.',
                    items: new ObjectSchema(
                        name: 'education_entry',
                        description: 'Eine Ausbildung.',
                        properties: [
                            new StringSchema('degree', 'Abschluss oder Studiengang.'),
                            new StringSchema('organization', 'Institution.'),
                            new StringSchema('location', 'Ort.'),
                            new StringSchema('start_month', 'Beginn als JJJJ-MM, sonst leer.'),
                            new StringSchema('end_month', 'Ende als JJJJ-MM, leer wenn laufend.'),
                            new StringSchema('is_current', '"true", wenn sie noch läuft, sonst "false".'),
                            new ArraySchema(
                                name: 'details',
                                description: 'Details wie Schwerpunkt, Note oder Abschlussarbeit.',
                                items: new StringSchema('detail', 'Ein Detail.'),
                            ),
                        ],
                        requiredFields: ['degree', 'organization'],
                    ),
                ),
                self::names('hard_skills', 'Fachliche Fähigkeiten, Technologien, Methoden.'),
                self::names('soft_skills', 'Überfachliche Fähigkeiten.'),
                new ArraySchema(
                    name: 'languages',
                    description: 'Sprachkenntnisse.',
                    items: new ObjectSchema(
                        name: 'language_skill',
                        description: 'Eine Sprache mit Niveau.',
                        properties: [
                            new StringSchema('name', 'Die Sprache.'),
                            new StringSchema('level', 'Das Niveau, etwa "Muttersprache" oder "C1".'),
                        ],
                        requiredFields: ['name'],
                    ),
                ),
                new ArraySchema(
                    name: 'projects',
                    description: 'Projekte, sofern der Lebenslauf welche nennt.',
                    items: new ObjectSchema(
                        name: 'project',
                        description: 'Ein Projekt.',
                        properties: [
                            new StringSchema('title', 'Projekttitel.'),
                            new StringSchema('summary', 'Ein bis zwei Sätze.'),
                            new ArraySchema(
                                name: 'tags',
                                description: 'Schlagworte.',
                                items: new StringSchema('tag', 'Ein Schlagwort.'),
                            ),
                        ],
                        requiredFields: ['title', 'summary'],
                    ),
                ),
            ],
            requiredFields: ['language', 'contact', 'experience', 'education', 'hard_skills', 'soft_skills', 'languages', 'projects'],
        );
    }

    private static function names(string $name, string $description): ArraySchema
    {
        return new ArraySchema(
            name: $name,
            description: $description,
            items: new StringSchema('name', 'Eine Bezeichnung, wörtlich wie im Lebenslauf.'),
        );
    }
}
