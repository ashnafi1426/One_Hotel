<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MenuItemService
{
    public function listMenuItems(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = MenuItem::with(['taxRate', 'categoryRelation']);

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['category'])) {
            $cat = $filters['category'];
            $query->where(function ($q) use ($cat) {
                $q->where('category', $cat)
                  ->orWhere('category_id', $cat)
                  ->orWhereHas('categoryRelation', fn($cq) => $cq->where('slug', $cat)->orWhere('name', $cat));
            });
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', filter_var($filters['is_available'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $allowedSorts = ['name', 'price', 'category', 'created_at'];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Create a menu item.
     */
    public function createMenuItem(array $data, ?UploadedFile $imageFile = null): MenuItem
    {
        return DB::transaction(function () use ($data, $imageFile) {

            $categorySlug = $data['category'] ?? '';
            $category = Category::where(function ($q) use ($categorySlug, $data) {
                $q->where('slug', $categorySlug)
                  ->orWhere('id', $data['category_id'] ?? null)
                  ->orWhere('name', $categorySlug);
            })->first();

            $imagePath = null;
            if ($imageFile) {
                $imagePath = $imageFile->store('menu-items', 'public');
            } elseif (!empty($data['image_url'])) {
                $imagePath = $data['image_url'];
            }

            $item = MenuItem::create([
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'category_id' => $category?->id,
                'category' => $category?->slug ?? $categorySlug,
                'price' => (float) $data['price'],
                'tax_rate_id' => $data['tax_rate_id'] ?? null,
                'tax_included' => filter_var($data['tax_included'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_available' => filter_var($data['is_available'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'image' => $imagePath,
            ]);

            MenuService::invalidateMenuCache();

            return $item;
        });
    }

    public function updateMenuItem(MenuItem $menuItem, array $data, ?UploadedFile $imageFile = null): MenuItem
    {
        return DB::transaction(function () use ($menuItem, $data, $imageFile) {

            $categorySlug = $data['category'] ?? $menuItem->category;
            $category = Category::where(function ($q) use ($categorySlug, $data) {
                $q->where('slug', $categorySlug)
                  ->orWhere('id', $data['category_id'] ?? null)
                  ->orWhere('name', $categorySlug);
            })->first();

            $updateData = [
                'name' => trim($data['name']),
                'description' => $data['description'] ?? $menuItem->description,
                'category_id' => $category?->id ?? $menuItem->category_id,
                'category' => $category?->slug ?? $categorySlug,
                'price' => (float) $data['price'],
                'tax_rate_id' => array_key_exists('tax_rate_id', $data) ? $data['tax_rate_id'] : $menuItem->tax_rate_id,
                'tax_included' => filter_var($data['tax_included'] ?? $menuItem->tax_included, FILTER_VALIDATE_BOOLEAN),
                'is_available' => filter_var($data['is_available'] ?? $menuItem->is_available, FILTER_VALIDATE_BOOLEAN),
            ];

            if ($imageFile) {
                $this->deleteStoredImage($menuItem->image);
                $updateData['image'] = $imageFile->store('menu-items', 'public');
            } elseif (!empty($data['image_url']) && $data['image_url'] !== $menuItem->image) {
                $this->deleteStoredImage($menuItem->image);
                $updateData['image'] = $data['image_url'];
            }

            $menuItem->update($updateData);

            MenuService::invalidateMenuCache();

            return $menuItem->fresh(['taxRate', 'categoryRelation']);
        });
    }

    public function deleteMenuItem(MenuItem $menuItem): void
    {
        DB::transaction(function () use ($menuItem) {
            $hasOrders = DB::table('order_items')
                ->where('menu_item_id', $menuItem->id)
                ->exists();

            if ($hasOrders) {
                $menuItem->update(['is_available' => false]);
                $menuItem->delete();
            } else {
                $this->deleteStoredImage($menuItem->image);
                $menuItem->forceDelete();
            }

            MenuService::invalidateMenuCache();
        });
    }

    /**
     * Toggle availability status of a menu item.
     */
    public function toggleAvailability(MenuItem $menuItem): MenuItem
    {
        $menuItem->update([
            'is_available' => !$menuItem->is_available,
        ]);

        MenuService::invalidateMenuCache();

        return $menuItem->fresh(['taxRate', 'categoryRelation']);
    }

    /**
     * Calculate menu item statistics.
     */
    public function getStatistics(): array
    {
        $query = MenuItem::query();

        $total = (clone $query)->count();
        $available = (clone $query)->where('is_available', true)->count();
        $unavailable = (clone $query)->where('is_available', false)->count();

        return [
            'total_items' => $total,
            'available_items' => $available,
            'unavailable_items' => $unavailable,
            'breakfast_items' => (clone $query)->where('category', 'breakfast')->count(),
            'lunch_items' => (clone $query)->where('category', 'lunch')->count(),
            'dinner_items' => (clone $query)->where('category', 'dinner')->count(),
            'drink_items' => (clone $query)->whereIn('category', ['drinks', 'beverages'])->count(),
            'dessert_items' => (clone $query)->where('category', 'dessert')->count(),
        ];
    }

    protected function deleteStoredImage(?string $path): void
    {
        if ($path && !filter_var($path, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($path);
        }
    }
}
