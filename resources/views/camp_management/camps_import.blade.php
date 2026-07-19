@extends('layouts.skeleton')

@section('title', 'استيراد المخيمات')

@section('content')
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-file-import me-2"></i>استيراد المخيمات من Excel</h1>
    <a href="{{ route('camps.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-right me-1"></i> رجوع
    </a>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-body">
        <form method="POST" action="{{ route('camps.import.preview') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="form-label">اختر ملف Excel <span class="text-danger">*</span></label>
                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                <small class="text-muted">الصيغ المدعومة: .xlsx, .xls, .csv - الحد الأقصى 10MB</small>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i>
                <strong>ملاحظات:</strong>
                <ul class="mb-0 mt-2">
                    <li>يجب أن يحتوي الملف على صف رؤوس (Headers) في الصف الأول</li>
                    <li>سيتم تحديث المخيمات الموجودة بناءً على اسم المخيم</li>
                    <li>سيتم إنشاء المخيمات الجديدة تلقائياً</li>
                </ul>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('camps.index') }}" class="btn btn-secondary">إلغاء</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload me-1"></i> متابعة
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
