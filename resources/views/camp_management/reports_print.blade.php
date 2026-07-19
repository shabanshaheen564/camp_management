<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير إحصائي - نظام إدارة المخيمات</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', sans-serif;
            background: #fff;
            color: #1e293b;
            direction: rtl;
            padding: 40px;
            line-height: 1.8;
        }
        .report-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .report-header {
            text-align: center;
            border-bottom: 3px double #1e3a5f;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .report-header h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e3a5f;
            margin-bottom: 8px;
        }
        .report-header p {
            font-size: 0.95rem;
            color: #64748b;
        }
        .report-meta {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            padding: 14px 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }
        .report-meta div { color: #475569; }
        .report-meta strong { color: #1e293b; }
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e3a5f;
            margin: 25px 0 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-box .label { font-size: 0.88rem; color: #64748b; }
        .stat-box .value { font-size: 1.3rem; font-weight: 800; color: #1e3a5f; }
        .stat-box.full { grid-column: 1 / -1; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 0.88rem;
        }
        table thead th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            text-align: right;
        }
        table tbody td {
            padding: 9px 12px;
            border: 1px solid #e2e8f0;
            color: #334155;
        }
        table tbody tr:nth-child(even) { background: #f8fafc; }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 0.82rem;
            color: #94a3b8;
        }
        .no-print {
            text-align: center;
            margin-bottom: 25px;
        }
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
        .no-print button:hover { background: #2563eb; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .report-container { max-width: 100%; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
            .section-title { page-break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="no-print">
            <button onclick="window.print()"><i class="fas fa-print me-1"></i> طباعة التقرير</button>
        </div>

        <div class="report-header">
            <h1>تقرير إحصائي - نظام إدارة المخيمات</h1>
            <p>تقرير رسمي يحتوي على الإحصائيات العامة والنازحين والمخيمات</p>
        </div>

        <div class="report-meta">
            <div>تاريخ التقرير: <strong>{{ now()->format('Y-m-d H:i') }}</strong></div>
            <div>عدد المخيمات النشطة: <strong>{{ $totalCamps }}</strong></div>
            <div>إجمالي العائلات: <strong>{{ $totalFamilies }}</strong></div>
        </div>

        <div class="section-title"><i class="fas fa-chart-pie me-1"></i> الإحصائيات العامة</div>
        <div class="stats-grid">
            <div class="stat-box">
                <div>
                    <div class="label">إجمالي المخيمات النشطة</div>
                </div>
                <div class="value">{{ $totalCamps }}</div>
            </div>
            <div class="stat-box">
                <div>
                    <div class="label">إجمالي العائلات المسجلة</div>
                </div>
                <div class="value">{{ $totalFamilies }}</div>
            </div>
            <div class="stat-box">
                <div>
                    <div class="label">إجمالي الأفراد</div>
                </div>
                <div class="value">{{ $totalMembers }}</div>
            </div>
            <div class="stat-box">
                <div>
                    <div class="label">إجمالي النازحين</div>
                </div>
                <div class="value">{{ $totalPersons }}</div>
            </div>
            <div class="stat-box full">
                <div>
                    <div class="label">إجمالي توزيعات المساعدات</div>
                </div>
                <div class="value">{{ $totalAids }}</div>
            </div>
        </div>

        <div class="section-title"><i class="fas fa-tent me-1"></i> توزيع النازحين على المخيمات</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم المخيم</th>
                    <th>عدد العائلات</th>
                    <th>عدد الأفراد</th>
                </tr>
            </thead>
            <tbody>
                @php $cumulativeMembers = 0; @endphp
                @foreach($camps as $i => $camp)
                    @php
                        $membersCount = $camp->guardians()->sum('family_member_number') + $camp->guardians()->count();
                        $cumulativeMembers += $membersCount;
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $camp->name }}</td>
                        <td>{{ $camp->guardians_count }}</td>
                        <td>{{ $membersCount }}</td>
                    </tr>
                @endforeach
                <tr style="background:#f1f5f9; font-weight:700;">
                    <td colspan="2">الإجمالي</td>
                    <td>{{ $camps->sum('guardians_count') }}</td>
                    <td>{{ $cumulativeMembers }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title"><i class="fas fa-users me-1"></i> توزيع الفئات العمرية</div>
        <table>
            <thead>
                <tr>
                    <th>الفئة العمرية</th>
                    <th>العدد</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ageGroups as $label => $count)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $count }}</td>
                    </tr>
                @endforeach
                <tr style="background:#f1f5f9; font-weight:700;">
                    <td>الإجمالي</td>
                    <td>{{ array_sum($ageGroups) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            تم إنشاء هذا التقرير تلقائياً بواسطة نظام إدارة المخيمات &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
