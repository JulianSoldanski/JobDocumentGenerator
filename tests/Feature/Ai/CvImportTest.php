<?php

namespace Tests\Feature\Ai;

use App\Ai\TaskRegistry;
use App\Enums\AiTaskType;
use App\Enums\Language;
use App\Enums\ProfileSection;
use App\Jobs\RunAiTask;
use App\Models\ProfileEntry;
use App\Models\User;
use App\Support\Pdf\PdfText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use RuntimeException;
use Tests\TestCase;

class CvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_pdf_upload_starts_a_background_task(): void
    {
        Queue::fake();
        $this->mock(PdfText::class)->shouldReceive('extract')->once()->andReturn('Lebenslauf-Text');

        $user = $this->withAiKey(User::factory()->create());

        $this->actingAs($user)
            ->postJson(route('profile.import.store'), [
                'file' => UploadedFile::fake()->create('lebenslauf.pdf', 120, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonStructure(['task_id']);

        $this->assertSame(AiTaskType::CvImport, $user->aiTasks()->sole()->type);
    }

    public function test_an_unreadable_pdf_says_so_instead_of_failing_silently(): void
    {
        Queue::fake();
        $this->mock(PdfText::class)
            ->shouldReceive('extract')
            ->andThrow(new RuntimeException('Aus dieser PDF-Datei ließ sich kein Text lesen.'));

        $this->actingAs($this->withAiKey(User::factory()->create()))
            ->postJson(route('profile.import.store'), [
                'file' => UploadedFile::fake()->create('scan.pdf', 120, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJson(['message' => 'Aus dieser PDF-Datei ließ sich kein Text lesen.']);

        Queue::assertNothingPushed();
    }

    public function test_only_pdf_files_are_accepted(): void
    {
        $this->actingAs($this->withAiKey(User::factory()->create()))
            ->postJson(route('profile.import.store'), [
                'file' => UploadedFile::fake()->create('lebenslauf.docx', 120),
            ])
            ->assertStatus(422);
    }

    public function test_the_extracted_structure_keeps_the_document_language(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'language' => 'en',
                'contact' => ['full_name' => 'Max Mustermann', 'city' => 'Berlin'],
                'experience' => [[
                    'title' => 'Working Student',
                    'organization' => 'MusterTech',
                    'start_month' => '2024-09',
                    'end_month' => 'unbekannt',
                    'is_current' => 'true',
                    'bullets' => ['Shipped features'],
                ]],
                'education' => [], 'hard_skills' => ['Python'], 'soft_skills' => [],
                'languages' => [['name' => 'German', 'level' => 'Native']], 'projects' => [],
            ]),
        ]);

        $user = $this->withAiKey(User::factory()->create());
        $task = $user->aiTasks()->create(['type' => AiTaskType::CvImport, 'input' => ['text' => 'CV']]);

        (new RunAiTask($task->id))->handle(app(TaskRegistry::class));

        $result = $task->refresh()->result;
        $this->assertSame('en', $result['language']);
        $this->assertSame('2024-09', $result['experience'][0]['start_month']);
        $this->assertNull($result['experience'][0]['end_month'], 'Ein unbrauchbares Datum wird verworfen, nicht geraten.');
        $this->assertTrue($result['experience'][0]['is_current']);
    }

    public function test_applying_adds_entries_in_the_documents_language(): void
    {
        $user = $this->withAiKey(User::factory()->create());

        $this->actingAs($user)->post(route('profile.import.apply'), [
            'language' => 'en',
            'contact' => ['full_name' => 'Max Mustermann', 'city' => 'Berlin'],
            'experience' => [[
                'title' => 'Working Student',
                'organization' => 'MusterTech',
                'location' => 'Berlin',
                'start_month' => '2024-09',
                'is_current' => true,
                'bullets' => ['Shipped features'],
            ]],
            'education' => [],
            'hard_skills' => ['Python', 'SQL'],
            'soft_skills' => [],
            'languages' => [['name' => 'German', 'level' => 'Native']],
            'projects' => [['title' => 'Dashboard', 'summary' => 'Usage data.', 'tags' => ['react']]],
        ])->assertRedirect(route('profile.experience'));

        $entry = $user->profileEntries()->section(ProfileSection::Experience)->sole();
        $this->assertSame('Working Student', $entry->translations['en']['title']);
        $this->assertSame('', $entry->translations['de']['title'], 'Die andere Sprache bleibt leer statt übersetzt.');
        $this->assertSame([Language::German], $entry->missingLanguages());

        $this->assertSame(2, $user->profileEntries()->section(ProfileSection::HardSkill)->count());
        $this->assertSame(1, $user->projects()->count());
        $this->assertSame('Max Mustermann', $user->contact()->refresh()->full_name);
    }

    public function test_applying_never_overwrites_what_is_already_there(): void
    {
        $user = $this->withAiKey(User::factory()->create());
        $user->contact()->fill(['full_name' => 'Julian', 'city' => ''])->save();
        ProfileEntry::factory()->for($user)->skill(ProfileSection::HardSkill, 'Python', 'Python')->create();
        $existing = ProfileEntry::factory()->for($user)->create();

        $this->actingAs($user)->post(route('profile.import.apply'), [
            'language' => 'de',
            'contact' => ['full_name' => 'Max Mustermann', 'city' => 'Berlin'],
            'hard_skills' => ['python', 'Rust'],
            'experience' => [],
            'education' => [],
            'soft_skills' => [],
            'languages' => [],
            'projects' => [],
        ])->assertRedirect();

        $contact = $user->contact()->refresh();
        $this->assertSame('Julian', $contact->full_name, 'Ein gefülltes Feld bleibt stehen.');
        $this->assertSame('Berlin', $contact->city, 'Ein leeres Feld wird ergänzt.');

        $skills = $user->profileEntries()->section(ProfileSection::HardSkill)->get();
        $this->assertSame(2, $skills->count(), 'Ein bereits vorhandener Skill kommt nicht doppelt.');
        $this->assertModelExists($existing);
    }
}
