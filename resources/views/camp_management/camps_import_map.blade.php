@extends('layouts.skeleton')

@section('title', 'ربط الأعمدة - استيراد المخيمات')

@section('content')
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-columns me-2"></i>ربط الأعمدة</h1>
    <a href="{{ route('camps.import.form') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-right me-1"></i> رجوع
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">تعيين أعمدة Excel إلى حقول قاعدة البيانات</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-magic me-2"></i>
            <div>تم تحديد الأعمدة تلقائياً — راجع المطابقة وعدّل أي حقل غير صحيح قبل الاستيراد.</div>
        </div>

        <form method="POST" action="{{ route('camps.import') }}">
            @csrf
            <input type="hidden" name="import_rows" value="{{ base64_encode(json_encode($rows)) }}">
            <input type="hidden" name="import_headers" value="{{ base64_encode(json_encode($headers)) }}">

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
                                    @if($field === 'name')
                                        <span class="text-danger">*</span>
                                    @endif
                                </td>
                                <td>
                                    <select name="mapping[{{ $field }}]" class="form-select field-mapping-select" data-field="{{ $field }}">
                                        <option value="">-- لا يوجد --</option>
                                        @foreach($headers as $header)
                                            <option value="{{ $header }}" {{ (old("mapping.$field") == $header || ($autoMapping[$field] ?? null) == $header) ? 'selected' : '' }}>
                                                {{ $header }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    @php
                                        $sampleKey = $autoMapping[$field] ?? old("mapping.$field") ?? null;
                                        $sample = $sampleKey ? ($rows[0][$sampleKey] ?? '') : '';
                                    @endphp
                                    <code class="mapping-sample" data-field="{{ $field }}">{{ $sample }}</code>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('camps.import.form') }}" class="btn btn-secondary">إلغاء</a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-1"></i> بدء الاستيراد
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const firstRowSample = @json($rows[0] ?? []);
    document.querySelectorAll('.field-mapping-select').forEach(function (select) {
        select.addEventListener('change', function () {
            const field = this.dataset.field;
            const sampleEl = document.querySelector('.mapping-sample[data-field="' + field + '"]');
            if (sampleEl) {
                sampleEl.textContent = this.value ? (firstRowSample[this.value] ?? '') : '';
            }
        });
    });
</script>

@endsection
