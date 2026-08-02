<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Generic admin-portal notification (database channel). `event` drives the
 * icon/colour in the UI; `url` is where clicking the notification leads.
 *
 * Events in use: needs_approval | stage_advanced | approved | rejected |
 * changes_requested | ai_pending | ai_approved | ai_rejected |
 * rights_submitted | rights_status | publish_ready
 */
class AdminNotification extends Notification
{
    public function __construct(
        public readonly string $event,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
