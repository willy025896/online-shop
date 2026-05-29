<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

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

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function averageRating(): float
    {
        if ($this->reviews_count === 0) {
            return 0;
        }

        return round($this->rating_sum / $this->reviews_count, 1);
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }
}
