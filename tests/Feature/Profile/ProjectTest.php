<?php

namespace Tests\Feature\Profile;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_project_needs_title_and_summary_in_one_language(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.projects.store'), [
            'translations' => ['de' => ['title' => '', 'summary' => '']],
        ])->assertSessionHasErrors(['translations.de.title', 'translations.de.summary']);

        $this->actingAs($user)->post(route('profile.projects.store'), [
            'tags' => ['react'],
            'translations' => [
                'en' => ['title' => 'Analytics dashboard', 'summary' => 'Usage data, visualised.'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $user->projects()->count());
    }

    public function test_long_form_fields_are_stored_per_language(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.projects.store'), [
            'client' => 'MusterTech GmbH',
            'period' => '03/2024 – 07/2024',
            'technologies' => ['React', 'PostgreSQL'],
            'translations' => [
                'de' => [
                    'title' => 'Dashboard',
                    'summary' => 'Kurz.',
                    'contributions' => "Datenmodell entworfen\nFrontend umgesetzt",
                ],
            ],
        ])->assertSessionHasNoErrors();

        $project = $user->projects()->sole();
        $this->assertTrue($project->hasLongForm());
        $this->assertSame(['Datenmodell entworfen', 'Frontend umgesetzt'], $project->translations['de']['contributions']);
    }

    public function test_flags_can_be_toggled_individually(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user)
            ->patch(route('profile.projects.flags', $project), ['in_project_list' => false])
            ->assertRedirect();

        $project->refresh();
        $this->assertFalse($project->in_project_list);
        $this->assertTrue($project->is_visible, 'Der andere Schalter bleibt unberührt.');
    }

    public function test_projects_of_other_users_are_out_of_reach(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        $this->actingAs($stranger)
            ->delete(route('profile.projects.destroy', $project))
            ->assertForbidden();

        $this->assertModelExists($project);
    }
}
