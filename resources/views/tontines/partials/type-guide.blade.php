@php
    $guides = [
        'fixed' => [
            'icon'  => 'fa-sync-alt',
            'class' => 'type-guide-fixed',
            'title' => __('member.type_fixed'),
            'help'  => __('member.type_fixed_help'),
        ],
        'auction' => [
            'icon'  => 'fa-gavel',
            'class' => 'type-guide-auction',
            'title' => __('member.type_auction'),
            'help'  => __('member.type_auction_help'),
        ],
        'forced_saving' => [
            'icon'  => 'fa-piggy-bank',
            'class' => 'type-guide-saving',
            'title' => __('member.type_saving'),
            'help'  => __('member.type_saving_help'),
        ],
        'ceremonial' => [
            'icon'  => 'fa-heart',
            'class' => 'type-guide-ceremonial',
            'title' => __('member.type_ceremonial'),
            'help'  => __('member.type_ceremonial_help'),
        ],
    ];
    $guide = $guides[$tontine->type] ?? $guides['fixed'];
@endphp
<div class="type-guide-card {{ $guide['class'] }} mb-4">
    <div class="d-flex align-items-start gap-3">
        <div class="type-guide-icon"><i class="fas {{ $guide['icon'] }}"></i></div>
        <div>
            <h6 class="fw-bold mb-1">{{ $guide['title'] }}</h6>
            <p class="mb-0 small text-muted">{{ $guide['help'] }}</p>
        </div>
    </div>
</div>
