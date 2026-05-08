<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTontineRequest;
use App\Jobs\ProcessCycle;
use App\Models\Tontine;
use App\Services\TontineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TontineController extends Controller
{
    public function __construct(private TontineService $service) {}

    public function index()
    {
        $tontines = Auth::user()->memberships()
            ->withPivot('status', 'position')
            ->with('creator', 'cycles')
            ->paginate(10);

        return view('tontines.index', compact('tontines'));
    }

    public function create()
    {
        return view('tontines.create');
    }

    public function store(StoreTontineRequest $request)
    {
        $tontine = Tontine::create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        // Ajouter le créateur comme membre actif (position 1)
        $tontine->members()->attach(Auth::id(), [
            'status'    => 'active',
            'position'  => 1,
            'joined_at' => now(),
        ]);

        return redirect()->route('tontines.show', $tontine)
                         ->with('success', 'Tontine créée avec succès !');
    }

    public function show(Tontine $tontine)
    {
        $this->authorize('view', $tontine);

        $tontine->load(['members', 'cycles.beneficiary', 'cycles.transactions']);
        $currentCycle = $tontine->currentCycle();

        return view('tontines.show', compact('tontine', 'currentCycle'));
    }

    public function edit(Tontine $tontine)
    {
        $this->authorize('update', $tontine);
        return view('tontines.edit', compact('tontine'));
    }

    public function update(StoreTontineRequest $request, Tontine $tontine)
    {
        $this->authorize('update', $tontine);
        $tontine->update($request->validated());

        return redirect()->route('tontines.show', $tontine)
                         ->with('success', 'Tontine mise à jour.');
    }

    public function activate(Tontine $tontine)
    {
        $this->authorize('update', $tontine);

        $tontine->update(['status' => 'active']);
        ProcessCycle::dispatch($tontine);

        return back()->with('success', 'Tontine activée. Les cycles ont été générés.');
    }

    public function join(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $tontine = Tontine::where('code', strtoupper($request->code))
                          ->where('status', 'pending')
                          ->firstOrFail();

        if ($tontine->isFull()) {
            return back()->withErrors(['code' => 'Cette tontine est complète.']);
        }

        $position = $tontine->members()->count() + 1;

        $tontine->members()->syncWithoutDetaching([
            Auth::id() => [
                'status'    => 'pending',
                'position'  => $position,
                'joined_at' => now(),
            ],
        ]);

        return redirect()->route('tontines.show', $tontine)
                         ->with('success', 'Demande d\'adhésion envoyée.');
    }

    public function destroy(Tontine $tontine)
    {
        $this->authorize('delete', $tontine);

        $tontine->delete();

        return redirect()->route('tontines.index')
                         ->with('success', 'Tontine supprimée.');
    }
}
