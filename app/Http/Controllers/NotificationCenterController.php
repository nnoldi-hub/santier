<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationCenterController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $filters = $request->validate([
            'status' => ['nullable', 'in:all,unread,read'],
            'event' => ['nullable', 'string', 'max:120'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $status = (string) ($filters['status'] ?? 'all');
        $event = trim((string) ($filters['event'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));

        $query = $user->notifications()->latest();

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($event !== '') {
            $query->where('data', 'like', '%"event":"' . $event . '"%');
        }

        if ($search !== '') {
            $escaped = addcslashes($search, '%_');
            $query->where('data', 'like', '%' . $escaped . '%');
        }

        $notifications = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function ($notification): array {
                return [
                    'id' => $notification->id,
                    'type' => class_basename($notification->type),
                    'read_at' => optional($notification->read_at)->toDateTimeString(),
                    'created_at' => optional($notification->created_at)->toDateTimeString(),
                    'data' => $notification->data,
                ];
            });

        return Inertia::render('Account/Notifications', [
            'notifications' => $notifications,
            'filters' => [
                'status' => $status,
                'event' => $event,
                'search' => $search,
            ],
            'eventOptions' => [
                'assigned',
                'assigned_bulk',
                'updated',
                'revoked',
                'task_overdue',
                'phase_overdue',
                'defect_overdue',
                'team_overloaded',
                'equipment_parallel',
                'material_low_stock',
                'subcontractor_parallel',
                'resource_discrepancy',
                'commercial_follow_up',
                'daily_briefing',
            ],
        ]);
    }
}
