<?php

namespace Tests\Feature\Generator;

use App\Ai\TaskRegistry;
use App\Enums\AiTaskType;
use App\Enums\DocumentType;
use App\Enums\GenerationScope;
use App\Enums\Language;
use App\Enums\ProfileSection;
use App\Jobs\RunAiTask;
use App\Models\GeneratorSession;
use App\Models\ProfileEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\TestCase;

/**
 * „Generieren": Der Lebenslauf wird zusammengesetzt, das Anschreiben
 * geschrieben.
 */
class GenerateDocumentsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ein Profil mit je einem Eintrag pro Abschnitt.
     *
     * @return array{user: User, session: GeneratorSession, experience: ProfileEntry, python: ProfileEntry, sql: ProfileEntry, teamwork: ProfileEntry, dashboard: Project, thesis: Project}
     */
    private function profile(): array
    {
        $user = $this->withAiKey(User::factory()->create());

        $user->contact()->fill([
            'full_name' => 'Julia Muster',
            'street' => 'Beispielweg 3',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'email' => 'julia@example.test',
        ])->save();

        return [
            'user' => $user,
            'session' => GeneratorSession::factory()->for($user)->withPosting()->create([
                'company' => 'MusterTech GmbH',
                'position' => 'Fullstack-Entwicklerin',
                'contact_person' => 'Frau Dr. Meyer',
                'company_address' => "Musterstraße 1\n20095 Hamburg",
            ]),
            'experience' => ProfileEntry::factory()->for($user)->create(),
            'python' => ProfileEntry::factory()->for($user)->skill(ProfileSection::HardSkill, 'Python', 'Python')->create(),
            'sql' => ProfileEntry::factory()->for($user)->skill(ProfileSection::HardSkill, 'SQL', 'SQL')->create(['position' => 1]),
            'teamwork' => ProfileEntry::factory()->for($user)->skill(ProfileSection::SoftSkill, 'Teamarbeit', 'Teamwork')->create(),
            'dashboard' => Project::factory()->for($user)->create(),
            'thesis' => Project::factory()->for($user)->create([
                'position' => 1,
                'translations' => [
                    'de' => ['title' => 'Bachelorarbeit', 'summary' => 'Prognosemodell für Lagerbestände.'],
                    'en' => ['title' => 'Bachelor thesis', 'summary' => 'Forecasting model for stock levels.'],
                ],
            ]),
        ];
    }

    private function runTask(User $user, AiTaskType $type, GeneratorSession $session): void
    {
        $task = $user->aiTasks()->create(['type' => $type, 'input' => []]);
        $task->subject()->associate($session)->save();

        (new RunAiTask($task->id))->handle(app(TaskRegistry::class));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fakeSelection(array $overrides = []): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured(array_merge([
                'statement' => 'Fullstack-Entwicklerin mit Erfahrung in Datenprodukten.',
                'projects' => [],
                'hard_skills' => [],
                'soft_skills' => [],
            ], $overrides)),
        ]);
    }

    public function test_generating_queues_one_task_per_document(): void
    {
        Queue::fake();
        ['user' => $user, 'session' => $session] = $this->profile();

        $response = $this->actingAs($user)->postJson(route('generator.generate', $session))->assertOk();

        $this->assertNotNull($response->json('tasks.cv'));
        $this->assertNotNull($response->json('tasks.letter'));
        $this->assertNotNull($response->json('tasks.summary'), 'Ohne Übersicht entsteht sie gleich mit.');
        Queue::assertPushed(RunAiTask::class, 3);
    }

    public function test_the_scope_decides_what_is_generated(): void
    {
        Queue::fake();
        ['user' => $user, 'session' => $session] = $this->profile();
        $session->forceFill(['scope' => GenerationScope::LetterOnly, 'summary' => ['company' => 'x', 'role' => 'y', 'technologies' => []]])->save();

        $response = $this->actingAs($user)->postJson(route('generator.generate', $session))->assertOk();

        $this->assertNull($response->json('tasks.cv'));
        $this->assertNotNull($response->json('tasks.letter'));
        $this->assertNull($response->json('tasks.summary'), 'Eine vorhandene Übersicht wird nicht neu bezahlt.');
    }

    /**
     * Ein leeres Profil ergäbe einen leeren Lebenslauf und ein Anschreiben
     * voller Erfundenem — das muss auffallen, bevor ein Aufruf Geld kostet.
     */
    public function test_an_empty_profile_is_stopped_before_anything_is_dispatched(): void
    {
        Queue::fake();
        $user = $this->withAiKey(User::factory()->create());
        $session = GeneratorSession::factory()->for($user)->withPosting()->create();

        $this->actingAs($user)
            ->postJson(route('generator.generate', $session))
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Profil → Import'));

        Queue::assertNothingPushed();
    }

    public function test_generating_credits_the_research_time(): void
    {
        Queue::fake();
        ['user' => $user, 'session' => $session] = $this->profile();
        $session->forceFill(['timer_started_at' => now()->subMinutes(25)])->save();

        $this->actingAs($user)->postJson(route('generator.generate', $session))->assertOk();

        $session->refresh();
        $this->assertEqualsWithDelta(25 * 60, $session->pending_research_seconds, 5);
        $this->assertTrue($session->timer_started_at->isAfter(now()->subMinute()), 'Die Uhr läuft danach neu an.');
    }

    public function test_a_forgotten_tab_does_not_count_as_research(): void
    {
        Queue::fake();
        config(['cvcreater.research_timer.max_segment_seconds' => 3600]);
        ['user' => $user, 'session' => $session] = $this->profile();
        $session->forceFill(['timer_started_at' => now()->subDays(3)])->save();

        $this->actingAs($user)->postJson(route('generator.generate', $session))->assertOk();

        $this->assertSame(3600, $session->refresh()->pending_research_seconds);
    }

    /**
     * Prinzip 1: Im Lebenslauf steht kein Satz, den der Nutzer nicht selbst
     * geschrieben hat — bis auf das Statement.
     */
    public function test_the_cv_is_assembled_from_the_profile_word_for_word(): void
    {
        $profile = $this->profile();
        $this->fakeSelection([
            'projects' => [(string) $profile['thesis']->id],
            'hard_skills' => [(string) $profile['sql']->id, (string) $profile['python']->id],
            'soft_skills' => [(string) $profile['teamwork']->id],
        ]);

        $this->runTask($profile['user'], AiTaskType::CvSelection, $profile['session']);

        $cv = $profile['session']->documents()->where('type', DocumentType::Cv->value)->sole();
        $content = $cv->content;

        $this->assertSame('Fullstack-Entwicklerin mit Erfahrung in Datenprodukten.', $content['statement']);
        $this->assertSame('Julia Muster', $content['contact']['full_name']);
        $this->assertSame(['Frontend-Features umgesetzt'], $content['experience'][0]['bullets']);
        $this->assertSame(['SQL', 'Python'], array_column($content['skills']['hard'], 'name'), 'Die Reihenfolge der KI gilt.');

        // Ausgewählt zuerst und eingeschaltet, der Rest ausgeschaltet dahinter.
        $this->assertSame('Bachelorarbeit', $content['projects'][0]['title']);
        $this->assertTrue($content['projects'][0]['included']);
        $this->assertFalse($content['projects'][1]['included']);
    }

    public function test_invented_ids_are_discarded(): void
    {
        $profile = $this->profile();
        $this->fakeSelection([
            'hard_skills' => ['999999', (string) $profile['python']->id, 'Kubernetes'],
        ]);

        $this->runTask($profile['user'], AiTaskType::CvSelection, $profile['session']);

        $content = $profile['session']->documents()->sole()->content;
        $this->assertSame(['Python'], array_column($content['skills']['hard'], 'name'));
    }

    /**
     * Eine schlechte Auswahl darf nie stillschweigend einen ganzen Abschnitt
     * vom Lebenslauf entfernen.
     */
    public function test_a_useless_selection_keeps_the_whole_section(): void
    {
        $profile = $this->profile();
        $this->fakeSelection(['projects' => ['erfunden'], 'hard_skills' => []]);

        $this->runTask($profile['user'], AiTaskType::CvSelection, $profile['session']);

        $content = $profile['session']->documents()->sole()->content;
        $this->assertCount(2, array_filter(array_column($content['projects'], 'included')));
        $this->assertSame(['Python', 'SQL'], array_column($content['skills']['hard'], 'name'));
    }

    public function test_the_other_language_fills_in_field_by_field(): void
    {
        $profile = $this->profile();
        $profile['session']->forceFill(['language' => Language::English])->save();
        ProfileEntry::factory()->for($profile['user'])->germanOnly()->create();
        $this->fakeSelection();

        $this->runTask($profile['user'], AiTaskType::CvSelection, $profile['session']);

        $titles = array_column($profile['session']->documents()->sole()->content['experience'], 'title');
        $this->assertContains('Working Student, Software Engineering', $titles);
        $this->assertContains('Werkstudent Datenanalyse', $titles, 'Fehlt Englisch, steht der deutsche Text — übersetzt wird nicht.');
    }

    public function test_regenerating_replaces_the_document_and_counts_the_version(): void
    {
        $profile = $this->profile();

        $this->fakeSelection();
        $this->runTask($profile['user'], AiTaskType::CvSelection, $profile['session']);

        $this->fakeSelection(['statement' => 'Neue Fassung.']);
        $this->runTask($profile['user'], AiTaskType::CvSelection, $profile['session']);

        $cv = $profile['session']->documents()->sole();
        $this->assertSame(2, $cv->version);
        $this->assertSame('Neue Fassung.', $cv->content['statement']);
    }

    /**
     * Prinzip 3: In den Prompt gehen die Stilregeln, nicht das Beispiel.
     */
    public function test_the_letter_follows_the_style_rules_not_the_example(): void
    {
        $profile = $this->profile();
        $profile['user']->style()->fill([
            'example' => 'GEHEIMES BEISPIEL über die Firma Altbau AG.',
            'rules' => ['Kurze Hauptsätze.', 'Keine Floskeln.'],
        ])->save();

        $fake = Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'subject' => 'Bewerbung als Fullstack-Entwicklerin',
                'salutation' => 'Sehr geehrte Frau Dr. Meyer,',
                'paragraphs' => ['Erster Absatz.', '  ', 'Zweiter Absatz.'],
                'closing' => 'Mit freundlichen Grüßen',
            ]),
        ]);

        $this->runTask($profile['user'], AiTaskType::CoverLetter, $profile['session']);

        $fake->assertRequest(function (array $requests): void {
            $prompt = $requests[0]->prompt();
            $this->assertStringContainsString('- Kurze Hauptsätze.', $prompt);
            $this->assertStringNotContainsString('GEHEIMES BEISPIEL', $prompt);
            $this->assertStringContainsString('Frau Dr. Meyer', $prompt);
        });

        $content = $profile['session']->documents()->where('type', DocumentType::Letter->value)->sole()->content;
        $this->assertSame(['Erster Absatz.', 'Zweiter Absatz.'], $content['paragraphs']);
        $this->assertSame('MusterTech GmbH', $content['recipient']['company']);
        $this->assertSame("Musterstraße 1\n20095 Hamburg", $content['recipient']['address']);
        $this->assertSame('Julia Muster', $content['sender']['full_name']);
        $this->assertSame('Berlin', $content['place']);
    }

    /**
     * Stilregeln dürfen Aufbau und Länge bestimmen, aber nie das Verbot, etwas
     * zu erfinden, aushebeln — und ein verlangter Platzhalter bleibt stehen.
     */
    public function test_style_rules_shape_the_letter_but_never_the_facts(): void
    {
        $profile = $this->profile();
        $profile['user']->style()->fill(['rules' => ['Den Warum-wir-Absatz nie selbst schreiben: [WHY_US: später]']])->save();

        $fake = Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'subject' => 'Fullstack-Entwicklerin',
                'salutation' => 'Sehr geehrte Frau Dr. Meyer,',
                'paragraphs' => ['Einstieg.', '[WHY_US: später]', 'Schluss.'],
                'closing' => 'Mit freundlichen Grüßen',
            ]),
        ]);

        $this->runTask($profile['user'], AiTaskType::CoverLetter, $profile['session']);

        $fake->assertRequest(function (array $requests): void {
            $prompt = $requests[0]->prompt();
            $this->assertMatchesRegularExpression('/immer gelten.*Erfinde keine/s', $prompt);
            $this->assertStringContainsString('gelten die Stilregeln', $prompt);
        });

        $paragraphs = $profile['session']->documents()->sole()->content['paragraphs'];
        $this->assertSame('[WHY_US: später]', $paragraphs[1]);
    }

    public function test_a_letter_without_paragraphs_fails_instead_of_saving_an_empty_page(): void
    {
        $profile = $this->profile();
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'subject' => 'Betreff', 'salutation' => 'Hallo,', 'paragraphs' => [], 'closing' => 'Grüße',
            ]),
        ]);

        $this->runTask($profile['user'], AiTaskType::CoverLetter, $profile['session']);

        $this->assertSame('failed', $profile['user']->aiTasks()->sole()->status->value);
        $this->assertSame(0, $profile['session']->documents()->count());
    }

    public function test_the_preview_renders_the_stored_document(): void
    {
        $profile = $this->profile();
        $this->fakeSelection();
        $this->runTask($profile['user'], AiTaskType::CvSelection, $profile['session']);
        $cv = $profile['session']->documents()->sole();

        $this->actingAs($profile['user'])
            ->get(route('documents.preview', $cv))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee('Julia Muster')
            ->assertSee('Frontend-Features umgesetzt');
    }

    public function test_changing_the_layout_reaches_the_existing_cv(): void
    {
        $profile = $this->profile();
        $this->fakeSelection();
        $this->runTask($profile['user'], AiTaskType::CvSelection, $profile['session']);

        $this->actingAs($profile['user'])->patch(route('generator.update', $profile['session']), [
            'language' => 'de',
            'layout' => 'classic',
            'scope' => 'both',
        ]);

        $this->assertSame('classic', $profile['session']->documents()->sole()->layout->value);
    }

    public function test_documents_of_other_users_stay_private(): void
    {
        $profile = $this->profile();
        $this->fakeSelection();
        $this->runTask($profile['user'], AiTaskType::CvSelection, $profile['session']);
        $stranger = $this->withAiKey(User::factory()->create());

        $this->actingAs($stranger)
            ->get(route('documents.preview', $profile['session']->documents()->sole()))
            ->assertForbidden();

        $this->actingAs($stranger)
            ->postJson(route('generator.generate', $profile['session']))
            ->assertForbidden();
    }
}
