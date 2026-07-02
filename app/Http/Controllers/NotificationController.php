<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function markRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 401);
        abort_unless($notification->notifiable_type === $user::class && (int) $notification->notifiable_id === (int) $user->id, 404);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notificarea a fost marcata ca citita.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return back()->with('success', 'Toate notificarile au fost marcate ca citite.');
    }
}
