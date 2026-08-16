<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\FamilyMember;
use App\Models\Camp;
use App\Notifications\FamilyCreatedNotification;
use App\Notifications\FamilyDeletedNotification;
use App\Notifications\FamilyForceDeletedNotification;
use App\Notifications\FamilyMemberAddedNotification;
use App\Notifications\FamilyMemberDeletedNotification;
use App\Notifications\FamilyRestoredNotification;
use App\Notifications\FamilyUpdatedNotification;
use App\Services\NotificationCenter;
use App\Support\NotificationSections;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function index(Request $request)
    {
        $this->markNotificationsRead(NotificationSections::FAMILIES);

        $user = auth()->user();
        $query = Guardian::with('camp')->withCount('familyMembers');

        if (!$user->isAdmin()) {
            $query->where('camp_id', $user->camp_id);
        } elseif ($request->filled('camp_id')) {
            $query->where('camp_id', $request->camp_id);
        }

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

        $families = $query->latest()->paginate(10);

        if ($user->isAdmin()) {
            $totalFamilies = Guardian::count();
            $totalMembers  = FamilyMember::count();
            $camps         = Camp::active()->get();
        } else {
            $totalFamilies = Guardian::where('camp_id', $user->camp_id)->count();
            $totalMembers  = FamilyMember::whereHas('guardian', fn ($q) => $q->where('camp_id', $user->camp_id))->count();
            $camps         = Camp::active()->where('id', $user->camp_id)->get();
        }

        $campsCount = $camps->count();

        return view('camp_management.families', compact(
            'families', 'totalFamilies', 'totalMembers', 'camps', 'campsCount'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name'      => 'required|string|max:255',
            'national_id'    => 'nullable|string|max:50',
            'phone'          => 'nullable|string|max:20',
            'camp_id'        => 'nullable|exists:camps,id',
            'gender'         => 'nullable|in:male,female',
            'date_of_birth'  => 'nullable|date',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
        ]);

        $campId = auth()->user()->isAdmin()
            ? ($data['camp_id'] ?? null)
            : auth()->user()->camp_id;

        if (!$campId) {
            return back()->withErrors(['camp_id' => 'يجب تحديد المخيم'])->withInput();
        }

        $this->authorizeCampAccess((int) $campId);
        $this->ensureCapacityForFamily((int) $campId, 1);

        $nameParts = $this->parseFullName($data['full_name']);

        $guardian = Guardian::create([
            'camp_id'              => $campId,
            'first_name'           => $nameParts['first_name'],
            'second_name'          => $nameParts['second_name'],
            'third_name'           => $nameParts['third_name'],
            'family_name'          => $nameParts['family_name'],
            'phone'                => $data['phone'] ?? null,
            'card_id'              => $data['national_id'] ?? null,
            'gender'               => $data['gender'] ?? 'male',
            'date_of_birth'        => $data['date_of_birth'] ?? null,
            'family_member_number' => 0,
            'nationality'          => 'فلسطيني',
            'marital_status'       => $data['marital_status'] ?? 'single',
            'is_disabled'          => 0,
        ]);

        $guardian->camp?->updateOccupancy();

        app(NotificationCenter::class)->notifyAdmins(
            new FamilyCreatedNotification(
                $guardian->full_name,
                $guardian->camp?->name,
                $guardian->card_id
            )
        );

        return back()->with('success', 'تم تسجيل العائلة');
    }

    public function update(Request $request, Guardian $family)
    {
        $this->authorizeGuardianAccess($family);

        $data = $request->validate([
            'full_name'      => 'required|string|max:255',
            'national_id'    => 'nullable|string|max:50',
            'camp_id'        => 'nullable|exists:camps,id',
            'gender'         => 'nullable|in:male,female',
            'date_of_birth'  => 'nullable|date',
            'phone'          => 'nullable|string|max:50',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
        ]);

        $oldCampId = (int) $family->camp_id;
        $campId = auth()->user()->isAdmin()
            ? ($data['camp_id'] ?? $family->camp_id)
            : auth()->user()->camp_id;
        $campId = (int) $campId;

        $this->authorizeCampAccess($campId);

        if ($campId !== $oldCampId) {
            $familySize = 1 + $family->familyMembers()->count();
            $this->ensureCapacityForFamily($campId, $familySize);
        }

        $nameParts = $this->parseFullName($data['full_name']);

        $family->update([
            'camp_id'        => $campId,
            'first_name'     => $nameParts['first_name'],
            'second_name'    => $nameParts['second_name'],
            'third_name'     => $nameParts['third_name'],
            'family_name'    => $nameParts['family_name'],
            'card_id'        => $data['national_id'] ?? null,
            'gender'         => $data['gender'] ?? 'male',
            'phone'          => $data['phone'] ?? null,
            'date_of_birth'  => $data['date_of_birth'] ?? null,
            'marital_status' => $data['marital_status'] ?? 'single',
        ]);

        if ($oldCampId !== $campId) {
            Camp::find($oldCampId)?->updateOccupancy();
            Camp::find($campId)?->updateOccupancy();
        }

        app(NotificationCenter::class)->notifyAdmins(
            new FamilyUpdatedNotification(
                $family->full_name,
                $family->camp?->name,
                $family->card_id
            )
        );

        return back()->with('success', 'تم التعديل');
    }

    public function destroy(Guardian $family)
    {
        $this->authorizeGuardianAccess($family);

        $familyName = $family->full_name;
        $camp = $family->camp;
        $campName = $camp?->name;

        $family->familyMembers()->delete();
        $family->delete();

        $camp?->updateOccupancy();

        app(NotificationCenter::class)->notifyAdmins(
            new FamilyDeletedNotification($familyName, $campName)
        );

        return back()->with('success', 'تم حذف العائلة وجميع أفرادها بنجاح');
    }

    public function trash(Request $request)
    {
        $this->markNotificationsRead(NotificationSections::FAMILIES_TRASH);

        $user = auth()->user();
        $query = Guardian::onlyTrashed()->with('camp')->withCount('familyMembers');

        if (!$user->isAdmin()) {
            $query->where('camp_id', $user->camp_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('family_name', 'like', "%{$search}%")
                    ->orWhere('card_id', 'like', "%{$search}%");
            });
        }

        $trashedFamilies = $query->orderByDesc('deleted_at')->paginate(20);

        return view('camp_management.families_trash', compact('trashedFamilies'));
    }

    public function restore($id)
    {
        $family = Guardian::onlyTrashed()->findOrFail($id);
        $this->authorizeGuardianAccess($family);

        $familySize = 1 + $family->familyMembers()->onlyTrashed()->count();
        $this->ensureCapacityForFamily((int) $family->camp_id, $familySize);

        $family->familyMembers()->onlyTrashed()->restore();
        $family->restore();
        $family->camp?->updateOccupancy();

        app(NotificationCenter::class)->notifyAdmins(
            new FamilyRestoredNotification($family->full_name, $family->camp?->name)
        );

        return back()->with('success', 'تم استرجاع العائلة وجميع أفرادها بنجاح');
    }

    public function forceDelete($id)
    {
        $family = Guardian::onlyTrashed()->findOrFail($id);
        $this->authorizeGuardianAccess($family);

        $familyName = $family->full_name;
        $campName = $family->camp?->name;

        $family->familyMembers()->onlyTrashed()->forceDelete();
        $family->forceDelete();

        app(NotificationCenter::class)->notifyAdmins(
            new FamilyForceDeletedNotification($familyName, $campName)
        );

        return back()->with('success', 'تم الحذف النهائي للعائلة وجميع أفرادها');
    }

    public function storeMember(Request $request, Guardian $guardian)
    {
        $this->authorizeGuardianAccess($guardian);

        $data = $request->validate([
            'full_name'       => 'required|string|max:255',
            'card_id'         => 'required|string|max:50|unique:family_members,card_id',
            'nationality'     => 'required|string|max:100',
            'gender'          => 'required|in:male,female',
            'date_of_birth'   => 'required|date',
            'relationship'    => 'nullable|string|max:50',
            'phone_number'    => 'nullable|string|max:20',
            'is_disabled'     => 'nullable|boolean',
            'marital_status'  => 'nullable|in:single,married,divorced,widowed',
        ]);

        $this->ensureCapacityForFamily((int) $guardian->camp_id, 1);

        FamilyMember::create([
            'guardian_id'    => $guardian->id,
            'name'           => $data['full_name'],
            'card_id'        => $data['card_id'],
            'nationality'    => $data['nationality'],
            'gender'         => $data['gender'],
            'date_of_birth'  => $data['date_of_birth'],
            'phone_number'   => $data['phone_number'] ?? null,
            'is_disabled'    => isset($data['is_disabled']) ? 1 : 0,
            'marital_status' => $data['marital_status'] ?? 'single',
        ]);

        app(NotificationCenter::class)->notifyAdmins(
            new FamilyMemberAddedNotification(
                $data['full_name'],
                $guardian->full_name,
                $guardian->camp?->name
            )
        );

        return back()->with('success', 'تم إضافة الفرد بنجاح.');
    }

    public function getMembersList(Guardian $guardian)
    {
        $this->authorizeGuardianAccess($guardian, expectsJson: true);

        return response()->json($guardian->familyMembers);
    }

    public function destroyMember(FamilyMember $member)
    {
        $this->authorizeFamilyMemberAccess($member);

        $memberName = $member->name;
        $familyName = $member->guardian?->full_name;
        $camp = $member->guardian?->camp;

        $member->delete();
        $camp?->updateOccupancy();

        app(NotificationCenter::class)->notifyAdmins(
            new FamilyMemberDeletedNotification($memberName, $familyName)
        );

        return back()->with('success', 'تم حذف الفرد بنجاح.');
    }

    /**
     * Make sure a new person/family can fit in the selected camp.
     * This is deliberately based on the real database count, not the cached
     * current_occupancy value, so stale occupancy cannot accidentally block
     * or allow an operation.
     */
    protected function ensureCapacityForFamily(int $campId, int $additionalPeople): void
    {
        $camp = Camp::findOrFail($campId);

        $currentPeople = $camp->guardians()->count()
            + $camp->guardians()->withCount('familyMembers')->get()->sum('family_members_count');

        if ($currentPeople + $additionalPeople > $camp->capacity) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'camp_id' => "لا يمكن تنفيذ العملية. سعة المخيم ({$camp->capacity}) لا تسمح بإضافة {$additionalPeople} شخص. الإشغال الحالي {$currentPeople} شخص.",
            ]);
        }
    }
}
