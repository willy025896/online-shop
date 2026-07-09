<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Shared boilerplate: mail content mirrors the database/broadcast payload.
 * Notifications representing order-lifecycle-critical events use this trait
 * so a recipient who isn't logged in still gets an email with the same
 * title/body/CTA the in-app bell would have shown.
 */
trait MailsAsArray
{
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($data['title'])
            ->line($data['body'])
            ->action(__('notifications.mail.view_details'), $data['url']);
    }
}
