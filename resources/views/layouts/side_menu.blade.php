<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-campground"></i>
        </div>
        <div class="brand-text">
            نظام المخيمات
            <small>Camp Management</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">الرئيسية</div>

        <a href="{{ route('dashboard') }}" class="nav-link-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fas fa-th-large"></i>
            لوحة التحكم
        </a>

        <div class="nav-section-label">إدارة المخيمات</div>

        <a href="{{ route('camps.index') }}" class="nav-link-item {{ request()->routeIs('camps.*') ? 'active' : '' }}">
            <i class="fas fa-tent"></i>
            المخيمات
        </a>

        <a href="{{ route('families.index') }}"
            class="nav-link-item {{ request()->routeIs('families.*') ? 'active' : '' }}">
            <i class="fas fa-users"></i>
            العائلات والأفراد
        </a>

        <a href="{{ route('aid.index') }}" class="nav-link-item {{ request()->routeIs('aid.*') ? 'active' : '' }}">
            <i class="fas fa-box-open"></i>
            توزيع المساعدات
        </a>

        <div class="nav-section-label">التحليل والمتابعة</div>

        <a href="{{ route('reports.index') }}"
            class="nav-link-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="fas fa-chart-bar"></i>
            التقارير والإحصائيات
        </a>

        <a href="{{ route('map.index') }}" class="nav-link-item {{ request()->routeIs('map.*') ? 'active' : '' }}">
            <i class="fas fa-map-marked-alt"></i>
            الخريطة التفاعلية
        </a>

        <a href="{{ route('notifications.index') }}"
            class="nav-link-item d-flex align-items-center justify-content-between {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
            <span><i class="fas fa-bell"></i> الإشعارات</span>
            <span id="sidebarNotifBadge" class="badge bg-danger rounded-pill" style="display:none;">0</span>
        </a>

        <div class="nav-section-label">الإدارة</div>

        @if(auth()->user()->role?->display_name === 'Administrator')
            <a href="{{ route('users.index') }}" class="nav-link-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="fas fa-user-cog"></i>
                المستخدمون
            </a>

            <a href="{{ route('roles.index') }}" class="nav-link-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <i class="fas fa-shield-alt"></i>
                الأدوار والصلاحيات
            </a>
        @endif
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name ?? 'المستخدم' }}</div>
                <div class="user-role">{{ auth()->user()->role?->name ?? 'مستخدم' }}</div>
            </div>
            <button class="logout-btn" onclick="logout()" title="تسجيل الخروج">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </div>
    </div>
</aside>