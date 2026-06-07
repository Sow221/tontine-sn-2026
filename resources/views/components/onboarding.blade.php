{{-- Onboarding — affiché une seule fois au premier login membre --}}
@auth
@if(!auth()->user()->isAdmin() && !auth()->user()->onboarding_completed)
<div id="onboarding-overlay" role="dialog" aria-modal="true" aria-labelledby="onboarding-title"
     style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9998;display:flex;align-items:center;justify-content:center;padding:16px;">

    <div style="background:white;border-radius:16px;max-width:440px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,0.2);overflow:hidden;">

        <div style="height:4px;background:#e2e8f0;">
            <div id="ob-fill" style="width:25%;background:#009639;transition:width 0.3s;height:4px;"></div>
        </div>

        {{-- Étape 1 --}}
        <div id="ob-1" class="p-4 text-center">
            <div style="font-size:52px;margin-bottom:12px;">🤝</div>
            <h5 class="fw-bold mb-2" id="onboarding-title">Bienvenue sur TontineSN !</h5>
            <p class="text-muted small mb-4">Gérez vos tontines en ligne en toute simplicité. Ce guide rapide vous explique l'essentiel en 4 étapes.</p>
            <button type="button" class="btn btn-primary w-100 rounded-pill" onclick="obGo(2)">
                Commencer <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </div>

        {{-- Étape 2 --}}
        <div id="ob-2" class="p-4" style="display:none;">
            <div class="text-center mb-3" style="font-size:40px;">➕</div>
            <h6 class="fw-bold mb-3 text-center">Créer ou rejoindre une tontine</h6>
            <div class="d-flex flex-column gap-3 mb-4">
                <div class="d-flex align-items-start gap-3 p-3 rounded" style="background:#f0fdf4;border:1px solid rgba(0,150,57,0.15);">
                    <i class="fas fa-plus-circle mt-1" style="color:#009639;font-size:18px;flex-shrink:0;"></i>
                    <div>
                        <p class="fw-semibold small mb-0">Créer une tontine</p>
                        <small class="text-muted">Choisissez un type, définissez le montant et invitez vos membres par code ou lien.</small>
                    </div>
                </div>
                <div class="d-flex align-items-start gap-3 p-3 rounded" style="background:#eff6ff;border:1px solid rgba(59,130,246,0.15);">
                    <i class="fas fa-link mt-1" style="color:#3b82f6;font-size:18px;flex-shrink:0;"></i>
                    <div>
                        <p class="fw-semibold small mb-0">Rejoindre avec un code</p>
                        <small class="text-muted">Entrez le code à 6 lettres partagé par le créateur de la tontine.</small>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1 rounded-pill" onclick="obGo(1)">Retour</button>
                <button type="button" class="btn btn-primary flex-grow-1 rounded-pill" onclick="obGo(3)">Suivant</button>
            </div>
        </div>

        {{-- Étape 3 --}}
        <div id="ob-3" class="p-4" style="display:none;">
            <div class="text-center mb-3" style="font-size:40px;">💳</div>
            <h6 class="fw-bold mb-3 text-center">Payer votre cotisation</h6>
            <div class="d-flex flex-column gap-2 mb-4">
                <div class="d-flex align-items-center gap-2 small"><i class="fas fa-check-circle" style="color:#009639;"></i><span>Payez via <strong>Wave</strong>, <strong>Orange Money</strong>, <strong>Free Money</strong></span></div>
                <div class="d-flex align-items-center gap-2 small"><i class="fas fa-check-circle" style="color:#009639;"></i><span>Ou en <strong>espèces</strong> — le créateur valide manuellement</span></div>
                <div class="d-flex align-items-center gap-2 small"><i class="fas fa-check-circle" style="color:#009639;"></i><span>Téléchargez votre <strong>reçu PDF</strong> après chaque paiement</span></div>
                <div class="d-flex align-items-center gap-2 small"><i class="fas fa-exclamation-triangle text-warning"></i><span>En cas de retard, une <strong>pénalité</strong> peut s'appliquer</span></div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1 rounded-pill" onclick="obGo(2)">Retour</button>
                <button type="button" class="btn btn-primary flex-grow-1 rounded-pill" onclick="obGo(4)">Suivant</button>
            </div>
        </div>

        {{-- Étape 4 --}}
        <div id="ob-4" class="p-4" style="display:none;">
            <div class="text-center mb-3" style="font-size:40px;">⭐</div>
            <h6 class="fw-bold mb-3 text-center">Votre score crédit &amp; suivi</h6>
            <div class="d-flex flex-column gap-2 mb-4">
                <div class="d-flex align-items-center gap-2 small"><i class="fas fa-chart-line" style="color:#009639;"></i><span>Chaque paiement à l'heure améliore votre <strong>score crédit</strong></span></div>
                <div class="d-flex align-items-center gap-2 small"><i class="fas fa-fire" style="color:#f59e0b;"></i><span>Maintenez votre <strong>série de paiements</strong> pour débloquer des badges</span></div>
                <div class="d-flex align-items-center gap-2 small"><i class="fas fa-bell" style="color:#009639;"></i><span>Recevez des <strong>rappels</strong> avant chaque échéance</span></div>
                <div class="d-flex align-items-center gap-2 small"><i class="fas fa-history" style="color:#009639;"></i><span>Consultez votre <strong>historique</strong> et exportez vos données</span></div>
            </div>
            <button type="button" class="btn btn-primary w-100 rounded-pill mb-2" onclick="obClose()">
                <i class="fas fa-rocket me-2"></i>C'est parti !
            </button>
            <button type="button" class="btn btn-link w-100 text-muted small" onclick="obClose()">Passer ce guide</button>
        </div>

    </div>
</div>

<script>
function obGo(step) {
    for (var i = 1; i <= 4; i++) {
        var el = document.getElementById('ob-' + i);
        if (el) el.style.display = (i === step) ? '' : 'none';
    }
    var fill = document.getElementById('ob-fill');
    if (fill) fill.style.width = (step / 4 * 100) + '%';
}
function obClose() {
    var overlay = document.getElementById('onboarding-overlay');
    if (overlay) overlay.style.display = 'none';
    fetch('{{ route('onboarding.complete') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).catch(function() {});
}
</script>
@endif
@endauth
