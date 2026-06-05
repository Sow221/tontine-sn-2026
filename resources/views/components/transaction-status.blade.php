@props(['status'])

@php
    $map = [
        'success'  => ['class' => 'success', 'label' => __('member.paid')],
        'pending'  => ['class' => 'warning', 'label' => __('member.pending_validation')],
        'failed'   => ['class' => 'danger',  'label' => __('member.failed')],
        'reversed' => ['class' => 'secondary', 'label' => __('member.reversed')],
    ];
    $s = $map[$status] ?? ['class' => 'secondary', 'label' => ucfirst($status)];
@endphp

<span {{ $attributes->merge(['class' => 'badge badge-' . $s['class']]) }}>{{ $s['label'] }}</span>
