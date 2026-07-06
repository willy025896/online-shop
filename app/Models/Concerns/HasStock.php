<?php

namespace App\Models\Concerns;

trait HasStock
{
    public function inStock(): bool
    {
        return $this->stock > 0;
    }
}
