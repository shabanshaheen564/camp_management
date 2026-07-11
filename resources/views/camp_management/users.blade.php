@extends('layouts.skeleton')

@section('title', 'إدارة المستخدمين')

@section('content')
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-users-cog me-2"></i>إدارة المستخدمين</h1>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fas fa-user-plus me-1"></i> إضافة مستخدم
    </button>
</div>

{{-- إحصاءات --}}
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="stat-card" style="border-top:4px solid #3b82f6">
            <div class="stat-icon" style="background:#eff6ff;color:#3b82f6"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ $totalUsers }}</div>
                <div class="stat-label">إجمالي المستخدمين</div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card" style="border-top:4px solid #10b981">
            <div class="stat-icon" style="background:#ecfdf5;color:#10b981"><i class="fas fa-user-check"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ $activeUsers }}</div>
                <div class="stat-label">المستخدمون النشطون</div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card" style="border-top:4px solid #8b5cf6">
            <div class="stat-icon" style="background:#f5f3ff;color:#8b5cf6"><i class="fas fa-user-shield"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ $admins }}</div>
                <div class="stat-label">المديرون</div>
            </div>
        </div>
    </div>
</div>

{{-- بحث وفلتر --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو البريد..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="role_id" class="form-select">
                    <option value="">كل الأدوار</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->display_name ?? $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
            @if(request()->hasAny(['search','role_id']))
            <div class="col-md-1">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary w-100"><i class="fas fa-times"></i></a>
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
                        <th>المستخدم</th>
                        <th>البريد الإلكتروني</th>
                        <th>الدور</th>
                        <th>المخيم المرتبط</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>{{ $loop->iteration + ($users->currentPage() - 1) * 10 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                                    {{ mb_substr($user->name, 0, 1) }}
                                </div>
                                <span>{{ $user->name }}</span>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                {{ $user->role->display_name ?? $user->role->name ?? 'غير محدد' }}
                            </span>
                        </td>
                        <td>{{ $user->camp->name ?? '<span class="text-muted">-</span>' }}</td>
                        <td>
                            @if($user->is_active)
                                <span class="badge bg-success">نشط</span>
                            @else
                                <span class="badge bg-secondary">موقوف</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1"
                                onclick="openEditModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}', {{ $user->role_id ?? 'null' }}, {{ $user->camp_id ?? 'null' }})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="{{ route('users.toggle', $user) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }} me-1"
                                    title="{{ $user->is_active ? 'تعليق' : 'تفعيل' }}">
                                    <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }}"></i>
                                </button>
                            </form>
                            @if($user->id !== auth()->id())
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="openDeleteModal({{ $user->id }})">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-2x mb-2 d-block"></i>
                            لا يوجد مستخدمون
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="p-3">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

{{-- مودال الإضافة / التعديل --}}
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">إضافة مستخدم جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm" method="POST">
                @csrf
                <span id="methodField"></span>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="f_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="f_email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">كلمة المرور <span id="passRequired" class="text-danger">*</span></label>
                            <input type="password" name="password" id="f_password" class="form-control">
                            <small id="passHint" class="text-muted d-none">اتركها فارغة إذا لم ترد تغييرها</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الدور <span class="text-danger">*</span></label>
                            <select name="role_id" id="f_role_id" class="form-select" required>
                                <option value="">-- اختر الدور --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->display_name ?? $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">المخيم المرتبط</label>
                            <select name="camp_id" id="f_camp_id" class="form-select">
                                <option value="">-- لا يوجد --</option>
                                @foreach($camps as $camp)
                                    <option value="{{ $camp->id }}">{{ $camp->name }}</option>
                                @endforeach
                            </select>
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
            <div class="modal-body">هل أنت متأكد من حذف هذا المستخدم؟</div>
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
    document.getElementById('modalTitle').textContent = 'إضافة مستخدم جديد';
    document.getElementById('userForm').action = "{{ route('users.store') }}";
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('submitBtn').textContent = 'إضافة';
    document.getElementById('f_name').value = '';
    document.getElementById('f_email').value = '';
    document.getElementById('f_password').value = '';
    document.getElementById('f_role_id').value = '';
    document.getElementById('f_camp_id').value = '';
    document.getElementById('f_password').required = true;
    document.getElementById('passRequired').style.display = '';
    document.getElementById('passHint').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function openEditModal(id, name, email, roleId, campId) {
    document.getElementById('modalTitle').textContent = 'تعديل المستخدم';
    document.getElementById('userForm').action = `/users/${id}`;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('submitBtn').textContent = 'تحديث';
    document.getElementById('f_name').value = name;
    document.getElementById('f_email').value = email;
    document.getElementById('f_password').value = '';
    document.getElementById('f_role_id').value = roleId || '';
    document.getElementById('f_camp_id').value = campId || '';
    document.getElementById('f_password').required = false;
    document.getElementById('passRequired').style.display = 'none';
    document.getElementById('passHint').classList.remove('d-none');
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function openDeleteModal(id) {
    document.getElementById('deleteForm').action = `/users/${id}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endpush
