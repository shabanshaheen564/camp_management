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
        body {
            font-family: 'Cairo', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            direction: rtl;
            padding: 30px;
            line-height: 1.6;
        }
        .report-container {
            max-width: 1400px;
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .08);
        }
        .no-print { text-align: center; margin-bottom: 25px; }
        .no-print button {
            padding: 10px 28px;
            background: #1e3a5f;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Cairo', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
        }
        .report-header {
            text-align: center;
            border-bottom: 3px double #1e3a5f;
            padding-bottom: 18px;
            margin-bottom: 20px;
        }
        .report-header h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e3a5f;
            margin-bottom: 6px;
        }
        .report-header p { font-size: 0.9rem; color: #64748b; }
        .report-meta {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            background: linear-gradient(135deg, #eff6ff, #f5f3ff);
            padding: 12px 16px;
            border: 1px solid #c7d2fe;
            border-right: 5px solid #3b82f6;
            margin-bottom: 20px;
            font-size: 0.85rem;
            border-radius: 8px;
        }
        .report-meta strong { color: #1e3a5f; }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            font-size: 0.76rem;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
        }
        thead { display: table-header-group; }
        thead th {
            background: linear-gradient(135deg, #1e3a5f, #2563eb);
            color: #fff;
            font-weight: 700;
            padding: 10px 7px;
            border: 0;
            border-left: 1px solid rgba(255,255,255,.2);
            text-align: right;
            white-space: nowrap;
        }
        thead th:last-child { border-left: 0; }
        tbody td {
            padding: 8px 7px;
            border: 0;
            border-left: 1px solid #e2e8f0;
            border-top: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: top;
        }
        tbody td:last-child { border-left: 0; }
        tbody tr:nth-child(odd) { background: #ffffff; }
        tbody tr:nth-child(even) { background: #eff6ff; }
        tbody tr:hover { background: #dbeafe; }
        tbody td:nth-child(1) {
            background: #f1f5f9;
            color: #1e3a5f;
            font-weight: 700;
            text-align: center;
        }
        tbody td:nth-child(2) { color: #1d4ed8; font-weight: 700; }
        tbody td:nth-child(3) { color: #047857; font-weight: 700; }
        tbody td:nth-child(4), tbody td:nth-child(5) {
            text-align: center;
            font-weight: 700;
            white-space: nowrap;
        }
        tbody td:nth-child(4) { color: #0369a1; }
        tbody td:nth-child(5) { color: #7c3aed; }
        tbody td:nth-child(6) {
            text-align: center;
            font-weight: 700;
            color: #b45309;
        }
        tbody td:nth-child(8), tbody td:nth-child(9) {
            white-space: nowrap;
            color: #475569;
        }
        tbody td:nth-child(10), tbody td:nth-child(11) {
            font-weight: 700;
            text-align: center;
        }
        .text-center { text-align: center; }
        .footer {
            margin-top: 25px;
            padding-top: 12px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 0.78rem;
            color: #64748b;
        }
        .empty-state {
            text-align: center;
            padding: 35px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            color: #64748b;
        }
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { padding: 0; background: #fff; font-size: 10px; }
            .report-container {
                max-width: 100%;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }
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
            <button onclick="window.print()"><i class="fas fa-print"></i> طباعة التقرير</button>
        </div>

        <div class="report-header">
            <h1>جدول توزيعات المساعدات</h1>
            <p>تقرير رسمي لتوزيعات المساعدات - نظام إدارة المخيمات</p>
        </div>

        <div class="report-meta">
            <div>تاريخ التقرير: <strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            <div>عدد توزيعات المساعدات: <strong>{{ $aids->count() }}</strong></div>
        </div>

        @if($aids->isEmpty())
            <div class="empty-state">لا توجد توزيعات مساعدات لعرضها.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المخيم</th>
                        <th>نوع المساعدة</th>
                        <th>الكمية المتاحة</th>
                        <th>الكمية الموزعة</th>
                        <th>عدد المستفيدين</th>
                        <th>أساس التوزيع</th>
                        <th>تاريخ التوزيع</th>
                        <th>تاريخ الانتهاء</th>
                        <th>الحالة</th>
                        <th>الأولوية</th>
                        <th>الملاحظات</th>
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

        <div class="footer">
            تم إنشاء هذا التقرير تلقائياً بواسطة نظام إدارة المخيمات &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
