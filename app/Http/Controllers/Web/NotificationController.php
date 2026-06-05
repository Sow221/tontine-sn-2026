<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        NotificationLog::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $notifications = NotificationLog::where('user_id', Auth::id())
            ->latest('created_at')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }
}
