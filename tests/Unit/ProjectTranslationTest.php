<?php

namespace Tests\Unit;

use App\Enums\Language;
use App\Models\Project;
use Tests\TestCase;

class ProjectTranslationTest extends TestCase
{
    public function test_long_form_fields_fall_back_per_field(): void
    {
        $project = new Project(['translations' => [
            'de' => [
                'title' => 'Analytics-Dashboard',
                'summary' => 'Kurzbeschreibung',
                'role' => 'Fullstack-Entwickler',
                'contributions' => ['Datenmodell entworfen'],
            ],
            'en' => [
                'title' => 'Analytics dashboard',
                'summary' => '',
            ],
        ]]);

        $localized = $project->localized(Language::English);

        $this->assertSame('Analytics dashboard', $localized['title']);
        $this->assertSame('Kurzbeschreibung', $localized['summary']);
        $this->assertSame('Fullstack-Entwickler', $localized['role']);
        $this->assertSame(['Datenmodell entworfen'], $localized['contributions']);
    }

    public function test_long_form_is_detected(): void
    {
        $this->assertFalse(Project::factory()->make(['user_id' => 1])->hasLongForm());
        $this->assertTrue(Project::factory()->withLongForm()->make(['user_id' => 1])->hasLongForm());
    }
}
