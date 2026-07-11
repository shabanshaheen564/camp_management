<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * عرض صفحة تسجيل الدخول للمتصفح
     * GET /login
     */
    public function showLogin()
    {
        return view('camp_management.login');
    }

    /**
     * تنفيذ تسجيل الدخول (ويب + API)
     * POST /login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // محاولة تسجيل الدخول
        if (!Auth::attempt($credentials)) {
            // إذا كان الطلب API (يتوقع JSON) نرجع رسالة JSON
            if ($request->expectsJson()) {
                return response()->json(['message' => 'البريد أو كلمة المرور غير صحيحة'], 401);
            }
            
            // أما إذا كان من صفحة الويب نرجع بنفس الصفحة مع رسالة خطأ
            return back()->withErrors([
                'email' => 'بيانات الدخول غير صحيحة',
            ])->withInput($request->only('email'));
        }

        // المستخدم الذي تم تسجيل دخوله
        $user = Auth::user();

        // ============================
        // إذا كان الطلب API
        // ============================
        if ($request->expectsJson()) {
            $token = $user->createToken('flutter-app')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user'  => [
                    'id'      => $user->id,
                    'name'    => $user->name,
                    'email'   => $user->email,
                    'camp_id' => $user->camp_id,
                    'role'    => optional($user->role)->name ?? 'supervisor',
                ],
            ]);
        }

        // ============================
        // إذا كان الطلب ويب
        // ============================
        $request->session()->regenerate();

        return redirect()->intended('/dashboard')->with('success', 'تم تسجيل الدخول بنجاح');
    }

    /**
     * تسجيل الخروج (ويب + API)
     * POST /logout
     */
    public function logout(Request $request)
    {
        // إذا كان الطلب API (سنحذف التوكن الحالي)
        if ($request->expectsJson()) {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'تم تسجيل الخروج']);
        }

        // إذا كان من المتصفح العادي
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'تم تسجيل الخروج بنجاح');
    }
}