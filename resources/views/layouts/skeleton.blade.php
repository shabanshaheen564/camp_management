<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نظام إدارة المخيمات')</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --sidebar-bg: #0f172a;
            --sidebar-width: 260px;
            --header-height: 65px;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --bg: #f1f5f9;
            --card-bg: #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg);
            color: #1e293b;
            direction: rtl;
            margin: 0;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 0; right: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 22px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--primary), var(--success));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.1rem;
            flex-shrink: 0;
        }

        .brand-text { color: white; font-weight: 700; font-size: 0.95rem; line-height: 1.3; }
        .brand-text small { color: var(--text-muted); font-weight: 400; font-size: 0.75rem; display: block; }

        .sidebar-nav { padding: 16px 0; flex: 1; }

        .nav-section-label {
            color: rgba(255,255,255,0.25);
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            padding: 12px 20px 6px;
            text-transform: uppercase;
        }

        .nav-link-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            margin: 2px 10px;
            border-radius: 10px;
            position: relative;
        }

        .nav-link-item i {
            width: 20px;
            text-align: center;
            font-size: 0.95rem;
        }

        .nav-link-item:hover {
            color: white;
            background: rgba(255,255,255,0.06);
        }

        .nav-link-item.active {
            color: white;
            background: linear-gradient(135deg, rgba(59,130,246,0.3), rgba(59,130,246,0.1));
            border: 1px solid rgba(59,130,246,0.3);
        }

        .nav-link-item.active i { color: var(--primary); }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
        }

        .user-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--success));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 0.85rem;
            flex-shrink: 0;
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name { color: white; font-size: 0.82rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { color: var(--text-muted); font-size: 0.72rem; }

        .logout-btn {
            color: #ef4444;
            background: none;
            border: none;
            padding: 6px;
            cursor: pointer;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .logout-btn:hover { background: rgba(239,68,68,0.1); }

        /* ===== HEADER ===== */
        .main-header {
            position: fixed;
            top: 0; left: 0;
            right: var(--sidebar-width);
            height: var(--header-height);
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .page-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        .page-title i { color: var(--primary); }

        .header-actions { display: flex; align-items: center; gap: 12px; }

        .header-btn {
            width: 38px; height: 38px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .header-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }

        /* ===== CONTENT ===== */
        .main-content {
            margin-right: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 28px;
            min-height: calc(100vh - var(--header-height));
        }

        /* ===== CARDS ===== */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }

        .card-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: transparent;
        }

        .card-title { font-size: 1rem; font-weight: 700; margin: 0; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        .card-title i { color: var(--primary); }

        /* ===== STAT CARDS ===== */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }

        .stat-icon {
            width: 54px; height: 54px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .stat-value { font-size: 1.8rem; font-weight: 800; line-height: 1; margin-bottom: 4px; }
        .stat-label { color: #64748b; font-size: 0.85rem; font-weight: 500; }

        /* ===== TABLES ===== */
        .table { margin: 0; }
        .table thead th {
            background: var(--bg);
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: none;
            padding: 12px 16px;
        }
        .table tbody td { padding: 13px 16px; vertical-align: middle; border-color: var(--border); font-size: 0.9rem; }
        .table tbody tr:hover { background: #f8fafc; }

        /* ===== BADGES ===== */
        .badge { font-size: 0.75rem; padding: 5px 10px; border-radius: 6px; font-weight: 600; }

        /* ===== BUTTONS ===== */
        .btn { font-family: 'Cairo', sans-serif; font-weight: 600; border-radius: 10px; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .btn-sm { padding: 5px 12px; font-size: 0.82rem; }

        /* ===== MODAL ===== */
        .modal-content { border-radius: 16px; border: none; }
        .modal-header { border-bottom: 1px solid var(--border); padding: 18px 22px; }
        .modal-title { font-weight: 700; font-size: 1rem; }
        .modal-footer { border-top: 1px solid var(--border); }
        .form-label { font-weight: 600; font-size: 0.85rem; color: #475569; margin-bottom: 6px; }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid var(--border);
            padding: 9px 13px;
            font-family: 'Cairo', sans-serif;
            font-size: 0.9rem;
        }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

        /* ===== ALERTS ===== */
        #flash-message {
            position: fixed;
            top: 80px; left: 20px;
            z-index: 9999;
            min-width: 280px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-right: 0; }
            .main-header { right: 0; }
        }
    </style>

    @stack('styles')
</head>
<body>

    @include('layouts.side_menu')

    <header class="main-header">
        <div class="page-title">
            <i class="fas @yield('page-icon', 'fa-home')"></i>
            @yield('page-title', 'لوحة التحكم')
        </div>
        <div class="header-actions">
            @include('layouts.header')
        </div>
    </header>

    <main class="main-content">
        {{-- Flash Messages --}}
        <div id="flash-message">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(a => {
                bootstrap.Alert.getOrCreateInstance(a)?.close();
            });
        }, 4000);

        // Logout
        function logout() {
            document.getElementById('logout-form').submit();
        }
    </script>

    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none">
        @csrf
    </form>

    @stack('scripts')
</body>
</html>