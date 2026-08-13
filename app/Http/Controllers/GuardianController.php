<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\Guardian;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    public function byCamp(Camp $camp)
    {
        $this->authorizeCampAccess($camp->id, expectsJson: true);

        return response()->json($camp->guardians()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'           => 'required|string|max:255',
            'second_name'          => 'nullable|string|max:255',
            'third_name'           => 'nullable|string|max:255',
            'family_name'          => 'required|string|max:255',
            'card_id'              => 'required|string|max:50',
            'phone'                => 'nullable|string|max:20',
            'family_member_number' => 'nullable|integer|min:0',
            'date_of_birth'        => 'nullable|date',
            'address'              => 'nullable|string|max:500',
            'gender'               => 'nullable|in:male,female',
            'camp_id'              => 'required|integer|exists:camps,id',
            'nationality'          => 'required|string|max:255',
            'marital_status'       => 'nullable|in:single,married,divorced,widowed',
            'is_disabled'          => 'nullable|boolean',
        ]);

        $this->authorizeCampAccess((int) $data['camp_id'], expectsJson: true);

        $guardian = Guardian::create($data);

        return response()->json($guardian, 201);
    }

    public function update(Request $request, Guardian $guardian)
    {
        $this->authorizeGuardianAccess($guardian, expectsJson: true);

        $data = $request->validate([
            'first_name'           => 'sometimes|required|string|max:255',
            'second_name'          => 'nullable|string|max:255',
            'third_name'           => 'nullable|string|max:255',
            'family_name'          => 'sometimes|required|string|max:255',
            'card_id'              => 'sometimes|required|string|max:50',
            'phone'                => 'nullable|string|max:20',
            'date_of_birth'        => 'nullable|date',
            'address'              => 'nullable|string|max:500',
            'family_member_number' => 'nullable|integer|min:0',
            'gender'               => 'nullable|in:male,female',
            'nationality'          => 'required|string|max:255',
            'marital_status'       => 'nullable|in:single,married,divorced,widowed',
            'is_disabled'          => 'nullable|boolean',
        ]);

        $guardian->update($data);

        return response()->json($guardian);
    }

    public function destroy(Guardian $guardian)
    {
        $this->authorizeGuardianAccess($guardian, expectsJson: true);

        $guardian->delete();

        return response()->json(['message' => 'تم الحذف']);
    }
}
