<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AdSession;
use App\Models\Category;
use App\Models\CoinTransaction;
use App\Models\Content;
use App\Models\Episode;
use App\Models\EpisodeAdSession;
use App\Models\Favorite;
use App\Models\Section;
use App\Models\Subscription;
use App\Models\WatchHistory;
use App\Services\DailyTaskService;
use App\Services\WatchDramaRewardService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DiscoverController extends Controller
{
    use ApiResponse;

    private const REQUIRED_EPISODE_ADS = 3;

    private function requiredEpisodeAds(): int
    {
        return self::REQUIRED_EPISODE_ADS;
    }

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
                ->withCount([
                    'episodes as total_episodes' => function ($query) {
                        $query->where('is_active', true);
                    },
                ])
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
                    'total_episodes' => (int) ($content->total_episodes ?? 0),
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
                        ->withCount('watchHistories')
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

        $legacyContentCoinUnlock = $this->hasLegacyContentCoinUnlock($user->id, $content->id);
        $legacyContentAdUnlock = $this->hasLegacyContentAdUnlock($user->id, $content->id);

        $episodeIds = $content->episodes->pluck('id');

        $unlockedEpisodeIds = CoinTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'spend')
            ->where('source', 'unlock_episode')
            ->whereIn('reference_id', $episodeIds)
            ->pluck('reference_id')
            ->flip();

        $unlockedAdEpisodeIds = EpisodeAdSession::query()
            ->where('user_id', $user->id)
            ->whereIn('episode_id', $episodeIds)
            ->whereNotNull('unlocked_until')
            ->where('unlocked_until', '>', now())
            ->pluck('episode_id')
            ->flip();

        $watchHistoryByEpisode = WatchHistory::query()
            ->where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->whereNotNull('episode_id')
            ->get()
            ->keyBy('episode_id');

        $episodes = $content->episodes->map(function (Episode $episode) use ($watchHistoryByEpisode, $content, $legacyContentCoinUnlock, $legacyContentAdUnlock, $unlockedEpisodeIds, $unlockedAdEpisodeIds, $user) {
            $history = $watchHistoryByEpisode->get($episode->id);
            $duration = (int) ($episode->duration ?? 0);
            $progress = (int) ($history->progress ?? 0);
            $viewsCount = (int) ($episode->watch_histories_count ?? 0);
            $episodeImage = $content->thumbnail ?: 'default.png';
            $episodeAccessType = $this->resolveEpisodeAccessType($content, $episode);
            $episodeCoins = (int) ($episode->coins_required ?? 0);

            $episodeAccess = $this->resolveEpisodeAccessStatus($content, $episode, $user->id);

            if ($episodeAccessType === 'coins') {
                $episodeUnlocked = $legacyContentCoinUnlock || $unlockedEpisodeIds->has($episode->id);
                $episodeCanWatch = $episodeUnlocked;
                $episodeLockReason = $episodeUnlocked ? null : 'coins_required';
            } elseif ($episodeAccessType === 'ads') {
                $episodeUnlocked = $legacyContentAdUnlock || $unlockedAdEpisodeIds->has($episode->id);
                $episodeCanWatch = $episodeUnlocked;
                $episodeLockReason = $episodeUnlocked ? null : 'watch_ads_required';
            } else {
                $episodeUnlocked = (bool) ($episodeAccess['can_watch'] ?? false);
                $episodeCanWatch = (bool) ($episodeAccess['can_watch'] ?? false);
                $episodeLockReason = $episodeAccess['lock_reason'] ?? null;
            }

            return [
                'id' => $episode->id,
                'title' => $episode->title,
                'episode_number' => $episode->episode_number,
                'access_type' => $episodeAccessType,
                'image' => $episodeImage,
                'image_url' => $this->buildMediaUrl($episodeImage),
                'coins_required' => $episodeCoins,
                'coins_unlocked' => $episodeUnlocked,
                'user_coins' => (int) ($user->coins ?? 0),
                'lock_reason' => $episodeLockReason,
                'duration_seconds' => $duration,
                'duration_label' => $this->formatDuration($duration),
                'views_count' => $viewsCount,
                'views_label' => $this->formatViewsLabel($viewsCount),
                'is_locked' => ! $episodeCanWatch,
                'can_watch' => $episodeCanWatch,
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
            'access' => $this->resolveAccessStatus($content, $user->id),
            'episodes' => $episodes,
        ];

        return $this->success($payload, 'Discover detail fetched successfully');
    }

    /**
     * Unlock a coins-locked episode for the authenticated user.
     */
    public function unlockWithCoins(int $episodeId)
    {
        $user = Auth::guard('api')->user();

        $episode = Episode::query()
            ->where('is_active', true)
            ->with('content')
            ->find($episodeId);

        if (! $episode || ! $episode->content || ! $episode->content->is_active) {
            return $this->error([], 'Episode not found', 404);
        }

        $content = $episode->content;

        if ($this->resolveEpisodeAccessType($content, $episode) !== 'coins') {
            return $this->error([], 'This episode does not use coin unlock', 422);
        }

        $alreadyUnlocked = $this->hasLegacyContentCoinUnlock($user->id, $content->id)
            || CoinTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'spend')
            ->where('source', 'unlock_episode')
            ->where('reference_id', $episode->id)
            ->exists();

        if (! $alreadyUnlocked) {
            $requiredCoins = (int) ($episode->coins_required ?? 0);

            if ($user->coins < $requiredCoins) {
                return $this->error([
                    'episode_id' => $episode->id,
                    'required_coins' => $requiredCoins,
                    'user_coins' => (int) $user->coins,
                ], 'Insufficient coins', 422);
            }

            DB::transaction(function () use ($user, $episode, $requiredCoins) {
                $user->coins = (int) $user->coins - $requiredCoins;
                $user->save();

                CoinTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'spend',
                    'amount' => $requiredCoins,
                    'source' => 'unlock_episode',
                    'reference_id' => $episode->id,
                ]);
            });
        }

        $user->refresh();
        $access = $this->resolveEpisodeAccessStatus($content, $episode, $user->id);

        return $this->success([
            'content_id' => $content->id,
            'episode_id' => $episode->id,
            'already_unlocked' => $alreadyUnlocked,
            'user_coins' => (int) $user->coins,
            'access' => $access,
        ], 'Episode unlocked with coins successfully');
    }

    /**
     * Unlock an ads-locked episode for the authenticated user.
     */
    public function unlockWithAd(Request $request, int $episodeId)
    {
        $request->validate([
            // One API call represents one completed ad watch event.
            'ads_watched' => ['nullable', 'integer', Rule::in([1])],
        ]);

        $user = Auth::guard('api')->user();

        $episode = Episode::query()
            ->where('is_active', true)
            ->with('content')
            ->find($episodeId);

        if (! $episode || ! $episode->content || ! $episode->content->is_active) {
            return $this->error([], 'Episode not found', 404);
        }

        $content = $episode->content;

        if ($this->resolveEpisodeAccessType($content, $episode) !== 'ads') {
            return $this->error([], 'This episode does not use ad unlock', 422);
        }

        $requiredAds = $this->requiredEpisodeAds();

        $adSession = EpisodeAdSession::firstOrCreate(
            [
                'user_id' => $user->id,
                'episode_id' => $episode->id,
            ],
            [
                'ads_watched' => 0,
                'unlocked_until' => null,
            ]
        );

        $currentlyUnlocked = $adSession->unlocked_until && Carbon::parse($adSession->unlocked_until)->isFuture();

        if ($currentlyUnlocked) {
            $access = $this->resolveEpisodeAccessStatus($content, $episode, $user->id);

            return $this->success([
                'content_id' => $content->id,
                'episode_id' => $episode->id,
                'ads_watched' => min((int) $adSession->ads_watched, $requiredAds),
                'required_ads' => $requiredAds,
                'remaining_ads' => 0,
                'unlocked_until' => $adSession->unlocked_until?->toIso8601String(),
                'access' => $access,
            ], 'Episode already unlocked with ads');
        }

        $isExpired = $adSession->unlocked_until && Carbon::parse($adSession->unlocked_until)->isPast();
        if ($isExpired || (int) $adSession->ads_watched >= $requiredAds) {
            $adSession->ads_watched = 0;
            $adSession->unlocked_until = null;
        }

        $adSession->ads_watched = min($requiredAds, (int) $adSession->ads_watched + 1);

        if ($adSession->ads_watched >= $requiredAds) {
            $adSession->unlocked_until = now()->addHours(24);
        }

        $adSession->save();

        $access = $this->resolveEpisodeAccessStatus($content, $episode, $user->id);

        return $this->success([
            'content_id' => $content->id,
            'episode_id' => $episode->id,
            'ads_watched' => (int) $adSession->ads_watched,
            'required_ads' => $requiredAds,
            'remaining_ads' => max(0, $requiredAds - (int) $adSession->ads_watched),
            'unlocked_until' => $adSession->unlocked_until?->toIso8601String(),
            'access' => $access,
        ], 'Episode ad unlock updated successfully');
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

        $access = $this->resolveEpisodeAccessStatus($episode->content, $episode, $user->id);

        if (! $access['can_watch']) {
            return $this->error([
                'access' => $access,
            ], 'Episode is locked', 403);
        }

        $duration = (int) ($episode->duration ?? 0);
        $progress = (int) $validated['progress'];

        $existingHistory = WatchHistory::query()
            ->where('user_id', $user->id)
            ->where('content_id', $episode->content_id)
            ->where('episode_id', $episode->id)
            ->first();

        $wasCompleted = $duration > 0
            && $existingHistory
            && (int) $existingHistory->progress >= $duration;

        $previousProgress = (int) ($existingHistory->progress ?? 0);

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

        $isCompletedNow = $duration > 0 && (int) $watchHistory->progress >= $duration;

        if (! $wasCompleted && $isCompletedNow) {
            app(DailyTaskService::class)->incrementProgress((int) $user->id, 'watch_episode', 1);
        }

        $watchedDelta = max(0, (int) $watchHistory->progress - $previousProgress);
        if ($watchedDelta > 0) {
            app(WatchDramaRewardService::class)->incrementWatchSeconds((int) $user->id, $watchedDelta);
        }

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
            'required_ads' => $this->requiredEpisodeAds(),
            'ad_unlocked_until' => null,
        ];

        if ($accessType === 'free') {
            $response['can_watch'] = true;
            return $response;
        }

        if ($accessType === 'coins') {
            $coinUnlock = $this->hasLegacyContentCoinUnlock($userId, $content->id)
                || CoinTransaction::query()
                ->where('user_id', $userId)
                ->where('type', 'spend')
                ->where('source', 'unlock_episode')
                ->whereIn('reference_id', Episode::query()->where('content_id', $content->id)->pluck('id'))
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
            $contentEpisodeIds = Episode::query()->where('content_id', $content->id)->pluck('id');

            $episodeSession = EpisodeAdSession::query()
                ->where('user_id', $userId)
                ->whereIn('episode_id', $contentEpisodeIds)
                ->orderByDesc('unlocked_until')
                ->first();

            $legacySession = AdSession::query()
                ->where('user_id', $userId)
                ->where('content_id', $content->id)
                ->whereNotNull('unlocked_until')
                ->orderByDesc('unlocked_until')
                ->first();

            $episodeUnlocked = $episodeSession
                && $episodeSession->unlocked_until
                && Carbon::parse($episodeSession->unlocked_until)->isFuture();
            $legacyUnlocked = $legacySession
                && $legacySession->unlocked_until
                && Carbon::parse($legacySession->unlocked_until)->isFuture();

            $isUnlocked = $episodeUnlocked || $legacyUnlocked;

            $response['ads_watched'] = max((int) ($episodeSession->ads_watched ?? 0), (int) ($legacySession->ads_watched ?? 0));
            $response['ad_unlocked_until'] = $episodeSession?->unlocked_until?->toIso8601String() ?? $legacySession?->unlocked_until?->toIso8601String();
            $response['can_watch'] = $isUnlocked;
            $response['lock_reason'] = $isUnlocked ? null : 'watch_ads_required';

            return $response;
        }

        return $response;
    }

    private function resolveEpisodeAccessStatus(Content $content, Episode $episode, int $userId): array
    {
        $accessType = $this->resolveEpisodeAccessType($content, $episode);

        $access = [
            'access_type' => $accessType,
            'can_watch' => false,
            'lock_reason' => null,
            'coins_required' => (int) ($episode->coins_required ?? 0),
            'user_coins' => 0,
            'coins_unlocked' => false,
            'subscription_active' => false,
            'subscription_ends_at' => null,
            'ads_watched' => 0,
            'required_ads' => $this->requiredEpisodeAds(),
            'ad_unlocked_until' => null,
        ];

        if ($accessType === 'free') {
            $access['can_watch'] = true;
            return $access;
        }

        if ($accessType === 'coins') {
            $episodeUnlock = $this->hasLegacyContentCoinUnlock($userId, $content->id)
                || CoinTransaction::query()
                ->where('user_id', $userId)
                ->where('type', 'spend')
                ->where('source', 'unlock_episode')
                ->where('reference_id', $episode->id)
                ->exists();

            $access['user_coins'] = (int) (Auth::guard('api')->user()->coins ?? 0);
            $access['coins_unlocked'] = $episodeUnlock;
            $access['can_watch'] = $episodeUnlock;
            $access['lock_reason'] = $episodeUnlock ? null : 'coins_required';

            return $access;
        }

        if ($accessType === 'subscription') {
            $activeSubscription = Subscription::query()
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where('end_date', '>=', now())
                ->latest('end_date')
                ->first();

            $access['subscription_active'] = (bool) $activeSubscription;
            $access['subscription_ends_at'] = $activeSubscription?->end_date?->toIso8601String();
            $access['can_watch'] = (bool) $activeSubscription;
            $access['lock_reason'] = $activeSubscription ? null : 'subscription_required';

            return $access;
        }

        if ($accessType === 'ads') {
            $adSession = EpisodeAdSession::query()
                ->where('user_id', $userId)
                ->where('episode_id', $episode->id)
                ->first();

            $legacyUnlocked = $this->hasLegacyContentAdUnlock($userId, $content->id);

            $isUnlocked = $legacyUnlocked || (
                $adSession
                && $adSession->unlocked_until
                && Carbon::parse($adSession->unlocked_until)->isFuture()
            );

            $requiredAds = $this->requiredEpisodeAds();

            $access['required_ads'] = $requiredAds;
            $access['ads_watched'] = min($requiredAds, (int) ($adSession->ads_watched ?? 0));
            $access['ad_unlocked_until'] = $adSession?->unlocked_until?->toIso8601String();
            $access['can_watch'] = $isUnlocked;
            $access['lock_reason'] = $isUnlocked ? null : 'watch_ads_required';

            return $access;
        }

        return $access;
    }

    private function resolveEpisodeAccessType(Content $content, Episode $episode): string
    {
        return (string) ($episode->access_type ?: $content->access_type);
    }

    private function hasLegacyContentCoinUnlock(int $userId, int $contentId): bool
    {
        return CoinTransaction::query()
            ->where('user_id', $userId)
            ->where('type', 'spend')
            ->where('source', 'unlock_content')
            ->where('reference_id', $contentId)
            ->exists();
    }

    private function hasLegacyContentAdUnlock(int $userId, int $contentId): bool
    {
        return AdSession::query()
            ->where('user_id', $userId)
            ->where('content_id', $contentId)
            ->whereNotNull('unlocked_until')
            ->where('unlocked_until', '>', now())
            ->exists();
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
