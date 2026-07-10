<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Walks the parent chain (root-first), skipping any ancestor that has
     * been deactivated — used for breadcrumb trails so we never link to a
     * category page that now 404s. Supports arbitrary nesting depth, unlike
     * eager-loading a fixed number of `parent` levels.
     */
    public function activeAncestors(): Collection
    {
        $ancestors = collect();
        $node = $this->parent;

        while ($node) {
            if ($node->is_active) {
                $ancestors->prepend($node);
            }
            $node = $node->parent;
        }

        return $ancestors;
    }

    /**
     * Breadcrumb-ready {name, url} pairs for this category's active ancestor
     * chain plus itself (root-first). Shared by every controller that renders
     * a category breadcrumb so the "walk ancestors, append self" shape only
     * lives in one place.
     */
    public function breadcrumbTrail(): array
    {
        return $this->activeAncestors()->push($this)
            ->map(fn (self $category) => ['name' => $category->name, 'url' => route('categories.show', $category->slug)])
            ->all();
    }
}
