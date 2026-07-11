<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام إدارة المخيمات</title>
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
        }

        body::before {
            content: '';
            position: fixed;
            width: 500px; height: 500px;
            background: rgba(59,130,246,0.07);
            border-radius: 50%;
            top: -150px; right: -150px;
            pointer-events: none;
        }

        .login-wrapper {
            display: flex;
            width: 900px;
            max-width: 95vw;
            min-height: 520px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.4);
            position: relative;
            z-index: 1;
        }

        /* الجانب الأيسر - معلومات */
        .login-side {
            flex: 1;
            background: linear-gradient(160deg, #1e3a5f, #0f172a);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 1px solid rgba(255,255,255,0.06);
        }

        .side-logo {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #3b82f6, #10b981);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; color: white;
            margin-bottom: 28px;
            box-shadow: 0 12px 30px rgba(59,130,246,0.35);
        }

        .side-title { color: white; font-size: 1.6rem; font-weight: 800; margin-bottom: 10px; }
        .side-desc { color: rgba(255,255,255,0.5); font-size: 0.9rem; line-height: 1.8; margin-bottom: 36px; }

        .feature-list { list-style: none; }
        .feature-list li {
            color: rgba(255,255,255,0.6);
            font-size: 0.85rem;
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .feature-list li:last-child { border-bottom: none; }
        .feature-list li i { color: #10b981; width: 16px; }

        /* الجانب الأيمن - الفورم */
        .login-form-wrap {
            width: 400px;
            background: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header { margin-bottom: 32px; }
        .form-header h2 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-bottom: 6px; }
        .form-header p { color: #64748b; font-size: 0.88rem; }

        .form-group { margin-bottom: 20px; }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 8px;
        }

        .input-wrap { position: relative; }

        .input-wrap i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Cairo', sans-serif;
            font-size: 0.9rem;
            color: #1e293b;
            transition: all 0.2s;
            outline: none;
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.08);
        }

        .form-control.is-invalid { border-color: #ef4444; }
        .invalid-feedback { color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block; }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            font-size: 0.85rem;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            cursor: pointer;
        }

        .remember-label input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: #3b82f6;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Cairo', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(59,130,246,0.35);
        }

        .btn-login:active { transform: translateY(0); }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.83rem;
            color: #94a3b8;
        }

        .back-link a { color: #3b82f6; text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 700px) {
            .login-side { display: none; }
            .login-form-wrap { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">

        {{-- الجانب التعريفي --}}
        <div class="login-side">
            <div class="side-logo">
                <i class="fas fa-campground"></i>
            </div>
            <h2 class="side-title">نظام إدارة المخيمات</h2>
            <p class="side-desc">
                منصة متكاملة لإدارة المخيمات الإنسانية،<br>
                متابعة العائلات النازحة، وتوزيع المساعدات.
            </p>
            <ul class="feature-list">
                <li><i class="fas fa-check-circle"></i> إدارة المخيمات والطاقة الاستيعابية</li>
                <li><i class="fas fa-check-circle"></i> تسجيل العائلات وأفراد الأسرة</li>
                <li><i class="fas fa-check-circle"></i> توزيع المساعدات الإنسانية</li>
                <li><i class="fas fa-check-circle"></i> تقارير وإحصائيات تفصيلية</li>
                <li><i class="fas fa-check-circle"></i> خريطة تفاعلية للمخيمات</li>
                <li><i class="fas fa-check-circle"></i> إدارة المستخدمين والصلاحيات</li>
            </ul>
        </div>

        {{-- فورم تسجيل الدخول --}}
        <div class="login-form-wrap">
            <div class="form-header">
                <h2>مرحباً بعودتك 👋</h2>
                <p>أدخل بيانات حسابك للدخول إلى النظام</p>
            </div>

            @if($errors->any())
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input
                            type="email"
                            name="email"
                            class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                            placeholder="example@email.com"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            dir="ltr"
                        >
                    </div>
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">كلمة المرور</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock"></i>
                        <input
                            type="password"
                            name="password"
                            class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                            placeholder="••••••••"
                            required
                            dir="ltr"
                        >
                    </div>
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="remember-row">
                    <label class="remember-label">
                        <input type="checkbox" name="remember">
                        تذكرني
                    </label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    تسجيل الدخول
                </button>
            </form>

            <div class="back-link">
                <a href="{{ route('home') }}">
                    <i class="fas fa-arrow-right"></i>
                    العودة للصفحة الرئيسية
                </a>
            </div>
        </div>

    </div>
</body>
</html>