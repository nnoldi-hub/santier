<?php

namespace App\Support;

use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;

class AnalyticsTracker
{
    public static function track(?Request $request, string $eventName, array $meta = [], bool $oncePerUser = false): void
    {
        $userId = $request?->user()?->id;

        if ($oncePerUser && $userId) {
            $exists = AnalyticsEvent::query()
                ->where('user_id', $userId)
                ->where('event_name', $eventName)
                ->exists();

            if ($exists) {
                return;
            }
        }

        AnalyticsEvent::create([
            'user_id' => $userId,
            'session_id' => $request?->hasSession() ? $request->session()->getId() : null,
            'event_name' => $eventName,
            'event_at' => now(),
            'meta' => $meta,
        ]);
    }
}
