<?php

namespace App\Models;

use App\Models\Concerns\HasStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, HasStock, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'compare_price',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_option_values',
        );
    }

    public function optionLabel(): string
    {
        return $this->optionValues
            ->loadMissing('option')
            ->map(fn (ProductOptionValue $value) => "{$value->option->name}: {$value->value}")
            ->implode(' / ');
    }
}
