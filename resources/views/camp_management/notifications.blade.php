@extends('layouts.skeleton')

@section('title', 'الإشعارات')
@section('page-title', 'الإشعارات')
@section('page-icon', 'fa-bell')

@section('content')
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-bell me-2"></i>الإشعارات</h1>
    @if($unreadCount > 0)
    <button class="btn btn-outline-primary" onclick="markAllAsRead()">
        <i class="fas fa-check-double me-1"></i> تحديد الكل كمقروء
    </button>
    @endif
</div>

<div class="card">
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            @forelse($notifications as $n)
            <li class="list-group-item {{ $n->read_at ? '' : 'bg-light' }}">
                <a href="{{ $n->data['url'] ?? '#' }}"
                   class="text-decoration-none text-dark d-flex align-items-start gap-3"
                   {{ $n->read_at ? '' : 'onclick="markAsRead(\'' . $n->id . '\', this)"' }}>
                    <div class="mt-1"><i class="fas {{ $n->data['icon'] ?? 'fa-bell' }}" style="color:#3b82f6;"></i></div>
                    <div style="flex:1;">
                        <div style="font-weight:600;">{{ $n->data['title'] ?? 'إشعار' }}</div>
                        <div class="text-muted" style="font-size:0.9rem;">{{ $n->data['message'] ?? '' }}</div>
                        <div class="text-muted" style="font-size:0.78rem;">{{ $n->created_at->diffForHumans() }}</div>
                    </div>
                    @if(!$n->read_at)
                        <span class="badge bg-primary align-self-center">جديد</span>
                    @endif
                </a>
            </li>
            @empty
            <li class="list-group-item text-center text-muted py-5">
                <i class="fas fa-bell-slash fa-2x mb-2 d-block"></i>
                لا توجد إشعارات حالياً
            </li>
            @endforelse
        </ul>
        @if($notifications->hasPages())
        <div class="p-3">
            {{ $notifications->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
async function markAsRead(id, el) {
    try {
        await fetch(`/notifications/${id}/read`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
    } catch (err) {
        console.error(err);
    }
}

async function markAllAsRead() {
    try {
        await fetch('/notifications/read-all', {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        location.reload();
    } catch (err) {
        console.error(err);
    }
}
</script>
@endpush
