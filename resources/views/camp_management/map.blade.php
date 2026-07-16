@extends('layouts.skeleton')

@section('title', 'خريطة المخيمات - تحليل GIS')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
/* ===== Layout ===== */
.map-wrapper{display:flex;height:calc(100vh - 160px);min-height:580px;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.12)}
#map{flex:1;position:relative}

/* ===== Side Panel ===== */
.side-panel{width:340px;background:#fff;display:flex;flex-direction:column;border-left:1px solid #e2e8f0;font-family:'Cairo',sans-serif;direction:rtl;z-index:10}
.sp-header{padding:16px;background:linear-gradient(135deg,#1e3a5f,#2563eb);color:#fff}
.sp-header h5{margin:0;font-size:15px;font-weight:700}
.sp-header p{margin:3px 0 0;font-size:11px;opacity:.8}

.panel-tabs{display:flex;border-bottom:2px solid #e2e8f0;background:#f8fafc}
.ptab{flex:1;padding:10px 4px;text-align:center;font-size:12px;font-weight:600;cursor:pointer;color:#64748b;border:none;background:none;transition:all .2s;border-bottom:2px solid transparent;margin-bottom:-2px}
.ptab.active{color:#2563eb;border-bottom-color:#2563eb;background:#fff}
.ptab i{display:block;font-size:14px;margin-bottom:2px}

.panel-body{flex:1;overflow-y:auto;padding:14px}

/* ===== Stats ===== */
.stats-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:12px}
.stat-pill{background:#f1f5f9;border-radius:10px;padding:8px 6px;text-align:center}
.stat-pill .num{font-size:18px;font-weight:800;color:#1e3a5f}
.stat-pill .lbl{font-size:10px;color:#64748b;margin-top:1px}

/* ===== Shapefile Upload ===== */
.upload-zone{border:2px dashed #cbd5e1;border-radius:12px;padding:16px;text-align:center;cursor:pointer;transition:all .2s;background:#f8fafc;margin-bottom:12px}
.upload-zone:hover,.upload-zone.drag{border-color:#2563eb;background:#eff6ff}
.upload-zone i{font-size:28px;color:#94a3b8;margin-bottom:6px;display:block}
.upload-zone p{font-size:12px;color:#64748b;margin:0}
.upload-zone small{color:#94a3b8;font-size:11px}
#shp-input{display:none}

.layer-legend{background:#f8fafc;border-radius:10px;padding:10px 12px;margin-bottom:12px}
.layer-legend h6{font-size:12px;font-weight:700;color:#1e3a5f;margin-bottom:8px}
.legend-row{display:flex;align-items:center;gap:8px;font-size:12px;color:#475569;margin-bottom:5px}
.legend-swatch{width:16px;height:16px;border-radius:3px;flex-shrink:0}

/* ===== Hospital Form ===== */
.add-section{background:#f8fafc;border-radius:12px;padding:12px;margin-bottom:12px;border:1.5px dashed #cbd5e1}
.add-section h6{font-size:12px;font-weight:700;color:#1e3a5f;margin-bottom:8px}
.click-hint{display:flex;align-items:center;gap:6px;background:#eff6ff;border-radius:8px;padding:7px 10px;font-size:11px;color:#2563eb;margin-bottom:8px;border:1px solid #bfdbfe}

.hospital-item{display:flex;align-items:center;justify-content:space-between;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:9px 11px;margin-bottom:7px;transition:all .2s}
.hospital-item:hover{border-color:#2563eb;box-shadow:0 2px 8px rgba(37,99,235,.1)}
.h-name{font-size:12px;font-weight:700;color:#1e293b}
.h-type{font-size:11px;color:#64748b}
.btn-del{background:none;border:none;color:#ef4444;font-size:13px;cursor:pointer;padding:3px 6px;border-radius:6px}
.btn-del:hover{background:#fee2e2}

/* ===== Analysis buttons ===== */
.btn-analyze{width:100%;padding:11px;border:none;border-radius:11px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;font-family:'Cairo',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:7px;margin-bottom:8px}
.btn-analyze:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(37,99,235,.4)}
.btn-analyze:disabled{opacity:.6;cursor:not-allowed;transform:none}
.btn-analyze.green{background:linear-gradient(135deg,#10b981,#059669)}
.btn-analyze.green:hover{box-shadow:0 4px 14px rgba(16,185,129,.4)}

.btn-clear{width:100%;padding:8px;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#64748b;font-family:'Cairo',sans-serif;font-size:12px;cursor:pointer;transition:all .2s;margin-bottom:8px}
.btn-clear:hover{border-color:#ef4444;color:#ef4444}

/* ===== Results ===== */
.result-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:11px;padding:11px;margin-bottom:8px;transition:all .2s}
.result-card.inside{border-color:#10b981;background:#f0fdf4}
.result-card.outside{border-color:#f59e0b;background:#fffbeb}
.result-card .rc-camp{font-size:12px;font-weight:700;color:#1e293b;margin-bottom:4px}
.result-card .rc-status{font-size:11px;font-weight:600}
.rc-status.inside{color:#10b981}
.rc-status.outside{color:#f59e0b}
.rc-dist{font-size:11px;color:#64748b;margin-top:3px}

/* ===== Spinner ===== */
.spinner{width:16px;height:16px;border:2.5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:none}
@keyframes spin{to{transform:rotate(360deg)}}

/* ===== Map controls ===== */
.map-legend-box{position:absolute;bottom:20px;right:10px;z-index:900;background:rgba(255,255,255,.95);border-radius:10px;padding:10px 13px;box-shadow:0 2px 12px rgba(0,0,0,.12);font-family:'Cairo',sans-serif;font-size:11px;direction:rtl}
.ml-item{display:flex;align-items:center;gap:7px;margin-bottom:4px}
.ml-dot{width:11px;height:11px;border-radius:50%}
.ml-sq{width:14px;height:11px;border-radius:2px;opacity:.7}

/* ===== Progress ===== */
.progress-bar-wrap{background:#e2e8f0;border-radius:10px;height:6px;margin-top:4px;overflow:hidden}
.progress-bar-fill{height:100%;border-radius:10px;background:linear-gradient(90deg,#2563eb,#10b981);transition:width .4s}

/* ===== Popup ===== */
.leaflet-popup-content-wrapper{border-radius:12px!important;box-shadow:0 8px 24px rgba(0,0,0,.15)!important}

/* ===== Route badge ===== */
.badge-road{display:inline-block;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:6px;font-size:10px;padding:1px 6px;margin-right:4px;font-weight:700}
.badge-straight{display:inline-block;background:#fffbeb;color:#d97706;border:1px solid #fde68a;border-radius:6px;font-size:10px;padding:1px 6px;margin-right:4px;font-weight:700}
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0"><i class="fas fa-map-marked-alt me-2 text-primary"></i>خريطة المخيمات - تحليل GIS</h1>
</div>

<div class="map-wrapper position-relative">

    {{-- MAP --}}
    <div id="map">
        <div class="map-legend-box">
            <div class="ml-item"><div class="ml-dot" style="background:#2563eb"></div> مخيم</div>
            <div class="ml-item"><div class="ml-dot" style="background:#dc2626"></div> مستشفى</div>
            <div class="ml-item"><div class="ml-sq" style="background:#22c55e"></div> مناطق ممتازة (gridcode 1)</div>
            <div class="ml-item"><div class="ml-sq" style="background:#2563eb"></div> مناطق جيدة (gridcode 2)</div>
            <div class="ml-item"><div class="ml-dot" style="background:#10b981"></div> مسار داخل منطقة الدراسة</div>
            <div class="ml-item"><div class="ml-dot" style="background:#ef4444"></div> خارج منطقة الدراسة</div>
        </div>
    </div>

    {{-- SIDE PANEL --}}
    <div class="side-panel">
        <div class="sp-header">
            <h5><i class="fas fa-layer-group me-2"></i>لوحة تحليل GIS</h5>
            <p>طبقات الخريطة • المستشفيات • تحليل المسارات</p>
        </div>

        <div class="panel-tabs">
            <button class="ptab active" onclick="switchTab('layers')"><i class="fas fa-layer-group"></i>الطبقات</button>
            <button class="ptab" onclick="switchTab('hospitals')"><i class="fas fa-hospital-alt"></i>مستشفيات</button>
            <button class="ptab" onclick="switchTab('analysis')"><i class="fas fa-chart-bar"></i> تحليل مسارات مستشفى </button>
            <button class="ptab" onclick="switchTab('relocation')"><i class="fas fa-map-pin"></i>إعادة توطين</button>
        </div>

        {{-- TAB: LAYERS --}}
        <div id="tab-layers" class="panel-body">
            <div class="stats-row">
                <div class="stat-pill">
                    <div class="num">{{ $camps->count() }}</div>
                    <div class="lbl">مخيم</div>
                </div>
                <div class="stat-pill">
                    <div class="num" id="cnt-hosp">{{ $hospitals->count() }}</div>
                    <div class="lbl">مستشفى</div>
                </div>
                <div class="stat-pill">
                    <div class="num" id="cnt-polygons">0</div>
                    <div class="lbl">منطقة</div>
                </div>
            </div>

            {{-- Shapefile upload --}}
            <div class="upload-zone" id="upload-zone" onclick="document.getElementById('shp-input').click()"
                 ondragover="event.preventDefault();this.classList.add('drag')"
                 ondragleave="this.classList.remove('drag')"
                 ondrop="handleDrop(event)">
                <i class="fas fa-file-upload" id="upload-icon"></i>
                <p id="upload-text">ارفع ملف/ملفات Shapefile</p>
                <small>اسحب وأفلت ملفات .zip أو .shp متعددة (تحتوي على .shp, .dbf, .shx, .prj)</small>
            </div>
            <input type="file" id="shp-input" accept=".zip,.shp" onchange="handleFileSelect(event)" multiple>

            <div class="mb-2">
                <label class="form-label" style="font-size:11px;color:#64748b;display:block;margin-bottom:4px">ملف حدود منطقة الدراسة</label>
                <select id="study-area-file" class="form-select form-select-sm" onchange="setSelectedShapefile('study', this.value)" disabled>
                    <option value="">اختر ملفًا</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label" style="font-size:11px;color:#64748b;display:block;margin-bottom:4px">ملف أفضل المواقع</label>
                <select id="best-site-file" class="form-select form-select-sm" onchange="setSelectedShapefile('bestSite', this.value)" disabled>
                    <option value="">اختر ملفًا</option>
                </select>
            </div>

            {{-- Layer controls --}}
            <div class="layer-legend" id="layer-controls" style="display:none">
                <h6><i class="fas fa-sliders-h me-1"></i>تحكم بالطبقات</h6>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="toggle-study" checked onchange="toggleLayer('study', this.checked)">
                    <label class="form-check-label" style="font-size:12px" for="toggle-study">
                        <span class="legend-swatch d-inline-block" style="background:#2563eb;width:14px;height:10px;border-radius:2px;vertical-align:middle;margin-left:4px"></span>
                        حدود منطقة الدراسة
                    </label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="toggle-best" checked onchange="toggleLayer('best', this.checked)">
                    <label class="form-check-label" style="font-size:12px" for="toggle-best">
                        <span class="legend-swatch d-inline-block" style="background:#22c55e;width:14px;height:10px;border-radius:2px;vertical-align:middle;margin-left:4px"></span>
                        مناطق ممتازة (gridcode 1)
                    </label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="toggle-good" checked onchange="toggleLayer('good', this.checked)">
                    <label class="form-check-label" style="font-size:12px" for="toggle-good">
                        <span class="legend-swatch d-inline-block" style="background:#2563eb;width:14px;height:10px;border-radius:2px;vertical-align:middle;margin-left:4px"></span>
                        مناطق جيدة (gridcode 2)
                    </label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggle-camps" checked onchange="toggleCamps(this.checked)">
                    <label class="form-check-label" style="font-size:12px" for="toggle-camps">
                        <span style="background:#2563eb;width:10px;height:10px;border-radius:50%;display:inline-block;vertical-align:middle;margin-left:4px"></span>
                        المخيمات
                    </label>
                </div>
            </div>

            {{-- GIS Analysis: camps inside best lands --}}
            <button class="btn-analyze green" id="btn-gis" onclick="analyzeLandSuitability()" style="display:none">
                <div class="spinner" id="gis-spinner"></div>
                <i class="fas fa-map-pin" id="gis-icon"></i>
                <span id="gis-text">تحليل ملاءمة موقع المخيمات</span>
            </button>

            <div id="gis-results"></div>
        </div>

        {{-- TAB: HOSPITALS --}}
        <div id="tab-hospitals" class="panel-body" style="display:none">
            <div class="add-section">
                <h6><i class="fas fa-plus-circle me-1 text-primary"></i>إضافة مستشفى</h6>
                <div class="click-hint"><i class="fas fa-mouse-pointer"></i><span>انقر على الخريطة لتحديد الموقع</span></div>
                <div class="mb-2"><input type="text" id="h-name" class="form-control form-control-sm" placeholder="اسم المستشفى *"></div>
                <div class="row g-2 mb-2">
                    <div class="col-6"><input type="number" id="h-lat" class="form-control form-control-sm" placeholder="خط العرض" step="any"></div>
                    <div class="col-6"><input type="number" id="h-lng" class="form-control form-control-sm" placeholder="خط الطول" step="any"></div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-7"><input type="text" id="h-phone" class="form-control form-control-sm" placeholder="هاتف (اختياري)"></div>
                    <div class="col-5">
                        <select id="h-type" class="form-select form-select-sm">
                            <option value="عام">عام</option><option value="خاص">خاص</option>
                            <option value="ميداني">ميداني</option><option value="طوارئ">طوارئ</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm w-100" onclick="addHospital()"><i class="fas fa-save me-1"></i>حفظ</button>
            </div>
            <div id="hospitals-list">
                @foreach($hospitals as $h)
                <div class="hospital-item" id="hi-{{ $h->id }}">
                    <div>
                        <div class="h-name"><i class="fas fa-hospital-alt text-danger me-1"></i>{{ $h->name }}</div>
                        <div class="h-type">{{ $h->type ?? 'عام' }}{{ $h->phone ? ' • '.$h->phone : '' }}</div>
                    </div>
                    <button class="btn-del" onclick="deleteHospital({{ $h->id }})"><i class="fas fa-trash"></i></button>
                </div>
                @endforeach
            </div>
        </div>

        {{-- TAB: ANALYSIS --}}
        <div id="tab-analysis" class="panel-body" style="display:none">
            <button class="btn-analyze" id="btn-route" onclick="analyzeRoutes()">
                <div class="spinner" id="route-spinner"></div>
                <i class="fas fa-route" id="route-icon"></i>
                <span id="route-text">تحليل أقرب مستشفى لكل مخيم</span>
            </button>
            <button class="btn-clear" onclick="clearRoutes()"><i class="fas fa-times me-1"></i>مسح المسارات</button>
            <div id="route-results" style="margin-top:10px"></div>
        </div>
        {{-- TAB: RELOCATION ANALYSIS --}}
<div id="tab-relocation" class="panel-body" style="display:none">

    {{-- Settings Card --}}
    <div style="background:#f8fafc;border-radius:12px;padding:12px;margin-bottom:12px;border:1.5px solid #e2e8f0">
        <h6 style="font-size:12px;font-weight:700;color:#1e3a5f;margin-bottom:10px">
            <i class="fas fa-sliders-h me-1 text-primary"></i>معايير التحليل
        </h6>

        <div class="mb-2">
            <label style="font-size:11px;color:#64748b;display:block;margin-bottom:3px">الحد الأدنى للمساحة (دونم)</label>
            <input type="number" id="rel-min-area" class="form-control form-control-sm" value="1" min="0.1" step="0.1">
        </div>

        <div class="mb-2">
            <label style="font-size:11px;color:#64748b;display:block;margin-bottom:3px">الحد الأقصى للمسافة من مستشفى (كم)</label>
            <input type="number" id="rel-max-hosp" class="form-control form-control-sm" value="10" min="0.5" step="0.5">
        </div>

        <div class="mb-2">
            <label style="font-size:11px;color:#64748b;display:block;margin-bottom:3px">أولوية التصنيف</label>
            <select id="rel-priority" class="form-select form-select-sm">
                <option value="best">الأراضي الممتازة فقط (gridcode 1)</option>
                <option value="both">الممتازة والجيدة (gridcode 1 و 2)</option>
            </select>
        </div>

        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="rel-avoid-overlap" checked>
            <label class="form-check-label" style="font-size:11px;color:#475569" for="rel-avoid-overlap">
                تجنب تداخل المخيمات الحالية
            </label>
        </div>
    </div>

    <button class="btn-analyze" id="btn-reloc" onclick="analyzeRelocation()">
        <div class="spinner" id="reloc-spinner"></div>
        <i class="fas fa-map-pin" id="reloc-icon"></i>
        <span id="reloc-text">تشغيل تحليل الإعادة التوطين</span>
    </button>

    <button class="btn-clear" id="btn-clear-reloc" onclick="clearRelocation()" style="display:none">
        <i class="fas fa-times me-1"></i>مسح نتائج التوطين
    </button>

    <div id="relocation-results"></div>
</div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/shpjs@4.0.4/dist/shp.js"></script>
<script>
// ===== DATA =====
const CAMPS_DATA     = @json($camps);
const HOSPITALS_INIT = @json($hospitals);
const CSRF           = '{{ csrf_token() }}';
const STORE_URL      = '{{ route("map.hospitals.store") }}';
const ORS_KEY        = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6ImFjMzE4ODQ2MzQ1YzQ5MDVhYTJlNmY1YmVkMTJjMTE0IiwiaCI6Im11cm11cjY0In0=';

// ===== MAP =====
const map = L.map('map').setView([31.52, 34.44], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19}).addTo(map);

// ===== ICONS =====
const mkIcon = (color, cls) => L.divIcon({
    html:`<div style="background:${color};color:#fff;border-radius:50% 50% 50% 0;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:14px;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);transform:rotate(-45deg)"><i class="${cls}" style="transform:rotate(45deg)"></i></div>`,
    iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[0,-34],className:''
});
const campIcon     = mkIcon('#2563eb','fas fa-campground');
const campFullIcon = mkIcon('#ef4444','fas fa-campground');
const hospIcon     = mkIcon('#dc2626','fas fa-hospital-alt');
const hospTmpIcon  = mkIcon('#f97316','fas fa-map-pin');

// ===== STATE =====
let hospitalMarkers = {};
let routeLayers     = [];
let tempMarker      = null;
let geoLayers       = { best: L.layerGroup(), good: L.layerGroup(), study: L.layerGroup() };
let campMarkers     = [];
let geojsonData     = [];
let uploadedShapefiles = [];
let selectedStudyAreaFileId = '';
let selectedBestSiteFileId = '';

// ===== DRAW CAMPS =====
const campBounds = [];
CAMPS_DATA.forEach(c => {
    const lat = +c.latitude, lng = +c.longitude;
    if(isNaN(lat)||isNaN(lng)) return;
    const rate  = c.capacity>0 ? Math.round((c.occupancy/c.capacity)*100) : 0;
    const icon  = rate>=90 ? campFullIcon : campIcon;
    const color = rate>=90?'#ef4444':rate>=70?'#f59e0b':'#10b981';
    const m = L.marker([lat,lng],{icon}).addTo(map);
    m.bindPopup(`<div style="font-family:Cairo,sans-serif;direction:rtl;min-width:200px;padding:4px">
        <h6 style="font-weight:700;border-bottom:2px solid #2563eb;padding-bottom:5px;color:#1e293b"><i class="fas fa-campground" style="color:#2563eb"></i> ${c.name}</h6>
        <div style="font-size:12px;color:#475569">
            <div><i class="fas fa-map-marker-alt" style="color:#ef4444;width:16px"></i> ${c.location||'غير محدد'}</div>
            <div><i class="fas fa-users" style="color:#10b981;width:16px"></i> ${c.families} عائلة • ${c.occupancy} نازح</div>
            <div style="margin-top:4px"><div style="background:#e2e8f0;border-radius:8px;height:6px"><div style="background:${color};width:${Math.min(rate,100)}%;height:100%;border-radius:8px"></div></div>
            <small>إشغال: ${rate}%</small></div>
        </div></div>`);
    campMarkers.push(m);
    campBounds.push([lat,lng]);
});
if(campBounds.length) map.fitBounds(campBounds,{padding:[40,40]});

// ===== DRAW HOSPITALS =====
function addHospitalMarker(h){
    const m = L.marker([+h.latitude,+h.longitude],{icon:hospIcon}).addTo(map);
    m.bindPopup(`<div style="font-family:Cairo;direction:rtl;min-width:160px;padding:4px">
        <h6 style="color:#dc2626;font-weight:700;border-bottom:2px solid #dc2626;padding-bottom:5px"><i class="fas fa-hospital-alt"></i> ${h.name}</h6>
        <div style="font-size:12px;color:#475569">
            <div><i class="fas fa-tag" style="width:15px"></i> ${h.type||'عام'}</div>
            ${h.phone?`<div><i class="fas fa-phone" style="width:15px"></i> ${h.phone}</div>`:''}
        </div></div>`);
    hospitalMarkers[h.id] = m;
}
HOSPITALS_INIT.forEach(addHospitalMarker);

// ===== MAP CLICK → HOSPITAL COORDS =====
map.on('click', e => {
    document.getElementById('h-lat').value = e.latlng.lat.toFixed(6);
    document.getElementById('h-lng').value = e.latlng.lng.toFixed(6);
    if(tempMarker) map.removeLayer(tempMarker);
    tempMarker = L.marker([e.latlng.lat,e.latlng.lng],{icon:hospTmpIcon}).addTo(map);
    tempMarker.bindPopup('<div style="font-family:Cairo;font-size:12px;direction:rtl">📍 موقع المستشفى المحدد<br><small>أكمل البيانات واضغط حفظ</small></div>').openPopup();
    switchTab('hospitals');
});

// ===== SHAPEFILE UPLOAD =====
function handleDrop(e){
    e.preventDefault();
    document.getElementById('upload-zone').classList.remove('drag');
    const files = Array.from(e.dataTransfer.files || []);
    if(files.length) files.forEach(file => processShapefile(file));
}
function handleFileSelect(e){
    const files = Array.from(e.target.files || []);
    if(files.length) files.forEach(file => processShapefile(file));
    e.target.value = '';
}

function getAllFeatures(){
    return uploadedShapefiles.flatMap(item => item.features || []);
}

function getSelectedStudyAreaFeatures(){
    if (!selectedStudyAreaFileId) return getAllFeatures();
    const selected = uploadedShapefiles.find(item => item.id === selectedStudyAreaFileId);
    return selected?.features || [];
}

function getSelectedBestSiteFeatures(){
    if (!selectedBestSiteFileId) return getAllFeatures();
    const selected = uploadedShapefiles.find(item => item.id === selectedBestSiteFileId);
    return selected?.features || [];
}

function renderStudyAreaLayer(fileEntry){
    geoLayers.study.clearLayers();
    if (!fileEntry?.features?.length) return;

    const style = {
        fillColor: '#2563eb',
        fillOpacity: 0.18,
        color: '#1d4ed8',
        weight: 2.2,
        opacity: 0.9,
        dashArray: '6,4'
    };

    fileEntry.features.forEach(feat => {
        const poly = L.geoJSON(feat, { style }).addTo(geoLayers.study);
        poly.bindPopup(`<div style="font-family:Cairo;direction:rtl;padding:4px;min-width:150px">
            <h6 style="color:#1d4ed8;font-weight:700;border-bottom:2px solid #2563eb;padding-bottom:4px">📏 حدود منطقة الدراسة</h6>
            <div style="font-size:12px;color:#475569">يظهر هذا الطبق بشكل منفصل ويُمكن تشغيله وإخفاؤه</div>
        </div>`);
    });

    const toggle = document.getElementById('toggle-study');
    if (toggle?.checked) geoLayers.study.addTo(map);
}

function setSelectedShapefile(type, id){
    if (type === 'study') {
        selectedStudyAreaFileId = id;
        const selected = uploadedShapefiles.find(item => item.id === id);
        renderStudyAreaLayer(selected);
    }
    if (type === 'bestSite') selectedBestSiteFileId = id;
}

function refreshShapefileSelectors(){
    const studySelect = document.getElementById('study-area-file');
    const bestSelect = document.getElementById('best-site-file');
    const currentStudy = studySelect.value;
    const currentBest = bestSelect.value;

    studySelect.innerHTML = '<option value="">اختر ملفًا</option>';
    bestSelect.innerHTML = '<option value="">اختر ملفًا</option>';

    uploadedShapefiles.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.name;
        studySelect.appendChild(opt.cloneNode(true));
        bestSelect.appendChild(opt.cloneNode(true));
    });

    studySelect.disabled = !uploadedShapefiles.length;
    bestSelect.disabled = !uploadedShapefiles.length;
    if (uploadedShapefiles.some(item => item.id === currentStudy)) studySelect.value = currentStudy;
    if (uploadedShapefiles.some(item => item.id === currentBest)) bestSelect.value = currentBest;
}

function toggleLayer(type, show){
    const layer = geoLayers[type];
    if(!layer) return;
    if(show) layer.addTo(map); else map.removeLayer(layer);
}

function isPointInStudyArea(lat, lng){
    const features = getSelectedStudyAreaFeatures();
    if (!features.length) return true;
    return features.some(feat => pointInPolygon([lng, lat], feat.geometry));
}

async function processShapefile(file){

    const icon = document.getElementById('upload-icon');
    const text = document.getElementById('upload-text');
    icon.className = 'fas fa-spinner fa-spin';
    text.textContent = 'جاري المعالجة...';
    try {
        const buffer  = await file.arrayBuffer();
        const geojson = await shp(buffer);
        const features = Array.isArray(geojson) ? geojson.flatMap(entry => entry?.features || []) : (geojson?.features || []);
        const fileEntry = { id: `${Date.now()}-${Math.random().toString(16).slice(2)}`, name: file.name, features };
        uploadedShapefiles.push(fileEntry);
        geojsonData.push(geojson);
        loadGeoJSON(geojson, file.name);
        refreshShapefileSelectors();
        if (!selectedStudyAreaFileId && uploadedShapefiles.length === 1) {
            selectedStudyAreaFileId = fileEntry.id;
            renderStudyAreaLayer(fileEntry);
        }
        icon.className  = 'fas fa-check-circle';
        icon.style.color = '#10b981';
        const count = getAllFeatures().length;
        text.textContent = `✓ تم تحميل ${count} منطقة`;
        document.getElementById('cnt-polygons').textContent = count;
        document.getElementById('layer-controls').style.display = 'block';
        document.getElementById('btn-gis').style.display = 'flex';
    } catch(err) {
        icon.className  = 'fas fa-exclamation-triangle';
        icon.style.color = '#ef4444';
        text.textContent = 'خطأ في قراءة الملف - تأكد أنه .zip يحتوي shp+dbf+shx';
        console.error(err);
    }
}

function loadGeoJSON(geojson, fileName = ''){
    const features = Array.isArray(geojson) ? geojson.flatMap(entry => entry?.features || []) : (geojson?.features || []);
    if (!features.length) return;

    features.forEach(feat => {
        const gc     = feat.properties.gridcode || feat.properties.GRIDCODE || 2;
        const isBest = parseInt(gc) === 1;
        const poly   = L.geoJSON(feat, {
            style: {
                fillColor:   isBest ? '#22c55e' : '#2563eb',
                fillOpacity: 0.45,
                color:       isBest ? '#16a34a' : '#1d4ed8',
                weight: 1.5, opacity: 0.8,
            }
        });
        const area   = feat.properties.Shape_Area || feat.properties.shape_area || feat.properties.area_m2 || 0;
        const areaDonum = (parseFloat(area) / 1000).toFixed(2);
        poly.bindPopup(`<div style="font-family:Cairo;direction:rtl;padding:4px;min-width:160px">
            <h6 style="color:${isBest?'#16a34a':'#65a30d'};font-weight:700;border-bottom:2px solid ${isBest?'#22c55e':'#84cc16'};padding-bottom:4px">
                ${isBest?'🌟 أرض ممتازة':'✅ أرض جيدة'}</h6>
            <div style="font-size:12px;color:#475569">
                <div>التصنيف: ${isBest?'ممتاز (1)':'جيد (2)'}</div>
                <div>المساحة: ${areaDonum} دونم</div>
                ${fileName?`<div style="margin-top:4px;color:#64748b">المصدر: ${fileName}</div>`:''}
            </div></div>`);
        if(isBest) geoLayers.best.addLayer(poly);
        else       geoLayers.good.addLayer(poly);
    });
    geoLayers.best.addTo(map);
    geoLayers.good.addTo(map);
    const bounds = L.geoJSON({ type:'FeatureCollection', features }).getBounds();
    if(bounds.isValid()) map.fitBounds(bounds, {padding:[30,30]});
}

function toggleCamps(show){
    campMarkers.forEach(m => show ? m.addTo(map) : map.removeLayer(m));
}

// ===== GIS ANALYSIS: CAMPS INSIDE BEST LANDS =====
function analyzeLandSuitability(){
    if(!geojsonData.length){ alert('يرجى رفع ملف الـ Shapefile أولاً'); return; }
    const btn = document.getElementById('btn-gis');
    btn.disabled = true;
    document.getElementById('gis-spinner').style.display = 'block';
    document.getElementById('gis-icon').style.display   = 'none';
    document.getElementById('gis-text').textContent     = 'جاري التحليل...';

    setTimeout(() => {
        const features = getSelectedBestSiteFeatures();
        const results  = [];

        CAMPS_DATA.forEach(camp => {
            const lat = +camp.latitude, lng = +camp.longitude;
            if(isNaN(lat)||isNaN(lng)) return;
            let insideBest = false, insideGood = false, nearestDist = Infinity;
            features.forEach(feat => {
                const gc = parseInt(feat.properties.gridcode || feat.properties.GRIDCODE || 2);
                if(pointInPolygon([lng,lat], feat.geometry)){
                    if(gc===1) insideBest = true; else insideGood = true;
                }
                const coords = feat.geometry.coordinates[0];
                const cx = coords.reduce((s,c)=>s+c[0],0)/coords.length;
                const cy = coords.reduce((s,c)=>s+c[1],0)/coords.length;
                const d  = haversine(lat,lng,cy,cx);
                if(d<nearestDist) nearestDist = d;
            });
            results.push({ camp, insideBest, insideGood, nearestDist });
        });

        const container = document.getElementById('gis-results');
        const inside    = results.filter(r=>r.insideBest||r.insideGood).length;
        const bestCount = results.filter(r=>r.insideBest).length;
        const outside   = results.filter(r=>!r.insideBest&&!r.insideGood).length;

        container.innerHTML = `
            <div style="background:#f1f5f9;border-radius:10px;padding:10px;margin-bottom:10px;font-size:12px;direction:rtl">
                <div style="font-weight:700;color:#1e3a5f;margin-bottom:6px"><i class="fas fa-chart-pie me-1"></i>ملخص التحليل</div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>داخل أفضل الأراضي:</span><span style="font-weight:700;color:#10b981">${bestCount}</span></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>داخل أراضي جيدة:</span><span style="font-weight:700;color:#84cc16">${results.filter(r=>!r.insideBest&&r.insideGood).length}</span></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span>خارج المناطق المناسبة:</span><span style="font-weight:700;color:#f59e0b">${outside}</span></div>
                <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:${results.length?Math.round(inside/results.length*100):0}%"></div></div>
                <small style="color:#64748b">${results.length?Math.round(inside/results.length*100):0}% من المخيمات في مواقع مناسبة</small>
            </div>
            ${results.map(r=>`
            <div class="result-card ${r.insideBest?'inside':r.insideGood?'':'outside'}">
                <div class="rc-camp"><i class="fas fa-campground text-primary me-1"></i>${r.camp.name}</div>
                <div class="rc-status ${r.insideBest?'inside':r.insideGood?'inside':'outside'}">
                    ${r.insideBest?'🌟 داخل أرض ممتازة':r.insideGood?'✅ داخل أرض جيدة':'⚠️ خارج المناطق المناسبة'}
                </div>
                ${!r.insideBest&&!r.insideGood?`<div class="rc-dist">أقرب منطقة مناسبة: ${r.nearestDist.toFixed(2)} كم</div>`:''}
            </div>`).join('')}`;

        btn.disabled = false;
        document.getElementById('gis-spinner').style.display = 'none';
        document.getElementById('gis-icon').style.display   = 'inline';
        document.getElementById('gis-text').textContent     = 'إعادة التحليل';
    }, 100);
}

// ===== ROUTE ANALYSIS (ORS - طرق حقيقية) =====
async function analyzeRoutes(){
    const hosps = Object.keys(hospitalMarkers);
    if(!hosps.length){ alert('أضف مستشفى واحد على الأقل'); return; }

    const btn = document.getElementById('btn-route');
    btn.disabled = true;
    document.getElementById('route-spinner').style.display = 'block';
    document.getElementById('route-icon').style.display   = 'none';
    document.getElementById('route-text').textContent     = 'جاري التحليل...';
    clearRoutes(false);

    const container = document.getElementById('route-results');
    container.innerHTML = '';

    for(const camp of CAMPS_DATA){
        const cLat = +camp.latitude, cLng = +camp.longitude;
        if(isNaN(cLat)||isNaN(cLng)) continue;

        let nearest = null, minD = Infinity;
        HOSPITALS_INIT.forEach(h => {
            const d = haversine(cLat, cLng, +h.latitude, +h.longitude);
            if(d < minD){ minD = d; nearest = h; }
        });
        if(!nearest) continue;

        const isInsideArea = isPointInStudyArea(cLat, cLng) && isPointInStudyArea(+nearest.latitude, +nearest.longitude);
        let roadDistKm  = null;
        let roadMinutes = null;
        let usedRoad    = false;

        try {
            // ===== طلب ORS Directions API =====
            const orsRes = await fetch(
                'https://api.openrouteservice.org/v2/directions/driving-car/geojson',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': ORS_KEY
                    },
                    body: JSON.stringify({
                        coordinates: [
                            [cLng, cLat],
                            [+nearest.longitude, +nearest.latitude]
                        ]
                    })
                }
            );

            if(!orsRes.ok) throw new Error(`ORS ${orsRes.status}`);

            const orsData   = await orsRes.json();
            const route     = orsData.features[0];
            roadDistKm      = (route.properties.summary.distance / 1000).toFixed(2);
            roadMinutes     = Math.round(route.properties.summary.duration / 60);
            usedRoad        = true;

            // رسم المسار الحقيقي على الخريطة
            const coords = route.geometry.coordinates.map(c => [c[1], c[0]]);
            const line   = L.polyline(coords, {
                color:     isInsideArea ? '#10b981' : '#ef4444',
                weight:    5,
                opacity:   0.85,
                lineJoin:  'round',
                lineCap:   'round',
                dashArray: isInsideArea ? undefined : '8,6'
            }).addTo(map);

            line.bindPopup(`<div style="font-family:Cairo;direction:rtl;font-size:12px;padding:4px">
                <b><i class="fas fa-campground text-primary"></i> ${camp.name}</b><br>
                <i class="fas fa-hospital-alt text-danger"></i> ${nearest.name}<br>
                <i class="fas fa-road" style="color:${isInsideArea ? '#10b981' : '#ef4444'}"></i> ${isInsideArea ? `${roadDistKm} كم • ${roadMinutes} دقيقة` : 'خارج منطقة الدراسة'}
            </div>`);

            routeLayers.push(line);

        } catch(err) {
            console.warn('ORS failed, fallback to straight line:', err.message);

            // احتياطي: خط مستقيم متقطع
            const line = L.polyline([[cLat,cLng],[+nearest.latitude,+nearest.longitude]], {
                color:     isInsideArea ? '#f59e0b' : '#ef4444',
                weight:    3,
                opacity:   0.75,
                dashArray: '8,6'
            }).addTo(map);
            routeLayers.push(line);
        }

        // بطاقة النتيجة
        const distLabel = usedRoad
            ? `<span class="badge-road"><i class="fas fa-road"></i> طريق</span>${roadDistKm} كم • ${roadMinutes} دقيقة`
            : `<span class="badge-straight"><i class="fas fa-ruler"></i> هوائي</span>${minD.toFixed(2)} كم`;

        const div = document.createElement('div');
        div.className = isInsideArea ? 'result-card inside' : 'result-card outside';
        div.innerHTML = `
            <div class="rc-camp"><i class="fas fa-campground text-primary me-1"></i>${camp.name}</div>
            <div class="rc-status ${isInsideArea ? 'inside' : 'outside'}"><i class="fas fa-hospital-alt me-1"></i>${nearest.name}</div>
            <div class="rc-dist" style="margin-top:5px">${isInsideArea ? distLabel : `<span style="color:#dc2626;font-weight:700">⚠️ خارج منطقة الدراسة</span>`}</div>`;
        container.appendChild(div);
    }

    btn.disabled = false;
    document.getElementById('route-spinner').style.display = 'none';
    document.getElementById('route-icon').style.display   = 'inline';
    document.getElementById('route-text').textContent     = 'إعادة التحليل';
}

function clearRoutes(clearUI=true){
    routeLayers.forEach(l => map.removeLayer(l));
    routeLayers = [];
    if(clearUI) document.getElementById('route-results').innerHTML = '';
}

// ===== HOSPITAL CRUD =====
async function addHospital(){
    const name  = document.getElementById('h-name').value.trim();
    const lat   = parseFloat(document.getElementById('h-lat').value);
    const lng   = parseFloat(document.getElementById('h-lng').value);
    const phone = document.getElementById('h-phone').value.trim();
    const type  = document.getElementById('h-type').value;
    if(!name){ alert('يرجى إدخال اسم المستشفى'); return; }
    if(isNaN(lat)||isNaN(lng)){ alert('يرجى تحديد الموقع على الخريطة'); return; }
    try{
        const res  = await fetch(STORE_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({name,latitude:lat,longitude:lng,phone,type})});
        const data = await res.json();
        if(!data.success) throw new Error();
        const h = data.hospital;
        addHospitalMarker(h);
        const list = document.getElementById('hospitals-list');
        const div  = document.createElement('div');
        div.className = 'hospital-item'; div.id = `hi-${h.id}`;
        div.innerHTML = `<div><div class="h-name"><i class="fas fa-hospital-alt text-danger me-1"></i>${h.name}</div><div class="h-type">${h.type||'عام'}${h.phone?' • '+h.phone:''}</div></div><button class="btn-del" onclick="deleteHospital(${h.id})"><i class="fas fa-trash"></i></button>`;
        list.prepend(div);
        const cnt = document.getElementById('cnt-hosp');
        cnt.textContent = parseInt(cnt.textContent)+1;
        ['h-name','h-lat','h-lng','h-phone'].forEach(id => document.getElementById(id).value='');
        if(tempMarker){ map.removeLayer(tempMarker); tempMarker=null; }
        HOSPITALS_INIT.push(h);
    }catch(e){ alert('حدث خطأ أثناء الحفظ'); }
}

async function deleteHospital(id){
    if(!confirm('حذف هذا المستشفى؟')) return;
    try{
        const res  = await fetch(`/map/hospitals/${id}`,{method:'DELETE',headers:{'X-CSRF-TOKEN':CSRF}});
        const data = await res.json();
        if(!data.success) throw new Error();
        if(hospitalMarkers[id]){ map.removeLayer(hospitalMarkers[id]); delete hospitalMarkers[id]; }
        document.getElementById(`hi-${id}`)?.remove();
        const cnt = document.getElementById('cnt-hosp');
        cnt.textContent = Math.max(0,parseInt(cnt.textContent)-1);
        const idx = HOSPITALS_INIT.findIndex(h=>h.id==id);
        if(idx>-1) HOSPITALS_INIT.splice(idx,1);
    }catch(e){ alert('فشل الحذف'); }
}

// ===== TABS =====
function switchTab(tab){
    ['layers','hospitals','analysis','relocation'].forEach((t,i)=>{
        document.getElementById(`tab-${t}`).style.display = t===tab?'block':'none';
        document.querySelectorAll('.ptab')[i].classList.toggle('active', t===tab);
    });
}
// ===== HELPERS =====
function haversine(lat1,lng1,lat2,lng2){
    const R=6371, r=d=>d*Math.PI/180;
    const dL=r(lat2-lat1), dl=r(lng2-lng1);
    const a=Math.sin(dL/2)**2+Math.cos(r(lat1))*Math.cos(r(lat2))*Math.sin(dl/2)**2;
    return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

function pointInPolygon(point, geometry){
    try {
        const coords = geometry.type==='MultiPolygon' ? geometry.coordinates[0][0] : geometry.coordinates[0];
        let inside=false;
        const x=point[0], y=point[1];
        for(let i=0,j=coords.length-1;i<coords.length;j=i++){
            const xi=coords[i][0],yi=coords[i][1],xj=coords[j][0],yj=coords[j][1];
            if(((yi>y)!==(yj>y))&&(x<(xj-xi)*(y-yi)/(yj-yi)+xi)) inside=!inside;
        }
        return inside;
    } catch(e){ return false; }
}
// ===== RELOCATION ANALYSIS =====
let relocationLayers = [];

async function analyzeRelocation() {
    if (!uploadedShapefiles.length) { alert('يرجى رفع ملف الـ Shapefile أولاً من تبويب "الطبقات"'); return; }

const minAreaDonum = parseFloat(document.getElementById('rel-min-area').value) || 20000;
    const maxHospKm   = parseFloat(document.getElementById('rel-max-hosp').value)   || 10;
    const priority    = document.getElementById('rel-priority').value;
    const avoidOverlap= document.getElementById('rel-avoid-overlap').checked;

    const btn = document.getElementById('btn-reloc');
    btn.disabled = true;
    document.getElementById('reloc-spinner').style.display = 'block';
    document.getElementById('reloc-icon').style.display   = 'none';
    document.getElementById('reloc-text').textContent     = 'جاري التحليل...';
    clearRelocation(false);

    await new Promise(r => setTimeout(r, 80)); // allow UI repaint

    const features = getSelectedBestSiteFeatures();

    // ── 1. فلترة المناطق حسب معايير المستخدم ──
    const candidatePolygons = [];
    features.forEach(feat => {
        const gc = parseInt(feat.properties.gridcode || feat.properties.GRIDCODE || 2);
        if (priority === 'best' && gc !== 1) return;
        if (priority === 'both' && gc > 2)   return;

        const rawArea = parseFloat(
            feat.properties.Shape_Area || feat.properties.shape_area || feat.properties.area_m2 || 0
        );
const areaDonum = rawArea / 1000;
        if (areaDonum < minAreaDonum) return;

        // مركز تقريبي للمضلع
        const coords = feat.geometry.type === 'MultiPolygon'
            ? feat.geometry.coordinates[0][0]
            : feat.geometry.coordinates[0];
        const cx = coords.reduce((s, c) => s + c[0], 0) / coords.length;
        const cy = coords.reduce((s, c) => s + c[1], 0) / coords.length;

        // أقرب مستشفى
        let nearestHosp = null, minHospDist = Infinity;
        HOSPITALS_INIT.forEach(h => {
            const d = haversine(cy, cx, +h.latitude, +h.longitude);
            if (d < minHospDist) { minHospDist = d; nearestHosp = h; }
        });
        if (minHospDist > maxHospKm) return;

        // هل تتداخل مع مخيم حالي؟
        let overlapsExistingCamp = false;
        if (avoidOverlap) {
            CAMPS_DATA.forEach(camp => {
                const lat = +camp.latitude, lng = +camp.longitude;
                if (!isNaN(lat) && !isNaN(lng)) {
                    if (pointInPolygon([lng, lat], feat.geometry)) overlapsExistingCamp = true;
                }
            });
        }

        // نقاط الأولوية: مساحة + قرب مستشفى
const score = (areaDonum / minAreaDonum) * 0.5 + ((maxHospKm - minHospDist) / maxHospKm) * 0.5;

        candidatePolygons.push({
            feat, gc, areaDonum, cx, cy,
            nearestHosp, hospDist: minHospDist,
            overlapsExistingCamp, score
        });
    });

    // ترتيب تنازلي حسب النقاط
    candidatePolygons.sort((a, b) => b.score - a.score);

    const container = document.getElementById('relocation-results');

    if (!candidatePolygons.length) {
        container.innerHTML = `<div style="background:#fff7ed;border:1.5px solid #fed7aa;border-radius:10px;padding:12px;text-align:center;font-size:12px;color:#c2410c;direction:rtl">
            <i class="fas fa-exclamation-triangle" style="font-size:18px;margin-bottom:6px;display:block"></i>
            لا توجد مناطق تستوفي المعايير المحددة.<br>
            <small style="color:#9a3412">جرب تقليل الحد الأدنى للمساحة أو زيادة المسافة من المستشفى.</small>
        </div>`;
    } else {
        // ── 2. اقتراح أفضل موقع لكل مخيم غير مناسب ──
        const campResults = [];
        CAMPS_DATA.forEach(camp => {
            const lat = +camp.latitude, lng = +camp.longitude;
            if (isNaN(lat) || isNaN(lng)) return;

            // هل هو أصلاً في موقع مناسب؟
            let alreadySuitable = false;
            features.forEach(feat => {
                const gc = parseInt(feat.properties.gridcode || feat.properties.GRIDCODE || 2);
                if ((priority === 'best' && gc === 1) || priority === 'both') {
                    if (pointInPolygon([lng, lat], feat.geometry)) alreadySuitable = true;
                }
            });

            // أقرب مرشح لإعادة التوطين
            let bestCandidate = null, minCandDist = Infinity;
            candidatePolygons.forEach(cand => {
                if (cand.overlapsExistingCamp) return;
                const d = haversine(lat, lng, cand.cy, cand.cx);
                if (d < minCandDist) { minCandDist = d; bestCandidate = cand; }
            });

            campResults.push({ camp, alreadySuitable, bestCandidate, moveDist: minCandDist });
        });

        // ── 3. رسم المناطق المقترحة على الخريطة ──
        const drawnCandidates = new Set();
        const top5 = candidatePolygons.slice(0, 5);

        top5.forEach((cand, idx) => {
            const fill  = cand.gc === 1 ? '#22c55e' : '#84cc16';
            const rank  = idx + 1;
            const poly  = L.geoJSON(cand.feat, {
                style: {
                    fillColor: fill, fillOpacity: 0.55,
                    color: '#1e3a5f', weight: 2.5, opacity: 0.9,
                    dashArray: '6,3'
                }
            }).addTo(map);

            // رقم الترتيب على المضلع
            const rankMarker = L.marker([cand.cy, cand.cx], {
                icon: L.divIcon({
                    html: `<div style="background:#1e3a5f;color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3)">${rank}</div>`,
                    iconSize: [26, 26], iconAnchor: [13, 13], className: ''
                })
            }).addTo(map);

            poly.bindPopup(`<div style="font-family:Cairo;direction:rtl;min-width:200px;padding:4px">
                <h6 style="font-weight:700;color:#1e3a5f;border-bottom:2px solid #2563eb;padding-bottom:5px">
                    🏆 الموقع المقترح #${rank}</h6>
                <div style="font-size:12px;color:#475569">
<div><i class="fas fa-ruler-combined" style="color:#2563eb;width:16px"></i> مساحة الأرض: ${cand.areaDonum.toFixed(2)} دونم</div>
                    <div><i class="fas fa-star" style="color:#f59e0b;width:16px"></i> التصنيف: ${cand.gc === 1 ? 'ممتاز (1)' : 'جيد (2)'}</div>
                    ${cand.nearestHosp ? `<div><i class="fas fa-hospital-alt" style="color:#dc2626;width:16px"></i> ${cand.nearestHosp.name}</div>
                    <div><i class="fas fa-road" style="color:#10b981;width:16px"></i> ${cand.hospDist.toFixed(2)} كم من المستشفى</div>` : ''}
                    <div style="margin-top:6px;background:#eff6ff;border-radius:6px;padding:5px 7px;font-weight:600;color:#1d4ed8">
                        <i class="fas fa-chart-line" style="margin-left:4px"></i>نقاط الملاءمة: ${(cand.score * 100).toFixed(0)}%
                    </div>
                </div></div>`);

            relocationLayers.push(poly);
            relocationLayers.push(rankMarker);
            drawnCandidates.add(idx);
        });

        // ── 4. رسم خطوط الاقتراح (مخيم → موقع مقترح) ──
        campResults.forEach(result => {
            if (!result.bestCandidate || result.alreadySuitable) return;
            const campLat = +result.camp.latitude, campLng = +result.camp.longitude;
            const arrow = L.polyline(
                [[campLat, campLng], [result.bestCandidate.cy, result.bestCandidate.cx]],
                { color: '#d97706', weight: 3, opacity: 0.85, dashArray: '6,4' }
            ).addTo(map);
            relocationLayers.push(arrow);
        });

        // ── 5. بناء واجهة النتائج ──
        const suitableCount    = campResults.filter(r => r.alreadySuitable).length;
        const unsuitableCount  = campResults.length - suitableCount;

        container.innerHTML = `
        <div style="background:#f1f5f9;border-radius:10px;padding:10px 12px;margin-bottom:10px;direction:rtl">
            <div style="font-weight:700;color:#1e3a5f;font-size:12px;margin-bottom:6px"><i class="fas fa-chart-pie me-1"></i>ملخص التوطين</div>
            <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:3px">
                <span>مخيمات تحتاج إعادة توطين</span>
                <span style="font-weight:700;color:#ef4444">${unsuitableCount}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:3px">
                <span>مخيمات في مواقع مناسبة</span>
                <span style="font-weight:700;color:#10b981">${suitableCount}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:6px">
                <span>مناطق مقترحة على الخريطة</span>
                <span style="font-weight:700;color:#2563eb">${top5.length}</span>
            </div>
        </div>

        <div style="font-size:12px;font-weight:700;color:#1e3a5f;margin-bottom:8px;direction:rtl">
            <i class="fas fa-trophy me-1 text-warning"></i>أفضل المناطق المقترحة
        </div>
        ${top5.map((cand, i) => `
        <div style="background:#fff;border:1.5px solid ${i===0?'#2563eb':'#e2e8f0'};border-radius:10px;padding:10px 11px;margin-bottom:7px;direction:rtl;cursor:pointer;transition:all .2s"
             onclick="map.flyTo([${cand.cy},${cand.cx}], 15)">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <span style="background:#1e3a5f;color:#fff;border-radius:50%;width:20px;height:20px;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;margin-left:6px">${i+1}</span>
                    <span style="font-size:12px;font-weight:700;color:#1e293b">مساحة: ${cand.areaDonum.toFixed(2)} دونم</span>

                </div>
                <span style="background:${cand.gc===1?'#dcfce7':'#f0fdf4'};color:${cand.gc===1?'#16a34a':'#4ade80'};font-size:10px;padding:2px 7px;border-radius:5px;font-weight:700">
                    ${cand.gc===1?'ممتاز':'جيد'}
                </span>
            </div>
            <div style="font-size:11px;color:#64748b;margin-top:5px">
                <i class="fas fa-hospital-alt text-danger me-1"></i>${cand.nearestHosp ? cand.nearestHosp.name + ' · ' + cand.hospDist.toFixed(1) + ' كم' : 'لا يوجد مستشفى قريب'}
            </div>
            <div style="margin-top:5px">
                <div style="background:#e2e8f0;border-radius:8px;height:5px;overflow:hidden">
                    <div style="background:linear-gradient(90deg,#2563eb,#10b981);width:${(cand.score*100).toFixed(0)}%;height:100%;border-radius:8px"></div>
                </div>
                <small style="color:#94a3b8;font-size:10px">ملاءمة: ${(cand.score*100).toFixed(0)}%</small>
            </div>
        </div>`).join('')}

        <div style="font-size:12px;font-weight:700;color:#1e3a5f;margin:12px 0 8px;direction:rtl">
            <i class="fas fa-campground me-1 text-primary"></i>توصية لكل مخيم
        </div>
        ${campResults.map(r => `
        <div class="result-card ${r.alreadySuitable ? 'inside' : 'outside'}" style="cursor:${r.bestCandidate?'pointer':'default'}"
             onclick="${r.bestCandidate ? `map.flyTo([${r.bestCandidate.cy},${r.bestCandidate.cx}],15)` : ''}">
            <div class="rc-camp"><i class="fas fa-campground text-primary me-1"></i>${r.camp.name}</div>
            ${r.alreadySuitable
                ? `<div class="rc-status inside">✅ موقعه الحالي مناسب</div>`
                : r.bestCandidate
                    ? `<div class="rc-status outside">🔄 يُقترح الانتقال ${r.moveDist.toFixed(1)} كم</div>
                       <div class="rc-dist"><i class="fas fa-map-pin me-1"></i>إلى ارض مساحتها ${r.bestCandidate.areaDonum.toFixed(2)} دونم (${r.bestCandidate.gc===1?'ممتاز':'جيد'})</div>`
                    : `<div class="rc-status outside">⚠️ لا يوجد موقع مناسب قريب</div>`
            }
        </div>`).join('')}`;

        document.getElementById('btn-clear-reloc').style.display = 'block';

        // تكبير الخريطة ليشمل النتائج
        if (top5.length) {
            const bounds = L.geoJSON({ type: 'FeatureCollection', features: top5.map(c => c.feat) }).getBounds();
            if (bounds.isValid()) map.fitBounds(bounds, { padding: [40, 40] });
        }
    }

    btn.disabled = false;
    document.getElementById('reloc-spinner').style.display = 'none';
    document.getElementById('reloc-icon').style.display   = 'inline';
    document.getElementById('reloc-text').textContent     = 'إعادة التحليل';
}

function clearRelocation(clearUI = true) {
    relocationLayers.forEach(l => map.removeLayer(l));
    relocationLayers = [];
    if (clearUI) {
        document.getElementById('relocation-results').innerHTML = '';
        document.getElementById('btn-clear-reloc').style.display = 'none';
    }
}
</script>
@endpush