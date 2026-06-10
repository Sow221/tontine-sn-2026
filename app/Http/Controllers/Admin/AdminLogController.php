<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminLogController extends Controller
{
    public function index(Request $request)
    {
        try {
            $logs = DB::table('activity_logs')
                ->join('users', 'users.id', '=', 'activity_logs.user_id')
                ->select('activity_logs.*', 'users.name', 'users.email')
                ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                    $q2->where('users.name', 'like', "%{$request->search}%")
                        ->orWhere('activity_logs.action', 'like', "%{$request->search}%");
                }))
                ->when($request->date_from, fn ($q) => $q->whereDate('activity_logs.created_at', '>=', $request->date_from))
                ->when($request->date_to, fn ($q) => $q->whereDate('activity_logs.created_at', '<=', $request->date_to))
                ->latest('activity_logs.created_at')
                ->paginate(50);

            return view('admin.logs', compact('logs'));
        } catch (\Throwable $e) {
            Log::error('Erreur chargement logs', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors du chargement des journaux.']);
        }
    }

    public function export()
    {
        try {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="logs-activite-tontinesn-'.now()->format('Y-m-d').'.csv"',
            ];

            return response()->stream(function () {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                fputcsv($file, ['ID', 'Utilisateur', 'Email', 'Action', 'IP', 'Date'], ';');

                DB::table('activity_logs')
                    ->join('users', 'users.id', '=', 'activity_logs.user_id')
                    ->select('activity_logs.*', 'users.name', 'users.email')
                    ->latest('activity_logs.created_at')
                    ->chunk(500, function ($logs) use ($file) {
                        foreach ($logs as $log) {
                            fputcsv($file, [
                                $log->id,
                                $log->name ?? '—',
                                $log->email ?? '—',
                                $log->action,
                                $log->ip_address ?? '—',
                                Carbon::parse($log->created_at)->format('d/m/Y H:i:s'),
                            ], ';');
                        }
                    });

                fclose($file);
            }, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Erreur export logs', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => "Erreur lors de l'export."]);
        }
    }

    public function notifications(Request $request)
    {
        try {
            $notifications = NotificationLog::with('user')
                ->when($request->channel, fn ($q) => $q->where('channel', $request->channel))
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->when($request->event, fn ($q) => $q->where('event', $request->event))
                ->latest()
                ->paginate(30)->withQueryString();

            return view('admin.notifications', compact('notifications'));
        } catch (\Throwable $e) {
            Log::error('Erreur logs notifications admin', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors du chargement.']);
        }
    }
}
