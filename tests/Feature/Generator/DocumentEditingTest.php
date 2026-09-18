<?php

namespace Tests\Feature\Generator;

use App\Documents\CvAssembler;
use App\Enums\DocumentType;
use App\Enums\ProfileSection;
use App\Models\Document;
use App\Models\GeneratorSession;
use App\Models\ProfileEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nach dem Generieren wird von Hand nachgeschärft — am Dokument, nicht am
 * Profil.
 */
class DocumentEditingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private GeneratorSession $session;

    private ProfileEntry $job;

    private ProfileEntry $degree;

    private Project $first;

    private Project $second;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->session = GeneratorSession::factory()->for($this->user)->withPosting()->create();
        $this->job = ProfileEntry::factory()->for($this->user)->create();
        $this->degree = ProfileEntry::factory()->for($this->user)->education()->create();
        ProfileEntry::factory()->for($this->user)->skill(ProfileSection::HardSkill, 'Python', 'Python')->create();
        $this->first = Project::factory()->for($this->user)->create();
        $this->second = Project::factory()->for($this->user)->create([
            'position' => 1,
            'translations' => ['de' => ['title' => 'Bachelorarbeit', 'summary' => 'Prototyp.'], 'en' => []],
        ]);
    }

    private function cv(): Document
    {
        $assembler = new CvAssembler($this->user, $this->session->language);

        return $this->session->storeDocument(DocumentType::Cv, $assembler->assemble(
            'Ein Statement.',
            [(string) $this->first->id],
            array_column($assembler->candidates()['hard_skills'], 'id'),
            [],
        ));
    }

    private function letter(): Document
    {
        return $this->session->storeDocument(DocumentType::Letter, [
            'subject' => 'Betreff',
            'salutation' => 'Sehr geehrte Damen und Herren,',
            'paragraphs' => ['Einstieg.', '[WHY_US: Manuell einzufügen nach Recherche]', 'Schluss.'],
            'closing' => 'Mit freundlichen Grüßen',
        ]);
    }

    public function test_the_editor_loads_the_stored_content(): void
    {
        $cv = $this->cv();

        $this->actingAs($this->user)
            ->getJson(route('documents.show', $cv))
            ->assertOk()
            ->assertJsonPath('content.statement', 'Ein Statement.')
            ->assertJsonPath('content.experience.0.bullets', ['Frontend-Features umgesetzt']);
    }

    public function test_the_cv_takes_every_editable_part(): void
    {
        $cv = $this->cv();

        $this->actingAs($this->user)->patchJson(route('documents.update', $cv), [
            'statement' => 'Geschärftes Statement.',
            'experience' => [['id' => (string) $this->job->id, 'bullets' => ['Neuer Punkt', '  ', 'Zweiter Punkt']]],
            'education' => [['id' => (string) $this->degree->id, 'details' => ['Schwerpunkt Daten']]],
            'projects' => [
                ['id' => (string) $this->second->id, 'included' => true],
                ['id' => (string) $this->first->id, 'included' => false],
            ],
            'skills' => [
                'hard' => ['SQL', 'Python'],
                'soft' => ['Moderation'],
                'languages' => [['name' => 'Deutsch', 'level' => 'Muttersprache'], ['name' => '', 'level' => 'C1']],
            ],
        ])->assertOk()->assertJson(['version' => 2, 'edited' => true]);

        $content = $cv->fresh()->content;
        $this->assertSame('Geschärftes Statement.', $content['statement']);
        $this->assertSame(['Neuer Punkt', 'Zweiter Punkt'], $content['experience'][0]['bullets']);
        $this->assertSame(['Schwerpunkt Daten'], $content['education'][0]['details']);
        $this->assertSame(['Bachelorarbeit', 'Internes Analytics-Dashboard'], array_column($content['projects'], 'title'));
        $this->assertSame([true, false], array_column($content['projects'], 'included'));
        $this->assertSame(['SQL', 'Python'], array_column($content['skills']['hard'], 'name'));
        $this->assertSame([['name' => 'Deutsch', 'level' => 'Muttersprache']], $content['skills']['languages']);
        $this->assertTrue($content['edited']);
    }

    /**
     * Titel, Firmen und Daten kommen aus dem Profil — der Editor kann sie
     * nicht umschreiben, auch nicht mit einem präparierten Aufruf.
     */
    public function test_facts_stay_as_the_profile_has_them(): void
    {
        $cv = $this->cv();

        $this->actingAs($this->user)->patchJson(route('documents.update', $cv), [
            'experience' => [
                ['id' => (string) $this->job->id, 'bullets' => ['Punkt'], 'title' => 'Geschäftsführer', 'organization' => 'Erfunden AG'],
                ['id' => '999999', 'bullets' => ['Unbekannt']],
            ],
        ])->assertOk();

        $entry = $cv->fresh()->content['experience'][0];
        $this->assertSame('Werkstudent Softwareentwicklung', $entry['title']);
        $this->assertSame($this->job->organization, $entry['organization']);
        $this->assertCount(1, $cv->fresh()->content['experience']);
    }

    /**
     * Ein Projekt, das im Formular fehlt, verschwindet nicht — es bleibt
     * ausgeschaltet am Ende.
     */
    public function test_a_project_missing_from_the_form_is_kept_switched_off(): void
    {
        $cv = $this->cv();

        $this->actingAs($this->user)->patchJson(route('documents.update', $cv), [
            'projects' => [['id' => (string) $this->second->id, 'included' => true]],
        ])->assertOk();

        $projects = $cv->fresh()->content['projects'];
        $this->assertCount(2, $projects);
        $this->assertSame((string) $this->first->id, $projects[1]['id']);
        $this->assertFalse($projects[1]['included']);
    }

    public function test_editing_the_cv_leaves_the_profile_alone(): void
    {
        $cv = $this->cv();

        $this->actingAs($this->user)->patchJson(route('documents.update', $cv), [
            'experience' => [['id' => (string) $this->job->id, 'bullets' => ['Nur für diese Stelle']]],
        ])->assertOk();

        $this->assertSame(['Frontend-Features umgesetzt'], $this->job->fresh()->translations['de']['bullets']);
    }

    public function test_the_letter_takes_its_paragraphs(): void
    {
        $letter = $this->letter();

        $this->actingAs($this->user)->patchJson(route('documents.update', $letter), [
            'subject' => 'Neuer Betreff',
            'paragraphs' => ['Einstieg.', 'Weil ihr Logistik neu denkt.', '', 'Schluss.'],
        ])->assertOk();

        $content = $letter->fresh()->content;
        $this->assertSame('Neuer Betreff', $content['subject']);
        $this->assertSame(['Einstieg.', 'Weil ihr Logistik neu denkt.', 'Schluss.'], $content['paragraphs']);
        $this->assertSame('Mit freundlichen Grüßen', $content['closing'], 'Was nicht mitkommt, bleibt.');
    }

    public function test_the_preview_shows_the_edited_version(): void
    {
        $cv = $this->cv();

        $this->actingAs($this->user)->patchJson(route('documents.update', $cv), [
            'statement' => 'Handgeschriebenes Statement.',
        ])->assertOk();

        $this->actingAs($this->user)
            ->get(route('documents.preview', $cv))
            ->assertSee('Handgeschriebenes Statement.');
    }

    /**
     * Das Profil gibt vor, ob eine Station leiser erscheint; jeder Lebenslauf
     * kann das für sich ändern.
     */
    public function test_a_position_can_appear_less_prominent(): void
    {
        $this->job->update(['is_subtle' => true]);
        $cv = $this->cv();

        $this->assertTrue($cv->content['experience'][0]['subtle']);
        $this->actingAs($this->user)
            ->get(route('documents.preview', $cv))
            ->assertSee('class="entry subtle"', false);

        $this->actingAs($this->user)->patchJson(route('documents.update', $cv), [
            'experience' => [['id' => (string) $this->job->id, 'bullets' => ['Punkt'], 'subtle' => false]],
        ])->assertOk();

        $this->assertFalse($cv->fresh()->content['experience'][0]['subtle']);
        $this->assertTrue($this->job->fresh()->is_subtle, 'Das Profil bleibt, wie es ist.');
        $this->actingAs($this->user)
            ->get(route('documents.preview', $cv))
            ->assertDontSee('class="entry subtle"', false);
    }

    /**
     * Ausgeblendet heißt nicht gelöscht: Der Text bleibt, nur der Abschnitt
     * fehlt im Lebenslauf.
     */
    public function test_the_profile_statement_can_be_hidden(): void
    {
        $cv = $this->cv();

        $this->actingAs($this->user)->patchJson(route('documents.update', $cv), [
            'statement' => 'Handgeschriebenes Statement.',
            'statement_included' => false,
        ])->assertOk();

        $this->assertSame('Handgeschriebenes Statement.', $cv->fresh()->content['statement']);
        $this->actingAs($this->user)
            ->get(route('documents.preview', $cv))
            ->assertDontSee('Handgeschriebenes Statement.');

        $this->actingAs($this->user)->patchJson(route('documents.update', $cv), [
            'statement_included' => true,
        ])->assertOk();

        $this->actingAs($this->user)
            ->get(route('documents.preview', $cv))
            ->assertSee('Handgeschriebenes Statement.');
    }

    /**
     * Die Oberfläche fragt vor dem Überschreiben nach — dafür muss sie wissen,
     * dass Hand angelegt wurde. Neu generieren setzt das zurück.
     */
    public function test_the_session_knows_which_documents_were_edited(): void
    {
        $cv = $this->cv();
        $this->actingAs($this->user)->patchJson(route('documents.update', $cv), ['statement' => 'Von Hand.']);

        $this->actingAs($this->user)
            ->get(route('generator.show', $this->session))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('session.documents.cv.edited', true));

        $this->cv();

        $this->assertArrayNotHasKey('edited', $cv->fresh()->content);
    }

    public function test_documents_of_other_users_stay_private(): void
    {
        $cv = $this->cv();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->getJson(route('documents.show', $cv))->assertForbidden();
        $this->actingAs($stranger)->patchJson(route('documents.update', $cv), ['statement' => 'Fremd.'])->assertForbidden();

        $this->assertSame('Ein Statement.', $cv->fresh()->content['statement']);
    }
}
