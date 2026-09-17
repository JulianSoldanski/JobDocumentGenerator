<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Ein Nutzer mit hinterlegtem KI-Zugang — ohne ihn lehnen die
     * KI-Endpunkte bewusst ab.
     */
    protected function withAiKey(User $user, string $key = 'test-schluessel'): User
    {
        $user->aiAccess()->fill([
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
            'api_key' => $key,
        ])->save();

        return $user;
    }
}
