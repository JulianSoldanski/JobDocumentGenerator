<?php

namespace App\Ai;

use App\Ai\Contracts\AiTaskHandler;
use App\Ai\Tasks\CvImportHandler;
use App\Ai\Tasks\ProjectDraftHandler;
use App\Ai\Tasks\StyleAnalysisHandler;
use App\Enums\AiTaskType;
use Illuminate\Contracts\Container\Container;

/**
 * Welcher Handler zu welchem Aufgabentyp gehört.
 */
class TaskRegistry
{
    /** @var array<string, class-string<AiTaskHandler>> */
    private const HANDLERS = [
        AiTaskType::StyleAnalysis->value => StyleAnalysisHandler::class,
        AiTaskType::ProjectDraft->value => ProjectDraftHandler::class,
        AiTaskType::CvImport->value => CvImportHandler::class,
    ];

    public function __construct(private readonly Container $container) {}

    public function handlerFor(AiTaskType $type): AiTaskHandler
    {
        $handler = self::HANDLERS[$type->value] ?? null;

        if ($handler === null) {
            throw new AiException("Für den Aufgabentyp \"{$type->value}\" gibt es noch keinen Handler.");
        }

        /** @var AiTaskHandler $instance */
        $instance = $this->container->make($handler);

        return $instance;
    }
}
