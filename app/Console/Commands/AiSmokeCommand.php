<?php

namespace App\Console\Commands;

use App\Ai\AiClient;
use App\Ai\Schemas\StyleSchema;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

/**
 * Prüft mit einem echten Aufruf, ob der Anbieter erreichbar ist — und vor
 * allem, ob der Denkmodus wirklich aus ist. Ein langer Prompt, der leer
 * zurückkommt, ist das typische Zeichen dafür, dass er noch an ist.
 */
class AiSmokeCommand extends Command
{
    protected $signature = 'ai:smoke {--user= : E-Mail des Nutzers, dessen Zugang verwendet wird}';

    protected $description = 'Sendet einen echten Prompt an das konfigurierte Modell und prüft die Antwort';

    public function handle(AiClient $ai): int
    {
        // Der Schlüssel hängt am Konto; ohne Angabe greift der aus der
        // Serverkonfiguration, falls dort einer steht.
        if ($email = $this->option('user')) {
            $user = User::where('email', $email)->first();

            if ($user === null) {
                $this->components->error("Kein Nutzer mit der E-Mail {$email}.");

                return self::FAILURE;
            }

            $ai = $ai->using($user);
            $this->line("Zugang von: {$user->email}");
        }

        $this->line("Anbieter: {$ai->provider()} · Modell: {$ai->model()}");
        $this->line('Optionen: '.json_encode($ai->providerOptions(), JSON_UNESCAPED_UNICODE));

        $example = str_repeat(
            'Die ausgeschriebene Stelle passt zu dem, woran ich arbeite. '.
            'Ich habe ein internes Dashboard von der Skizze bis zum Rollout begleitet. ',
            40
        );

        try {
            $result = $ai->structured(
                $ai->prompts()->render('style_analysis', ['example' => $example]),
                StyleSchema::make(),
                2048,
            );
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $rules = is_array($result['rules'] ?? null) ? $result['rules'] : [];

        if ($rules === []) {
            $this->components->error('Die Antwort enthielt keine Regeln.');

            return self::FAILURE;
        }

        $this->components->info(count($rules).' Regeln erhalten:');
        foreach ($rules as $rule) {
            $this->line('  · '.$rule);
        }

        return self::SUCCESS;
    }
}
