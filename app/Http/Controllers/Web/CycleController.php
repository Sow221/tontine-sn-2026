<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cycle;
use App\Services\TontineService;
use Illuminate\Http\Request;

class CycleController extends Controller
{
    public function __construct(private TontineService $service) {}

    public function draw(Cycle $cycle)
    {
        $this->authorize('update', $cycle->tontine);

        if ($cycle->beneficiary_id) {
            return back()->withErrors(['draw' => 'Le tirage a déjà été effectué.']);
        }

        $this->service->drawBeneficiary($cycle);

        return back()->with('success', 'Tirage effectué avec succès.');
    }
}
