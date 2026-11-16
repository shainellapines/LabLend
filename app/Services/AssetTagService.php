<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Equipment;

/**
 * Suggests the next asset tag for a category (FR-E1): PRJ-0001, PRJ-0002, ... PRJ-0006.
 * A service is a plain class for business logic that does not belong to one controller or model.
 */
class AssetTagService
{
    public function nextFor(Category $category): string
    {
        // withTrashed(): soft-deleted items keep their tag, so it is never handed out again.
        $highest = Equipment::withTrashed()
            ->where('asset_tag', 'like', $category->code . '-%')
            ->pluck('asset_tag')
            ->map(fn (string $tag) => (int) substr($tag, strlen($category->code) + 1)) // "PRJ-0004" -> 4
            ->max() ?? 0;

        return sprintf('%s-%04d', $category->code, $highest + 1);
    }

    /** Next tag for every category, e.g. [1 => 'PRJ-0006', 2 => 'LAP-0005']. Used by the create form. */
    public function nextForAll(iterable $categories): array
    {
        $tags = [];
        foreach ($categories as $category) {
            $tags[$category->id] = $this->nextFor($category);
        }

        return $tags;
    }
}
