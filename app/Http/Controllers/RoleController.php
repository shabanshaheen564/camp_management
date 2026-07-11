<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

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

        return view('camp_management.roles', compact('roles', 'totalRoles'));
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
}
