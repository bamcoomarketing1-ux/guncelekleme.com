<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function broadcast(Request $request, NotificationService $notifications): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
        ]);

        $count = $notifications->broadcast($request->title, $request->body);

        return response()->json(['status' => 'success', 'message' => "{$count} kullanıcıya bildirim gönderildi."]);
    }
}
