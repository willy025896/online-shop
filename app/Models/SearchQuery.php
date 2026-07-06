<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchQuery extends Model
{
    use HasFactory;

    protected $fillable = [
        'query',
        'count',
        'last_searched_at',
    ];

    protected $casts = [
        'count' => 'integer',
        'last_searched_at' => 'datetime',
    ];

    public static function record(?string $query): void
    {
        $normalized = self::normalizeQuery($query);
        if ($normalized === '') {
            return;
        }

        static::query()->updateOrInsert(
            ['query' => $normalized],
            [
                'count' => DB::raw('COALESCE(`count`, 0) + 1'),
                'last_searched_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public static function normalizeQuery(?string $query): string
    {
        $query = trim((string) $query);
        $query = preg_replace('/\s+/u', ' ', $query);
        return Str::of($query)->lower()->limit(255, '')->__toString();
    }
}
