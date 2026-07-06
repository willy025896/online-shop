<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConversationService
{
    public function getOrCreateForOrder(Order $order): Conversation
    {
        return DB::transaction(function () use ($order) {
            $existing = Conversation::where('order_id', $order->id)->first();
            if ($existing) {
                return $existing;
            }

            $order->loadMissing('shop');

            return Conversation::create([
                'order_id' => $order->id,
                'buyer_id' => $order->user_id,
                'seller_user_id' => $order->shop->user_id,
            ]);
        });
    }

    public function getOrCreateForProduct(Product $product, User $buyer): Conversation
    {
        $product->loadMissing('shop');

        return Conversation::firstOrCreate([
            'buyer_id' => $buyer->id,
            'seller_user_id' => $product->shop->user_id,
            'order_id' => null,
        ]);
    }

    public function sendMessage(
        Conversation $conversation,
        User $sender,
        ?string $body = null,
        ?UploadedFile $image = null,
        ?Product $product = null,
    ): Message {
        if (blank($body) && ! $image && ! $product) {
            throw new \InvalidArgumentException('Message must contain text, image, or product.');
        }

        $imagePath = null;
        if ($image) {
            $filename = Str::uuid().'.'.$image->getClientOriginalExtension();
            $imagePath = $image->storeAs("messages/{$conversation->id}", $filename, 'public');
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'product_id' => $product?->id,
            'body' => $body,
            'image_path' => $imagePath,
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        $message->load('sender', 'product.primaryImage');
        broadcast(new MessageSent($message))->toOthers();

        $conversation->loadMissing('buyer', 'seller');
        $conversation->otherParticipant($sender)->notify(new NewMessageNotification($message));

        return $message;
    }

    public function markAsRead(Conversation $conversation, User $user): int
    {
        return $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
