<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\FamilyMember;
use App\Models\Camp;
use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\FamilyCreatedNotification;
use App\Notifications\FamilyUpdatedNotification;
use App\Notifications\FamilyMemberAddedNotification;

class FamilyController extends Controller
{
    public function index(Request $request)
    {
        $query = Guardian::with('camp')->withCount('familyMembers');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('second_name', 'like', "%{$s}%")
                  ->orWhere('third_name', 'like', "%{$s}%")
                  ->orWhere('family_name', 'like', "%{$s}%")
                  ->orWhere('card_id', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('camp_id')) {
            $query->where('camp_id', $request->camp_id);
        }

        $families     = $query->latest()->paginate(10);
        $totalFamilies = Guardian::count();
        $totalMembers  = FamilyMember::count();
        $camps         = Camp::where('is_active', true)->get();
        $campsCount    = $camps->count();

        return view('camp_management.families', compact(
            'families', 'totalFamilies', 'totalMembers', 'camps', 'campsCount'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name'     => 'required|string|max:255',
            'national_id'   => 'nullable|string|max:50',
            'phone'         => 'nullable|string|max:20',
            'camp_id'       => 'nullable|exists:camps,id',
            'gender'        => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
        ]);

    $parts = explode(' ', trim($data['full_name']));

   Guardian::create([
      'camp_id' => $data['camp_id'],
      'first_name' => $parts[0] ?? '',
      'second_name' => $parts[1] ?? '',
      'third_name' => $parts[2] ?? '',
      'family_name' => $parts[3] ?? '',
      'phone' => $data['phone'] ?? null,
      'card_id' => $data['national_id'] ?? null,
      'gender' => $data['gender'] ?? 'male',
      'date_of_birth' => $data['date_of_birth'] ?? null,
      'family_member_number' => 0,

      'nationality' => 'فلسطيني',
      'marital_status' => $data['marital_status'] ?? 'single',
      'is_disabled' => 0
  ]);

    $guardian = Guardian::where('card_id', $data['national_id'])->first();
    if ($guardian) {
        $campName = $guardian->camp?->name;
        $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
        foreach ($admins as $admin) {
            $admin->notify(new FamilyCreatedNotification($guardian->full_name ?? $guardian->first_name, $campName, $guardian->card_id));
        }
    }

    return back()->with('success', 'تم تسجيل العائلة');
}

    public function update(Request $request, Guardian $family)
    {
        $data = $request->validate([
            'full_name'     => 'required|string|max:255',
            'national_id'   => 'nullable|string|max:50',
            'camp_id'       => 'nullable|exists:camps,id',
            'gender'        => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'phone'        => 'nullable|string|max:50',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
        ]);

    $parts = explode(' ', trim($data['full_name']));

    $family->update([
        'camp_id' => $data['camp_id'],
        'first_name' => $parts[0] ?? '',
        'second_name' => $parts[1] ?? '',
        'third_name' => $parts[2] ?? '',
        'family_name' => $parts[3] ?? '',
        'card_id' => $data['national_id'] ?? null,
        'gender' => $data['gender'] ?? 'male',
        'phone' => $data['phone'] ?? null,
        'date_of_birth' => $data['date_of_birth'],
        'marital_status' => $data['marital_status'] ?? 'single',
    ]);

    $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
    foreach ($admins as $admin) {
        $admin->notify(new FamilyUpdatedNotification($family->full_name ?? $family->first_name, $family->camp?->name, $family->card_id));
    }

    return back()->with('success', 'تم التعديل');
}

    public function destroy(Guardian $family)
    {
        $family->familyMembers()->delete();
        $family->delete();
        return back()->with('success', 'تم حذف العائلة وجميع أفرادها بنجاح');
    }

    public function storeMember(Request $request, Guardian $guardian)
    {
        $data = $request->validate([
            'full_name'    => 'required|string|max:255',
            'card_id'      => 'required|string|max:50|unique:family_members,card_id',
            'nationality'  => 'required|string|max:100',
            'gender'       => 'required|in:male,female',
            'date_of_birth'=> 'required|date',
            'relationship' => 'nullable|string|max:50',
            'phone_number' => 'nullable|string|max:20',
            'is_disabled'  => 'nullable|boolean',
        ]);

        FamilyMember::create([
            'guardian_id'   => $guardian->id,
            'name'          => $data['full_name'],
            'card_id'       => $data['card_id'],
            'nationality'   => $data['nationality'],
            'gender'        => $data['gender'],
            'date_of_birth' => $data['date_of_birth'],
            'phone_number'  => $data['phone_number'] ?? null,
            'is_disabled'   => isset($data['is_disabled']) ? 1 : 0,
            'marital_status' => $guardian->marital_status === 'married' ? 'married' : 'single',
        ]);

        $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
        foreach ($admins as $admin) {
            $admin->notify(new FamilyMemberAddedNotification($data['full_name'], $guardian->full_name ?? $guardian->first_name, $guardian->camp?->name));
        }

        return back()->with('success', 'تم إضافة الفرد بنجاح.');
    }

    public function getMembersList(Guardian $guardian)
    {
        return response()->json($guardian->familyMembers);
    }

    public function destroyMember(FamilyMember $member)
    {
        $member->delete();
        return back()->with('success', 'تم حذف الفرد بنجاح.');
    }
    
}