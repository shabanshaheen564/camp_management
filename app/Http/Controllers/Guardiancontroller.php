<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\Guardian;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    /**
     * جلب كل العائلات في مخيم معين
     * GET /api/camps/{camp}/guardians
     */
    public function byCamp(Camp $camp)
    {
        if (auth()->user()->camp_id !== $camp->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        return response()->json($camp->guardians()->get());
    }

    /**
     * إضافة عائلة جديدة
     * POST /api/guardians
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'second_name' => 'nullable|string|max:255',
            'third_name' => 'nullable|string|max:255',
            'family_name' => 'required|string|max:255',
            'card_id' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'family_member_number' => 'nullable|integer|min:0',
            'date_of_birth' => 'nullable|date',       // ✅ جديد
            'address' => 'nullable|string|max:500',
            'gender' => 'nullable|in:male,female',
            'camp_id' => 'required|integer|exists:camps,id',
            'nationality' => 'required|string|max:255',   // ✅ جديد
        ]);

        // تأكد أن اليوزر يضيف فقط لمخيمه
        if (auth()->user()->camp_id !== (int) $data['camp_id']) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $guardian = Guardian::create($data);
        return response()->json($guardian, 201);
    }

    /**
     * تعديل عائلة
     * PUT /api/guardians/{guardian}
     */
    public function update(Request $request, Guardian $guardian)
    {
        if (auth()->user()->camp_id !== $guardian->camp_id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $data = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'second_name' => 'nullable|string|max:255',
            'third_name' => 'nullable|string|max:255',
            'family_name' => 'sometimes|required|string|max:255',
            'card_id' => 'sometimes|required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',       // ✅ جديد
            'address' => 'nullable|string|max:500',
            'family_member_number' => 'nullable|integer|min:0',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'required|string|max:255',   // ✅ جديد
        ]);

        $guardian->update($data);
        return response()->json($guardian);
    }

    /**
     * حذف عائلة
     * DELETE /api/guardians/{guardian}
     */
    public function destroy(Guardian $guardian)
    {
        if (auth()->user()->camp_id !== $guardian->camp_id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $guardian->delete();
        return response()->json(['message' => 'تم الحذف']);
    }
}