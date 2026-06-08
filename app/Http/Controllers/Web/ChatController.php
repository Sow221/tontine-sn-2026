<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Tontine;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Compter les messages non lus par tontine (depuis la dernière visite réelle du chat)
        $lastSeenMap = DB::table('tontine_members')
            ->where('user_id', $user->id)
            ->whereIn('tontine_id', $tontines->pluck('id'))
            ->pluck('chat_last_seen_at', 'tontine_id');

        $unreadCounts = \App\Models\ChatMessage::whereIn('tontine_id', $tontines->pluck('id'))
            ->where('user_id', '!=', $user->id)
            ->where(function ($q) use ($lastSeenMap) {
                foreach ($lastSeenMap as $tontineId => $lastSeen) {
                    $q->orWhere(function ($q2) use ($tontineId, $lastSeen) {
                        $q2->where('tontine_id', $tontineId)
                           ->where('created_at', '>', $lastSeen ?? '1970-01-01');
                    });
                }
            })
            ->selectRaw('tontine_id, COUNT(*) as cnt')
            ->groupBy('tontine_id')
            ->pluck('cnt', 'tontine_id');

        return view('chat.index', compact('tontines', 'unreadCounts'));
    }

    public function stream(Request $request, Tontine $tontine)
    {
        $user = Auth::user();
        $this->authorizeAccess($tontine, $user);

        $lastId    = (int) $request->query('lastEventId', $request->query('after', 0));
        $tontineId = $tontine->id;

        // Désactiver le max_execution_time pour les connexions persistantes SSE
        set_time_limit(0);

        return response()->stream(function () use ($tontineId, $lastId, $user) {
            if (ob_get_level()) ob_end_flush();

            $current  = $lastId;
            $ticks    = 0;
            $maxTicks = 300; // 5 minutes max puis fermeture propre

            while ($ticks < $maxTicks) {
                if (connection_aborted()) break;

                $messages = ChatMessage::where('tontine_id', $tontineId)
                    ->where('id', '>', $current)
                    ->with('user:id,name')
                    ->orderBy('id')
                    ->limit(20)
                    ->get();

                foreach ($messages as $msg) {
                    $data = json_encode([
                        'id'      => $msg->id,
                        'user_id' => $msg->user_id,
                        'name'    => $msg->user?->name,
                        'message' => $msg->message,
                        'time'    => $msg->created_at->isoFormat('HH:mm \u00b7 D MMM'),
                    ]);
                    echo "id: {$msg->id}\ndata: {$data}\n\n";
                    $current = $msg->id;
                }

                // Heartbeat toutes les 15s pour maintenir la connexion
                if (++$ticks % 15 === 0) {
                    $online = DB::table('tontine_members')
                        ->where('tontine_id', $tontineId)
                        ->where('status', 'active')
                        ->where('chat_last_seen_at', '>=', now()->subMinutes(2))
                        ->count();
                    echo 'event: heartbeat' . "\n";
                    echo 'data: ' . json_encode(['online' => $online]) . "\n\n";
                }

                if (ob_get_level()) ob_flush();
                flush();

                sleep(1);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',  // Désactive le buffer Nginx
            'Connection'        => 'keep-alive',
        ]);
    }

    public function poll(Request $request, Tontine $tontine)
    {
        $user = Auth::user();
        $this->authorizeAccess($tontine, $user);

        $after = (int) $request->query('after', 0);

        $messages = ChatMessage::where('tontine_id', $tontine->id)
            ->where('id', '>', $after)
            ->with('user:id,name')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn($m) => [
                'id'      => $m->id,
                'user_id' => $m->user_id,
                'name'    => $m->user?->name,
                'message' => $m->message,
                'time'    => $m->created_at->isoFormat('HH:mm · D MMM'),
            ]);

        // Membres actifs vus dans les 2 dernières minutes (indicateur de présence)
        $online = DB::table('tontine_members')
            ->where('tontine_id', $tontine->id)
            ->where('status', 'active')
            ->where('chat_last_seen_at', '>=', now()->subMinutes(2))
            ->count();

        return response()->json(['messages' => $messages, 'online' => $online]);
    }

    public function show(Tontine $tontine)
    {
        $user = Auth::user();

        $this->authorizeAccess($tontine, $user);

        // Marquer les messages comme lus (met à jour chat_last_seen_at)
        DB::table('tontine_members')
            ->where('tontine_id', $tontine->id)
            ->where('user_id', $user->id)
            ->update(['chat_last_seen_at' => now()]);

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
            $msg = ChatMessage::create([
                'tontine_id' => $tontine->id,
                'user_id'    => $user->id,
                'message'    => trim($request->message),
            ]);

            // Mettre à jour last_seen pour l'expéditeur
            DB::table('tontine_members')
                ->where('tontine_id', $tontine->id)
                ->where('user_id', $user->id)
                ->update(['chat_last_seen_at' => now()]);

            $notifier->notifyNewChatMessage($tontine, $user, $request->message);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok'   => true,
                    'id'   => $msg->id,
                    'time' => $msg->created_at->isoFormat('HH:mm · D MMM'),
                ]);
            }

            return redirect()->route('chat.show', $tontine);
        } catch (\Throwable $e) {
            Log::error('Erreur envoi message chat', ['tontine_id' => $tontine->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['message' => ['Erreur lors de l\'envoi.']]], 422);
            }
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
