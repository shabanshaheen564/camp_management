@extends('layouts.skeleton')

@section('title', 'التقارير والإحصائيات')

@section('content')
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-chart-bar me-2"></i>التقارير والإحصائيات</h1>
    <div class="ms-auto">
        <a href="{{ route('reports.export.camps') }}" class="btn btn-sm btn-success">
            <i class="fas fa-file-excel"></i> تصدير إلى Excel
        </a>
    </div>
</div>

{{-- بطاقات الإحصاء العامة --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="border-top:4px solid #3b82f6">
            <div class="stat-icon" style="background:#eff6ff;color:#3b82f6"><i class="fas fa-campground"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ $totalCamps }}</div>
                <div class="stat-label">إجمالي المخيمات النشطة</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="border-top:4px solid #f59e0b">
            <div class="stat-icon" style="background:#fffbeb;color:#f59e0b"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalFamilies) }}</div>
                <div class="stat-label">إجمالي العائلات</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="border-top:4px solid #10b981">
            <div class="stat-icon" style="background:#ecfdf5;color:#10b981"><i class="fas fa-person"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalPersons) }}</div>
                <div class="stat-label">إجمالي النازحين</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="border-top:4px solid #8b5cf6">
            <div class="stat-icon" style="background:#f5f3ff;color:#8b5cf6"><i class="fas fa-hands-helping"></i></div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalAids) }}</div>
                <div class="stat-label">توزيعات المساعدات</div>
            </div>
        </div>
    </div>
</div>

{{-- الصف الأول من المخططات --}}
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>توزيع النازحين على المخيمات</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="campsChart" style="max-height:300px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-bar me-2 text-success"></i>المساعدات الموزعة - آخر 6 أشهر</h6>
            </div>
            <div class="card-body">
                <canvas id="aidsChart" style="max-height:300px"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- الصف الثاني من المخططات --}}
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-line me-2 text-warning"></i>نمو أعداد العائلات - آخر 6 أشهر</h6>
            </div>
            <div class="card-body">
                <canvas id="growthChart" style="max-height:280px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-doughnut me-2 text-danger"></i>توزيع الفئات العمرية</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="ageChart" style="max-height:280px"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Cairo', sans-serif";
Chart.defaults.color = '#64748b';

// مخطط توزيع النازحين (دائري)
const campsData = @json($campsData);
new Chart(document.getElementById('campsChart'), {
    type: 'pie',
    data: {
        labels: campsData.map(c => c.name),
        datasets: [{
            data: campsData.map(c => c.count),
            backgroundColor: [
                '#3b82f6','#10b981','#f59e0b','#ef4444',
                '#8b5cf6','#06b6d4','#f97316','#84cc16'
            ],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 15, font: { size: 12 } } }
        }
    }
});

// مخطط المساعدات الشهرية (أعمدة)
const monthlyAids = @json($monthlyAids);
new Chart(document.getElementById('aidsChart'), {
    type: 'bar',
    data: {
        labels: monthlyAids.map(m => m.month),
        datasets: [{
            label: 'عدد التوزيعات',
            data: monthlyAids.map(m => m.count),
            backgroundColor: 'rgba(16, 185, 129, 0.8)',
            borderColor: '#10b981',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});

// مخطط نمو الأعداد (خطي)
const growthData = @json($monthlyGrowth);
new Chart(document.getElementById('growthChart'), {
    type: 'line',
    data: {
        labels: growthData.map(m => m.month),
        datasets: [{
            label: 'عائلات جديدة',
            data: growthData.map(m => m.count),
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#f59e0b',
            pointRadius: 5,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true },
            x: { grid: { display: false } }
        }
    }
});

// مخطط الفئات العمرية (دونات)
const ageData = @json($ageGroups);
new Chart(document.getElementById('ageChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(ageData),
        datasets: [{
            data: Object.values(ageData),
            backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 15, font: { size: 12 } } }
        },
        cutout: '65%',
    }
});
</script>
@endpush
