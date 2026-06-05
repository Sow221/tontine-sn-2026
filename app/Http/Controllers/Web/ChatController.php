<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Tontine;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $tontines = $user->memberships()
            ->wherePivot('status', 'active')
            ->with(['latestMessage.user'])
            ->withCount(['members as members_count' => fn($q) => $q->where('tontine_members.status', 'active')])
            ->get();

        // Compter les messages non lus par tontine (messages après la dernière visite)
        $unreadCounts = \App\Models\ChatMessage::whereIn('tontine_id', $tontines->pluck('id'))
            ->where('user_id', '!=', $user->id)
            ->where('created_at', '>', now()->subDays(7))
            ->selectRaw('tontine_id, COUNT(*) as cnt')
            ->groupBy('tontine_id')
            ->pluck('cnt', 'tontine_id');

        return view('chat.index', compact('tontines', 'unreadCounts'));
    }

    public function show(Tontine $tontine)
    {
        $user = Auth::user();

        $this->authorizeAccess($tontine, $user);

        $messages = ChatMessage::where('tontine_id', $tontine->id)
            ->with('user')
            ->orderBy('created_at')
            ->paginate(50);

        return view('chat.show', compact('tontine', 'messages'));
    }

    public function send(Request $request, Tontine $tontine, NotificationService $notifier)
    {
        $user = Auth::user();

        $this->authorizeAccess($tontine, $user);

        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'message.required' => 'Le message ne peut pas être vide.',
            'message.max'      => 'Le message ne doit pas dépasser 2000 caractères.',
        ]);

        try {
            ChatMessage::create([
                'tontine_id' => $tontine->id,
                'user_id'    => $user->id,
                'message'    => trim($request->message),
            ]);

            $notifier->notifyNewChatMessage($tontine, $user, $request->message);

            return redirect()->route('chat.show', $tontine)
                ->with('success', 'Message envoyé.');
        } catch (\Throwable $e) {
            Log::error('Erreur envoi message chat', ['tontine_id' => $tontine->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors de l\'envoi du message.']);
        }
    }

    private function authorizeAccess(Tontine $tontine, $user): void
    {
        $isMember = $tontine->members()
            ->where('users.id', $user->id)
            ->where('tontine_members.status', 'active')
            ->exists();

        abort_unless($isMember, 403, 'Vous n\'êtes pas membre actif de cette tontine.');
    }
}
