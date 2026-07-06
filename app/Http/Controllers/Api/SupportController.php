<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Services\SupportAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function messages(Request $request): JsonResponse
    {
        $rows = SupportMessage::where('user_id', $request->user()->id)
            ->orderBy('id')
            ->get()
            ->map(fn (SupportMessage $message) => $message->toChatApiArray())
            ->values();

        return response()->json($rows);
    }

    public function send(Request $request, SupportAiService $ai): JsonResponse
    {
        $request->validate(['message' => 'required|string']);
        $msg = SupportMessage::create([
            'user_id' => $request->user()->id,
            'sender' => 'user',
            'message' => $request->message,
        ]);

        $botReply = $ai->reply($msg);

        return response()->json($this->sendResponse($msg, $botReply));
    }

    public function guestMessage(Request $request, SupportAiService $ai): JsonResponse
    {
        $request->validate(['message' => 'required|string']);
        $msg = SupportMessage::create([
            'user_id' => null,
            'sender' => 'guest',
            'message' => $request->input('message'),
        ]);

        $botReply = $ai->reply($msg);

        return response()->json($this->sendResponse($msg, $botReply, 'Mesajınız alındı.'));
    }

    public function clearMessages(Request $request): JsonResponse
    {
        SupportMessage::where('user_id', $request->user()->id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Sohbet geçmişi temizlendi.']);
    }

    public function adminHistory(): JsonResponse
    {
        $rows = SupportMessage::orderByDesc('id')->limit(200)->get();

        return response()->json(['status' => 'success', 'data' => $rows, 'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => $rows->count()]]);
    }

    public function adminStats(): JsonResponse
    {
        $userMessages = SupportMessage::where('sender', 'user')->get(['created_at']);
        $botMessages = SupportMessage::where('sender', 'bot')->orderBy('id')->get(['created_at', 'id']);
        $avgResponse = 0;
        if ($userMessages->isNotEmpty() && $botMessages->isNotEmpty()) {
            $total = 0;
            $count = 0;
            foreach ($userMessages as $um) {
                $reply = $botMessages->first(fn ($b) => $b->created_at >= $um->created_at);
                if ($reply) {
                    $total += $um->created_at->diffInSeconds($reply->created_at);
                    $count++;
                }
            }
            $avgResponse = $count > 0 ? round($total / $count) : 0;
        }

        return response()->json(['status' => 'success', 'data' => [
            'total_chats' => SupportMessage::distinct('user_id')->count('user_id'),
            'today_chats' => SupportMessage::whereDate('created_at', today())->count(),
            'avg_response_time' => $avgResponse,
        ]]);
    }

    public function adminReply(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'message' => 'required|string',
        ]);

        $msg = SupportMessage::create([
            'user_id' => $request->user_id,
            'sender' => 'admin',
            'message' => $request->message,
        ]);

        return response()->json(['status' => 'success', 'data' => $msg]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sendResponse(SupportMessage $userMessage, ?SupportMessage $botReply, string $message = 'Mesaj gönderildi.'): array
    {
        $response = [
            'status' => 'success',
            'success' => true,
            'message' => $message,
            'user_message' => $userMessage->toChatApiArray(),
            'data' => $userMessage,
        ];

        if ($botReply) {
            $response['bot_message'] = $botReply->toChatApiArray();
            $response['reply'] = $botReply;
        }

        return $response;
    }
}
