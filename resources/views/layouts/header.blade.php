{{-- Notification Bell --}}
<div class="dropdown">
    <button class="header-btn dropdown-toggle" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="الإشعارات">
        <i class="fas fa-bell"></i>
        <span id="notificationBadge" class="badge bg-danger rounded-pill" style="position:absolute; top:-4px; left:-4px; font-size:0.65rem; display:none;">0</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" style="width: 360px; max-height: 420px; overflow-y: auto;">
        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
            <strong>الإشعارات</strong>
            <button class="btn btn-sm btn-link p-0" onclick="markAllAsRead()">تحديد الكل كمقروء</button>
        </li>
        <li id="notificationsList">
            <div class="text-center py-3 text-muted">
                <i class="fas fa-spinner fa-spin"></i> جاري التحميل...
            </div>
        </li>
    </ul>
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

    document.getElementById('notificationsDropdown').addEventListener('show.bs.dropdown', function () {
        loadNotifications();
    });

    async function loadNotifications() {
        const list = document.getElementById('notificationsList');
        try {
            const res = await fetch('/notifications');
            const data = await res.json();
            updateNotificationBadge(data.unread_count || 0);
            renderNotifications(data.notifications || []);
        } catch (err) {
            list.innerHTML = '<div class="text-center py-3 text-muted">فشل تحميل الإشعارات</div>';
        }
    }

    function updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline';
        } else {
            badge.style.display = 'none';
        }
    }

    function renderNotifications(notifications) {
        const list = document.getElementById('notificationsList');
        if (notifications.length === 0) {
            list.innerHTML = '<div class="text-center py-3 text-muted">لا توجد إشعارات</div>';
            return;
        }

        let html = '';
        notifications.forEach(n => {
            const time = new Date(n.created_at).toLocaleString('ar-SA');
            const readClass = n.read_at ? '' : 'bg-light';
            html += `<li>
                <a class="dropdown-item ${readClass}" href="${n.url || '#'}" ${n.read_at ? '' : `onclick="markAsRead('${n.id}', this)"`}>
                    <div class="d-flex align-items-start gap-2">
                        <div class="mt-1"><i class="fas ${n.icon || 'fa-bell'}" style="color:#3b82f6;"></i></div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600; font-size:0.85rem;">${n.title}</div>
                            <div style="font-size:0.82rem; color:#64748b;">${n.message}</div>
                            <div style="font-size:0.72rem; color:#94a3b8;">${time}</div>
                        </div>
                    </div>
                </a>
            </li>`;
        });
        list.innerHTML = html;
    }

    async function markAsRead(id, el) {
        try {
            await fetch(`/notifications/${id}/read`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            if (el) el.classList.remove('bg-light');
            loadNotifications();
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
            loadNotifications();
        } catch (err) {
            console.error(err);
        }
    }
</script>
