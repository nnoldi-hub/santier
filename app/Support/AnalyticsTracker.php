<?php

namespace App\Support;

use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;

class AnalyticsTracker
{
    public static function track(?Request $request, string $eventName, array $meta = [], bool $oncePerUser = false): void
    {
        self::trackForUser($request?->user()?->id, $eventName, $meta, $oncePerUser, $request?->hasSession() ? $request->session()->getId() : null);
    }

    /**
     * Record an event outside of an HTTP request context (e.g. a Stripe
     * webhook), where there is no authenticated user to resolve from.
     */
    public static function trackForUser(?int $userId, string $eventName, array $meta = [], bool $oncePerUser = false, ?string $sessionId = null): void
    {
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
            'session_id' => $sessionId,
            'event_name' => $eventName,
            'event_at' => now(),
            'meta' => $meta,
        ]);
    }
}
