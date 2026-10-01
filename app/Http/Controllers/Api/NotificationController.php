<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (AppNotification $notification) => $this->payload($notification));

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => ['unread' => $count],
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $notification = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->payload($notification->fresh()),
        ]);
    }

    public function markAllRead(Request $request)
    {
        AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read',
        ]);
    }

    public function storeDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:512',
            'platform' => 'nullable|string|in:android,ios,web',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $device = DeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => $request->user()->id,
                'platform' => $request->platform,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Device token saved',
            'data' => $device,
        ]);
    }

    private function payload(AppNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'body' => $notification->body,
            'type' => $notification->type,
            'data' => $notification->data,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }
}
