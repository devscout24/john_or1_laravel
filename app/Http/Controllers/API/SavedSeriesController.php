<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Favorite;
use App\Services\DailyTaskService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SavedSeriesController extends Controller
{
    use ApiResponse;

    /**
     * Saved series list for authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated', 401);
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['series', 'movie', 'all'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $validated['search'] ?? null;
        $type = $validated['type'] ?? 'series';
        $limit = $validated['limit'] ?? 20;

        $userId = (int) $user->id;

        $favoritesQuery = Favorite::query()
            ->where('user_id', $userId)
            ->whereHas('content', function ($query) use ($search, $type) {
                $query->where('is_active', true);

                if ($type !== 'all') {
                    $query->where('type', $type);
                }

                if ($search) {
                    $query->where(function ($nested) use ($search) {
                        $nested->where('title', 'like', '%' . $search . '%')
                            ->orWhere('description', 'like', '%' . $search . '%');
                    });
                }
            })
            ->with([
                'content' => function ($query) {
                    $query->with(['categories:id,name,slug'])
                        ->withCount('watchHistories');
                },
            ])
            ->latest();

        $favorites = $favoritesQuery->limit($limit)->get();

        $items = $favorites->map(function ($favorite) {
            $content = $favorite->content;
            $primaryCategory = $content->categories->first();
            $viewsCount = (int) ($content->watch_histories_count ?? 0);

            return [
                'favorite_id' => $favorite->id,
                'content_id' => $content->id,
                'title' => $content->title,
                'description' => $content->description,
                'type' => $content->type,
                'thumbnail' => $content->thumbnail,
                'thumbnail_url' => $this->buildMediaUrl($content->thumbnail),
                'banner' => $content->banner,
                'banner_url' => $this->buildMediaUrl($content->banner),
                'genre' => $primaryCategory?->name,
                'genre_slug' => $primaryCategory?->slug,
                'views_count' => $viewsCount,
                'views_label' => $this->formatViewsLabel($viewsCount),
                'is_saved' => true,
                'saved_at' => $favorite->created_at?->toIso8601String(),
            ];
        })->values();

        $payload = [
            'filters' => [
                'search' => $search,
                'type' => $type,
                'limit' => $limit,
            ],
            'total' => $items->count(),
            'items' => $items,
        ];

        return $this->success($payload, 'Saved series fetched successfully');
    }

    /**
     * Add content to saved list.
     */
    public function add(int $contentId)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated', 401);
        }

        $userId = (int) $user->id;

        $content = Content::query()
            ->where('is_active', true)
            ->find($contentId);

        if (! $content) {
            return $this->error([], 'Content not found', 404);
        }

        $existingFavorite = Favorite::query()
            ->where('user_id', $userId)
            ->where('content_id', $contentId)
            ->first();

        if (! $existingFavorite) {
            Favorite::create([
                'user_id' => $userId,
                'content_id' => $contentId,
            ]);

            app(DailyTaskService::class)->incrementProgress($userId, 'follow_series', 1);
        }

        $savedCount = Favorite::query()
            ->where('user_id', $userId)
            ->count();

        return $this->success([
            'content_id' => $contentId,
            'is_saved' => true,
            'already_saved' => (bool) $existingFavorite,
            'saved_count' => $savedCount,
        ], 'Content saved successfully');
    }

    /**
     * Remove content from saved list.
     */
    public function remove(int $contentId)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated', 401);
        }

        $userId = (int) $user->id;

        $content = Content::query()
            ->where('is_active', true)
            ->find($contentId);

        if (! $content) {
            return $this->error([], 'Content not found', 404);
        }

        $removed = Favorite::query()
            ->where('user_id', $userId)
            ->where('content_id', $contentId)
            ->delete() > 0;

        $savedCount = Favorite::query()
            ->where('user_id', $userId)
            ->count();

        return $this->success([
            'content_id' => $contentId,
            'is_saved' => false,
            'was_saved' => $removed,
            'saved_count' => $savedCount,
        ], 'Content removed from saved list');
    }

    private function buildMediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }

    private function formatViewsLabel(int $views): string
    {
        if ($views >= 1000000) {
            return rtrim(rtrim(number_format($views / 1000000, 1), '0'), '.') . 'M views';
        }

        if ($views >= 1000) {
            return rtrim(rtrim(number_format($views / 1000, 1), '0'), '.') . 'K views';
        }

        return $views . ' views';
    }
}
