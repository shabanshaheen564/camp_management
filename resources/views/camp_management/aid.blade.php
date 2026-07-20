@extends('layouts.skeleton')

@section('title', 'توزيع المساعدات')

@section('content')
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-hands-helping me-2"></i>توزيع المساعدات</h1>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fas fa-plus me-1"></i> إضافة توزيع جديد
    </button>
</div>

{{-- بطاقات الإحصاء --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="border-top:4px solid #3b82f6">
            <div class="stat-icon" style="background:#eff6ff;color:#3b82f6"><i class="fas fa-box-open"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ $totalDistributions }}</div>
                <div class="stat-label">إجمالي التوزيعات</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="border-top:4px solid #f59e0b">
            <div class="stat-icon" style="background:#fffbeb;color:#f59e0b"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ $thisMonth }}</div>
                <div class="stat-label">هذا الشهر</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="border-top:4px solid #10b981">
            <div class="stat-icon" style="background:#ecfdf5;color:#10b981"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ $completed }}</div>
                <div class="stat-label">المكتملة</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="border-top:4px solid #ef4444">
            <div class="stat-icon" style="background:#fef2f2;color:#ef4444"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ $pending }}</div>
                <div class="stat-label">المعلقة</div>
            </div>
        </div>
    </div>
</div>

