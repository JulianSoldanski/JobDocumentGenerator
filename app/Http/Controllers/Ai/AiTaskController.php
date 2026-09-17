<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiTask;
use Illuminate\Http\JsonResponse;

/**
 * Der Stand eines KI-Aufrufs. Das Frontend fragt hier ab, statt auf die
 * Antwort zu warten.
 */
class AiTaskController extends Controller
{
    public function show(AiTask $aiTask): JsonResponse
    {
        $this->authorize('view', $aiTask);

        return response()->json([
            'id' => $aiTask->id,
            'type' => $aiTask->type->value,
            'status' => $aiTask->status->value,
            'result' => $aiTask->result,
            'error' => $aiTask->error,
        ]);
    }
}
