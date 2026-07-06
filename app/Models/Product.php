<?php

namespace App\Models;

use App\Models\Concerns\HasStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasStock, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'shop_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'compare_price',
        'stock',
        'status',
        'is_featured',
        'reviews_count',
        'rating_sum',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'reviews_count' => 'integer',
            'rating_sum' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrderByRating($query)
    {
        return $query
            ->orderByRaw('CASE WHEN reviews_count = 0 THEN 0 ELSE rating_sum / reviews_count END DESC')
            ->orderByDesc('reviews_count');
    }

    /**
     * Products at or below the low-stock threshold (includes out-of-stock).
     * Defaults to config('inventory.low_stock_threshold') when $threshold is null.
     */
    public function scopeLowStock($query, ?int $threshold = null)
    {
        return $query->where('stock', '<=', $threshold ?? config('inventory.low_stock_threshold'));
    }

    public function scopePriceRange($query, ?string $min, ?string $max)
    {
        if ($min !== null && $min !== '') {
            $query->where('price', '>=', (float) $min);
        }

        if ($max !== null && $max !== '') {
            $query->where('price', '<=', (float) $max);
        }
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    public function averageRating(): float
    {
        if ($this->reviews_count === 0) {
            return 0;
        }

        return round($this->rating_sum / $this->reviews_count, 1);
    }
}