{{-- فلاتر البحث --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" id="aidFilterForm" action="{{ route('aid.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" id="aidSearchInput" class="form-control" placeholder="بحث..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="camp_id" id="aidCampFilter" class="form-select">
                    <option value="">كل المخيمات</option>
                    @foreach($camps as $camp)
                        <option value="{{ $camp->id }}" {{ request('camp_id') == $camp->id ? 'selected' : '' }}>{{ $camp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="aid_type_id" id="aidTypeFilter" class="form-select">
                    <option value="">كل الأنواع</option>
                    @foreach($aidTypes as $type)
                        <option value="{{ $type->id }}" {{ request('aid_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" id="aidStatusFilter" class="form-select">
                    <option value="">كل الحالات</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>معلق</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>منتهي</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
            @if(request()->hasAny(['search','camp_id','aid_type_id','status']))
            <div class="col-md-1">
                <a href="{{ route('aid.index') }}" class="btn btn-outline-secondary w-100"><i class="fas fa-times"></i></a>
            </div>
            @endif
        </form>
    </div>
</div>

{{-- الجدول --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المخيم</th>
                        <th>نوع المساعدة</th>
                        <th>تاريخ التوزيع</th>
                        <th>الكمية المتاحة</th>
                        <th>الكمية الموزعة</th>
                        <th>الحالة</th>
                        <th>الأولوية</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aids as $aid)
                    <tr>
                        <td>{{ $loop->iteration + ($aids->currentPage() - 1) * 10 }}</td>
                        <td>
                            <i class="fas fa-campground text-primary me-1"></i>
                            {{ $aid->camp->name ?? '-' }}
                        </td>
                        <td>{{ $aid->aidType->name ?? '-' }}</td>
                        <td>{{ $aid->distribution_date ? $aid->distribution_date->format('Y/m/d') : '-' }}</td>
                        <td>{{ number_format($aid->available_quantity, 0) }}</td>
                        <td>{{ number_format($aid->distributed_quantity, 0) }}</td>
                        <td>
                            @php
                                $statusMap = [
                                    'pending'   => ['class' => 'warning', 'label' => 'معلق'],
                                    'active'    => ['class' => 'primary', 'label' => 'نشط'],
                                    'completed' => ['class' => 'success', 'label' => 'مكتمل'],
                                    'expired'   => ['class' => 'danger',  'label' => 'منتهي'],
                                ];
                                $s = $statusMap[$aid->status] ?? ['class' => 'secondary', 'label' => $aid->status];
                            @endphp
                            <span class="badge bg-{{ $s['class'] }}">{{ $s['label'] }}</span>
                        </td>
                        <td>
                            @php
                                $priorityMap = [
                                    'low'    => ['class' => 'secondary', 'label' => 'منخفض'],
                                    'medium' => ['class' => 'info',      'label' => 'متوسط'],
                                    'high'   => ['class' => 'warning',   'label' => 'عالي'],
                                    'urgent' => ['class' => 'danger',    'label' => 'عاجل'],
                                ];
                                $p = $priorityMap[$aid->priority] ?? ['class' => 'secondary', 'label' => $aid->priority ?? '-'];
                            @endphp
                            <span class="badge bg-{{ $p['class'] }}">{{ $p['label'] }}</span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1"
                                onclick="openEditModal({{ json_encode($aid) }})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="openDeleteModal({{ $aid->id }})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            لا توجد توزيعات مساعدات
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($aids->hasPages())
        <div class="p-3">
            {{ $aids->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

{{-- مودال الإضافة / التعديل --}}
<div class="modal fade" id="aidModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">إضافة توزيع مساعدات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="aidForm" method="POST">
                @csrf
                <span id="methodField"></span>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">المخيم <span class="text-danger">*</span></label>
                            <select name="camp_id" id="f_camp_id" class="form-select" required>
                                <option value="">-- اختر المخيم --</option>
                                @foreach($camps as $camp)
                                    <option value="{{ $camp->id }}">{{ $camp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نوع المساعدة <span class="text-danger">*</span></label>
                            <select name="aid_type_id" id="f_aid_type_id" class="form-select" required>
                                <option value="">-- اختر النوع --</option>
                                @foreach($aidTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ التوزيع <span class="text-danger">*</span></label>
                            <input type="date" name="distribution_date" id="f_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الكمية المتاحة <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" id="f_qty" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الحالة <span class="text-danger">*</span></label>
                            <select name="status" id="f_status" class="form-select" required>
                                <option value="pending">معلق</option>
                                <option value="active">نشط</option>
                                <option value="completed">مكتمل</option>
                                <option value="expired">منتهي</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الأولوية</label>
                            <select name="priority" id="f_priority" class="form-select">
                                <option value="low">منخفض</option>
                                <option value="medium" selected>متوسط</option>
                                <option value="high">عالي</option>
                                <option value="urgent">عاجل</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="special_notes" id="f_notes" class="form-control" rows="3" placeholder="ملاحظات اختيارية..."></textarea>
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

{{-- مودال الحذف --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle me-2"></i>تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">هل أنت متأكد من حذف هذا التوزيع؟ لا يمكن التراجع عن هذا الإجراء.</div>
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
    document.getElementById('modalTitle').textContent = 'إضافة توزيع مساعدات';
    document.getElementById('aidForm').action = "{{ route('aid.store') }}";
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('submitBtn').textContent = 'إضافة';
    document.getElementById('f_camp_id').value = '';
    document.getElementById('f_aid_type_id').value = '';
    document.getElementById('f_date').value = '';
    document.getElementById('f_qty').value = '';
    document.getElementById('f_status').value = 'pending';
    document.getElementById('f_priority').value = 'medium';
    document.getElementById('f_notes').value = '';
    new bootstrap.Modal(document.getElementById('aidModal')).show();
}

function openEditModal(aid) {
    document.getElementById('modalTitle').textContent = 'تعديل توزيع المساعدات';
    document.getElementById('aidForm').action = `/aid/${aid.id}`;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('submitBtn').textContent = 'تحديث';
    document.getElementById('f_camp_id').value = aid.camp_id;
    document.getElementById('f_aid_type_id').value = aid.aid_type_id;
    document.getElementById('f_date').value = aid.distribution_date ? aid.distribution_date.substring(0,10) : '';
    document.getElementById('f_qty').value = aid.available_quantity;
    document.getElementById('f_status').value = aid.status;
    document.getElementById('f_priority').value = aid.priority || 'medium';
    document.getElementById('f_notes').value = aid.special_notes || '';
    new bootstrap.Modal(document.getElementById('aidModal')).show();
}

function openDeleteModal(id) {
    document.getElementById('deleteForm').action = `/aid/${id}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
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

(function() {
    const form = document.getElementById('aidFilterForm');
    const searchInput = document.getElementById('aidSearchInput');
    const campFilter = document.getElementById('aidCampFilter');
    const typeFilter = document.getElementById('aidTypeFilter');
    const statusFilter = document.getElementById('aidStatusFilter');
    let timeout = null;

    function submitForm() {
        form.submit();
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length === 0 || query.length >= 3) {
                clearTimeout(timeout);
                timeout = setTimeout(submitForm, 400);
            }
        });
    }

    [campFilter, typeFilter, statusFilter].forEach(function(el) {
        if (el) {
            el.addEventListener('change', function() {
                clearTimeout(timeout);
                timeout = setTimeout(submitForm, 200);
            });
        }
    });
})();
</script>
@endpush