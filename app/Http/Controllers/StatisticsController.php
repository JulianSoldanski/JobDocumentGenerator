<?php

namespace App\Http\Controllers;

use App\Support\ApplicationStatistics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Funnel, Verweildauer, Absagen, Verlauf — alles aus der Stufen-Historie.
 */
class StatisticsController extends Controller
{
    public function index(Request $request): Response
    {
        $statistics = new ApplicationStatistics(
            $request->user()->applications()->with('stageEvents')->get()
        );

        return Inertia::render('statistics/index', [
            'summary' => $statistics->summary(),
            'funnel' => $statistics->funnel(),
            'durations' => $statistics->durations(),
            'rejections' => $statistics->rejections(),
            'months' => $statistics->months(),
        ]);
    }
}
