<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AdSession;
use App\Models\Category;
use App\Models\CoinTransaction;
use App\Models\Content;
use App\Models\Episode;
use App\Models\Favorite;
use App\Models\Section;
use App\Models\Subscription;
use App\Models\WatchHistory;
use App\Traits\ApiResponse;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DiscoverController extends Controller
{
    use ApiResponse;

    /**
     * Discover page API payload.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['movie', 'series', 'all'])],
            'section_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $search = $validated['search'] ?? null;
        $category = $validated['category'] ?? null;
        $type = $validated['type'] ?? 'all';
        $sectionLimit = $validated['section_limit'] ?? 12;

        $discoverSectionKeys = [
            'trending' => 'trending_now',
            'new_releases' => 'new_releases',
            'recommended' => 'you_might_also_like',
        ];

        $sections = Section::query()
            ->where('is_active', true)
            ->whereIn('slug', array_keys($discoverSectionKeys))
            ->orderBy('order')
            ->get()
            ->keyBy('slug');

        $sectionContentMap = [];

        foreach ($discoverSectionKeys as $sectionSlug => $responseKey) {
            $section = $sections->get($sectionSlug);

            if (! $section) {
                $sectionContentMap[$responseKey] = [
                    'id' => null,
                    'name' => null,
                    'slug' => $sectionSlug,
                    'items' => [],
                ];

                continue;
            }

            $contentQuery = $section->contents()
                ->where('contents.is_active', true)
                ->with(['categories:id,name,slug,icon'])
                ->orderBy('section_contents.order');

            if ($search) {
                $contentQuery->where(function ($query) use ($search) {
                    $query->where('contents.title', 'like', '%' . $search . '%')
                        ->orWhere('contents.description', 'like', '%' . $search . '%');
                });
            }

            if ($type !== 'all') {
                $contentQuery->where('contents.type', $type);
            }

            if ($category) {
                $contentQuery->whereHas('categories', function ($query) use ($category) {
                    $query->where('categories.slug', $category)
                        ->orWhere('categories.name', 'like', '%' . $category . '%');
                });
            }

            $contents = $contentQuery->limit($sectionLimit)->get();

            $sectionContentMap[$responseKey] = [
                'id' => $section->id,
                'name' => $section->name,
                'slug' => $section->slug,
                'items' => $contents,
            ];
        }

        $allContentIds = collect($sectionContentMap)
            ->pluck('items')
            ->flatten(1)
            ->pluck('id')
            ->unique()
            ->values();

        $favoriteIds = Favorite::query()
            ->where('user_id', Auth::guard('api')->id())
            ->whereIn('content_id', $allContentIds)
            ->pluck('content_id')
            ->flip();

        foreach ($sectionContentMap as $key => $sectionData) {
            $sectionContentMap[$key]['items'] = $sectionData['items']->map(function ($content) use ($favoriteIds) {
                return [
                    'id' => $content->id,
                    'title' => $content->title,
                    'description' => $content->description,
                    'type' => $content->type,
                    'thumbnail' => $content->thumbnail,
                    'thumbnail_url' => $this->buildMediaUrl($content->thumbnail),
                    'banner' => $content->banner,
                    'banner_url' => $this->buildMediaUrl($content->banner),
                    'access_type' => $content->access_type,
                    'coins_required' => $content->coins_required,
                    'is_favorite' => $favoriteIds->has($content->id),
                    'categories' => $content->categories->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'icon' => $category->icon,
                            'icon_url' => $this->buildMediaUrl($category->icon),
                        ];
                    })->values(),
                ];
            })->values();
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon'])
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'icon' => $category->icon,
                    'icon_url' => $this->buildMediaUrl($category->icon),
                ];
            })
            ->values();

        $payload = [
            'filters' => [
                'search' => $search,
                'category' => $category,
                'type' => $type,
                'section_limit' => $sectionLimit,
            ],
            'categories' => $categories,
            'sections' => [
                'trending_now' => $sectionContentMap['trending_now'],
                'new_releases' => $sectionContentMap['new_releases'],
                'you_might_also_like' => $sectionContentMap['you_might_also_like'],
            ],
        ];

        return $this->success($payload, 'Discover page data fetched successfully');
    }

    /**
     * Discover detail payload for a selected content/reel.
     */
    public function show(int $contentId)
    {
        $user = Auth::guard('api')->user();

        $content = Content::query()
            ->where('is_active', true)
            ->with([
                'categories:id,name,slug,icon',
                'episodes' => function ($query) {
                    $query->where('is_active', true)
                        ->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')
                        ->orderBy('episode_number')
                        ->orderBy('id');
                },
            ])
            ->find($contentId);

        if (! $content) {
            return $this->error([], 'Content not found', 404);
        }

        $isFavorite = Favorite::query()
            ->where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->exists();

        $access = $this->resolveAccessStatus($content, $user->id);

        $watchHistoryByEpisode = WatchHistory::query()
            ->where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->whereNotNull('episode_id')
            ->get()
            ->keyBy('episode_id');

        $episodes = $content->episodes->map(function (Episode $episode) use ($access, $watchHistoryByEpisode) {
            $history = $watchHistoryByEpisode->get($episode->id);
            $duration = (int) ($episode->duration ?? 0);
            $progress = (int) ($history->progress ?? 0);

            return [
                'id' => $episode->id,
                'title' => $episode->title,
                'episode_number' => $episode->episode_number,
                'duration_seconds' => $duration,
                'duration_label' => $this->formatDuration($duration),
                'is_locked' => ! $access['can_watch'],
                'can_watch' => $access['can_watch'],
                'progress_seconds' => $progress,
                'progress_percent' => $duration > 0 ? min(100, (int) floor(($progress / $duration) * 100)) : 0,
                'is_completed' => $duration > 0 && $progress >= $duration,
            ];
        })->values();

        $payload = [
            'content' => [
                'id' => $content->id,
                'title' => $content->title,
                'description' => $content->description,
                'type' => $content->type,
                'thumbnail' => $content->thumbnail,
                'thumbnail_url' => $this->buildMediaUrl($content->thumbnail),
                'banner' => $content->banner,
                'banner_url' => $this->buildMediaUrl($content->banner),
                'access_type' => $content->access_type,
                'coins_required' => (int) $content->coins_required,
                'is_favorite' => $isFavorite,
                'categories' => $content->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'icon' => $category->icon,
                        'icon_url' => $this->buildMediaUrl($category->icon),
                    ];
                })->values(),
            ],
            'access' => $access,
            'episodes' => $episodes,
        ];

        return $this->success($payload, 'Discover detail fetched successfully');
    }

    /**
     * Unlock a coins-locked content for the authenticated user.
     */
    public function unlockWithCoins(int $contentId)
    {
        $user = Auth::guard('api')->user();

        $content = Content::query()
            ->where('is_active', true)
            ->find($contentId);

        if (! $content) {
            return $this->error([], 'Content not found', 404);
        }

        if ($content->access_type !== 'coins') {
            return $this->error([], 'This content does not use coin unlock', 422);
        }

        $alreadyUnlocked = CoinTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'spend')
            ->where('source', 'unlock_content')
            ->where('reference_id', $content->id)
            ->exists();

        if (! $alreadyUnlocked) {
            $requiredCoins = (int) $content->coins_required;

            if ($user->coins < $requiredCoins) {
                return $this->error([
                    'required_coins' => $requiredCoins,
                    'user_coins' => (int) $user->coins,
                ], 'Insufficient coins', 422);
            }

            DB::transaction(function () use ($user, $content, $requiredCoins) {
                $user->coins = (int) $user->coins - $requiredCoins;
                $user->save();

                CoinTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'spend',
                    'amount' => $requiredCoins,
                    'source' => 'unlock_content',
                    'reference_id' => $content->id,
                ]);
            });
        }

        $user->refresh();
        $access = $this->resolveAccessStatus($content, $user->id);

        return $this->success([
            'content_id' => $content->id,
            'already_unlocked' => $alreadyUnlocked,
            'user_coins' => (int) $user->coins,
            'access' => $access,
        ], 'Content unlocked with coins successfully');
    }

    /**
     * Unlock an ads-locked content for the authenticated user.
     */
    public function unlockWithAd(Request $request, int $contentId)
    {
        $request->validate([
            'ads_watched' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $user = Auth::guard('api')->user();

        $content = Content::query()
            ->where('is_active', true)
            ->find($contentId);

        if (! $content) {
            return $this->error([], 'Content not found', 404);
        }

        if ($content->access_type !== 'ads') {
            return $this->error([], 'This content does not use ad unlock', 422);
        }

        $requiredAds = 1;
        $watched = (int) ($request->ads_watched ?? 1);

        $adSession = AdSession::firstOrCreate(
            [
                'user_id' => $user->id,
                'content_id' => $content->id,
            ],
            [
                'ads_watched' => 0,
                'unlocked_until' => null,
            ]
        );

        $adSession->ads_watched = (int) $adSession->ads_watched + $watched;

        if ($adSession->ads_watched >= $requiredAds) {
            $adSession->unlocked_until = now()->addHours(24);
        }

        $adSession->save();

        $access = $this->resolveAccessStatus($content, $user->id);

        return $this->success([
            'content_id' => $content->id,
            'ads_watched' => (int) $adSession->ads_watched,
            'required_ads' => $requiredAds,
            'unlocked_until' => $adSession->unlocked_until?->toIso8601String(),
            'access' => $access,
        ], 'Ad unlock updated successfully');
    }

    /**
     * Save watch progress for an episode.
     */
    public function updateEpisodeProgress(Request $request, int $episodeId)
    {
        $validated = $request->validate([
            'progress' => ['required', 'integer', 'min:0'],
        ]);

        $user = Auth::guard('api')->user();

        $episode = Episode::query()
            ->where('is_active', true)
            ->with('content')
            ->find($episodeId);

        if (! $episode || ! $episode->content || ! $episode->content->is_active) {
            return $this->error([], 'Episode not found', 404);
        }

        $access = $this->resolveAccessStatus($episode->content, $user->id);

        if (! $access['can_watch']) {
            return $this->error([
                'access' => $access,
            ], 'Episode is locked', 403);
        }

        $duration = (int) ($episode->duration ?? 0);
        $progress = (int) $validated['progress'];

        if ($duration > 0) {
            $progress = min($progress, $duration);
        }

        $watchHistory = WatchHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'content_id' => $episode->content_id,
                'episode_id' => $episode->id,
            ],
            [
                'progress' => $progress,
                'last_watched' => now(),
            ]
        );

        return $this->success([
            'content_id' => $episode->content_id,
            'episode_id' => $episode->id,
            'progress_seconds' => (int) $watchHistory->progress,
            'duration_seconds' => $duration,
            'progress_percent' => $duration > 0 ? min(100, (int) floor(($watchHistory->progress / $duration) * 100)) : 0,
            'is_completed' => $duration > 0 && (int) $watchHistory->progress >= $duration,
            'last_watched' => $watchHistory->last_watched?->toIso8601String(),
        ], 'Watch progress updated successfully');
    }

    private function resolveAccessStatus(Content $content, int $userId): array
    {
        $accessType = $content->access_type;

        $response = [
            'access_type' => $accessType,
            'can_watch' => false,
            'lock_reason' => null,
            'coins_required' => (int) $content->coins_required,
            'user_coins' => 0,
            'coins_unlocked' => false,
            'subscription_active' => false,
            'subscription_ends_at' => null,
            'ads_watched' => 0,
            'required_ads' => 1,
            'ad_unlocked_until' => null,
        ];

        if ($accessType === 'free') {
            $response['can_watch'] = true;
            return $response;
        }

        if ($accessType === 'coins') {
            $coinUnlock = CoinTransaction::query()
                ->where('user_id', $userId)
                ->where('type', 'spend')
                ->where('source', 'unlock_content')
                ->where('reference_id', $content->id)
                ->exists();

            $userCoins = (int) (Auth::guard('api')->user()->coins ?? 0);

            $response['user_coins'] = $userCoins;
            $response['coins_unlocked'] = $coinUnlock;
            $response['can_watch'] = $coinUnlock;
            $response['lock_reason'] = $coinUnlock ? null : 'coins_required';

            return $response;
        }

        if ($accessType === 'subscription') {
            $activeSubscription = Subscription::query()
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where('end_date', '>=', now())
                ->latest('end_date')
                ->first();

            $response['subscription_active'] = (bool) $activeSubscription;
            $response['subscription_ends_at'] = $activeSubscription?->end_date?->toIso8601String();
            $response['can_watch'] = (bool) $activeSubscription;
            $response['lock_reason'] = $activeSubscription ? null : 'subscription_required';

            return $response;
        }

        if ($accessType === 'ads') {
            $adSession = AdSession::query()
                ->where('user_id', $userId)
                ->where('content_id', $content->id)
                ->first();

            $isUnlocked = $adSession
                && $adSession->unlocked_until
                && Carbon::parse($adSession->unlocked_until)->isFuture();

            $response['ads_watched'] = (int) ($adSession->ads_watched ?? 0);
            $response['ad_unlocked_until'] = $adSession?->unlocked_until?->toIso8601String();
            $response['can_watch'] = $isUnlocked;
            $response['lock_reason'] = $isUnlocked ? null : 'watch_ads_required';

            return $response;
        }

        return $response;
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0s';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $remaining);
        }

        return sprintf('%ds', $remaining);
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
}
