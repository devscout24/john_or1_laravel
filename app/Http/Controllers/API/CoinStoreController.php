<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CoinPackage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CoinStoreController extends Controller
{
    use ApiResponse;

    /**
     * Coin store page payload for mobile app.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'platform' => ['nullable', Rule::in(['ios', 'android', 'all'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $platform = $validated['platform'] ?? 'all';
        $limit = $validated['limit'] ?? 20;

        $query = CoinPackage::query()
            ->where('is_active', true)
            ->orderBy('coins');

        if ($platform !== 'all') {
            $query->where('platform', $platform);
        }

        $packages = $query->limit($limit)->get();

        $basePricePerCoin = $this->resolveBasePricePerCoin($packages);
        $bestValuePackageId = $this->resolveBestValuePackageId($packages);
        $mostPopularPackageId = $this->resolveMostPopularPackageId($packages);

        $formattedPackages = $packages->values()->map(function ($package, $index) use ($basePricePerCoin, $bestValuePackageId, $mostPopularPackageId) {
            $pricePerCoin = $package->coins > 0 ? ((float) $package->price / $package->coins) : 0.0;

            $discountPercent = 0;
            if ($basePricePerCoin > 0 && $pricePerCoin > 0) {
                $discountPercent = (int) round((1 - ($pricePerCoin / $basePricePerCoin)) * 100);
                $discountPercent = max(0, $discountPercent);
            }

            $imagePath = 'service_icon/' . (($index % 7) + 1) . '.png';

            return [
                'id' => $package->id,
                'coins' => $package->coins,
                'price' => (float) $package->price,
                'platform' => $package->platform,
                'product_id' => $package->product_id,
                'price_per_coin' => round($pricePerCoin, 4),
                'discount_percent' => $discountPercent,
                'is_most_popular' => $package->id === $mostPopularPackageId,
                'is_best_value' => $package->id === $bestValuePackageId,
                'badge' => $this->resolveBadge($package->id, $mostPopularPackageId, $bestValuePackageId),
                'image' => $imagePath,
                'image_url' => $this->buildMediaUrl($imagePath),
            ];
        });

        $payload = [
            'filters' => [
                'platform' => $platform,
                'limit' => $limit,
            ],
            'balance' => [
                'coins' => (int) (Auth::guard('api')->user()->coins ?? 0),
            ],
            'packages' => $formattedPackages,
        ];

        return $this->success($payload, 'Coin store data fetched successfully');
    }

    private function resolveBasePricePerCoin($packages): float
    {
        $base = $packages
            ->filter(fn($item) => $item->coins > 0 && (float) $item->price > 0)
            ->map(fn($item) => (float) $item->price / $item->coins)
            ->min();

        return $base ? (float) $base : 0.0;
    }

    private function resolveBestValuePackageId($packages): ?int
    {
        $best = $packages
            ->filter(fn($item) => $item->coins > 0 && (float) $item->price > 0)
            ->sortBy(fn($item) => (float) $item->price / $item->coins)
            ->first();

        return $best?->id;
    }

    private function resolveMostPopularPackageId($packages): ?int
    {
        $count = $packages->count();

        if ($count === 0) {
            return null;
        }

        // When there are 3+ packages, pick the middle one as default popular package.
        if ($count >= 3) {
            $middleIndex = (int) floor($count / 2);
            return $packages->values()->get($middleIndex)?->id;
        }

        return $packages->first()?->id;
    }

    private function resolveBadge(int $packageId, ?int $mostPopularPackageId, ?int $bestValuePackageId): ?string
    {
        if ($packageId === $mostPopularPackageId) {
            return 'most_popular';
        }

        if ($packageId === $bestValuePackageId) {
            return 'best_value';
        }

        return null;
    }

    private function buildMediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return null;
    }
}
