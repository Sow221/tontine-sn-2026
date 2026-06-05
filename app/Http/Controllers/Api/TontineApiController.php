<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCycle;
use App\Models\Tontine;
use App\Services\NotificationService;
use App\Services\TontineService;
use Illuminate\Http\Request;

class TontineApiController extends Controller
{
    public function __construct(private TontineService $service) {}

    public function index(Request $request)
    {
        $tontines = $request->user()->memberships()
            ->withPivot('status', 'position')
            ->withCount(['members as active_members_count' => fn($q) => $q->where('tontine_members.status', 'active')])
            ->with('currentCycle')
            ->paginate(15);

        return response()->json($tontines->through(fn($t) => $this->tontineResource($t)));
    }

    public function show(Tontine $tontine)
    {
        $this->authorize('view', $tontine);

        $tontine->load(['members', 'cycles.beneficiary', 'currentCycle.transactions', 'currentCycle.auctionBids']);

        return response()->json($this->tontineDetailResource($tontine));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'amount'       => ['required', 'integer', 'min:500', 'max:500000'],
            'frequency'    => ['required', 'in:daily,weekly,monthly'],
            'type'         => ['required', 'in:fixed,auction,forced_saving,ceremonial'],
            'start_date'   => ['required', 'date', 'after_or_equal:today'],
            'end_date'     => ['nullable', 'date', 'after:start_date'],
            'max_members'  => ['required', 'integer', 'min:2', 'max:50'],
            'penalty_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'draw_method'  => ['required', 'in:random,sequential'],
        ]);

        $tontine = Tontine::create([...$data, 'created_by' => $request->user()->id]);
        $tontine->members()->attach($request->user()->id, [
            'status'    => 'active',
            'position'  => 1,
            'joined_at' => now(),
        ]);

        return response()->json($this->tontineResource($tontine), 201);
    }

    public function join(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $tontine = Tontine::where('code', strtoupper($request->code))->first();
        $result  = $this->service->joinTontine($tontine, $request->user()->id);

        $status = $result['ok'] ? 200 : 422;
        if (!$result['ok'] && str_contains($result['message'], 'invalide')) $status = 404;

        return response()->json(['message' => $result['message']], $status);
    }

    public function activate(Tontine $tontine)
    {
        $this->authorize('update', $tontine);

        if ($tontine->activeMembers()->count() < 2) {
            return response()->json(['message' => 'Minimum 2 membres actifs requis.'], 422);
        }
        if ($tontine->cycles()->exists()) {
            return response()->json(['message' => 'Tontine déjà activée.'], 422);
        }

        $tontine->update(['status' => 'active']);
        ProcessCycle::dispatch($tontine);

        return response()->json(['message' => 'Tontine activée. Cycles en cours de génération.']);
    }

    public function approveMember(Request $request, Tontine $tontine, \App\Models\User $user)
    {
        $this->authorize('update', $tontine);

        $tontine->members()->updateExistingPivot($user->id, ['status' => 'active']);

        app(\App\Services\NotificationService::class)->notifyMemberApproved($user, $tontine->name);

        return response()->json(['message' => $user->name . ' a été approuvé.']);
    }

    private function tontineResource(Tontine $t): array
    {
        return [
            'id'                  => $t->id,
            'name'                => $t->name,
            'code'                => $t->code,
            'type'                => $t->type,
            'amount'              => $t->amount,
            'frequency'           => $t->frequency,
            'status'              => $t->status,
            'active_members_count'=> $t->active_members_count ?? 0,
            'max_members'         => $t->max_members,
            'my_status'           => $t->pivot?->status,
            'current_cycle'       => $t->currentCycle ? [
                'id'               => $t->currentCycle->id,
                'cycle_number'     => $t->currentCycle->cycle_number,
                'due_date'         => $t->currentCycle->due_date->format('Y-m-d'),
                'status'           => $t->currentCycle->status,
                'completion_rate'  => $t->currentCycle->completionRate(),
            ] : null,
        ];
    }

    private function tontineDetailResource(Tontine $t): array
    {
        $base = $this->tontineResource($t);
        $base['members'] = $t->members->map(fn($m) => [
            'id'       => $m->id,
            'name'     => $m->name,
            'avatar'   => $m->avatar,
            'status'   => $m->pivot->status,
            'position' => $m->pivot->position,
        ]);
        $base['cycles'] = $t->cycles->map(fn($c) => [
            'id'             => $c->id,
            'cycle_number'   => $c->cycle_number,
            'due_date'       => $c->due_date->format('Y-m-d'),
            'status'         => $c->status,
            'total_collected'=> $c->total_collected,
            'beneficiary'    => $c->beneficiary?->name,
        ]);
        return $base;
    }
}
