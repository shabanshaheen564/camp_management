@extends('layouts.skeleton')

@section('title', 'الأدوار والصلاحيات')

@section('content')
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-shield-alt me-2"></i>الأدوار والصلاحيات</h1>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fas fa-plus me-1"></i> إضافة دور جديد
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="border-top:4px solid #3b82f6">
            <div class="stat-icon" style="background:#eff6ff;color:#3b82f6"><i class="fas fa-shield-alt"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ $totalRoles }}</div>
                <div class="stat-label">إجمالي الأدوار</div>
            </div>
        </div>
    </div>
</div>

{{-- بحث --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" id="rolesFilterForm" action="{{ route('roles.index') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <input type="text" name="search" id="rolesSearchInput" class="form-control" placeholder="بحث بالاسم..." value="{{ request('search') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
            @if(request('search'))
            <div class="col-md-1">
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary w-100"><i class="fas fa-times"></i></a>
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
                        <th>اسم الدور</th>
                        <th>الاسم المعروض</th>
                        <th>الوصف</th>
                        <th>عدد المستخدمين</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                    <tr>
                        <td>{{ $loop->iteration + ($roles->currentPage() - 1) * 10 }}</td>
                        <td>
                            <code style="background:#f1f5f9;padding:3px 8px;border-radius:6px;color:#3b82f6;font-size:13px;">
                                {{ $role->name }}
                            </code>
                        </td>
                        <td><strong>{{ $role->display_name ?? $role->name }}</strong></td>
                        <td>{{ $role->description ?? '-' }}</td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                <i class="fas fa-users me-1"></i>{{ $role->users_count }} مستخدم
                            </span>
                        </td>
                        <td>
                            @if($role->is_active ?? true)
                                <span class="badge bg-success">نشط</span>
                            @else
                                <span class="badge bg-secondary">غير نشط</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1"
                                onclick="openEditModal({{ $role->id }}, '{{ addslashes($role->name) }}', '{{ addslashes($role->display_name ?? '') }}', '{{ addslashes($role->description ?? '') }}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            @if($role->users_count == 0)
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="openDeleteModal({{ $role->id }})">
                                <i class="fas fa-trash"></i>
                            </button>
                            @else
                            <button class="btn btn-sm btn-outline-danger" disabled title="لا يمكن الحذف: الدور مرتبط بمستخدمين">
                                <i class="fas fa-lock"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-shield-alt fa-2x mb-2 d-block"></i>
                            لا توجد أدوار
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($roles->hasPages())
        <div class="p-3">
            {{ $roles->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

{{-- مودال الإضافة / التعديل --}}
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">إضافة دور جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="roleForm" method="POST">
                @csrf
                <span id="methodField"></span>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">اسم الدور (بالإنجليزية) <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="f_name" class="form-control" placeholder="مثال: admin" required>
                            <small class="text-muted">يُستخدم داخلياً في النظام، بحروف صغيرة وشرطة سفلية</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">الاسم المعروض</label>
                            <input type="text" name="display_name" id="f_display_name" class="form-control" placeholder="مثال: مدير النظام">
                        </div>
                        <div class="col-12">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" id="f_description" class="form-control" rows="3" placeholder="وصف مختصر لصلاحيات هذا الدور..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">إضافة</button>
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
            <div class="modal-body">هل أنت متأكد من حذف هذا الدور؟</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <form id="deleteForm" method="POST">
                    @csrf @method('DELETE')
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
    document.getElementById('modalTitle').textContent = 'إضافة دور جديد';
    document.getElementById('roleForm').action = "{{ route('roles.store') }}";
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('submitBtn').textContent = 'إضافة';
    document.getElementById('f_name').value = '';
    document.getElementById('f_display_name').value = '';
    document.getElementById('f_description').value = '';
    new bootstrap.Modal(document.getElementById('roleModal')).show();
}

function openEditModal(id, name, displayName, description) {
    document.getElementById('modalTitle').textContent = 'تعديل الدور';
    document.getElementById('roleForm').action = `/roles/${id}`;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('submitBtn').textContent = 'تحديث';
    document.getElementById('f_name').value = name;
    document.getElementById('f_display_name').value = displayName;
    document.getElementById('f_description').value = description;
    new bootstrap.Modal(document.getElementById('roleModal')).show();
}

function openDeleteModal(id) {
    document.getElementById('deleteForm').action = `/roles/${id}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

(function() {
    const form = document.getElementById('rolesFilterForm');
    const searchInput = document.getElementById('rolesSearchInput');
    let timeout = null;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length === 0 || query.length >= 3) {
                clearTimeout(timeout);
                timeout = setTimeout(() => form.submit(), 400);
            }
        });
    }
})();
</script>
@endpush
