<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnhanceFlashMessages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->headers->contains('Content-Type', 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || ! str_contains($html, '</body>')) {
            return $response;
        }

        $assets = <<<'HTML'
<style id="camp-global-flash-styles">
#flash-message .alert,
.alert.alert-dismissible {
    position: relative;
    padding: 16px 48px 16px 18px;
    margin-bottom: 12px;
    border-radius: 14px;
    font-size: .92rem;
    line-height: 1.8;
    box-shadow: 0 10px 30px rgba(15,23,42,.14);
}
#flash-message .alert .btn-close,
.alert.alert-dismissible .btn-close {
    position: absolute;
    left: 12px;
    right: auto;
    top: 12px;
    opacity: .75;
    width: 1.1rem;
    height: 1.1rem;
}
#flash-message .alert ul,
.alert.alert-dismissible ul {
    max-height: 320px;
    overflow-y: auto;
    padding-right: 20px;
    padding-left: 0;
}
@media (max-width: 768px) {
    #flash-message { width: calc(100vw - 24px) !important; left: 12px !important; }
}
</style>
<script>
(function () {
    const duration = 15000;
    window.setTimeout(function () {
        document.querySelectorAll('.alert.alert-dismissible').forEach(function (alert) {
            if (document.body.contains(alert) && window.bootstrap?.Alert) {
                window.bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        });
    }, duration);
})();
</script>
HTML;

        // Add the basemap switcher only to the existing Leaflet camp map.
        // The replacement is deliberately scoped to the current OSM tile line,
        // so no controllers, GIS layers, or analysis logic are changed.
        $mapTileLine = "L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(map);";
        $mapTileReplacement = <<<'JS'
        const baseMaps = {
            'الخريطة العادية': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }),
            '🛰️ صور جوية / Satellite': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Sources: Esri, Maxar, Earthstar Geographics, and the GIS User Community',
                maxZoom: 19
            })
        };

        baseMaps['الخريطة العادية'].addTo(map);
        L.control.layers(baseMaps, null, {
            collapsed: false,
            position: 'topright'
        }).addTo(map);
JS;

        if (str_contains($html, $mapTileLine)) {
            $html = str_replace($mapTileLine, $mapTileReplacement, $html, $count);
            if ($count > 0) {
                $switcherStyle = <<<'HTML'
<style id="camp-basemap-switcher-styles">
.leaflet-control-layers {
    direction: rtl;
    text-align: right;
    font-family: 'Cairo', sans-serif;
    font-size: 12px;
    border-radius: 10px;
    box-shadow: 0 4px 16px rgba(15,23,42,.18);
}
.leaflet-control-layers-toggle {
    width: 40px;
    height: 40px;
}
.leaflet-control-layers-expanded {
    padding: 8px 10px;
}
.leaflet-control-layers label {
    margin: 4px 0;
    cursor: pointer;
}
@media (max-width: 768px) {
    .leaflet-control-layers-expanded {
        font-size: 11px;
    }
}
</style>
HTML;
                $assets .= "\n" . $switcherStyle;
            }
        }

        // Make hospital placement explicit: clicking the map is ignored unless
        // the user first activates the medical-point placement mode.
        $hospitalClickBlock = <<<'JS'
        // ===== MAP CLICK → HOSPITAL COORDS =====
        map.on('click', e => {
            document.getElementById('h-lat').value = e.latlng.lat.toFixed(6);
            document.getElementById('h-lng').value = e.latlng.lng.toFixed(6);
            if (tempMarker) map.removeLayer(tempMarker);
            tempMarker = L.marker([e.latlng.lat, e.latlng.lng], { icon: hospTmpIcon }).addTo(map);
            tempMarker.bindPopup('<div style="font-family:Cairo;font-size:12px;direction:rtl">📍 موقع المستشفى المحدد<br><small>أكمل البيانات واضغط حفظ</small></div>').openPopup();
            switchTab('hospitals');
        });
