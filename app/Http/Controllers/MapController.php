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

        $html = view('camp_management.map', compact('camps', 'hospitals'))->render();

        // Inject the basemap switcher immediately after Leaflet loads and before
        // the existing map initialization script. This keeps web.php untouched.
        $leafletScript = '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
        $basemapScript = <<<'HTML'
<style>
    .camp-basemap-switcher {
        background: rgba(255, 255, 255, .97);
        border-radius: 10px;
        padding: 8px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .18);
        font-family: 'Cairo', sans-serif;
        direction: rtl;
        min-width: 155px;
    }
    .camp-basemap-title {
        font-size: 11px;
        font-weight: 800;
        color: #1e3a5f;
        margin: 0 2px 6px;
    }
    .camp-basemap-btn {
        display: block;
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        background: #fff;
        color: #475569;
        padding: 6px 8px;
        margin: 3px 0;
        text-align: right;
        font-family: 'Cairo', sans-serif;
        font-size: 11px;
        cursor: pointer;
    }
    .camp-basemap-btn:hover {
        border-color: #2563eb;
        color: #2563eb;
    }
    .camp-basemap-btn.active {
        background: #eff6ff;
        border-color: #2563eb;
        color: #1d4ed8;
        font-weight: 700;
    }
</style>
<script>
(function () {
    if (typeof L === 'undefined' || !L.Map || !L.TileLayer) return;

    L.Map.addInitHook(function () {
        const mapInstance = this;
        let osmLayer = null;
        let satelliteLayer = null;

        const satellite = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                attribution: '© Esri',
                maxZoom: 19
            }
        );
        satelliteLayer = satellite;

        mapInstance.on('layeradd', function (event) {
            if (event.layer instanceof L.TileLayer && event.layer !== satelliteLayer && !osmLayer) {
                osmLayer = event.layer;
            }
        });

        const control = L.control({ position: 'topright' });
        control.onAdd = function () {
            const container = L.DomUtil.create('div', 'camp-basemap-switcher');
            container.innerHTML = `
                <div class="camp-basemap-title">الخريطة</div>
                <button type="button" class="camp-basemap-btn active" data-basemap="osm">🗺️ OpenStreetMap</button>
                <button type="button" class="camp-basemap-btn" data-basemap="satellite">🛰️ صورة جوية</button>
            `;

            L.DomEvent.disableClickPropagation(container);
            L.DomEvent.disableScrollPropagation(container);

            const buttons = container.querySelectorAll('.camp-basemap-btn');
            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    const selected = this.dataset.basemap;

                    if (selected === 'satellite') {
                        if (osmLayer && mapInstance.hasLayer(osmLayer)) mapInstance.removeLayer(osmLayer);
                        if (!mapInstance.hasLayer(satelliteLayer)) satelliteLayer.addTo(mapInstance);
                    } else {
                        if (mapInstance.hasLayer(satelliteLayer)) mapInstance.removeLayer(satelliteLayer);
                        if (osmLayer && !mapInstance.hasLayer(osmLayer)) osmLayer.addTo(mapInstance);
                    }

                    buttons.forEach(btn => btn.classList.toggle('active', btn === this));
                });
            });

            return container;
        };

        control.addTo(mapInstance);
    });
})();
</script>
HTML;

        if (str_contains($html, $leafletScript)) {
            $html = str_replace($leafletScript, $leafletScript . $basemapScript, $html, $count);
            if ($count !== 1) {
                abort(500, 'Unable to initialize map basemap switcher.');
            }
        } else {
            abort(500, 'Leaflet script was not found in the map view.');
        }

        return response($html);
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
