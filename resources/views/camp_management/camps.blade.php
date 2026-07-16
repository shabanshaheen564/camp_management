@extends('layouts.skeleton')

@section('title', 'إدارة المخيمات')
@section('page-title', 'إدارة المخيمات')
@section('page-icon', 'fa-tent')

@section('content')

    {{-- رأس الصفحة --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 style="font-weight:800; margin:0; color:#1e293b;">المخيمات</h4>
            <p style="color:#64748b; font-size:0.85rem; margin:0;">إجمالي: {{ $camps->total() }} مخيم</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#campModal" onclick="openAddModal()">
            <i class="fas fa-plus me-2"></i> إضافة مخيم
        </button>
    </div>

    {{-- بطاقات إحصاء سريعة --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(59,130,246,0.1);">
                    <i class="fas fa-tent" style="color:#3b82f6;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#3b82f6;">{{ $totalCamps }}</div>
                    <div class="stat-label">إجمالي المخيمات</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.1);">
                    <i class="fas fa-check-circle" style="color:#10b981;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#10b981;">{{ $activeCamps }}</div>
                    <div class="stat-label">مخيمات نشطة</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.1);">
                    <i class="fas fa-users" style="color:#f59e0b;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#f59e0b;">{{ number_format($totalCapacity) }}</div>
                    <div class="stat-label">الطاقة الاستيعابية</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(139,92,246,0.1);">
                    <i class="fas fa-map-marker-alt" style="color:#8b5cf6;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#8b5cf6;">{{ $totalCamps - $activeCamps }}</div>
                    <div class="stat-label">مخيمات موقوفة</div>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول المخيمات --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-list"></i> قائمة المخيمات</h5>
            {{-- بحث --}}
            <form method="GET" class="d-flex gap-2" style="min-width:280px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                    placeholder="ابحث باسم المخيم أو الموقع...">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-search"></i>
                </button>
                @if(request('search'))
                    <a href="{{ route('camps.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم المخيم</th>
                        <th>الموقع</th>
                        <th>الطاقة الاستيعابية</th>
                        <th>عدد العائلات</th>
                        <th>نسبة الإشغال</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($camps as $i => $camp)
                        <tr>
                            <td style="color:#94a3b8; font-size:0.82rem;">{{ $camps->firstItem() + $i }}</td>
                            <td>
                                <div style="font-weight:700; font-size:0.9rem;">{{ $camp->name }}</div>
                                @if($camp->description)
                                    <div style="font-size:0.75rem; color:#94a3b8;">{{ Str::limit($camp->description, 40) }}</div>
                                @endif
                            </td>
                            <td style="color:#475569; font-size:0.88rem;">
                                <i class="fas fa-map-marker-alt me-1" style="color:#ef4444;"></i>
                                {{ $camp->location ?? '—' }}
                            </td>
                            <td style="font-weight:600;">{{ number_format($camp->capacity) }}</td>
                            <td>{{ $camp->guardians_count ?? 0 }}</td>
                            <td>
                                @php
                                    $occ = $camp->capacity > 0
                                        ? min(100, round((($camp->guardians_count ?? 0) / $camp->capacity) * 100))
                                        : 0;
                                    $color = $occ >= 90 ? '#ef4444' : ($occ >= 70 ? '#f59e0b' : '#10b981');
                                @endphp
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="flex:1; background:#f1f5f9; border-radius:4px; height:6px; min-width:60px;">
                                        <div style="width:{{ $occ }}%; background:{{ $color }}; height:6px; border-radius:4px;">
                                        </div>
                                    </div>
                                    <span style="font-size:0.78rem; font-weight:600; color:{{ $color }};">{{ $occ }}%</span>
                                </div>
                            </td>
                            <td>
                                @if($camp->is_active)
                                    <span class="badge" style="background:#dcfce7; color:#166534;">
                                        <i class="fas fa-circle me-1" style="font-size:0.5rem;"></i>نشط
                                    </span>
                                @else
                                    <span class="badge" style="background:#fee2e2; color:#991b1b;">
                                        <i class="fas fa-circle me-1" style="font-size:0.5rem;"></i>موقوف
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-primary me-1"
                                        onclick="openEditModal({{ json_encode($camp) }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm" style="background:#fef2f2; color:#dc2626; border:none;"
                                        onclick="openDeleteModal({{ $camp->id }})" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5" style="color:#94a3b8;">
                                <i class="fas fa-tent fa-3x mb-3 d-block"></i>
                                لا توجد مخيمات
                                @if(request('search'))
                                    تطابق بحثك "{{ request('search') }}"
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($camps->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center"
                style="background:transparent; border-top:1px solid #f1f5f9; padding:14px 20px;">
                <span style="font-size:0.83rem; color:#64748b;">
                    عرض {{ $camps->firstItem() }} - {{ $camps->lastItem() }} من {{ $camps->total() }}
                </span>
                {{ $camps->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    {{-- مودال الإضافة / التعديل --}}
    <div class="modal fade" id="campModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">إضافة مخيم جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="campForm" method="POST">
                    @csrf
                    <span id="methodField"></span>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">اسم المخيم <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="f_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الموقع <span class="text-danger">*</span></label>
                                <input type="text" name="location" id="f_location" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">مدير المخيم <span class="text-danger">*</span></label>
                                <input type="text" name="manager" id="f_manager" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="f_phone" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الطاقة الاستيعابية <span class="text-danger">*</span></label>
                                <input type="number" name="capacity" id="f_capacity" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الحالة</label>
                                <select name="status" id="f_status" class="form-select">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                    <option value="full">ممتلئ</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">خط العرض (Latitude)</label>
                                <input type="number" name="latitude" id="f_latitude" class="form-control" step="0.00000001"
                                    placeholder="31.5">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">خط الطول (Longitude)</label>
                                <input type="number" name="longitude" id="f_longitude" class="form-control"
                                    step="0.00000001" placeholder="34.47">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الحالة التشغيلية</label>
                                <select name="is_active" id="f_is_active" class="form-select">
                                    <option value="1">مفعّل</option>
                                    <option value="0">موقوف</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">الوصف</label>
                                <textarea name="description" id="f_description" class="form-control" rows="3"
                                    placeholder="وصف مختصر عن المخيم..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- مودال تأكيد الحذف --}}
 {{-- مودال الحذف --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>تأكيد الحذف
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                هل أنت متأكد من حذف هذا المخيم؟ لا يمكن التراجع عن هذا الإجراء.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">حذف</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'إضافة مخيم جديد';
            document.getElementById('campForm').action = "{{ route('camps.store') }}";
            document.getElementById('methodField').innerHTML = '';
            document.getElementById('submitBtn').textContent = 'إضافة';
            document.getElementById('f_name').value = '';
            document.getElementById('f_location').value = '';
            document.getElementById('f_manager').value = '';
            document.getElementById('f_phone').value = '';
            document.getElementById('f_capacity').value = '';
            document.getElementById('f_status').value = 'active';
            document.getElementById('f_latitude').value = '';
            document.getElementById('f_longitude').value = '';
            document.getElementById('f_is_active').value = '1';
            document.getElementById('f_description').value = '';
            new bootstrap.Modal(document.getElementById('campModal')).show();
        }

        function openEditModal(camp) {
            document.getElementById('modalTitle').textContent = 'تعديل المخيم';
            document.getElementById('campForm').action = `/camps/${camp.id}`;
            document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('submitBtn').textContent = 'تحديث';
            document.getElementById('f_name').value = camp.name;
            document.getElementById('f_location').value = camp.location;
            document.getElementById('f_manager').value = camp.manager;
            document.getElementById('f_phone').value = camp.phone;
            document.getElementById('f_capacity').value = camp.capacity;
            document.getElementById('f_status').value = camp.status;
            document.getElementById('f_latitude').value = camp.latitude ?? '';
            document.getElementById('f_longitude').value = camp.longitude ?? '';
            document.getElementById('f_is_active').value = camp.is_active ? '1' : '0';
            document.getElementById('f_description').value = camp.description ?? '';
            new bootstrap.Modal(document.getElementById('campModal')).show();
        }

      function openDeleteModal(id) {
    document.getElementById('deleteForm').action = `/camps/${id}`;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
// إصلاح مشكلة بقاء الـ backdrop بعد إغلاق المودال
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('hidden.bs.modal', function () {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    });
});
    </script>
    
@endpush