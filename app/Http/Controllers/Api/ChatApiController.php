<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Tontine;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatApiController extends Controller
{
    public function index(Tontine $tontine): JsonResponse
    {
        $this->authorizeAccess($tontine);

        $messages = ChatMessage::where('tontine_id', $tontine->id)
            ->with('user:id,name,avatar')
            ->latest('created_at')
            ->paginate(50);

        return response()->json($messages);
    }

    public function poll(Request $request, Tontine $tontine): JsonResponse
    {
        $this->authorizeAccess($tontine);

        $after = (int) $request->query('after', 0);

        $messages = ChatMessage::where('tontine_id', $tontine->id)
            ->where('id', '>', $after)
            ->with('user:id,name,avatar')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'name' => $m->user?->name,
                'avatar' => $m->user?->avatar,
                'message' => $m->message,
                'created_at' => $m->created_at->toIso8601String(),
            ]);

        return response()->json(['messages' => $messages]);
    }

    public function send(Request $request, Tontine $tontine, NotificationService $notifier): JsonResponse
    {
        $this->authorizeAccess($tontine);

        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $message = ChatMessage::create([
                'tontine_id' => $tontine->id,
                'user_id' => Auth::id(),
                'message' => trim($request->message),
            ]);

            $notifier->notifyNewChatMessage($tontine, Auth::user(), $request->message);

            return response()->json([
                'success' => true,
                'message' => 'Message envoyé.',
                'data' => $message->load('user:id,name,avatar'),
            ]);
        } catch (\Throwable $e) {
            Log::error('API chat erreur envoi', ['tontine_id' => $tontine->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Erreur lors de l\'envoi.'], 500);
        }
    }

    private function authorizeAccess(Tontine $tontine): void
    {
        abort_unless(
            $tontine->members()
                ->where('users.id', Auth::id())
                ->where('tontine_members.status', 'active')
                ->exists(),
            403,
            'Vous n\'êtes pas membre actif de cette tontine.'
        );
    }
}
