<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\BroadcastMessage;

/**
 * Shared boilerplate: broadcast payload mirrors the database payload.
 * All in-app notifications in this project follow this convention so the
 * NotificationBell front-end can render any type from one template.
 */
trait BroadcastsAsArray
{
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
