@extends('layouts.skeleton')

@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')
@section('page-icon', 'fa-th-large')

@section('content')

{{-- ترحيب --}}
<div style="margin-bottom:24px;">
    <h2 style="font-size:1.3rem; font-weight:800; color:#1e293b; margin-bottom:4px;">
        مرحباً، {{ auth()->user()->name }} 👋
    </h2>
    <p style="color:#64748b; font-size:0.9rem;">إليك ملخص النظام اليوم</p>
</div>

{{-- بطاقات الإحصائيات --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(59,130,246,0.1);">
                <i class="fas fa-tent" style="color:#3b82f6;"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#3b82f6;">{{ $stats['total_camps'] }}</div>
                <div class="stat-label">إجمالي المخيمات</div>
                <div style="font-size:0.75rem; color:#10b981; margin-top:2px;">
                    <i class="fas fa-circle" style="font-size:0.5rem;"></i>
                    {{ $stats['active_camps'] }} نشط
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(16,185,129,0.1);">
                <i class="fas fa-users" style="color:#10b981;"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#10b981;">{{ number_format($stats['total_displaced']) }}</div>
                <div class="stat-label">إجمالي النازحين</div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:2px;">
                    {{ $stats['total_families'] }} عائلة مسجلة
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(245,158,11,0.1);">
                <i class="fas fa-box-open" style="color:#f59e0b;"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#f59e0b;">{{ $stats['total_aid'] }}</div>
                <div class="stat-label">توزيعات المساعدات</div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:2px;">هذا الشهر</div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(139,92,246,0.1);">
                <i class="fas fa-chart-pie" style="color:#8b5cf6;"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#8b5cf6;">{{ $stats['occupancy_rate'] }}%</div>
                <div class="stat-label">نسبة الإشغال</div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:2px;">
                    متوسط حجم الأسرة: {{ $stats['avg_family_size'] }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- المحتوى الرئيسي --}}
<div class="row g-3">

    {{-- المخيمات الأخيرة --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-tent"></i>
                    آخر المخيمات المضافة
                </h5>
                <a href="{{ route('camps.index') }}" style="font-size:0.82rem; color:#3b82f6; text-decoration:none;">
                    عرض الكل <i class="fas fa-arrow-left ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>اسم المخيم</th>
                            <th>الموقع</th>
                            <th>الطاقة</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent_camps as $camp)
                        <tr>
                            <td>
                                <div style="font-weight:600; font-size:0.88rem;">{{ $camp->name }}</div>
                            </td>
                            <td style="color:#64748b; font-size:0.85rem;">
                                <i class="fas fa-map-marker-alt me-1" style="color:#ef4444;"></i>
                                {{ $camp->location ?? 'غير محدد' }}
                            </td>
                            <td style="font-size:0.85rem;">{{ number_format($camp->capacity) }}</td>
                            <td>
                                @if($camp->is_active)
                                    <span class="badge" style="background:#dcfce7; color:#166534;">نشط</span>
                                @else
                                    <span class="badge" style="background:#fee2e2; color:#991b1b;">موقوف</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4" style="color:#94a3b8;">
                                <i class="fas fa-tent fa-2x mb-2 d-block"></i>
                                لا توجد مخيمات مضافة بعد
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- الروابط السريعة + التنبيهات --}}
    <div class="col-lg-5">

        {{-- روابط سريعة --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-bolt"></i> إجراءات سريعة</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="{{ route('camps.index') }}" class="d-flex flex-column align-items-center justify-content-center p-3 text-decoration-none"
                           style="background:#eff6ff; border-radius:12px; color:#1d4ed8; transition:all 0.2s;"
                           onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                            <i class="fas fa-tent fa-lg mb-2"></i>
                            <span style="font-size:0.8rem; font-weight:600;">إضافة مخيم</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('families.index') }}" class="d-flex flex-column align-items-center justify-content-center p-3 text-decoration-none"
                           style="background:#f0fdf4; border-radius:12px; color:#166534; transition:all 0.2s;"
                           onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
                            <i class="fas fa-user-plus fa-lg mb-2"></i>
                            <span style="font-size:0.8rem; font-weight:600;">تسجيل عائلة</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('aid.index') }}" class="d-flex flex-column align-items-center justify-content-center p-3 text-decoration-none"
                           style="background:#fffbeb; border-radius:12px; color:#92400e; transition:all 0.2s;"
                           onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='#fffbeb'">
                            <i class="fas fa-box-open fa-lg mb-2"></i>
                            <span style="font-size:0.8rem; font-weight:600;">توزيع مساعدات</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('reports.index') }}" class="d-flex flex-column align-items-center justify-content-center p-3 text-decoration-none"
                           style="background:#faf5ff; border-radius:12px; color:#6d28d9; transition:all 0.2s;"
                           onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">
                            <i class="fas fa-chart-bar fa-lg mb-2"></i>
                            <span style="font-size:0.8rem; font-weight:600;">عرض التقارير</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- تنبيهات --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-bell"></i> التنبيهات</h5>
            </div>
            <div class="card-body p-0">
                @forelse($alerts as $alert)
                <div style="padding:13px 18px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:12px;">
                    <div style="width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;
                        background:{{ $alert['type'] === 'warning' ? '#fffbeb' : '#eff6ff' }};">
                        <i class="fas fa-{{ $alert['icon'] }}"
                           style="color:{{ $alert['type'] === 'warning' ? '#f59e0b' : '#3b82f6' }}; font-size:0.9rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:0.85rem; font-weight:600; color:#1e293b;">{{ $alert['message'] }}</div>
                        <div style="font-size:0.75rem; color:#94a3b8;">{{ $alert['time'] }}</div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4" style="color:#94a3b8; font-size:0.85rem;">
                    <i class="fas fa-check-circle fa-2x mb-2 d-block" style="color:#10b981;"></i>
                    لا توجد تنبيهات
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

@endsection