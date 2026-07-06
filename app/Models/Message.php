<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'product_id',
        'body',
        'image_path',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (! $this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Full message shape shared by the Inertia thread payload and the
     * real-time broadcast (MessageSent) — kept in one place so the two
     * can't drift. Expects `sender` and `product.primaryImage` preloaded.
     */
    public function toChatPayload(): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'body' => $this->body,
            'image_path' => $this->image_path,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'profile_photo_url' => $this->sender->profile_photo_url,
            ],
            'product_id' => $this->product_id,
            'product' => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'price' => $this->product->price,
                'thumbnail' => $this->product->primaryImage?->path,
            ] : null,
        ];
    }
}
