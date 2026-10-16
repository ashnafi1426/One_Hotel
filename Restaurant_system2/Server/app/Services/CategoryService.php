<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    /**
     * List categories for the single hotel.
     */
    public function listCategories(array $filters = []): Collection
    {
        $query = Category::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $categories = $query->withCount('menuItems')
            ->orderBy('display_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $categories->each(function ($cat) {
            if (!$cat->icon || in_array($cat->icon, ['grid', 'menu'])) {
                $cat->icon = MenuService::guessCategoryIcon($cat->slug ?: $cat->name);
            }
            $cat->name_en = $cat->name;
            $cat->name_am = \App\Translations\FrontLang::trans($cat->name, 'am', $cat->name);
            $cat->name_localized = \App\Translations\FrontLang::trans($cat->name, default: $cat->name);
        });

        return $categories;
    }

    /**
     * Create a new category.
     */
    public function createCategory(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $slug = Str::slug($data['name']);

            $baseSlug = $slug;
            $counter = 1;
            while (Category::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            return Category::create([
                'name' => trim($data['name']),
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'display_order' => $data['display_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $updateData = [
                'name' => trim($data['name']),
                'description' => $data['description'] ?? $category->description,
                'icon' => $data['icon'] ?? $category->icon,
                'display_order' => $data['display_order'] ?? $category->display_order,
                'is_active' => $data['is_active'] ?? $category->is_active,
            ];

            if ($category->name !== $updateData['name']) {
                $slug = Str::slug($updateData['name']);
                $baseSlug = $slug;
                $counter = 1;
                while (Category::where('slug', $slug)
                    ->where('id', '!=', $category->id)
                    ->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                $updateData['slug'] = $slug;
            }

            $category->update($updateData);

            return $category->fresh()->loadCount('menuItems');
        });
    }

    /**
     * Delete a category (prevent deletion if menu items belong to it).
     */
    public function deleteCategory(Category $category): void
    {
        if ($category->menuItems()->count() > 0) {
            throw ValidationException::withMessages([
                'category' => 'Cannot delete category with existing menu items. Please reassign or delete menu items first.',
            ]);
        }

        $category->delete();
    }

    /**
     * Toggle category active status.
     */
    public function toggleCategory(Category $category): Category
    {
        $category->update([
            'is_active' => !$category->is_active,
        ]);

        return $category->fresh();
    }

    /**
     * Reorder categories.
     */
    public function reorderCategories(array $categoriesList): void
    {
        DB::transaction(function () use ($categoriesList) {
            foreach ($categoriesList as $item) {
                Category::where('id', $item['id'])
                    ->update([
                        'display_order' => (int) $item['display_order'],
                    ]);
            }
        });
    }
}
