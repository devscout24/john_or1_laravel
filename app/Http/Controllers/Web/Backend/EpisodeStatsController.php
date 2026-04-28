<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\Episode;
use Yajra\DataTables\DataTables;

class EpisodeStatsController extends Controller
{
    public function index(Request $request)
    {
        $seriesList = Content::query()
            ->where('type', 'series')
            ->orderBy('title')
            ->get(['id', 'title']);

        $filters = [
            'series_id' => $request->integer('series_id') ?: null,
            'episode_id' => $request->integer('episode_id') ?: null,
            'search' => trim((string) $request->input('search', '')),
            'sort' => $request->input('sort', 'top_likes'),
        ];

        $episodeOptions = collect();
        if ($filters['series_id']) {
            $episodeOptions = Episode::query()
                ->where('content_id', $filters['series_id'])
                ->orderBy('episode_number')
                ->get(['id', 'title', 'episode_number']);
        }

        return view('backend.episode_stats.index', compact('seriesList', 'episodeOptions', 'filters'));
    }

    public function data(Request $request)
    {
        $query = Episode::query()
            ->whereHas('content', function ($q) {
                $q->where('type', 'series');
            })
            ->with([
                'content:id,title',
                'gifts' => function ($giftQuery) {
                    $giftQuery->latest()->with('sender:id,name')->limit(5);
                },
            ])
            ->withCount([
                'likes',
                'savedByUsers as saves_count',
                'gifts',
            ]);

        $seriesId = $request->integer('series_id') ?: null;
        $episodeId = $request->integer('episode_id') ?: null;
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', 'top_likes');

        if ($seriesId) {
            $query->where('content_id', $seriesId);
        }

        if ($episodeId) {
            $query->where('id', $episodeId);
        }

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', '%' . $search . '%')
                    ->orWhereHas('content', function ($contentQuery) use ($search) {
                        $contentQuery->where('title', 'like', '%' . $search . '%');
                    });
            });
        }

        switch ($sort) {
            case 'top_saves':
                $query->orderByDesc('saves_count');
                break;
            case 'top_gifts':
                $query->orderByDesc('gifts_count');
                break;
            case 'newest':
                $query->latest('id');
                break;
            case 'oldest':
                $query->oldest('id');
                break;
            case 'top_likes':
            default:
                $query->orderByDesc('likes_count');
                break;
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('series_title', function ($episode) {
                return e($episode->content?->title ?? '-');
            })
            ->addColumn('episode_title', function ($episode) {
                return '<div class="fw-semibold">#' . e($episode->episode_number) . ' - ' . e($episode->title) . '</div>';
            })
            ->addColumn('likes_badge', function ($episode) {
                return '<span class="badge bg-danger-subtle text-danger">' . (int) $episode->likes_count . '</span>';
            })
            ->addColumn('saves_badge', function ($episode) {
                return '<span class="badge bg-primary-subtle text-primary">' . (int) $episode->saves_count . '</span>';
            })
            ->addColumn('gifts_badge', function ($episode) {
                return '<span class="badge bg-success-subtle text-success">' . (int) $episode->gifts_count . '</span>';
            })
            ->addColumn('recent_senders', function ($episode) {
                if ($episode->gifts->isEmpty()) {
                    return '<span class="text-muted">-</span>';
                }

                $html = '<div class="d-flex flex-wrap gap-1">';
                foreach ($episode->gifts as $gift) {
                    $html .= '<span class="badge bg-light text-dark border">' . e($gift->sender?->name ?? 'Unknown') . '</span>';
                }
                $html .= '</div>';

                return $html;
            })
            ->addColumn('action', function ($episode) {
                if ((int) $episode->gifts_count === 0) {
                    return '<span class="text-muted">-</span>';
                }

                return '<button type="button" class="btn btn-outline-primary btn-sm js-view-gifts" data-url="' .
                    route('episode.stats.gifts', $episode->id) . '"><i class="ti ti-gift me-1"></i>View Gifts</button>';
            })
            ->rawColumns(['episode_title', 'likes_badge', 'saves_badge', 'gifts_badge', 'recent_senders', 'action'])
            ->make(true);
    }

    public function giftsHistory(Episode $episode)
    {
        if (! $episode->content || $episode->content->type !== 'series') {
            return response()->json([
                'message' => 'Invalid episode.',
            ], 404);
        }

        $gifts = $episode->gifts()
            ->with('sender:id,name')
            ->latest()
            ->get(['id', 'sender_user_id', 'gift_key', 'gift_label', 'coins', 'created_at']);

        $data = $gifts->map(function ($gift) {
            return [
                'sender' => $gift->sender?->name ?? 'Unknown',
                'gift_type' => $gift->gift_label ?: ($gift->gift_key ?: 'Gift'),
                'coins' => (int) $gift->coins,
                'sent_at' => $gift->created_at?->format('d M Y, h:i A'),
            ];
        });

        return response()->json([
            'episode_title' => $episode->title,
            'episode_number' => $episode->episode_number,
            'total' => $data->count(),
            'gifts' => $data,
        ]);
    }
}
