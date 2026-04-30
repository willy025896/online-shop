<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
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

    public function sendMessage(
        Conversation $conversation,
        User $sender,
        ?string $body = null,
        ?UploadedFile $image = null,
    ): Message {
        if (blank($body) && ! $image) {
            throw new \InvalidArgumentException('Message must contain text or image.');
        }

        $imagePath = null;
        if ($image) {
            $filename = Str::uuid().'.'.$image->getClientOriginalExtension();
            $imagePath = $image->storeAs("messages/{$conversation->id}", $filename, 'public');
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => $body,
            'image_path' => $imagePath,
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        $message->load('sender');
        broadcast(new MessageSent($message))->toOthers();

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
