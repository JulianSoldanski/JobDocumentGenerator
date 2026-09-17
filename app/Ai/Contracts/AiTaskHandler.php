<?php

namespace App\Ai\Contracts;

use App\Models\AiTask;

/**
 * Ein einzelner KI-Anwendungsfall.
 *
 * Der Handler baut den Prompt, ruft das Modell und gibt das Ergebnis zurück.
 * Alles, was danach passiert — Dokumente anlegen, Bewerbung verknüpfen —
 * gehört ebenfalls hierher, damit ein Task für sich abgeschlossen ist.
 */
interface AiTaskHandler
{
    /**
     * @return array<string, mixed>
     */
    public function handle(AiTask $task): array;
}
