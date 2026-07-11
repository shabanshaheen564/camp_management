<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة المخيمات</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f4c81 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            direction: rtl;
            overflow: hidden;
        }

        /* خلفية متحركة */
        body::before {
            content: '';
            position: fixed;
            width: 600px; height: 600px;
            background: rgba(59, 130, 246, 0.08);
            border-radius: 50%;
            top: -200px; right: -200px;
            animation: float 8s ease-in-out infinite;
        }
        body::after {
            content: '';
            position: fixed;
            width: 400px; height: 400px;
            background: rgba(16, 185, 129, 0.06);
            border-radius: 50%;
            bottom: -100px; left: -100px;
            animation: float 10s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-30px) scale(1.05); }
        }

        .welcome-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 60px 50px;
            text-align: center;
            max-width: 550px;
            width: 90%;
            position: relative;
            z-index: 10;
            animation: slideUp 0.8s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-wrap {
            width: 90px; height: 90px;
            background: linear-gradient(135deg, #3b82f6, #10b981);
            border-radius: 24px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 28px;
            font-size: 2.4rem; color: white;
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.4);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 20px 40px rgba(59, 130, 246, 0.4); }
            50% { box-shadow: 0 20px 60px rgba(59, 130, 246, 0.7); }
        }

        h1 {
            color: white;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .subtitle {
            color: rgba(255,255,255,0.6);
            font-size: 1rem;
            margin-bottom: 40px;
            line-height: 1.7;
        }

        .stats-row {
            display: flex;
            gap: 16px;
            margin-bottom: 40px;
        }

        .stat-item {
            flex: 1;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 16px 10px;
        }

        .stat-num {
            color: #3b82f6;
            font-size: 1.5rem;
            font-weight: 800;
            display: block;
        }

        .stat-label {
            color: rgba(255,255,255,0.5);
            font-size: 0.78rem;
            margin-top: 4px;
        }

        .btn-login {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            text-decoration: none;
            padding: 16px 48px;
            border-radius: 14px;
            font-size: 1.1rem;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
            border: none;
            cursor: pointer;
            width: 100%;
            justify-content: center;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(59, 130, 246, 0.5);
        }

        .features {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .feature-tag {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.6);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .feature-tag i { color: #10b981; font-size: 0.7rem; }
    </style>
</head>
<body>
    <div class="welcome-card">
        <div class="logo-wrap">
            <i class="fas fa-campground"></i>
        </div>

        <h1>نظام إدارة المخيمات</h1>
        <p class="subtitle">
            منصة متكاملة لإدارة المخيمات والعائلات النازحة<br>
            وتوزيع المساعدات الإنسانية
        </p>

        <div class="stats-row">
            <div class="stat-item">
                <span class="stat-num"><i class="fas fa-tent" style="font-size:1.2rem"></i></span>
                <div class="stat-label">إدارة المخيمات</div>
            </div>
            <div class="stat-item">
                <span class="stat-num"><i class="fas fa-users" style="font-size:1.2rem; color:#10b981"></i></span>
                <div class="stat-label">متابعة العائلات</div>
            </div>
            <div class="stat-item">
                <span class="stat-num"><i class="fas fa-box-heart" style="font-size:1.2rem; color:#f59e0b"></i></span>
                <div class="stat-label">توزيع المساعدات</div>
            </div>
        </div>

        <a href="{{ route('login') }}" class="btn-login">
            <i class="fas fa-sign-in-alt"></i>
            تسجيل الدخول للنظام
        </a>

        <div class="features">
            <div class="feature-tag"><i class="fas fa-circle"></i> خريطة تفاعلية</div>
            <div class="feature-tag"><i class="fas fa-circle"></i> تقارير شاملة</div>
            <div class="feature-tag"><i class="fas fa-circle"></i> إدارة الصلاحيات</div>
            <div class="feature-tag"><i class="fas fa-circle"></i> واجهة عربية</div>
        </div>
    </div>
</body>
</html>