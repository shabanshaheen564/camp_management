@extends('layouts.skeleton')

@section('title', 'إدارة العائلات')
@section('page-title', 'العائلات والأفراد')
@section('page-icon', 'fa-users')

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 style="font-weight:800; margin:0; color:#1e293b;">العائلات المسجلة</h4>
            <p style="color:#64748b; font-size:0.85rem; margin:0;">إجمالي: {{ $families->total() }} عائلة</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#familyModal" onclick="openAddModal()">
            <i class="fas fa-user-plus me-2"></i> تسجيل عائلة
        </button>
    </div>

    {{-- إحصاء --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.1);">
                    <i class="fas fa-users" style="color:#10b981;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#10b981;">{{ $totalFamilies }}</div>
                    <div class="stat-label">إجمالي العائلات</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(59,130,246,0.1);">
                    <i class="fas fa-person" style="color:#3b82f6;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#3b82f6;">{{ $totalMembers }}</div>
                    <div class="stat-label">إجمالي الأفراد</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.1);">
                    <i class="fas fa-baby" style="color:#f59e0b;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#f59e0b;">{{ $totalFamilies + $totalMembers }}</div>
                    <div class="stat-label">إجمالي النازحين</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(139,92,246,0.1);">
                    <i class="fas fa-tent" style="color:#8b5cf6;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#8b5cf6;">{{ $campsCount }}</div>
                    <div class="stat-label">مخيمات نشطة</div>
                </div>
            </div>
        </div>
    </div>

    {{-- فلتر --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                        placeholder="ابحث بالاسم أو رقم الهوية...">
                </div>
                <div class="col-md-3">
                    <select name="camp_id" class="form-select form-select-sm">
                        <option value="">كل المخيمات</option>
                        @foreach($camps as $camp)
                            <option value="{{ $camp->id }}" {{ request('camp_id') == $camp->id ? 'selected' : '' }}>
                                {{ $camp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search me-1"></i> بحث
                    </button>
                    @if(request()->hasAny(['search', 'camp_id']))
                        <a href="{{ route('families.index') }}" class="btn btn-sm btn-outline-secondary ms-1">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- الجدول --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-list"></i> قائمة العائلات</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ولي الأمر</th>
                        <th>رقم الهوية</th>
                        <th>المخيم</th>
                        <th>الهاتف</th>
                        <th>عدد الأفراد</th>
                        <th>تاريخ التسجيل</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($families as $i => $family)
                        <tr>
                            <td style="color:#94a3b8; font-size:0.82rem;">{{ $families->firstItem() + $i }}</td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:34px; height:34px; background:linear-gradient(135deg,#3b82f6,#10b981);
                                                border-radius:10px; display:flex; align-items:center; justify-content:center;
                                                color:white; font-weight:700; font-size:0.8rem; flex-shrink:0;">
                                        {{ mb_substr($family->full_name ?? $family->name ?? 'ع', 0, 1) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:700; font-size:0.88rem;">
                                            {{ $family->full_name ?? $family->name }}
                                        </div>
                                        <div style="font-size:0.75rem; color:#94a3b8;">ولي الأمر</div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:0.85rem; font-family:monospace; color:#475569;">
                                {{ $family->card_id ?? $family->national_id ?? '—' }}
                            </td>
                            <td style="font-size:0.85rem;">
                                @if($family->camp)
                                    <span
                                        style="background:#eff6ff; color:#1d4ed8; padding:3px 10px; border-radius:6px; font-size:0.78rem; font-weight:600;">
                                        {{ $family->camp->name }}
                                    </span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td style="font-size:0.85rem; direction:ltr; text-align:right;">
                                {{ $family->phone ?? '—' }}
                            </td>
                            <td>
                                <span
                                    style="background:#f0fdf4; color:#166534; padding:3px 10px; border-radius:6px; font-size:0.82rem; font-weight:600;">
                                    {{ $family->family_members_count ?? 0 }} فرد
                                </span>
                            </td>
                            <td style="font-size:0.82rem; color:#64748b;">
                                {{ $family->created_at?->format('Y/m/d') ?? '—' }}
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    {{-- عرض الأفراد --}}
                                    <button class="btn btn-sm" style="background:#f0fdf4; color:#166534; border:none;"
                                        onclick="showMembers({{ $family->id }}, '{{ addslashes($family->full_name ?? $family->name) }}')"
                                        title="أفراد الأسرة">
                                        <i class="fas fa-users"></i>
                                    </button>
                                    {{-- تعديل --}}
                                    <button class="btn btn-sm" style="background:#eff6ff; color:#2563eb; border:none;"
                                        onclick='openEditModal({{ $family->id }}, @json($family))' title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {{-- حذف --}}
                                    <button class="btn btn-sm" style="background:#fef2f2; color:#dc2626; border:none;"
                                        onclick="confirmDelete({{ $family->id }}, '{{ addslashes($family->full_name ?? $family->name) }}')"
                                        title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5" style="color:#94a3b8;">
                                <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                لا توجد عائلات مسجلة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($families->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center"
                style="background:transparent; border-top:1px solid #f1f5f9; padding:14px 20px;">
                <span style="font-size:0.83rem; color:#64748b;">
                    عرض {{ $families->firstItem() }} - {{ $families->lastItem() }} من {{ $families->total() }}
                </span>
                {{ $families->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    {{-- ===== مودال إضافة/تعديل عائلة ===== --}}
    <div class="modal fade" id="familyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="familyModalTitle">
                        <i class="fas fa-user-plus me-2" style="color:#10b981;"></i>تسجيل عائلة جديدة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="familyForm" method="POST">
                    @csrf
                    <div id="familyMethodField"></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">الاسم الكامل لولي الأمر <span
                                        style="color:#ef4444;">*</span></label>
                                <input type="text" name="full_name" id="ff_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رقم الهوية</label>
                                <input type="text" name="national_id" id="ff_nid" class="form-control" dir="ltr">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رقم الهاتف</label>
                                <input type="text" name="phone" id="ff_phone" class="form-control" dir="ltr">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">المخيم</label>
                                <select name="camp_id" id="ff_camp" class="form-select">
                                    <option value="">اختر المخيم</option>
                                    @foreach($camps as $camp)
                                        <option value="{{ $camp->id }}">{{ $camp->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الجنس</label>
                                <select name="gender" id="ff_gender" class="form-select">
                                    <option value="male">ذكر</option>
                                    <option value="female">أنثى</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تاريخ الميلاد</label>
                                <input type="date" name="date_of_birth" id="ff_dob" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">العنوان</label>
                                <input type="text" name="address" id="ff_address" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-success" id="familySubmitBtn">
                            <i class="fas fa-save me-1"></i> حفظ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- مودال أفراد الأسرة --}}
    <div class="modal fade" id="membersModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="membersTitle">
                        <i class="fas fa-users me-2" style="color:#10b981;"></i>أفراد الأسرة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- إضافة فرد --}}
                    <form id="addMemberForm" method="POST">
                        @csrf
                        <div class="row g-2 mb-4 p-3"
                            style="background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                            <div style="font-weight:700; font-size:0.85rem; color:#475569; margin-bottom:4px; width:100%;">
                                <i class="fas fa-plus me-1" style="color:#10b981;"></i> إضافة فرد جديد
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="full_name" class="form-control form-control-sm"
                                    placeholder="الاسم الكامل *" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="card_id" class="form-control form-control-sm"
                                    placeholder="رقم البطاقة *" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="nationality" class="form-control form-control-sm"
                                    placeholder="الجنسية *" required>
                            </div>
                            <div class="col-md-3">
                                <select name="gender" class="form-select form-select-sm">
                                    <option value="male">ذكر</option>
                                    <option value="female">أنثى</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="date_of_birth" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <select name="relationship" class="form-select form-select-sm">
                                    <option value="son">ابن</option>
                                    <option value="daughter">ابنة</option>
                                    <option value="spouse">زوج/زوجة</option>
                                    <option value="father">أب</option>
                                    <option value="mother">أم</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="phone_number" class="form-control form-control-sm"
                                    placeholder="رقم الهاتف">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_disabled" value="1"
                                        id="memberDisabled">
                                    <label class="form-check-label" for="memberDisabled" style="font-size:0.85rem;">ذوو
                                        الاحتياجات الخاصة</label>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-sm btn-success w-100">
                                    <i class="fas fa-plus me-1"></i> إضافة
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- قائمة الأفراد --}}
                    <div id="membersList">
                        <div class="text-center py-3" style="color:#94a3b8;">
                            <i class="fas fa-spinner fa-spin"></i> جاري التحميل...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- مودال الحذف --}}
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div
                        style="width:60px; height:60px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                        <i class="fas fa-trash" style="color:#ef4444; font-size:1.4rem;"></i>
                    </div>
                    <h6 style="font-weight:700; margin-bottom:8px;">تأكيد الحذف</h6>
                    <p style="color:#64748b; font-size:0.87rem;" id="deleteMsg">هل أنت متأكد؟</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash me-1"></i> حذف
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        let currentFamilyId = null;

        function openAddModal() {
            document.getElementById('familyModalTitle').innerHTML =
                '<i class="fas fa-user-plus me-2" style="color:#10b981;"></i>تسجيل عائلة جديدة';
            document.getElementById('familyForm').action = '{{ route('families.store') }}';
            document.getElementById('familyMethodField').innerHTML = '';
            document.getElementById('familySubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i> تسجيل';
            ['ff_name', 'ff_nid', 'ff_phone', 'ff_address', 'ff_dob'].forEach(id => {
                document.getElementById(id).value = '';
            });
            document.getElementById('ff_camp').value = '';
            document.getElementById('ff_gender').value = 'male';
            new bootstrap.Modal(document.getElementById('familyModal')).show();
        }

        function openEditModal(id, data) {
            document.getElementById('familyModalTitle').innerHTML =
                '<i class="fas fa-edit me-2" style="color:#3b82f6;"></i>تعديل بيانات العائلة';
            document.getElementById('familyForm').action = '/families/' + id;
            document.getElementById('familyMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('familySubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i> حفظ التعديلات';

            // الاسم الكامل مجمع من الأجزاء
            const fullName = [data.first_name, data.second_name, data.third_name]
                .filter(Boolean).join(' ');
            document.getElementById('ff_name').value = fullName || data.full_name || '';

            document.getElementById('ff_nid').value = data.national_id || data.card_id || '';
            document.getElementById('ff_phone').value = data.phone || '';
            document.getElementById('ff_address').value = data.address || '';
            document.getElementById('ff_dob').value = data.date_of_birth || '';
            document.getElementById('ff_camp').value = data.camp_id || '';
            document.getElementById('ff_gender').value = data.gender || 'male';

            new bootstrap.Modal(document.getElementById('familyModal')).show();
        }

        function showMembers(familyId, familyName) {
            currentFamilyId = familyId;
            document.getElementById('membersTitle').innerHTML =
                '<i class="fas fa-users me-2" style="color:#10b981;"></i>أفراد أسرة: ' + familyName;
            document.getElementById('addMemberForm').action = '/families/' + familyId + '/members';

            fetch('/families/' + familyId + '/members-list')
                .then(r => r.json())
                .then(members => {
                    let html = '';
                    if (members.length === 0) {
                        html = '<div class="text-center py-3" style="color:#94a3b8;">لا يوجد أفراد مسجلون</div>';
                    } else {
                        html = '<table class="table table-sm"><thead><tr><th>الاسم</th><th>الصلة</th><th>الجنس</th><th>تاريخ الميلاد</th><th></th></tr></thead><tbody>';
                        members.forEach(m => {
                            const rel = { son: 'ابن', daughter: 'ابنة', spouse: 'زوج/زوجة', father: 'أب', mother: 'أم', other: 'أخرى' };
                            html += `<tr>
                            <td style="font-size:0.85rem;">${m.name || m.full_name}</td>
                            <td style="font-size:0.82rem;">${rel[m.relationship] || m.relationship || '—'}</td>
                            <td style="font-size:0.82rem;">${m.gender === 'male' ? 'ذكر' : 'أنثى'}</td>
                            <td style="font-size:0.82rem;">${m.date_of_birth || '—'}</td>
                            <td>
                                <form method="POST" action="/families/members/${m.id}" style="display:inline;">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm" style="background:#fef2f2; color:#dc2626; border:none;"
                                            onclick="return confirm('حذف هذا الفرد؟')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>`;
                        });
                        html += '</tbody></table>';
                    }
                    document.getElementById('membersList').innerHTML = html;
                });

            new bootstrap.Modal(document.getElementById('membersModal')).show();
        }

        function confirmDelete(id, name) {
            document.getElementById('deleteMsg').textContent = 'هل أنت متأكد من حذف عائلة "' + name + '"؟';
            document.getElementById('deleteForm').action = '/families/' + id;
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
    </script>
@endpush