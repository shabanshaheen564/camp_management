@extends('layouts.skeleton')

@section('title', 'سلة محذوفات العائلات')
@section('page-title', 'سلة محذوفات العائلات')
@section('page-icon', 'fa-trash')

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 style="font-weight:800; margin:0; color:#1e293b;">العائلات المحذوفة</h4>
            <p style="color:#64748b; font-size:0.85rem; margin:0;">إجمالي: {{ $trashedFamilies->total() }} عائلة محذوفة</p>
        </div>
        <a href="{{ route('families.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right me-2"></i> رجوع للعائلات
        </a>
    </div>

    <form method="GET" action="{{ route('families.trash') }}" class="mb-4">
        <div class="input-group" style="max-width:400px;">
            <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو رقم الهوية..." value="{{ request('search') }}">
            <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
        </div>
    </form>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="ps-3">الاسم</th>
                            <th>رقم الهوية</th>
                            <th>المخيم</th>
                            <th>عدد الأفراد</th>
                            <th>تاريخ الحذف</th>
                            <th class="text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trashedFamilies as $family)
                        <tr>
                            <td class="ps-3" style="font-weight:600;">
                                {{ trim("{$family->first_name} {$family->second_name} {$family->third_name} {$family->family_name}") }}
                            </td>
                            <td>{{ $family->card_id }}</td>
                            <td>{{ $family->camp->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $family->family_members_count }} فرد</span>
                            </td>
                            <td style="color:#64748b; font-size:0.85rem;">
                                {{ $family->deleted_at?->format('Y-m-d H:i') }}
                            </td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('families.restore', $family->id) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="استرجاع"
                                        onclick="return confirm('استرجاع هذه العائلة وجميع أفرادها؟')">
                                        <i class="fas fa-trash-restore"></i> استرجاع
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('families.force-delete', $family->id) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف نهائي"
                                        onclick="return confirm('تحذير: حذف نهائي لا يمكن التراجع عنه! سيتم حذف العائلة وجميع أفرادها نهائياً. متأكد؟')">
                                        <i class="fas fa-trash-alt"></i> حذف نهائي
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-check-circle fa-2x mb-2 d-block" style="color:#10b981;"></i>
                                سلة المحذوفات فارغة
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($trashedFamilies->hasPages())
            <div class="p-3">
                {{ $trashedFamilies->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>

@endsection
