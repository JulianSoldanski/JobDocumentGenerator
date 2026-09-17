<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Der Arbeitsplatz ist der Generator — ein eigenes Dashboard gibt es nicht.
     */
    public function test_authenticated_users_land_in_the_generator(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('dashboard'));

        $response->assertRedirect('/generator');
    }
}
