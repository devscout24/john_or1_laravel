<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Section;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
