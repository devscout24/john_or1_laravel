<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AdSession;
use App\Models\CoinTransaction;
use App\Models\Episode;
use App\Models\EpisodeAdSession;
use App\Models\EpisodeGift;
use App\Models\EpisodeLike;
use App\Models\SavedEpisode;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReelController extends Controller
{
    use ApiResponse;

    /**
     * @return array<string, array{label: string, coins: int}>
     */
    private function giftCatalog(): array
    {
        return [
            'heart' => ['label' => 'Heart', 'coins' => 5],
            'fire' => ['label' => 'Fire', 'coins' => 50],
            'crown' => ['label' => 'Crown', 'coins' => 200],
            'diamond' => ['label' => 'Diamond', 'coins' => 500],
        ];
    }

    private function authenticatedUser(): ?User
    {
        $user = Auth::guard('api')->user();

        return $user instanceof User ? $user : null;
    }

    public function index(Request $request)
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'exclude_episode_ids' => ['nullable', 'array', 'max:200'],
            'exclude_episode_ids.*' => ['integer'],
        ]);

        $limit = (int) ($validated['limit'] ?? 12);
        $excludeIds = collect($validated['exclude_episode_ids'] ?? [])->map(fn($id) => (int) $id)->all();
        $unlockedEpisodeIds = CoinTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'spend')
            ->where('source', 'unlock_episode')
            ->pluck('reference_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $unlockedContentIds = CoinTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'spend')
            ->where('source', 'unlock_content')
            ->pluck('reference_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $adUnlockedEpisodeIds = EpisodeAdSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('unlocked_until')
            ->where('unlocked_until', '>', now())
            ->pluck('episode_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $adUnlockedContentIds = AdSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('unlocked_until')
            ->where('unlocked_until', '>', now())
            ->pluck('content_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $episodes = Episode::query()
            ->where('is_active', true)
            ->where(function ($query) use ($unlockedEpisodeIds, $unlockedContentIds, $adUnlockedEpisodeIds, $adUnlockedContentIds) {
                $query->where(function ($query) {
                    $query->where(function ($query) {
                        $query->where('access_type', 'free')
                            ->orWhereNull('access_type');
                    })
                        ->whereHas('content', function ($query) {
                            $query->whereNotIn('access_type', ['coins', 'ads']);
                        });
                });

                if (! empty($unlockedEpisodeIds)) {
                    $query->orWhere(function ($query) use ($unlockedEpisodeIds) {
                        $query->where('access_type', 'coins')
                            ->whereIn('id', $unlockedEpisodeIds);
                    });
                }

                if (! empty($unlockedContentIds)) {
                    $query->orWhere(function ($query) use ($unlockedContentIds) {
                        $query->whereHas('content', function ($query) use ($unlockedContentIds) {
                            $query->where('access_type', 'coins')
                            ->whereIn('id', $unlockedContentIds);
                        });
                    });
                }

                if (! empty($adUnlockedEpisodeIds)) {
                    $query->orWhere(function ($query) use ($adUnlockedEpisodeIds) {
                        $query->where('access_type', 'ads')
                            ->whereIn('id', $adUnlockedEpisodeIds);
                    });
                }

                if (! empty($adUnlockedContentIds)) {
                    $query->orWhere(function ($query) use ($adUnlockedContentIds) {
                        $query->whereHas('content', function ($query) use ($adUnlockedContentIds) {
                            $query->where('access_type', 'ads')
                                ->whereIn('id', $adUnlockedContentIds);
                        });
                    });
                }
            })
            ->whereHas('content', function ($query) {
                $query->where('is_active', true)
                    ->where('type', 'series');
            })
            ->when(! empty($excludeIds), function ($query) use ($excludeIds) {
                $query->whereNotIn('id', $excludeIds);
            })
            ->with('content:id,title,thumbnail,banner')
            ->withCount('watchHistories')
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        $episodeIds = $episodes->pluck('id');

        $likedEpisodeIds = EpisodeLike::query()
            ->where('user_id', $user->id)
            ->whereIn('episode_id', $episodeIds)
            ->pluck('episode_id')
            ->flip();

        $savedEpisodeIds = SavedEpisode::query()
            ->where('user_id', $user->id)
            ->whereIn('episode_id', $episodeIds)
            ->pluck('episode_id')
            ->flip();

        $likeCounts = EpisodeLike::query()
            ->whereIn('episode_id', $episodeIds)
            ->select('episode_id', DB::raw('COUNT(*) as total'))
            ->groupBy('episode_id')
            ->pluck('total', 'episode_id');

        $giftCounts = EpisodeGift::query()
            ->whereIn('episode_id', $episodeIds)
            ->select('episode_id', DB::raw('COUNT(*) as total'))
            ->groupBy('episode_id')
            ->pluck('total', 'episode_id');

        $items = $episodes->map(function (Episode $episode) use ($likedEpisodeIds, $savedEpisodeIds, $likeCounts, $giftCounts) {
            $content = $episode->content;
            $viewsCount = (int) ($episode->watch_histories_count ?? 0);

            return [
                'episode_id' => (int) $episode->id,
                'episode_title' => $episode->title,
                'episode_number' => $episode->episode_number,
                'video_type' => $episode->video_type,
                'video_url' => $episode->video_url == null ? asset($episode->storage_path) : $episode->video_url,
                'storage_path' => $episode->storage_path,
                'duration_seconds' => (int) ($episode->duration ?? 0),
                'content_id' => (int) $content->id,
                'content_title' => $content->title,
                'thumbnail_url' => $this->buildMediaUrl($content->thumbnail),
                'banner_url' => $this->buildMediaUrl($content->banner),
                'views_count' => $viewsCount,
                'likes_count' => (int) ($likeCounts[$episode->id] ?? 0),
                'gifts_count' => (int) ($giftCounts[$episode->id] ?? 0),
                'is_liked' => $likedEpisodeIds->has($episode->id),
                'is_saved' => $savedEpisodeIds->has($episode->id),
            ];
        })->values();

        $gifts = collect($this->giftCatalog())
            ->map(function (array $gift, string $key) {
                return [
                    'key' => $key,
                    'label' => $gift['label'],
                    'coins' => (int) $gift['coins'],
                ];
            })
            ->values();

        return $this->success([
            'gifts' => $gifts,
            'items' => $items,
            'total' => $items->count(),
        ], 'Reels fetched successfully', 200);
    }

    public function toggleLike(int $episodeId)
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $episode = Episode::query()
            ->where('is_active', true)
            ->whereHas('content', function ($query) {
                $query->where('is_active', true)->where('type', 'series');
            })
            ->find($episodeId);

        if (! $episode) {
            return $this->error([], 'Episode not found', 404);
        }

        $existing = EpisodeLike::query()
            ->where('user_id', $user->id)
            ->where('episode_id', $episode->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            EpisodeLike::query()->create([
                'user_id' => $user->id,
                'episode_id' => $episode->id,
            ]);
            $liked = true;
        }

        $likesCount = EpisodeLike::query()->where('episode_id', $episode->id)->count();

        return $this->success([
            'episode_id' => $episode->id,
            'is_liked' => $liked,
            'likes_count' => (int) $likesCount,
        ], $liked ? 'Episode liked successfully' : 'Episode unliked successfully', 200);
    }

    public function sendGift(Request $request, int $episodeId)
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $episode = Episode::query()
            ->where('is_active', true)
            ->whereHas('content', function ($query) {
                $query->where('is_active', true)->where('type', 'series');
            })
            ->find($episodeId);

        if (! $episode) {
            return $this->error([], 'Episode not found', 404);
        }

        $giftCatalog = $this->giftCatalog();

        $validated = $request->validate([
            'gift_key' => ['required', 'string', Rule::in(array_keys($giftCatalog))],
        ]);

        $giftKey = (string) $validated['gift_key'];
        $gift = $giftCatalog[$giftKey];

        $result = DB::transaction(function () use ($user, $episode, $giftKey, $gift) {
            $lockedUser = User::query()->where('id', $user->id)->lockForUpdate()->firstOrFail();
            $coins = (int) $gift['coins'];

            if ((int) $lockedUser->coins < $coins) {
                return [
                    'ok' => false,
                    'code' => 422,
                    'message' => 'Not enough coins to send this gift',
                    'data' => [
                        'required_coins' => $coins,
                        'available_coins' => (int) $lockedUser->coins,
                    ],
                ];
            }

            $adminRecipient = User::query()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'admin');
                })
                ->orderBy('id')
                ->first();

            $lockedUser->coins = (int) $lockedUser->coins - $coins;
            $lockedUser->save();

            $episodeGift = EpisodeGift::query()->create([
                'sender_user_id' => $lockedUser->id,
                'recipient_user_id' => $adminRecipient?->id,
                'episode_id' => $episode->id,
                'gift_key' => $giftKey,
                'gift_label' => (string) $gift['label'],
                'coins' => $coins,
            ]);

            CoinTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'spend',
                'amount' => $coins,
                'source' => 'episode_gift',
                'reference_id' => $episodeGift->id,
            ]);

            return [
                'ok' => true,
                'code' => 200,
                'message' => 'Gift sent successfully',
                'data' => [
                    'episode_id' => (int) $episode->id,
                    'gift_id' => (int) $episodeGift->id,
                    'gift_key' => $giftKey,
                    'gift_label' => (string) $gift['label'],
                    'spent_coins' => $coins,
                    'total_coins' => (int) $lockedUser->coins,
                ],
            ];
        });

        if (! ($result['ok'] ?? false)) {
            return $this->error($result['data'] ?? [], $result['message'] ?? 'Unable to send gift', (int) ($result['code'] ?? 422));
        }

        return $this->success($result['data'] ?? [], $result['message'] ?? 'Gift sent successfully', (int) ($result['code'] ?? 200));
    }

    public function savedEpisodes(Request $request)
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = (int) ($validated['limit'] ?? 20);

        $saved = SavedEpisode::query()
            ->where('user_id', $user->id)
            ->with([
                'episode' => function ($query) {
                    $query->with('content:id,title,thumbnail,banner');
                },
            ])
            ->latest()
            ->limit($limit)
            ->get();

        $items = $saved->map(function (SavedEpisode $savedEpisode) {
            $episode = $savedEpisode->episode;
            $content = $episode?->content;

            return [
                'saved_id' => (int) $savedEpisode->id,
                'episode_id' => (int) ($episode?->id ?? 0),
                'episode_title' => $episode?->title,
                'episode_number' => $episode?->episode_number,
                'duration_seconds' => (int) ($episode?->duration ?? 0),
                'video_type' => $episode?->video_type,
                'video_url' => $episode?->video_url,
                'storage_path' => $episode?->storage_path,
                'content_id' => (int) ($content?->id ?? 0),
                'content_title' => $content?->title,
                'thumbnail_url' => $this->buildMediaUrl($content?->thumbnail),
                'banner_url' => $this->buildMediaUrl($content?->banner),
                'saved_at' => $savedEpisode->created_at?->toIso8601String(),
            ];
        })->values();

        return $this->success([
            'items' => $items,
            'total' => $items->count(),
        ], 'Saved episodes fetched successfully', 200);
    }

    public function saveEpisode(int $episodeId)
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $episode = Episode::query()
            ->where('is_active', true)
            ->whereHas('content', function ($query) {
                $query->where('is_active', true)->where('type', 'series');
            })
            ->find($episodeId);

        if (! $episode) {
            return $this->error([], 'Episode not found', 404);
        }

        $existing = SavedEpisode::query()
            ->where('user_id', $user->id)
            ->where('episode_id', $episode->id)
            ->first();

        if (! $existing) {
            SavedEpisode::query()->create([
                'user_id' => $user->id,
                'episode_id' => $episode->id,
            ]);
        }

        $savedCount = SavedEpisode::query()->where('user_id', $user->id)->count();

        return $this->success([
            'episode_id' => (int) $episode->id,
            'is_saved' => true,
            'already_saved' => (bool) $existing,
            'saved_count' => (int) $savedCount,
        ], 'Episode saved successfully', 200);
    }

    public function unsaveEpisode(int $episodeId)
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $removed = SavedEpisode::query()
            ->where('user_id', $user->id)
            ->where('episode_id', $episodeId)
            ->delete() > 0;

        $savedCount = SavedEpisode::query()->where('user_id', $user->id)->count();

        return $this->success([
            'episode_id' => (int) $episodeId,
            'is_saved' => false,
            'was_saved' => (bool) $removed,
            'saved_count' => (int) $savedCount,
        ], 'Episode removed from saved list', 200);
    }

    public function adminReceivedGifts(Request $request)
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        if (! $user->hasRole('admin')) {
            return $this->error([], 'Only admin can access gifts report', 403);
        }

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = (int) ($validated['limit'] ?? 50);

        $query = EpisodeGift::query()
            ->with([
                'sender:id,name,email',
                'episode:id,content_id,title,episode_number',
                'episode.content:id,title',
            ])
            ->latest();

        $gifts = $query->limit($limit)->get();

        $items = $gifts->map(function (EpisodeGift $gift) {
            return [
                'gift_id' => (int) $gift->id,
                'gift_key' => $gift->gift_key,
                'gift_label' => $gift->gift_label,
                'coins' => (int) $gift->coins,
                'sender' => [
                    'id' => (int) ($gift->sender?->id ?? 0),
                    'name' => $gift->sender?->name,
                    'email' => $gift->sender?->email,
                ],
                'episode' => [
                    'id' => (int) ($gift->episode?->id ?? 0),
                    'title' => $gift->episode?->title,
                    'episode_number' => $gift->episode?->episode_number,
                    'content_id' => (int) ($gift->episode?->content?->id ?? 0),
                    'content_title' => $gift->episode?->content?->title,
                ],
                'sent_at' => $gift->created_at?->toIso8601String(),
            ];
        })->values();

        return $this->success([
            'items' => $items,
            'total' => $items->count(),
            'summary' => [
                'total_gifts' => (int) $gifts->count(),
                'total_coins' => (int) $gifts->sum('coins'),
            ],
        ], 'Admin gifts report fetched successfully', 200);
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
