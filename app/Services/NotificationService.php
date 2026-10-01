<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    public function notify(
        User $user,
        string $title,
        string $body,
        string $type,
        array $data = []
    ): AppNotification {
        return AppNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
        ]);
    }
}