JS;
        $hospitalClickReplacement = <<<'JS'
        // ===== MAP CLICK → HOSPITAL COORDS =====
        let hospitalPickMode = false;
        map.on('click', e => {
            if (!hospitalPickMode) return;

            document.getElementById('h-lat').value = e.latlng.lat.toFixed(6);
            document.getElementById('h-lng').value = e.latlng.lng.toFixed(6);
            if (tempMarker) map.removeLayer(tempMarker);
            tempMarker = L.marker([e.latlng.lat, e.latlng.lng], { icon: hospTmpIcon }).addTo(map);
            tempMarker.bindPopup('<div style="font-family:Cairo;font-size:12px;direction:rtl">📍 موقع النقطة الطبية المحدد<br><small>أكمل البيانات واضغط حفظ</small></div>').openPopup();

            hospitalPickMode = false;
            const pickBtn = document.getElementById('hospital-pick-btn');
            if (pickBtn) {
                pickBtn.classList.remove('active');
                pickBtn.innerHTML = '<i class="fas fa-crosshairs me-1"></i>تحديد موقع نقطة طبية على الخريطة';
            }
            switchTab('hospitals');
        });
JS;

        if (str_contains($html, $hospitalClickBlock)) {
            $html = str_replace($hospitalClickBlock, $hospitalClickReplacement, $html, $count);
            if ($count > 0) {
                $hospitalTools = <<<'HTML'
<style id="camp-hospital-tools-styles">
#hospital-pick-btn.active {
    background: #dc2626;
    border-color: #dc2626;
    color: #fff;
    box-shadow: 0 4px 14px rgba(220,38,38,.28);
}
.hospital-import-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px;
    margin-top: 10px;
}
.hospital-import-card .import-title {
    font-size: 11px;
    font-weight: 700;
    color: #1e3a5f;
    margin-bottom: 6px;
}
.hospital-import-card .import-status {
    font-size: 11px;
    color: #64748b;
    margin-top: 6px;
    line-height: 1.6;
}
</style>
<script>
(function () {
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]);
        });
    }

    function getNameField(features) {
        const keys = [...new Set(features.flatMap(function (feature) {
            return Object.keys(feature.properties || {});
        }))];
        const preferred = [
            'name', 'NAME', 'Name', 'hospital', 'HOSPITAL', 'Hospital',
            'facility', 'FACILITY', 'Facility', 'title', 'TITLE', 'Title',
            'الاسم', 'اسم', 'اسم_المستشفى', 'اسم المستشفى'
        ];
        return preferred.find(function (key) { return keys.includes(key); }) || keys.find(function (key) {
            return features.some(function (feature) {
                const value = feature.properties?.[key];
                return typeof value === 'string' && value.trim();
            });
        }) || '';
    }

    function buildHospitalFromFeature(feature, nameField, index) {
        if (!feature || feature.geometry?.type !== 'Point') return null;
        const coordinates = feature.geometry.coordinates || [];
        const lng = Number(coordinates[0]);
        const lat = Number(coordinates[1]);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;

        const props = feature.properties || {};
        const rawName = nameField ? props[nameField] : '';
        const name = String(rawName ?? '').trim() || `نقطة طبية ${index + 1}`;
        const phoneKey = ['phone', 'PHONE', 'Phone', 'telephone', 'TELEPHONE', 'هاتف'].find(function (key) {
            return props[key] !== undefined && props[key] !== null && String(props[key]).trim() !== '';
        });
        const typeKey = ['type', 'TYPE', 'Type', 'facility_type', 'FACILITY_TYPE', 'نوع'].find(function (key) {
            return props[key] !== undefined && props[key] !== null && String(props[key]).trim() !== '';
        });

        return {
            name: name,
            latitude: lat,
            longitude: lng,
            phone: phoneKey ? String(props[phoneKey]).trim() : null,
            type: typeKey ? String(props[typeKey]).trim() : 'عام'
        };
    }

    function addImportedMarker(h) {
        const m = L.marker([+h.latitude, +h.longitude], { icon: hospIcon }).addTo(map);
        const safeName = escapeHtml(h.name);
        const safeType = escapeHtml(h.type || 'عام');
        const safePhone = escapeHtml(h.phone || '');
        m.bindPopup(`<div style="font-family:Cairo;direction:rtl;min-width:160px;padding:4px">
            <h6 style="color:#dc2626;font-weight:700;border-bottom:2px solid #dc2626;padding-bottom:5px"><i class="fas fa-hospital-alt"></i> ${safeName}</h6>
            <div style="font-size:12px;color:#475569">
                <div><i class="fas fa-tag" style="width:15px"></i> ${safeType}</div>
                ${safePhone ? `<div><i class="fas fa-phone" style="width:15px"></i> ${safePhone}</div>` : ''}
            </div></div>`);
        hospitalMarkers[h.id] = m;
    }

    function appendHospitalItem(h) {
        const list = document.getElementById('hospitals-list');
        if (!list) return;
        const div = document.createElement('div');
        div.className = 'hospital-item';
        div.id = `hi-${h.id}`;
        div.innerHTML = `<div><div class="h-name"><i class="fas fa-hospital-alt text-danger me-1"></i>${escapeHtml(h.name)}</div><div class="h-type">${escapeHtml(h.type || 'عام')}${h.phone ? ' • ' + escapeHtml(h.phone) : ''}</div></div><button class="btn-del" onclick="deleteHospital(${h.id})"><i class="fas fa-trash"></i></button>`;
        list.prepend(div);
    }

    function setupHospitalTools() {
        const addSection = document.querySelector('#tab-hospitals .add-section');
        if (!addSection || document.getElementById('hospital-pick-btn')) return;

        const title = addSection.querySelector('h6');
        const hint = addSection.querySelector('.click-hint');
        if (hint) hint.innerHTML = '<i class="fas fa-info-circle"></i><span>فعّل زر تحديد الموقع أولاً، ثم انقر على الخريطة</span>';

        const pickBtn = document.createElement('button');
        pickBtn.type = 'button';
        pickBtn.id = 'hospital-pick-btn';
        pickBtn.className = 'btn btn-outline-danger btn-sm w-100 mb-2';
        pickBtn.innerHTML = '<i class="fas fa-crosshairs me-1"></i>تحديد موقع نقطة طبية على الخريطة';
        pickBtn.addEventListener('click', function () {
            hospitalPickMode = !hospitalPickMode;
            pickBtn.classList.toggle('active', hospitalPickMode);
            pickBtn.innerHTML = hospitalPickMode
                ? '<i class="fas fa-location-arrow me-1"></i>انقر الآن على الخريطة لتحديد الموقع'
                : '<i class="fas fa-crosshairs me-1"></i>تحديد موقع نقطة طبية على الخريطة';
            if (hospitalPickMode) switchTab('hospitals');
        });
        if (title) title.insertAdjacentElement('afterend', pickBtn);

        const importCard = document.createElement('div');
        importCard.className = 'hospital-import-card';
        importCard.innerHTML = `
            <div class="import-title"><i class="fas fa-file-import me-1"></i>استيراد مستشفيات من Shapefile</div>
            <input type="file" id="hospital-shp-input" class="form-control form-control-sm" accept=".zip,.shp">
            <div id="hospital-shp-name-wrap" style="display:none;margin-top:7px">
                <label style="font-size:10px;color:#64748b;display:block;margin-bottom:3px">حقل اسم المستشفى</label>
                <select id="hospital-shp-name-field" class="form-select form-select-sm"></select>
            </div>
            <button type="button" id="hospital-shp-import-btn" class="btn btn-success btn-sm w-100 mt-2" disabled>
                <i class="fas fa-upload me-1"></i>استيراد النقاط الطبية
            </button>
            <div id="hospital-shp-status" class="import-status">يفضل استخدام ZIP يحتوي على .shp + .dbf + .shx + .prj.</div>
        `;
        addSection.appendChild(importCard);

        const input = document.getElementById('hospital-shp-input');
        const fieldSelect = document.getElementById('hospital-shp-name-field');
        const importBtn = document.getElementById('hospital-shp-import-btn');
        const status = document.getElementById('hospital-shp-status');
        let pendingHospitals = [];

        input.addEventListener('change', async function () {
            const file = input.files?.[0];
            pendingHospitals = [];
            importBtn.disabled = true;
            document.getElementById('hospital-shp-name-wrap').style.display = 'none';
            if (!file) return;

            status.textContent = 'جاري قراءة Shapefile...';
            try {
                const geojson = await shp(await file.arrayBuffer());
                const features = Array.isArray(geojson)
                    ? geojson.flatMap(function (entry) { return entry?.features || []; })
                    : (geojson?.features || []);
                const pointFeatures = features.filter(function (feature) {
                    return feature?.geometry?.type === 'Point';
                });
                if (!pointFeatures.length) throw new Error('لم يتم العثور على نقاط Point داخل الملف.');

                const nameField = getNameField(pointFeatures);
                const keys = [...new Set(pointFeatures.flatMap(function (feature) {
                    return Object.keys(feature.properties || {});
                }))];
                fieldSelect.innerHTML = '<option value="">بدون حقل اسم — إنشاء أسماء تلقائية</option>' + keys.map(function (key) {
                    return `<option value="${escapeHtml(key)}">${escapeHtml(key)}</option>`;
                }).join('');
                if (nameField) fieldSelect.value = nameField;
                document.getElementById('hospital-shp-name-wrap').style.display = 'block';

                pendingHospitals = pointFeatures.map(function (feature, index) {
                    return buildHospitalFromFeature(feature, fieldSelect.value || nameField, index);
                }).filter(Boolean);

                status.textContent = `تم العثور على ${pendingHospitals.length} نقطة. اختر حقل الاسم ثم اضغط استيراد.`;
                importBtn.disabled = !pendingHospitals.length;
            } catch (error) {
                console.error(error);
                status.textContent = 'تعذر قراءة الملف. تأكد أن ZIP يحتوي على مكونات Shapefile كاملة وبنظام إحداثيات صحيح.';
            }
        });

        fieldSelect.addEventListener('change', function () {
            const selectedField = fieldSelect.value;
            const file = input.files?.[0];
            if (!file || !pendingHospitals.length) return;
            // Rebuild names from the parsed features kept in the latest parse result.
            if (window.__hospitalShpFeatures) {
                pendingHospitals = window.__hospitalShpFeatures.map(function (feature, index) {
                    return buildHospitalFromFeature(feature, selectedField, index);
                }).filter(Boolean);
                status.textContent = `جاهز لاستيراد ${pendingHospitals.length} نقطة.`;
            }
        });

        input.addEventListener('change', async function () {
            const file = input.files?.[0];
            if (!file) return;
            try {
                const geojson = await shp(await file.arrayBuffer());
                window.__hospitalShpFeatures = Array.isArray(geojson)
                    ? geojson.flatMap(function (entry) { return entry?.features || []; }).filter(function (feature) { return feature?.geometry?.type === 'Point'; })
                    : (geojson?.features || []).filter(function (feature) { return feature?.geometry?.type === 'Point'; });
            } catch (_) {
                window.__hospitalShpFeatures = null;
            }
        });

        importBtn.addEventListener('click', async function () {
            if (!pendingHospitals.length) return;
            importBtn.disabled = true;
            status.textContent = 'جاري حفظ النقاط الطبية...';
            try {
                const res = await fetch('/map/hospitals/import', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ hospitals: pendingHospitals })
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'فشل الاستيراد');

                (data.hospitals || []).forEach(function (h) {
                    addImportedMarker(h);
                    appendHospitalItem(h);
                    HOSPITALS_INIT.push(h);
                });
                const cnt = document.getElementById('cnt-hosp');
                cnt.textContent = parseInt(cnt.textContent || '0', 10) + (data.count || 0);
                status.textContent = `✓ تم استيراد ${data.count || 0} نقطة طبية بنجاح.`;
                input.value = '';
                pendingHospitals = [];
                importBtn.disabled = true;
            } catch (error) {
                console.error(error);
                status.textContent = 'فشل استيراد النقاط الطبية. تأكد من البيانات ثم حاول مرة أخرى.';
                importBtn.disabled = false;
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupHospitalTools);
    } else {
        setupHospitalTools();
    }
})();
</script>
HTML;
                $assets .= "\n" . $hospitalTools;
            }
        }

        $response->setContent(str_replace('</body>', $assets . "\n</body>", $html));
        return $response;
    }
}