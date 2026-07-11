<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use Illuminate\Http\Request;

class CampController extends Controller
{
    public function index(Request $request)
    {
        $query = Camp::withCount('guardians');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $camps        = $query->latest()->paginate(10);
        $totalCamps   = Camp::count();
        $activeCamps  = Camp::where('is_active', true)->count();
        $totalCapacity = Camp::sum('capacity');

        return view('camp_management.camps', compact(
            'camps', 'totalCamps', 'activeCamps', 'totalCapacity'
        ));
    }

  public function store(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'location' => 'required|string|max:255',
        'manager'  => 'required|string|max:255',
        'phone'    => 'required|string|max:20',
        'capacity' => 'required|integer|min:1',
        'status'   => 'required|in:active,inactive,full',
        'latitude' => 'nullable|numeric',
        'longitude'=> 'nullable|numeric',
        'is_active'=> 'required|boolean',
    ]);

    Camp::create([
        'name'        => $request->name,
        'location'    => $request->location,
        'manager'     => $request->manager,
        'phone'       => $request->phone,
        'capacity'    => $request->capacity,
        'status'      => $request->status,
        'latitude'    => $request->latitude,
        'longitude'   => $request->longitude,
        'is_active'   => $request->is_active,
        'description' => $request->description,
        'created_by'  => auth()->id(),
    ]);

    return redirect()->route('camps.index')->with('success', 'تمت إضافة المخيم بنجاح');
}

public function update(Request $request, Camp $camp)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'location' => 'required|string|max:255',
        'manager'  => 'required|string|max:255',
        'phone'    => 'required|string|max:20',
        'capacity' => 'required|integer|min:1',
        'status'   => 'required|in:active,inactive,full',
        'latitude' => 'nullable|numeric',
        'longitude'=> 'nullable|numeric',
        'is_active'=> 'required|boolean',
    ]);

    $camp->update([
        'name'        => $request->name,
        'location'    => $request->location,
        'manager'     => $request->manager,
        'phone'       => $request->phone,
        'capacity'    => $request->capacity,
        'status'      => $request->status,
        'latitude'    => $request->latitude,
        'longitude'   => $request->longitude,
        'is_active'   => $request->is_active,
        'description' => $request->description,
    ]);

    return redirect()->route('camps.index')->with('success', 'تم تحديث المخيم بنجاح');
}

    public function destroy(Camp $camp)
    {
        $camp->delete();
        return back()->with('success', 'تم حذف المخيم بنجاح.');
    }
   public function show(Camp $camp)
    {
        // تأكد أن اليوزر يملك هذا المخيم فقط
        if (auth()->user()->camp_id !== $camp->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }
 
        return response()->json($camp);
    }
 
    /**
     * إحصائيات المخيم
     * GET /api/camps/{id}/statistics
     */
    public function statistics(Camp $camp)
    {
        if (auth()->user()->camp_id !== $camp->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }
 
        $totalFamilies    = $camp->guardians()->count();
        $totalIndividuals = $camp->guardians()->sum('family_member_number');
 
        return response()->json([
            'total_families'    => $totalFamilies,
            'total_individuals' => $totalIndividuals,
            'camp'              => $camp,
        ]);
    }
}