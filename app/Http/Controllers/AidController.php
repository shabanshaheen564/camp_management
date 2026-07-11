<?php

namespace App\Http\Controllers;

use App\Models\AidDistribution;
use App\Models\AidType;
use App\Models\Camp;
use Illuminate\Http\Request;

class AidController extends Controller
{
    public function index(Request $request)
    {
        $query = AidDistribution::with(['camp', 'aidType']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('aidType', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('camp', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('camp_id')) {
            $query->where('camp_id', $request->camp_id);
        }

        if ($request->filled('aid_type_id')) {
            $query->where('aid_type_id', $request->aid_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('distribution_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('distribution_date', '<=', $request->date_to);
        }

        $aids = $query->latest()->paginate(10)->withQueryString();

        $totalDistributions = AidDistribution::count();
        $thisMonth = AidDistribution::whereMonth('distribution_date', now()->month)
            ->whereYear('distribution_date', now()->year)->count();
        $completed = AidDistribution::where('status', 'completed')->count();
        $pending = AidDistribution::where('status', 'pending')->count();

        $camps = Camp::where('is_active', true)->get();
        $aidTypes = AidType::where('is_active', true)->get();

        return view('camp_management.aid', compact(
            'aids', 'totalDistributions', 'thisMonth', 'completed', 'pending', 'camps', 'aidTypes'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'camp_id'           => 'required|exists:camps,id',
            'aid_type_id'       => 'required|exists:aid_types,id',
            'distribution_date' => 'required|date',
            'available_quantity'=> 'required|numeric|min:0',
            'status'            => 'required|in:pending,active,completed,expired',
            'priority'          => 'nullable|in:low,medium,high,urgent',
            'special_notes'     => 'nullable|string|max:500',
        ]);

        AidDistribution::create([
            'camp_id'            => $request->camp_id,
            'aid_type_id'        => $request->aid_type_id,
            'distribution_date'  => $request->distribution_date,
            'available_quantity' => $request->available_quantity,
            'distributed_quantity' => 0,
            'status'             => $request->status,
            'priority'           => $request->priority ?? 'medium',
            'special_notes'      => $request->special_notes,
            'created_by'         => auth()->id(),
        ]);

        return redirect()->route('aid.index')->with('success', 'تمت إضافة توزيع المساعدات بنجاح');
    }

    public function update(Request $request, AidDistribution $aid)
    {
        $request->validate([
            'camp_id'           => 'required|exists:camps,id',
            'aid_type_id'       => 'required|exists:aid_types,id',
            'distribution_date' => 'required|date',
            'available_quantity'=> 'required|numeric|min:0',
            'status'            => 'required|in:pending,active,completed,expired',
            'priority'          => 'nullable|in:low,medium,high,urgent',
            'special_notes'     => 'nullable|string|max:500',
        ]);

        $aid->update([
            'camp_id'            => $request->camp_id,
            'aid_type_id'        => $request->aid_type_id,
            'distribution_date'  => $request->distribution_date,
            'available_quantity' => $request->available_quantity,
            'status'             => $request->status,
            'priority'           => $request->priority ?? 'medium',
            'special_notes'      => $request->special_notes,
        ]);

        return redirect()->route('aid.index')->with('success', 'تم تحديث توزيع المساعدات بنجاح');
    }

    public function destroy(AidDistribution $aid)
    {
        $aid->delete();
        return redirect()->route('aid.index')->with('success', 'تم حذف توزيع المساعدات بنجاح');
    }
}
