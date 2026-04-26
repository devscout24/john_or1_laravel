<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Content;

class EpisodeStatsController extends Controller
{
    public function index()
    {
        // Fetch all series-type content with episodes and stats
        $series = Content::where('type', 'series')
            ->with(['episodes' => function ($q) {
                $q->withCount('likes', 'savedByUsers')
                    ->with(['gifts' => function ($g) {
                        $g->with('sender');
                    }]);
            }])
            ->get();

        return view('backend.episode_stats.index', compact('series'));
    }
}
