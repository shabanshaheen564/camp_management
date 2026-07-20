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
                    إذا لم يتم تعيينه، لن يتم استيراد السطر.
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
                                            <option value="{{ $header }}" {{ (old("mapping.$field") == $header || ($loop->first && in_array($field, ['guardian_card_id', 'name']))) ? 'selected' : '' }}>
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