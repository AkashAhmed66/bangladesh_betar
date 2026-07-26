@props(['status'])

@php
    $map = [
        // success
        'published' => 'green', 'active' => 'green', 'approved' => 'green', 'cleared' => 'green',
        'completed' => 'green', 'success' => 'green', 'pass' => 'green', 'accepted' => 'green',
        'resolved' => 'green', 'verified' => 'green', 'ok' => 'green', 'qc_passed' => 'green', 'archived' => 'green',
        // danger
        'rejected' => 'red', 'failed' => 'red', 'fail' => 'red', 'banned' => 'red', 'expired' => 'red',
        'corrupt' => 'red', 'removed' => 'red', 'disputed' => 'red', 'refunded' => 'red', 'upheld' => 'red',
        'ai_rejected' => 'red', 'deleted' => 'slate',
        // warning
        'pending' => 'amber', 'pending_approval' => 'amber', 'in_review' => 'amber',
        'in_progress' => 'amber', 'changes_requested' => 'amber', 'warning' => 'amber', 'grace' => 'amber',
        'trialing' => 'amber', 'scheduled' => 'amber', 'investigating' => 'amber', 'qc_pending' => 'amber',
        'partially_refunded' => 'amber', 'in_production' => 'amber', 'restricted' => 'amber',
        'analyzing' => 'amber', 'ai_flagged' => 'amber',
        // info
        'draft' => 'slate', 'registered' => 'slate', 'inactive' => 'slate', 'cancelled' => 'slate',
        'unpublished' => 'slate', 'hidden' => 'slate', 'dismissed' => 'slate', 'received' => 'blue',
        'open' => 'blue', 'new' => 'blue', 'captured' => 'blue', 'restored' => 'blue', 'submitted' => 'blue', 'paused' => 'slate',
        'reviewed' => 'blue', 'actioned' => 'green', 'read' => 'slate', 'responded' => 'green', 'closed' => 'slate',
        'reprocess' => 'amber', 'changes requested' => 'amber',
    ];
    $color = $map[$status] ?? 'slate';
@endphp

<span class="badge-{{ $color }}">{{ str_replace('_', ' ', ucfirst($status)) }}</span>
