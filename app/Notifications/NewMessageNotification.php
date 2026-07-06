<?php

namespace App\Notifications;

use App\Models\Message;
use App\Notifications\Concerns\BroadcastsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use BroadcastsAsArray, Queueable;

    public function __construct(public Message $message) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'message.new',
            'title' => __('notifications.message.new.title', [
                'name' => $this->message->sender->name,
            ]),
            'body' => $this->message->body ?: __('notifications.message.new.attachment'),
            'url' => route('messages.show', $this->message->conversation_id),
            'meta' => [
                'conversation_id' => $this->message->conversation_id,
                'message_id' => $this->message->id,
            ],
        ];
    }
}
