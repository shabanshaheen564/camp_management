{{-- Notification Bell --}}
<div class="header-btn" title="الإشعارات">
    <i class="fas fa-bell"></i>
</div>

{{-- Current Date --}}
<div style="font-size:0.8rem; color:#64748b; background:#f1f5f9; padding:6px 14px; border-radius:10px; border:1px solid #e2e8f0;">
    <i class="fas fa-calendar-alt me-1" style="color:#3b82f6"></i>
    <span id="current-date"></span>
</div>

<script>
    const d = new Date();
    document.getElementById('current-date').textContent = d.toLocaleDateString('ar-SA', {
        weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
    });
</script>