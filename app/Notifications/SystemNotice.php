<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A notification shown in the bell: a short message and the page it opens.
 * kind: "recommendation" or "class" (shown as a small label in the bell).
 */
class SystemNotice extends Notification
{
    public function __construct(
        public string $kind,
        public string $message,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'message' => $this->message, 'url' => $this->url];
    }
}
