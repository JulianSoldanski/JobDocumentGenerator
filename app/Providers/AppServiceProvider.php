<?php

namespace App\Providers;

use App\Models\AiSetting;
use App\Models\AiTask;
use App\Models\Application;
use App\Models\ContactDetail;
use App\Models\Document;
use App\Models\GeneratorSession;
use App\Models\ProfileEntry;
use App\Models\Project;
use App\Models\QueueItem;
use App\Models\WritingStyle;
use App\Policies\OwnerPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerPolicies();
        $this->configureRateLimits();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Daten gehören je Nutzer — jedes dieser Modelle wird gegen dieselbe
     * Eigentumsfrage geprüft.
     */
    protected function registerPolicies(): void
    {
        $owned = [
            AiSetting::class,
            AiTask::class,
            Application::class,
            ContactDetail::class,
            Document::class,
            GeneratorSession::class,
            ProfileEntry::class,
            Project::class,
            QueueItem::class,
            WritingStyle::class,
        ];

        foreach ($owned as $model) {
            Gate::policy($model, OwnerPolicy::class);
        }
    }

    /**
     * KI-Aufrufe kosten echtes Geld — ein Limit je Nutzer und Minute.
     */
    protected function configureRateLimits(): void
    {
        RateLimiter::for('ai', fn (Request $request) => Limit::perMinute(
            (int) config('cvcreater.ai.rate_limit')
        )->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
    }
}
