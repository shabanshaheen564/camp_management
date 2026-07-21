<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\RoleCreatedNotification;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::withCount('users');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('display_name', 'like', '%' . $request->search . '%');
        }

        $roles = $query->latest()->paginate(10)->withQueryString();

        $totalRoles = Role::count();

        $allPermissions = \App\Models\Permission::orderBy('group')->orderBy('display_name')->get();

        return view('camp_management.roles', compact('roles', 'totalRoles', 'allPermissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100|unique:roles,name',
            'display_name' => 'nullable|string|max:100',
            'description'  => 'nullable|string|max:500',
        ]);

        Role::create([
            'name'         => $request->name,
            'display_name' => $request->display_name ?? $request->name,
            'description'  => $request->description,
            'is_active'    => true,
        ]);

        $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
        foreach ($admins as $admin) {
            $admin->notify(new RoleCreatedNotification($request->name, $request->display_name));
        }

        return redirect()->route('roles.index')->with('success', 'تمت إضافة الدور بنجاح');
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'         => 'required|string|max:100|unique:roles,name,' . $role->id,
            'display_name' => 'nullable|string|max:100',
            'description'  => 'nullable|string|max:500',
        ]);

        $role->update([
            'name'         => $request->name,
            'display_name' => $request->display_name ?? $request->name,
            'description'  => $request->description,
        ]);

        return redirect()->route('roles.index')->with('success', 'تم تحديث الدور بنجاح');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')->with('error', 'لا يمكن حذف الدور لأنه مرتبط بمستخدمين');
        }
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'تم حذف الدور بنجاح');
    }

    public function toggleStatus(Role $role)
    {
        if ($role->name === 'admin') {
            return back()->with('error', 'لا يمكن تعديل حالة دور المدير العام');
        }
        $role->update(['is_active' => !$role->is_active]);
        $status = $role->is_active ? 'تم تفعيل الدور' : 'تم تعليق الدور';
        return back()->with('success', $status);
    }

    public function getRolePermissions(Request $request, Role $role)
    {
        $allPermissions = Permission::orderBy('group')->orderBy('display_name')->get();
        $assigned = $role->permissions()->pluck('permissions.id')->toArray();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'permissions' => $allPermissions,
                'assigned' => $assigned,
            ]);
        }

        return view('camp_management.role_permissions', compact('role', 'allPermissions', 'assigned'));
    }

    public function updatePermissions(Request $request, Role $role)
    {
        if ($role->name === 'admin') {
            $allPermissions = Permission::all();
            $role->permissions()->sync($allPermissions->pluck('id'));

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم تحديث الصلاحيات بنجاح']);
            }

            return back()->with('success', 'تم تحديث الصلاحيات بنجاح');
        }

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissionIds = $request->input('permissions', []);

        $filtered = Permission::whereIn('id', $permissionIds)
            ->where('group', '!=', 'roles')
            ->pluck('id')
            ->toArray();

        $role->permissions()->sync($filtered);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم تحديث الصلاحيات بنجاح']);
        }

        return back()->with('success', 'تم تحديث الصلاحيات بنجاح');
    }
}
