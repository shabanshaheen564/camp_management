<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\Hospital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    public function index()
    {
        $camps = Camp::active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withCount('guardians')
            ->get();

        $hospitals = Hospital::active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return view('camp_management.map', compact('camps', 'hospitals'));
    }

    public function campsData()
    {
        $camps = Camp::active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withCount('guardians')
            ->get()
            ->map(fn($c) => [
                'id'        => $c->id,
                'name'      => $c->name,
                'location'  => $c->location,
                'latitude'  => (float) $c->latitude,
                'longitude' => (float) $c->longitude,
                'capacity'  => $c->capacity,
                'occupancy' => $c->current_occupancy,
                'families'  => $c->guardians_count,
                'status'    => $c->status,
            ]);

        return response()->json($camps);
    }

    // ==================== Hospitals ====================

    public function hospitalsData()
    {
        $hospitals = Hospital::active()
            ->get()
            ->map(fn($h) => [
                'id'        => $h->id,
                'name'      => $h->name,
                'latitude'  => (float) $h->latitude,
                'longitude' => (float) $h->longitude,
                'phone'     => $h->phone,
                'type'      => $h->type,
            ]);

        return response()->json($hospitals);
    }

    public function storeHospital(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'phone'     => 'nullable|string|max:50',
            'type'      => 'nullable|string|max:100',
        ]);

        $hospital = Hospital::create(array_merge($validated, ['is_active' => true]));

        return response()->json([
            'success'  => true,
            'hospital' => [
                'id'        => $hospital->id,
                'name'      => $hospital->name,
                'latitude'  => (float) $hospital->latitude,
                'longitude' => (float) $hospital->longitude,
                'phone'     => $hospital->phone,
                'type'      => $hospital->type,
            ],
        ]);
    }

    public function importHospitals(Request $request)
    {
        $validated = $request->validate([
            'hospitals' => 'required|array|min:1|max:2000',
            'hospitals.*.name' => 'required|string|max:255',
            'hospitals.*.latitude' => 'required|numeric|between:-90,90',
            'hospitals.*.longitude' => 'required|numeric|between:-180,180',
            'hospitals.*.phone' => 'nullable|string|max:50',
            'hospitals.*.type' => 'nullable|string|max:100',
        ]);

        $created = [];

        DB::transaction(function () use ($validated, &$created) {
            foreach ($validated['hospitals'] as $item) {
                $hospital = Hospital::create([
                    'name' => $item['name'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'phone' => $item['phone'] ?? null,
                    'type' => $item['type'] ?? 'عام',
                    'is_active' => true,
                ]);

                $created[] = [
                    'id' => $hospital->id,
                    'name' => $hospital->name,
                    'latitude' => (float) $hospital->latitude,
                    'longitude' => (float) $hospital->longitude,
                    'phone' => $hospital->phone,
                    'type' => $hospital->type,
                ];
            }
        });

        return response()->json([
            'success' => true,
            'count' => count($created),
            'hospitals' => $created,
        ]);
    }

    public function destroyHospital($id)
    {
        $hospital = Hospital::findOrFail($id);
        $hospital->delete();

        return response()->json(['success' => true]);
    }
}
