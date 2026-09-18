<?php

namespace Tests\Feature;

use App\Enums\AiTaskType;
use App\Enums\QueueItemStatus;
use App\Models\GeneratorSession;
use App\Models\ProfileEntry;
use App\Models\QueueItem;
use App\Models\User;
use App\Support\JobPosting\PostingFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Die Queue: vom Bookmarklet bis „erledigt".
 */
class QueueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * Die Namensauflösung gehört nicht in einen Test.
     */
    private function withoutDns(): void
    {
        $this->app->bind(PostingFetcher::class, fn (): PostingFetcher => new class extends PostingFetcher
        {
            /** @return array<int, string> */
            protected function addressesOf(string $host): array
            {
                return ['93.184.216.34'];
            }
        });
    }

    public function test_the_bookmarklet_drops_a_page_into_the_queue(): void
    {
        $this->get(route('queue.capture', [
            'token' => $this->user->captureToken(),
            'url' => 'https://www.linkedin.com/jobs/view/42/?trackingId=abc&utm_source=x',
            'title' => 'Product Owner | MusterTech',
        ]))->assertOk()->assertSee('In der Queue.')->assertSee('window.close', false);

        $item = $this->user->queueItems()->sole();
        $this->assertSame('https://www.linkedin.com/jobs/view/42/', $item->url);
        $this->assertSame('Product Owner | MusterTech', $item->title);
        $this->assertSame(QueueItemStatus::Open, $item->status);
    }

    public function test_the_same_job_from_another_source_lands_only_once(): void
    {
        $capture = fn (string $url) => $this->get(route('queue.capture', ['token' => $this->user->captureToken(), 'url' => $url]));

        $capture('https://www.linkedin.com/jobs/view/42/?trk=newsletter');
        $capture('https://www.linkedin.com/jobs/view/42/?gclid=123')->assertSee('Schon in der Queue');

        $this->actingAs($this->user)
            ->post(route('queue.store'), ['url' => 'https://www.linkedin.com/jobs/view/42/?utm_source=google'])
            ->assertSessionHasErrors('url');

        $this->assertSame(1, $this->user->queueItems()->count());
    }

    public function test_an_old_or_missing_token_captures_nothing(): void
    {
        $old = $this->user->captureToken();
        $this->actingAs($this->user)->post(route('queue.token'))->assertRedirect();

        $this->get(route('queue.capture', ['token' => $old, 'url' => 'https://example.com/job']))
            ->assertSee('nicht mehr gültig');
        $this->get(route('queue.capture', ['url' => 'https://example.com/job']))
            ->assertSee('nicht mehr gültig');

        $this->assertSame(0, QueueItem::count());
    }

    public function test_only_web_pages_can_be_queued(): void
    {
        $this->get(route('queue.capture', ['token' => $this->user->captureToken(), 'url' => 'javascript:alert(1)']))
            ->assertSee('lässt sich nicht merken');

        $this->actingAs($this->user)
            ->post(route('queue.store'), ['url' => 'ftp://example.com/job'])
            ->assertSessionHasErrors('url');

        $this->assertSame(0, QueueItem::count());
    }

    /**
     * Die Queue wird von hinten abgearbeitet: offene zuerst, die ältesten oben.
     */
    public function test_the_list_shows_open_items_oldest_first(): void
    {
        $newer = QueueItem::factory()->for($this->user)->create(['created_at' => now()->subDay()]);
        $older = QueueItem::factory()->for($this->user)->create(['created_at' => now()->subDays(5)]);
        $done = QueueItem::factory()->for($this->user)->create(['status' => QueueItemStatus::Done]);
        QueueItem::factory()->create();

        $this->actingAs($this->user)
            ->get(route('queue.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('items', 3)
                ->where('items.0.id', $older->id)
                ->where('items.1.id', $newer->id)
                ->where('items.2.id', $done->id)
                ->where('queueOpen', 2));
    }

    public function test_skipping_and_reopening(): void
    {
        $item = QueueItem::factory()->for($this->user)->create();

        $this->actingAs($this->user)->patch(route('queue.update', $item), ['status' => 'skipped']);
        $this->assertSame(QueueItemStatus::Skipped, $item->fresh()->status);
        $this->assertNotNull($item->fresh()->processed_at);

        $this->actingAs($this->user)->patch(route('queue.update', $item), ['status' => 'open']);
        $this->assertSame(QueueItemStatus::Open, $item->fresh()->status);
        $this->assertNull($item->fresh()->processed_at);
    }

    /**
     * „→ Generieren" öffnet die Stelle im Generator, die Anzeige schon geladen —
     * und ein zweiter Klick führt an denselben Arbeitsplatz zurück.
     */
    public function test_generate_opens_the_job_in_the_generator_with_the_posting_loaded(): void
    {
        $this->withoutDns();
        Http::fake(['*' => Http::response('<html><body><p>'.str_repeat('Wir suchen eine Product Ownerin. ', 12).'</p></body></html>')]);
        $item = QueueItem::factory()->for($this->user)->create(['url' => 'https://muster.example/job/42']);

        $response = $this->actingAs($this->user)->post(route('queue.generate', $item));

        $session = $this->user->generatorSessions()->sole();
        $response->assertRedirect(route('generator.show', $session));
        $this->assertSame('https://muster.example/job/42', $session->job_url);
        $this->assertStringContainsString('Product Ownerin', (string) $session->job_posting);
        $this->assertSame(QueueItemStatus::InProgress, $item->fresh()->status);

        $this->actingAs($this->user)->post(route('queue.generate', $item))->assertRedirect(route('generator.show', $session));
        $this->assertSame(1, $this->user->generatorSessions()->count());
    }

    public function test_a_page_that_cannot_be_loaded_still_opens_the_generator(): void
    {
        $this->withoutDns();
        Http::fake(['*' => Http::response('Gesperrt', 403)]);
        $item = QueueItem::factory()->for($this->user)->create(['url' => 'https://muster.example/job/42']);

        $this->actingAs($this->user)->post(route('queue.generate', $item))->assertRedirect();

        $session = $this->user->generatorSessions()->sole();
        $this->assertSame('https://muster.example/job/42', $session->job_url);
        $this->assertNull($session->job_posting, 'Die Anzeige fügt man dann von Hand ein.');
    }

    /**
     * Ist das Dokument erzeugt, ist der Eintrag abgearbeitet — verknüpft mit
     * der Bewerbung, die dabei entstand.
     */
    public function test_generating_documents_marks_the_item_done(): void
    {
        Queue::fake();
        $this->user = $this->withAiKey($this->user);
        ProfileEntry::factory()->for($this->user)->create();
        $item = QueueItem::factory()->for($this->user)->create(['status' => QueueItemStatus::InProgress]);
        $session = GeneratorSession::factory()->for($this->user)->withPosting()->create([
            'queue_item_id' => $item->id,
            'company' => 'MusterTech GmbH',
            'position' => 'Product Owner',
        ]);

        $this->actingAs($this->user)->postJson(route('generator.generate', $session))->assertOk();

        $item->refresh();
        $this->assertSame(QueueItemStatus::Done, $item->status);
        $this->assertSame($this->user->applications()->sole()->id, $item->application_id);
        $this->assertTrue($this->user->aiTasks()->where('type', AiTaskType::CvSelection)->exists());
    }

    public function test_items_of_other_users_stay_private(): void
    {
        $item = QueueItem::factory()->create();

        $this->actingAs($this->user)->patch(route('queue.update', $item), ['status' => 'skipped'])->assertForbidden();
        $this->actingAs($this->user)->post(route('queue.generate', $item))->assertForbidden();
        $this->actingAs($this->user)->delete(route('queue.destroy', $item))->assertForbidden();

        $this->assertModelExists($item);
    }
}
