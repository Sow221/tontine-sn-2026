<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index()
    {
        // 1. Charger d'abord
        $notifications = NotificationLog::where('user_id', Auth::id())
            ->latest('created_at')
            ->paginate(20);

        // 2. Marquer comme lu après le chargement + invalider le cache
        NotificationLog::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Cache::forget('unread_notifications_' . Auth::id());

        return view('notifications.index', compact('notifications'));
    }
}
