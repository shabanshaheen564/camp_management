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
            background: #fff;
            color: #1e293b;
            direction: rtl;
            padding: 30px;
            line-height: 1.6;
        }
        .report-container { max-width: 1400px; margin: 0 auto; }
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
            background: #f8fafc;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }
        .report-meta strong { color: #1e293b; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 0.76rem;
        }
        thead { display: table-header-group; }
        thead th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            padding: 8px 7px;
            border: 1px solid #cbd5e1;
            text-align: right;
            white-space: nowrap;
        }
        tbody td {
            padding: 7px;
            border: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: top;
        }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .text-center { text-align: center; }
        .footer {
            margin-top: 25px;
            padding-top: 12px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 0.78rem;
            color: #94a3b8;
        }
        .empty-state {
            text-align: center;
            padding: 35px;
            border: 1px solid #e2e8f0;
            color: #64748b;
        }
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { padding: 0; font-size: 10px; }
            .no-print { display: none !important; }
            .report-container { max-width: 100%; }
            .report-header { margin-bottom: 12px; padding-bottom: 10px; }
            .report-header h1 { font-size: 1.35rem; }
            .report-meta { margin-bottom: 12px; padding: 7px 10px; }
            table { page-break-inside: auto; font-size: 8px; }
            tr { page-break-inside: avoid; page-break-after: auto; }
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
