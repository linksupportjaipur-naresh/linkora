@php
    $map = [
        'active' => 'green', 'suspended' => 'red', 'deactivated' => 'amber',
        'published' => 'green', 'hidden' => 'amber', 'blocked' => 'red', 'live' => 'green',
        'paid' => 'green', 'pending' => 'amber', 'refunded' => '', 'failed' => 'red',
        'current' => 'green', 'cancelled' => '', 'expired' => 'amber',
        'new' => '', 'read' => 'amber', 'replied' => 'green', 'closed' => '',
    ];
    $labels = ['suspended' => 'Blocked'];
@endphp
<span class="badge-soft {{ $map[$s] ?? '' }}">{{ $labels[$s] ?? ucfirst($s) }}</span>
