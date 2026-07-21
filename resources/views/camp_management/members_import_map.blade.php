@extends('layouts.skeleton')

@section('title', 'ربط الأعمدة - استيراد الأفراد')

@section('content')
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-columns me-2"></i>ربط الأعمدة</h1>
    <a href="{{ route('families.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-right me-1"></i> رجوع
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">تعيين أعمدة Excel إلى حقول قاعدة البيانات</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('members.import') }}">
            @csrf
            <input type="hidden" name="import_rows" value="{{ base64_encode(json_encode($rows)) }}">
            <input type="hidden" name="import_headers" value="{{ base64_encode(json_encode($headers)) }}">

            <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div>
                    حقل <strong>رقم هوية رب الأسرة (Guardian Card ID)</strong> مطلوب لربط الفرد بعائلته.
                    إذا لم يتم العثور على رب الأسرة وكانت الحالة الاجتماعية (متزوج/مطلق/أرمل/منفصل)، سيتم إنشاء عائلة جديدة تلقائيًا في المخيم المحدد.
                    أما إذا كانت الحالة الاجتماعية (غير متزوج/أعزب)، فسيتم إضافة الفرد إلى عائلة رب الأسرة الموجود فقط.
                </div>
            </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 30%">حقل قاعدة البيانات</th>
                            <th style="width: 30%">عمود Excel</th>
                            <th>مثال من البيانات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dbFields as $field => $label)
                            <tr>
                                <td>
                                    <strong>{{ $label }}</strong>
                                    @if(in_array($field, ['guardian_card_id', 'name']))
                                        <span class="text-danger">*</span>
                                    @endif
                                </td>
                                <td>
                                    <select name="mapping[{{ $field }}]" class="form-select">
                                        <option value="">-- لا يوجد --</option>
                                        @foreach($headers as $header)
                                            <option value="{{ $header }}" {{ (old("mapping.$field") == $header || ($autoMapping[$field] ?? null) == $header || (empty($autoMapping[$field]) && $loop->first && in_array($field, ['guardian_card_id', 'guardian_name', 'guardian_marital_status', 'name']))) ? 'selected' : '' }}>
                                                {{ $header }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    @php
                                        $sampleKey = $headers[0] ?? null;
                                        $sample = $sampleKey ? ($rows[0][$sampleKey] ?? '') : '';
                                    @endphp
                                    <code>{{ $sample }}</code>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($guardians->isNotEmpty())
                <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>أولياء الأمور المكتشفين في الملف</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>رقم هوية رب الأسرة</th>
                                        <th>الاسم الكامل</th>
                                        <th>المخيم</th>
                                        <th>الهاتف</th>
                                        <th>الحالة الاجتماعية</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($guardians as $guardian)
                                        <tr>
                                            <td style="font-family:monospace;">{{ $guardian->card_id }}</td>
                                            <td>{{ $guardian->full_name }}</td>
                                            <td>
                                                @if($guardian->camp)
                                                    <span class="badge bg-primary">{{ $guardian->camp->name }}</span>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>{{ $guardian->phone ?? '—' }}</td>
                                            <td>
                                                @if($guardian->marital_status === 'married')
                                                    <span class="badge bg-success">متزوج</span>
                                                @else
                                                    <span class="badge bg-secondary">غير متزوج</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($guardian->is_disabled)
                                                    <span class="badge bg-danger">ذوي احتياجات</span>
                                                @else
                                                    <span class="badge bg-success">نشط</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if(!empty($newGuardianCardIds))
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-user-plus me-2"></i>أولياء أمور سيتم إنشاؤهم تلقائيًا</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>رقم هوية رب الأسرة</th>
                                        <th>اسم رب الأسرة من الإكسل</th>
                                        <th>الحالة الاجتماعية</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($newGuardianCardIds as $cardId)
                                        <tr>
                                            <td style="font-family:monospace;">{{ $cardId }}</td>
                                            <td>
                                                @php
                                                    $gName = '';
                                                    foreach ($rows as $r) {
                                                        if (trim((string)($r[$autoMapping['guardian_card_id'] ?? ''] ?? '')) === $cardId) {
                                                            $gName = trim((string)($r[$autoMapping['guardian_name'] ?? ''] ?? ''));
                                                            break;
                                                        }
                                                    }
                                                @endphp
                                                {{ $gName ?: 'رب أسرة ' . $cardId }}
                                            </td>
                                            <td>
                                                @php
                                                    $gMarital = '';
                                                    foreach ($rows as $r) {
                                                        if (trim((string)($r[$autoMapping['guardian_card_id'] ?? ''] ?? '')) === $cardId) {
                                                            $gMarital = trim((string)($r[$autoMapping['guardian_marital_status'] ?? ''] ?? ''));
                                                            break;
                                                        }
                                                    }
                                                    $gMaritalLower = mb_strtolower($gMarital, 'UTF-8');
                                                @endphp
                                                @if(in_array($gMaritalLower, ['married', 'متزوج']))
                                                    <span class="badge bg-success">متزوج</span>
                                                @else
                                                    <span class="badge bg-secondary">غير متزوج</span>
                                                @endif
                                            </td>
                                            <td><span class="badge bg-success">جديد</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-muted" style="font-size:0.82rem; padding:8px 16px;">
                        <i class="fas fa-info-circle me-1"></i>
                        ملاحظة: الأشخاص غير المتزوجين في هذه القائمة لن يتم إنشاء عائلة جديدة لهم؛ سيتم إضافتهم كأفراد فقط إلى عائلة رب الأسرة الموجود.
                    </div>
                </div>
            @endif

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('families.index') }}" class="btn btn-secondary">إلغاء</a>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-check me-1"></i> بدء الاستيراد
                </button>
            </div>
        </form>
    </div>
</div>

@endsection