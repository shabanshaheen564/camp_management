<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جدول توزيعات المساعدات - نظام إدارة المخيمات</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Cairo', sans-serif; background: #f1f5f9; color: #1e293b; direction: rtl; padding: 30px; line-height: 1.6; }
        .report-container { max-width: 1400px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 14px; box-shadow: 0 8px 30px rgba(15, 23, 42, .08); }
        .no-print { margin-bottom: 25px; }
        .filter-box { background: linear-gradient(135deg, #eff6ff, #f5f3ff); border: 1px solid #c7d2fe; border-right: 5px solid #3b82f6; border-radius: 10px; padding: 18px; margin-bottom: 25px; }
        .filter-title { font-size: 1rem; font-weight: 800; color: #1e3a5f; margin-bottom: 14px; }
        .filter-grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: 14px; align-items: end; }
        .field label { display: block; font-size: .82rem; font-weight: 700; color: #334155; margin-bottom: 6px; }
        .field input, .field select { width: 100%; min-height: 42px; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; font-family: 'Cairo', sans-serif; color: #1e293b; }
        .camp-select { min-height: 110px !important; }
        .filter-actions { display: flex; gap: 8px; }
        .btn { border: 0; border-radius: 8px; padding: 10px 18px; font-family: 'Cairo', sans-serif; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 7px; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-print { background: #1e3a5f; color: #fff; padding: 10px 28px; }
        .filter-help { margin-top: 8px; color: #64748b; font-size: .75rem; }
        .report-header { text-align: center; border-bottom: 3px double #1e3a5f; padding-bottom: 18px; margin-bottom: 20px; }
        .report-header h1 { font-size: 1.8rem; font-weight: 800; color: #1e3a5f; margin-bottom: 6px; }
        .report-header p { font-size: .9rem; color: #64748b; }
        .report-meta { display: flex; justify-content: space-between; gap: 15px; background: linear-gradient(135deg, #eff6ff, #f5f3ff); padding: 12px 16px; border: 1px solid #c7d2fe; border-right: 5px solid #3b82f6; margin-bottom: 20px; font-size: .85rem; border-radius: 8px; flex-wrap: wrap; }
        .report-meta strong { color: #1e3a5f; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; font-size: .76rem; overflow: hidden; border: 1px solid #cbd5e1; border-radius: 10px; }
        thead { display: table-header-group; }
        thead th { background: linear-gradient(135deg, #1e3a5f, #2563eb); color: #fff; font-weight: 700; padding: 10px 7px; border: 0; border-left: 1px solid rgba(255,255,255,.2); text-align: right; white-space: nowrap; }
        thead th:last-child { border-left: 0; }
        tbody td { padding: 8px 7px; border: 0; border-left: 1px solid #e2e8f0; border-top: 1px solid #e2e8f0; color: #334155; vertical-align: top; }
        tbody td:last-child { border-left: 0; }
        tbody tr:nth-child(odd) { background: #fff; }
        tbody tr:nth-child(even) { background: #eff6ff; }
        tbody tr:hover { background: #dbeafe; }
        tbody td:nth-child(1) { background: #f1f5f9; color: #1e3a5f; font-weight: 700; text-align: center; }
        tbody td:nth-child(2) { color: #1d4ed8; font-weight: 700; }
        tbody td:nth-child(3) { color: #047857; font-weight: 700; }
        tbody td:nth-child(4), tbody td:nth-child(5) { text-align: center; font-weight: 700; white-space: nowrap; }
        tbody td:nth-child(4) { color: #0369a1; }
        tbody td:nth-child(5) { color: #7c3aed; }
        tbody td:nth-child(6) { text-align: center; font-weight: 700; color: #b45309; }
        tbody td:nth-child(8), tbody td:nth-child(9) { white-space: nowrap; color: #475569; }
        tbody td:nth-child(10), tbody td:nth-child(11) { font-weight: 700; text-align: center; }
        .text-center { text-align: center; }
        .footer { margin-top: 25px; padding-top: 12px; border-top: 2px solid #e2e8f0; text-align: center; font-size: .78rem; color: #64748b; }
        .empty-state { text-align: center; padding: 35px; border: 1px solid #cbd5e1; border-radius: 10px; background: #f8fafc; color: #64748b; }
        @media (max-width: 900px) { .filter-grid { grid-template-columns: 1fr; } .filter-actions { flex-wrap: wrap; } }
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { padding: 0; background: #fff; font-size: 10px; }
            .report-container { max-width: 100%; padding: 0; box-shadow: none; border-radius: 0; }
            .no-print { display: none !important; }
            .report-header { margin-bottom: 12px; padding-bottom: 10px; }
            .report-header h1 { font-size: 1.35rem; }
            .report-meta { margin-bottom: 12px; padding: 7px 10px; }
            table { page-break-inside: auto; font-size: 8px; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead th { background: #1e3a5f !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tbody tr:nth-child(even) { background: #eff6ff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tbody td:nth-child(1) { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tbody td, tbody tr, .report-meta { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            thead th, tbody td { padding: 5px 4px; }
            .footer { margin-top: 15px; }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="no-print">
            @if(auth()->user()->isAdmin())
                <form method="GET" action="{{ route('reports.print-aids') }}" class="filter-box">
                    <div class="filter-title"><i class="fas fa-filter"></i> خيارات تقرير المساعدات</div>
                    <div class="filter-grid">
                        <div class="field">
                            <label for="camp_ids">المخيمات (يمكن اختيار أكثر من مخيم)</label>
                            <select id="camp_ids" name="camp_ids[]" class="camp-select" multiple>
                                @foreach($camps as $camp)
                                    <option value="{{ $camp->id }}" @selected(in_array($camp->id, $selectedCampIds))>{{ $camp->name }}</option>
                                @endforeach
                            </select>
                            <div class="filter-help">اضغط Ctrl في ويندوز لاختيار أكثر من مخيم.</div>
                        </div>
                        <div class="field">
                            <label for="month">الشهر</label>
                            <input id="month" type="month" name="month" value="{{ $month ?? '' }}">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> عرض التقرير</button>
                            <a href="{{ route('reports.print-aids') }}" class="btn btn-secondary"><i class="fas fa-rotate-left"></i> الكل</a>
                        </div>
                    </div>
                </form>
            @endif
            <div style="text-align:center;">
                <button class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> طباعة التقرير</button>
            </div>
        </div>

        <div class="report-header">
            <h1>جدول توزيعات المساعدات</h1>
            <p>تقرير رسمي لتوزيعات المساعدات - نظام إدارة المخيمات</p>
        </div>

        <div class="report-meta">
            <div>تاريخ التقرير: <strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            <div>عدد التوزيعات: <strong>{{ $aids->count() }}</strong></div>
            <div>المخيمات: <strong>{{ $selectedCamps->isNotEmpty() ? $selectedCamps->pluck('name')->join('، ') : 'جميع المخيمات' }}</strong></div>
            <div>الشهر: <strong>{{ $month ?: 'جميع الأشهر' }}</strong></div>
        </div>

        @if($aids->isEmpty())
            <div class="empty-state">لا توجد توزيعات مساعدات مطابقة للفلاتر المحددة.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>المخيم</th><th>نوع المساعدة</th><th>الكمية المتاحة</th><th>الكمية الموزعة</th><th>عدد المستفيدين</th><th>أساس التوزيع</th><th>تاريخ التوزيع</th><th>تاريخ الانتهاء</th><th>الحالة</th><th>الأولوية</th><th>الملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($aids as $i => $aid)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>{{ $aid->camp?->name ?? 'غير محدد' }}</td>
                            <td>{{ $aid->aidType?->name ?? 'غير محدد' }}</td>
                            <td>{{ number_format((float) $aid->available_quantity, 2) }}</td>
                            <td>{{ number_format((float) $aid->distributed_quantity, 2) }}</td>
                            <td class="text-center">{{ number_format((int) $aid->target_beneficiaries) }}</td>
                            <td>{{ $aid->distribution_basis ?? 'غير محدد' }}</td>
                            <td>{{ $aid->distribution_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $aid->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $aid->status ?? '—' }}</td>
                            <td>{{ $aid->priority ?? '—' }}</td>
                            <td>{{ $aid->special_notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="footer">تم إنشاء هذا التقرير تلقائياً بواسطة نظام إدارة المخيمات &copy; {{ date('Y') }}</div>
    </div>
</body>
</html>
