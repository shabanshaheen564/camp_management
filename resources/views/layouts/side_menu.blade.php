<aside class="sidebar" id="sidebar">
    @php
        $badges = $sectionBadges ?? [];
    @endphp

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

        <a href="{{ route('camps.index') }}" class="nav-link-item d-flex align-items-center justify-content-between {{ request()->routeIs('camps.*') ? 'active' : '' }}">
            <span><i class="fas fa-tent"></i> المخيمات</span>
            <span id="sidebarBadgeCamps" class="badge bg-danger rounded-pill" style="{{ ($badges['camps'] ?? 0) > 0 ? '' : 'display:none;' }}">{{ ($badges['camps'] ?? 0) > 99 ? '99+' : ($badges['camps'] ?? 0) }}</span>
        </a>

        <a href="{{ route('families.index') }}"
            class="nav-link-item d-flex align-items-center justify-content-between {{ request()->routeIs('families.index') ? 'active' : '' }}">
            <span><i class="fas fa-users"></i> العائلات والأفراد</span>
            <span id="sidebarBadgeFamilies" class="badge bg-danger rounded-pill" style="{{ ($badges['families'] ?? 0) > 0 ? '' : 'display:none;' }}">{{ ($badges['families'] ?? 0) > 99 ? '99+' : ($badges['families'] ?? 0) }}</span>
        </a>

        <a href="{{ route('families.trash') }}"
            class="nav-link-item d-flex align-items-center justify-content-between {{ request()->routeIs('families.trash') ? 'active' : '' }}"
            style="padding-right:2.2rem; font-size:0.85rem; opacity:0.85;">
            <span><i class="fas fa-trash" style="font-size:0.8rem;"></i> سلة محذوفات العائلات</span>
            <span id="sidebarBadgeFamiliesTrash" class="badge bg-danger rounded-pill" style="{{ ($badges['families.trash'] ?? 0) > 0 ? '' : 'display:none;' }}">{{ ($badges['families.trash'] ?? 0) > 99 ? '99+' : ($badges['families.trash'] ?? 0) }}</span>
        </a>

        <a href="{{ route('aid.index') }}" class="nav-link-item d-flex align-items-center justify-content-between {{ request()->routeIs('aid.*') ? 'active' : '' }}">
            <span><i class="fas fa-box-open"></i> توزيع المساعدات</span>
            <span id="sidebarBadgeAid" class="badge bg-danger rounded-pill" style="{{ ($badges['aid'] ?? 0) > 0 ? '' : 'display:none;' }}">{{ ($badges['aid'] ?? 0) > 99 ? '99+' : ($badges['aid'] ?? 0) }}</span>
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
            <span id="sidebarNotifBadge" class="badge bg-danger rounded-pill" style="{{ ($badges['notifications'] ?? 0) > 0 ? '' : 'display:none;' }}">{{ ($badges['notifications'] ?? 0) > 99 ? '99+' : ($badges['notifications'] ?? 0) }}</span>
        </a>

        <div class="nav-section-label">الإدارة</div>

        @if(auth()->user()->role?->display_name === 'Administrator')
            <a href="{{ route('users.index') }}" class="nav-link-item d-flex align-items-center justify-content-between {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <span><i class="fas fa-user-cog"></i> المستخدمون</span>
                <span id="sidebarBadgeUsers" class="badge bg-danger rounded-pill" style="{{ ($badges['users'] ?? 0) > 0 ? '' : 'display:none;' }}">{{ ($badges['users'] ?? 0) > 99 ? '99+' : ($badges['users'] ?? 0) }}</span>
            </a>

            <a href="{{ route('roles.index') }}" class="nav-link-item d-flex align-items-center justify-content-between {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <span><i class="fas fa-shield-alt"></i> الأدوار والصلاحيات</span>
                <span id="sidebarBadgeRoles" class="badge bg-danger rounded-pill" style="{{ ($badges['roles'] ?? 0) > 0 ? '' : 'display:none;' }}">{{ ($badges['roles'] ?? 0) > 99 ? '99+' : ($badges['roles'] ?? 0) }}</span>
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
