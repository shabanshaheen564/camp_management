<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Notifications\UserCreatedNotification;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'camp']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        $totalUsers  = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $admins      = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->count();

        $roles = Role::all();
        $camps = Camp::where('is_active', true)->get();

        return view('camp_management.users', compact('users', 'totalUsers', 'activeUsers', 'admins', 'roles', 'camps'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role_id'  => 'required|exists:roles,id',
            'camp_id'  => 'nullable|exists:camps,id',
        ]);

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role_id'   => $request->role_id,
            'camp_id'   => $request->camp_id,
            'is_active' => true,
        ]);

        $user = User::where('email', $request->email)->first();
        if ($user && $user->role) {
            $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
            foreach ($admins as $admin) {
                $admin->notify(new UserCreatedNotification($user->name, $user->role->display_name ?? $user->role->name));
            }
        }

        return redirect()->route('users.index')->with('success', 'تمت إضافة المستخدم بنجاح');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'camp_id' => 'nullable|exists:camps,id',
        ]);

        $data = [
            'name'    => $request->name,
            'email'   => $request->email,
            'role_id' => $request->role_id,
            'camp_id' => $request->camp_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'تم تحديث المستخدم بنجاح');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'لا يمكنك حذف حسابك الخاص');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'تم حذف المستخدم بنجاح');
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'تفعيل' : 'تعليق';
        return redirect()->route('users.index')->with('success', "تم {$status} المستخدم بنجاح");
    }

    public function getPermissions(User $user)
    {
        $allPermissions = \App\Models\Permission::orderBy('group')->orderBy('display_name')->get();
        $rolePermissions = $user->role ? $user->role->permissions()->pluck('permissions.id')->toArray() : [];
        $userPermissions = $user->permissions()->pluck('permissions.id')->toArray();

        return response()->json([
            'success' => true,
            'permissions' => $allPermissions,
            'role_permissions' => $rolePermissions,
            'user_permissions' => $userPermissions,
            'role_name' => $user->role?->name,
        ]);
    }

    public function updatePermissions(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissionIds = $request->input('permissions', []);
        $user->permissions()->sync($permissionIds);

        return response()->json(['success' => true, 'message' => 'تم تحديث صلاحيات المستخدم بنجاح']);
    }
}
