<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MenuService
{
    /**
     * Invalidate cached menu and category queries.
     */
    public static function invalidateMenuCache(?string $hotelId = null): void
    {
        Cache::forget('qr_menu_items:single');
        Cache::forget('qr_menu_categories:single');
        Cache::forget('qr_menu_items:all');
        Cache::forget('qr_menu_categories:all');
        Cache::forget('qr_menu_items:default');
        Cache::forget('qr_menu_categories:default');
        if ($hotelId) {
            Cache::forget("qr_menu_items:{$hotelId}");
            Cache::forget("qr_menu_categories:{$hotelId}");
        }
    }

    /**
     * Get menu items for guest view, grouped by category.
     * High-speed execution with Cache::remember, column pruning, and preloaded tax relations.
     */
    public function getCategorizedMenuItems(?string $hotelId = null, bool $forceRefresh = false): Collection
    {
        $cacheKey = 'qr_menu_items:single';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $cachedData = Cache::remember($cacheKey, 600, function () {
            $this->ensureHasMenuAndCategories();

            $query = MenuItem::query()
                ->select([
                    'id',
                    'category_id',
                    'category',
                    'name',
                    'description',
                    'price',
                    'tax_rate_id',
                    'tax_included',
                    'is_available',
                    'image',
                    'created_at',
                    'updated_at',
                ])
                ->with(['taxRate:id,name,rate,type'])
                ->withCount(['approvedReviews as review_count'])
                ->withAvg('approvedReviews as average_rating', 'rating')
                ->where('is_available', true);

            $menuItems = $query->orderBy('category')->orderBy('name')->get();

            return $menuItems->groupBy(function ($item) {
                    $cat = trim($item->category ?? '');
                    return !empty($cat) ? $cat : 'Other';
                })
                ->map(fn($items, $category) => [
                    'category' => $category,
                    'items' => $items->map(fn($item) => $this->formatMenuItemForGuest($item))->values()->all(),
                ])
                ->values()
                ->all();
        });

        return collect($cachedData);
    }

    /**
     * Get all active categories with counts (without N+1 queries).
     * Cached with a 10-minute TTL for lightning-fast catalog navigation.
     */
    public function getCategoriesWithCounts(?string $hotelId = null, bool $forceRefresh = false): array
    {
        $cacheKey = 'qr_menu_categories:single';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 600, function () {
            $this->ensureHasMenuAndCategories();

            $itemCountQuery = MenuItem::query()
                ->where('is_available', true)
                ->selectRaw('category, category_id, COUNT(*) as item_count');

            $itemCounts = $itemCountQuery->groupBy('category', 'category_id')->get();

            $countByCatId = [];
            $countBySlug = [];
            foreach ($itemCounts as $row) {
                if ($row->category_id) {
                    $countByCatId[$row->category_id] = ($countByCatId[$row->category_id] ?? 0) + $row->item_count;
                }
                if ($row->category) {
                    $slug = Str::slug($row->category);
                    $countBySlug[$slug] = ($countBySlug[$slug] ?? 0) + $row->item_count;
                }
            }

            $catQuery = Category::query()
                ->select(['id', 'name', 'slug', 'icon', 'display_order', 'is_active'])
                ->where('is_active', true);

            $dbCategories = $catQuery->orderBy('display_order')->get();

            $resultMap = [];

            foreach ($dbCategories as $category) {
                $slug = $category->slug ?: Str::slug($category->name);
                $cnt = $countByCatId[$category->id] ?? $countBySlug[$slug] ?? 0;

                $icon = $category->icon;
                if (!$icon || in_array($icon, ['grid', 'menu'])) {
                    $icon = self::guessCategoryIcon($slug);
                }

                $resultMap[$slug] = [
                    'id' => $category->id ?: $slug,
                    'name' => $category->name,
                    'slug' => $slug,
                    'icon' => $icon,
                    'count' => $cnt,
                ];
            }

            foreach ($countBySlug as $slug => $cnt) {
                if (!isset($resultMap[$slug])) {
                    $resultMap[$slug] = [
                        'id' => $slug,
                        'name' => ucwords(str_replace('-', ' ', $slug)),
                        'slug' => $slug,
                        'icon' => self::guessCategoryIcon($slug),
                        'count' => $cnt,
                    ];
                }
            }

            return array_values($resultMap);
        });
    }

    /**
     * Format a single menu item with price and tax calculations for guests.
     */
    public function formatMenuItemForGuest($item): array
    {
        $imageUrl = null;
        if ($item->image) {
            $imageUrl = filter_var($item->image, FILTER_VALIDATE_URL)
                ? $item->image
                : asset('storage/' . $item->image);
        }

        $price = (float) $item->price;
        $taxRateModel = $item->relationLoaded('taxRate') ? $item->taxRate : $item->taxRate;
        $rate = $taxRateModel ? (float) $taxRateModel->rate : 0.0;
        $taxIncluded = (bool) ($item->tax_included ?? false);

        if ($rate > 0) {
            if ($taxIncluded) {
                $basePrice = round($price / (1 + ($rate / 100)), 2);
                $taxAmount = round($price - $basePrice, 2);
                $totalPrice = $price;
            } else {
                $basePrice = $price;
                $taxAmount = round($price * ($rate / 100), 2);
                $totalPrice = round($price + $taxAmount, 2);
            }
        } else {
            $basePrice = $price;
            $taxAmount = 0.0;
            $totalPrice = $price;
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'price' => $price,
            'base_price' => $basePrice,
            'tax_amount' => $taxAmount,
            'total_price' => $totalPrice,
            'formatted_price' => number_format($price, 2),
            'formatted_total_price' => number_format($totalPrice, 2),
            'tax_rate_id' => $item->tax_rate_id,
            'tax_included' => $taxIncluded,
            'tax_rate' => $taxRateModel ? [
                'id' => $taxRateModel->id,
                'name' => $taxRateModel->name,
                'rate' => (float) $taxRateModel->rate,
                'type' => $taxRateModel->type,
            ] : null,
            'image' => $imageUrl,
            'category' => !empty(trim($item->category ?? '')) ? trim($item->category) : 'Other',
            'is_available' => (bool) $item->is_available,
            'average_rating' => $item->average_rating ? round((float) $item->average_rating, 1) : null,
            'rating' => $item->average_rating ? round((float) $item->average_rating, 1) : null,
            'review_count' => (int) ($item->review_count ?? 0),
        ];
    }

    /**
     * Map category names or slugs to intuitive UI icons.
     */
    public static function guessCategoryIcon(?string $nameOrSlug): string
    {
        $key = strtolower(trim(str_replace([' ', '_'], '-', $nameOrSlug ?? '')));
        return match (true) {
            str_contains($key, 'breakfast') || str_contains($key, 'morning') => 'clock',
            str_contains($key, 'soup') => 'soup',
            str_contains($key, 'appetizer') || str_contains($key, 'starter') => 'leaf',
            str_contains($key, 'salad') => 'salad',
            str_contains($key, 'main') || str_contains($key, 'entree') => 'utensils',
            str_contains($key, 'sandwich') || str_contains($key, 'burger') => 'sandwich',
            str_contains($key, 'pasta') || str_contains($key, 'noodle') => 'layers',
            str_contains($key, 'pizza') => 'pizza',
            str_contains($key, 'dessert') || str_contains($key, 'sweet') || str_contains($key, 'cake') => 'cake',
            str_contains($key, 'drink') || str_contains($key, 'beverage') || str_contains($key, 'wine') || str_contains($key, 'bar') || str_contains($key, 'coffee') => 'wine',
            default => 'utensils',
        };
    }

    /**
     * Seed initial menu categories if none exist.
     */
    public function ensureHasMenuAndCategories(): void
    {
        try {
            $catCount = Category::count();

            if ($catCount === 0) {
                $standards = [
                    ['name' => 'Breakfast', 'slug' => 'breakfast', 'icon' => 'clock', 'description' => 'Morning delicacies to start your day', 'display_order' => 1],
                    ['name' => 'Soups', 'slug' => 'soups', 'icon' => 'soup', 'description' => 'Hearty and comforting soups', 'display_order' => 2],
                    ['name' => 'Appetizers', 'slug' => 'appetizers', 'icon' => 'leaf', 'description' => 'Delicious starters and small plates', 'display_order' => 3],
                    ['name' => 'Main Courses', 'slug' => 'main-courses', 'icon' => 'utensils', 'description' => 'Signature dishes and entrees', 'display_order' => 4],
                    ['name' => 'Sandwiches', 'slug' => 'sandwiches', 'icon' => 'sandwich', 'description' => 'Gourmet burgers and sandwiches', 'display_order' => 5],
                    ['name' => 'Pasta', 'slug' => 'pasta', 'icon' => 'layers', 'description' => 'Handcrafted pasta specialties', 'display_order' => 6],
                    ['name' => 'Desserts', 'slug' => 'desserts', 'icon' => 'cake', 'description' => 'Sweet treats and decadent desserts', 'display_order' => 7],
                    ['name' => 'Beverages', 'slug' => 'beverages', 'icon' => 'wine', 'description' => 'Refreshing beverages and drinks', 'display_order' => 8],
                ];

                foreach ($standards as $std) {
                    Category::firstOrCreate(
                        ['slug' => $std['slug']],
                        [
                            'id' => (string) Str::uuid(),
                            'name' => $std['name'],
                            'description' => $std['description'],
                            'icon' => $std['icon'],
                            'display_order' => $std['display_order'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[MENU SERVICE] Could not auto-seed categories: ' . $e->getMessage());
        }
    }

    public function ensureHotelHasMenuAndCategories(?string $hotelId = null): void
    {
        $this->ensureHasMenuAndCategories();
    }
}
