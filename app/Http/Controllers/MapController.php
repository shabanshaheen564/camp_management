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
<script src="https://cdn.jsdelivr.net/npm/proj4@2.19.10/dist/proj4.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
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

    .camp-hospital-place-btn,
    .camp-hospital-shp-btn {
        width: 100%;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: #fff;
        color: #2563eb;
        padding: 7px 9px;
        margin-bottom: 7px;
        font-family: 'Cairo', sans-serif;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
    }
    .camp-hospital-place-btn:hover,
    .camp-hospital-shp-btn:hover {
        background: #eff6ff;
    }
    .camp-hospital-place-btn.active {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
    }
    .camp-hospital-import-status {
        font-family: 'Cairo', sans-serif;
        font-size: 11px;
        color: #64748b;
        margin: 3px 0 7px;
        line-height: 1.5;
    }
</style>
<script>
(function () {
    if (typeof L === 'undefined' || !L.Map || !L.TileLayer) return;

    const PALESTINE_1923_GRID = '+proj=cass +lat_0=31.7340969444444 +lon_0=35.2120805555556 +x_0=170251.555 +y_0=126867.909 +a=6378300.789 +b=6356566.435 +towgs84=-275.7224,94.7824,340.8944,-8.001,-4.42,-11.821,1 +units=m +no_defs +type=crs';
    const WGS84 = '+proj=longlat +datum=WGS84 +no_defs';
    if (typeof proj4 === 'function') proj4.defs('EPSG:28191', PALESTINE_1923_GRID);

    async function parsePalestine1923Zip(file) {
        if (!file || !/\.zip$/i.test(file.name || '') || typeof JSZip === 'undefined' || typeof shp !== 'function' || typeof proj4 !== 'function') {
            return null;
        }

        const buffer = await file.arrayBuffer();
        const zip = await JSZip.loadAsync(buffer);
        const entries = Object.keys(zip.files).filter(name => !zip.files[name].dir);
        const shpFiles = entries.filter(name => /\.shp$/i.test(name));
        if (!shpFiles.length) return null;

        const groups = [];
        let foundPalestineGrid = false;

        for (const shpName of shpFiles) {
            const base = shpName.replace(/\.shp$/i, '');
            const dbfName = entries.find(name => name.replace(/\.dbf$/i, '') === base && /\.dbf$/i.test(name));
            const prjName = entries.find(name => name.replace(/\.prj$/i, '') === base && /\.prj$/i.test(name));
            if (!dbfName || !prjName) continue;

            const prjText = await zip.file(prjName).async('text');
            const normalizedPrj = String(prjText || '').toLowerCase().replace(/\s+/g, ' ');
            const isPalestineGrid =
                (normalizedPrj.includes('palestine') || normalizedPrj.includes('gcs_palestine_1923')) &&
                (
                    normalizedPrj.includes('28191') ||
                    normalizedPrj.includes('palestine_1923_palestine_grid') ||
                    normalizedPrj.includes('palestine 1923 / palestine grid') ||
                    normalizedPrj.includes('cassini')
                );

            if (!isPalestineGrid) continue;
            foundPalestineGrid = true;

            const shpBuffer = await zip.file(shpName).async('arraybuffer');
            const dbfBuffer = await zip.file(dbfName).async('arraybuffer');
            const transform = proj4(PALESTINE_1923_GRID, WGS84);
            const geometries = shp.parseShp(shpBuffer, transform);
            const properties = shp.parseDbf(dbfBuffer);
            const geojson = shp.combine([geometries, properties]);
            geojson.fileName = base.split('/').pop();
            groups.push(geojson);
        }

        return foundPalestineGrid ? (groups.length === 1 ? groups[0] : groups) : null;
    }

    async function loadShapefileWithCrsCorrection(file) {
        const corrected = await parsePalestine1923Zip(file);
        if (corrected) return corrected;
        return shp(await file.arrayBuffer());
    }

    let hospitalPlacementMode = false;
    let hospitalPlacementGuardInstalled = false;

    function setupHospitalControls(mapInstance) {
        const hint = document.querySelector('#tab-hospitals .click-hint');
        if (!hint || hint.dataset.hospitalControlsReady === '1') return;
        hint.dataset.hospitalControlsReady = '1';

        hint.innerHTML = '<i class="fas fa-info-circle"></i><span>اضغط زر تحديد الموقع أولًا، ثم اضغط على الخريطة</span>';

        const placeButton = document.createElement('button');
        placeButton.type = 'button';
        placeButton.className = 'camp-hospital-place-btn';
        placeButton.innerHTML = '<i class="fas fa-map-marker-alt me-1"></i>تحديد موقع المستشفى على الخريطة';
        placeButton.addEventListener('click', function () {
            hospitalPlacementMode = true;
            placeButton.classList.add('active');
            placeButton.innerHTML = '<i class="fas fa-crosshairs me-1"></i>اضغط الآن على موقع المستشفى';
            hint.innerHTML = '<i class="fas fa-mouse-pointer"></i><span>وضع تحديد الموقع مفعل — اضغط على الخريطة</span>';
            if (mapInstance.getContainer()) mapInstance.getContainer().style.cursor = 'crosshair';
        });
        hint.parentNode.insertBefore(placeButton, hint);

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.zip,.shp';
        fileInput.multiple = false;
        fileInput.style.display = 'none';
        fileInput.id = 'hospital-shp-input';

        const shpButton = document.createElement('button');
        shpButton.type = 'button';
        shpButton.className = 'camp-hospital-shp-btn';
        shpButton.innerHTML = '<i class="fas fa-file-import me-1"></i>رفع Shapefile للمستشفيات';
        shpButton.addEventListener('click', function () {
            fileInput.click();
        });

        const status = document.createElement('div');
        status.className = 'camp-hospital-import-status';
        status.id = 'hospital-shp-status';

        fileInput.addEventListener('change', async function (event) {
            const file = event.target.files && event.target.files[0];
            event.target.value = '';
            if (!file) return;

            try {
                if (typeof shp !== 'function') {
                    throw new Error('مكتبة Shapefile غير متاحة');
                }

                status.textContent = 'جاري قراءة Shapefile...';
                const geojson = await loadShapefileWithCrsCorrection(file);
                const features = Array.isArray(geojson) ? geojson.flatMap(item => item.features || []) : (geojson.features || []);
                const points = features.filter(feature =>
                    feature && feature.geometry && feature.geometry.type === 'Point' &&
                    Array.isArray(feature.geometry.coordinates) && feature.geometry.coordinates.length >= 2
                );

                if (!points.length) {
                    throw new Error('لم يتم العثور على معالم Point داخل Shapefile');
                }
                if (points.length > 2000) {
                    throw new Error('عدد المستشفيات يتجاوز الحد المسموح به وهو 2000');
                }

                const hospitals = points.map((feature, index) => {
                    const props = feature.properties || {};
                    const keys = Object.keys(props);
                    const getValue = (names) => {
                        const key = keys.find(k => names.includes(String(k).trim().toLowerCase()));
                        return key ? props[key] : null;
                    };

                    const nameValue = getValue(['name', 'hospital', 'hospital_name', 'hosp_name', 'اسم', 'اسم المستشفى']);
                    const phoneValue = getValue(['phone', 'telephone', 'tel', 'mobile', 'هاتف', 'رقم الهاتف']);
                    const typeValue = getValue(['type', 'hospital_type', 'category', 'نوع', 'نوع المستشفى']);
                    const name = nameValue !== null && String(nameValue).trim() !== ''
                        ? String(nameValue).trim()
                        : `مستشفى ${index + 1}`;

                    return {
                        name: name.substring(0, 255),
                        latitude: Number(feature.geometry.coordinates[1]),
                        longitude: Number(feature.geometry.coordinates[0]),
                        phone: phoneValue === null || String(phoneValue).trim() === '' ? null : String(phoneValue).trim().substring(0, 50),
                        type: typeValue === null || String(typeValue).trim() === '' ? 'عام' : String(typeValue).trim().substring(0, 100),
                    };
                });

                const response = await fetch('{{ route("map.hospitals.import") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ hospitals }),
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'تعذر استيراد المستشفيات');
                }

                (data.hospitals || []).forEach(function (hospital) {
                    if (typeof addHospitalMarker === 'function') addHospitalMarker(hospital);
                    if (typeof HOSPITALS_INIT !== 'undefined') HOSPITALS_INIT.push(hospital);
                });

                const count = document.getElementById('cnt-hosp');
                if (count) count.textContent = String((parseInt(count.textContent || '0', 10) || 0) + Number(data.count || 0));

                if (typeof switchTab === 'function') switchTab('hospitals');
                status.textContent = `تم استيراد ${data.count || hospitals.length} مستشفى بنجاح.`;
            } catch (error) {
                console.error(error);
                status.textContent = error.message || 'حدث خطأ أثناء استيراد Shapefile.';
            }
        });

        hint.parentNode.insertBefore(shpButton, hint.nextSibling);
        hint.parentNode.insertBefore(fileInput, hint.nextSibling);
        hint.parentNode.insertBefore(status, hint.nextSibling);
    }

    function installHospitalPlacementGuard(mapInstance) {
        if (hospitalPlacementGuardInstalled || !mapInstance || !mapInstance._events || !mapInstance._events.click) return;

        const clickEvents = Array.isArray(mapInstance._events.click)
            ? mapInstance._events.click.slice()
            : [mapInstance._events.click];

        const hospitalHandler = clickEvents.find(listener => {
            const fn = listener && listener.fn;
            if (typeof fn !== 'function') return false;
            const source = Function.prototype.toString.call(fn);
            return source.includes("getElementById('h-lat')") && source.includes("getElementById('h-lng')");
        });

        if (!hospitalHandler) return;

        const originalHandler = hospitalHandler.fn;
        const context = hospitalHandler.ctx;
        mapInstance.off('click', originalHandler, context);
        mapInstance.on('click', function (event) {
            if (!hospitalPlacementMode) return;
            originalHandler.call(this, event);
            hospitalPlacementMode = false;

            const button = document.querySelector('.camp-hospital-place-btn');
            const hint = document.querySelector('#tab-hospitals .click-hint');
            if (button) {
                button.classList.remove('active');
                button.innerHTML = '<i class="fas fa-map-marker-alt me-1"></i>تحديد موقع المستشفى على الخريطة';
            }
            if (hint) hint.innerHTML = '<i class="fas fa-check-circle"></i><span>تم تحديد الموقع. أكمل بيانات المستشفى ثم اضغط حفظ.</span>';
            if (mapInstance.getContainer()) mapInstance.getContainer().style.cursor = '';
        });

        hospitalPlacementGuardInstalled = true;
    }

    L.Map.addInitHook(function () {
        const mapInstance = this;
        const finishSetup = function () {
            setupHospitalControls(mapInstance);
            installHospitalPlacementGuard(mapInstance);
        };

        setTimeout(finishSetup, 0);
        setTimeout(finishSetup, 100);
        setTimeout(finishSetup, 500);
    });

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

    // The main map script defines processShapefile later in the page.
    // Patch it after page scripts have loaded so only Palestine 1923 / Grid files
    // use the explicit EPSG:28191 -> WGS84 transformation.
    setTimeout(function () {
        if (typeof window.processShapefile !== 'function' || window.processShapefile._crsAwareInstalled) return;

        const originalProcessShapefile = window.processShapefile;
        window.processShapefile = async function (file) {
            const explicitGeoJson = await parsePalestine1923Zip(file);
            if (!explicitGeoJson) {
                return originalProcessShapefile.call(this, file);
            }

            const icon = document.getElementById('upload-icon');
            const text = document.getElementById('upload-text');
            if (icon) icon.className = 'fas fa-spinner fa-spin';
            if (text) text.textContent = 'جاري المعالجة بنظام Palestine 1923 / Palestine Grid...';

            try {
                const features = Array.isArray(explicitGeoJson)
                    ? explicitGeoJson.flatMap(entry => entry?.features || [])
                    : (explicitGeoJson?.features || []);

                if (!features.length) {
                    throw new Error('لم يتم العثور على معالم داخل Shapefile');
                }

                const fileEntry = {
                    id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                    name: file.name,
                    features
                };

                uploadedShapefiles.push(fileEntry);
                geojsonData.push(explicitGeoJson);
                refreshShapefileSelectors();

                if (!selectedStudyAreaFileId && uploadedShapefiles.length === 1) {
                    selectedStudyAreaFileId = fileEntry.id;
                    renderStudyAreaLayer(fileEntry);
                }

                if (icon) {
                    icon.className = 'fas fa-check-circle';
                    icon.style.color = '#10b981';
                }
                const count = getAllFeatures().length;
                if (text) text.textContent = `✓ تم تحميل ${count} منطقة (EPSG:28191 → WGS84)`;
                const polygonCount = document.getElementById('cnt-polygons');
                if (polygonCount) polygonCount.textContent = count;
                const controls = document.getElementById('layer-controls');
                if (controls) controls.style.display = 'block';
                const gisButton = document.getElementById('btn-gis');
                if (gisButton) gisButton.style.display = 'flex';
            } catch (error) {
                if (icon) {
                    icon.className = 'fas fa-exclamation-triangle';
                    icon.style.color = '#ef4444';
                }
                if (text) text.textContent = 'خطأ في تحويل ملف Palestine 1923';
                console.error(error);
            }
        };
        window.processShapefile._crsAwareInstalled = true;
    }, 0);
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
