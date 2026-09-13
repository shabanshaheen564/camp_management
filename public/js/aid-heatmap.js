(function () {
    'use strict';

    if (!document.getElementById('map')) return;

    function getGlobalValue(name, fallback) {
        try {
            return window.eval("typeof " + name + " !== 'undefined' ? " + name + " : null") || fallback;
        } catch (error) {
            return fallback;
        }
    }

    function getMapInstance() {
        try {
            return window.eval("typeof map !== 'undefined' ? map : null");
        } catch (error) {
            console.warn('Unable to access existing Leaflet map instance.', error);
            return null;
        }
    }

    function waitForMap(callback, attempts) {
        const mapInstance = getMapInstance();
        if (mapInstance && window.L) {
            callback(mapInstance);
            return;
        }
        if ((attempts || 0) >= 100) return;
        setTimeout(function () { waitForMap(callback, (attempts || 0) + 1); }, 100);
    }

    function loadHeatPlugin(callback) {
        if (window.L && typeof L.heatLayer === 'function') {
            callback();
            return;
        }

        const existing = document.querySelector('script[data-aid-heat-plugin="1"]');
        if (existing) {
            existing.addEventListener('load', callback, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js';
        script.async = false;
        script.dataset.aidHeatPlugin = '1';
        script.addEventListener('load', callback, { once: true });
        document.head.appendChild(script);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setupCampAidPopups() {
        const camps = getGlobalValue('CAMPS_DATA', []);
        const markers = getGlobalValue('campMarkers', []);
        if (!Array.isArray(camps) || !Array.isArray(markers) || !markers.length) return;

        camps.forEach(function (camp) {
            const lat = Number(camp.latitude);
            const lng = Number(camp.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            const marker = markers.find(function (item) {
                const position = item.getLatLng();
                return Math.abs(position.lat - lat) < 0.000001 && Math.abs(position.lng - lng) < 0.000001;
            });
            if (!marker || marker.__campAidPopupReady) return;

            marker.__campAidPopupReady = true;
            marker.__campAidBasePopup = marker.getPopup() ? marker.getPopup().getContent() : '';
            marker.__campAidLoaded = false;
            marker.__campAidLoading = false;

            marker.on('click', function () {
                if (marker.__campAidLoading || marker.__campAidLoaded) return;

                marker.__campAidLoading = true;
                marker.setPopupContent(marker.__campAidBasePopup +
                    '<div data-camp-aid-section style="margin-top:10px;border-top:1px solid #e2e8f0;padding-top:8px;font-family:Cairo,sans-serif;direction:rtl;text-align:right;min-width:200px">' +
                    '<div style="font-weight:800;color:#1e3a5f;margin-bottom:5px"><i class="fas fa-hand-holding-heart" style="color:#dc2626"></i> طلبات المساعدات</div>' +
                    '<div style="font-size:11px;color:#64748b">جاري تحميل البيانات...</div>' +
                    '</div>'
                );

                fetch('/map/camp-aid-requests/' + encodeURIComponent(camp.id), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('تعذر تحميل طلبات المساعدات');
                        return response.json();
                    })
                    .then(function (data) {
                        const types = Array.isArray(data.aid_types) ? data.aid_types : [];
                        let details = '';

                        if (!types.length || Number(data.total_requests) === 0) {
                            details = '<div style="font-size:11px;color:#64748b">لا توجد طلبات مساعدات مسجلة لهذا المخيم.</div>';
                        } else {
                            details = '<div style="font-size:12px;color:#475569;margin-bottom:6px">الإجمالي: <strong style="color:#dc2626">' +
                                Number(data.total_requests).toLocaleString('ar') + '</strong> طلب</div>' +
                                '<div style="display:flex;flex-direction:column;gap:4px">' +
                                types.map(function (type) {
                                    return '<div style="display:flex;justify-content:space-between;align-items:center;background:#f8fafc;border-radius:6px;padding:5px 7px;font-size:11px">' +
                                        '<span><i class="fas fa-box-open" style="color:#2563eb;width:15px"></i> ' + escapeHtml(type.aid_type_name || 'غير محدد') + '</span>' +
                                        '<strong style="color:#1e3a5f">' + Number(type.request_count || 0).toLocaleString('ar') + '</strong>' +
                                        '</div>';
                                }).join('') +
                                '</div>';
                        }

                        marker.__campAidLoaded = true;
                        marker.__campAidLoading = false;
                        marker.setPopupContent(marker.__campAidBasePopup +
                            '<div data-camp-aid-section style="margin-top:10px;border-top:1px solid #e2e8f0;padding-top:8px;font-family:Cairo,sans-serif;direction:rtl;text-align:right;min-width:200px">' +
                            '<div style="font-weight:800;color:#1e3a5f;margin-bottom:6px"><i class="fas fa-hand-holding-heart" style="color:#dc2626"></i> طلبات المساعدات</div>' +
                            details +
                            '</div>'
                        );
                        marker.openPopup();
                    })
                    .catch(function (error) {
                        marker.__campAidLoading = false;
                        marker.setPopupContent(marker.__campAidBasePopup +
                            '<div data-camp-aid-section style="margin-top:10px;border-top:1px solid #e2e8f0;padding-top:8px;font-family:Cairo,sans-serif;direction:rtl;text-align:right;min-width:200px">' +
                            '<div style="font-weight:800;color:#1e3a5f;margin-bottom:5px"><i class="fas fa-hand-holding-heart" style="color:#dc2626"></i> طلبات المساعدات</div>' +
                            '<div style="font-size:11px;color:#dc2626">تعذر تحميل بيانات طلبات المساعدات.</div>' +
                            '</div>'
                        );
                        console.warn('Camp aid requests:', error.message || error);
                    });
            });
        });
    }

    function init(mapInstance) {
        if (mapInstance.__aidRequestHeatmapReady) return;
        mapInstance.__aidRequestHeatmapReady = true;

        setupCampAidPopups();

        loadHeatPlugin(function () {
            fetch('/map/aid-requests-data', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('تعذر تحميل طلبات المساعدات');
                    return response.json();
                })
                .then(function (requestData) {
                    const requests = Array.isArray(requestData) ? requestData.filter(function (item) {
                        return item && Number.isFinite(Number(item.latitude)) && Number.isFinite(Number(item.longitude)) && Number(item.request_count) > 0;
                    }) : [];

                    let enabled = true;
                    let heatLayer = null;
                    let popupLayer = null;

                    function render() {
                        if (heatLayer) {
                            mapInstance.removeLayer(heatLayer);
                            heatLayer = null;
                        }
                        if (popupLayer) {
                            mapInstance.removeLayer(popupLayer);
                            popupLayer = null;
                        }
                        if (!enabled || !requests.length || typeof L.heatLayer !== 'function') return;

                        const maxCount = Math.max.apply(null, requests.map(function (item) { return Number(item.request_count); }).concat([1]));
                        const points = requests.map(function (item) {
                            const count = Number(item.request_count);
                            return [Number(item.latitude), Number(item.longitude), 0.35 + (0.65 * count / maxCount)];
                        });

                        heatLayer = L.heatLayer(points, {
                            radius: 48,
                            blur: 30,
                            maxZoom: 17,
                            minOpacity: 0.30,
                            max: 1.0,
                        }).addTo(mapInstance);

                        popupLayer = L.layerGroup();
                        requests.forEach(function (item) {
                            const marker = L.circleMarker([Number(item.latitude), Number(item.longitude)], {
                                radius: 12,
                                stroke: false,
                                fill: false,
                                opacity: 0,
                                fillOpacity: 0,
                                interactive: true,
                            });
                            marker.bindPopup(
                                '<div style="font-family:Cairo,sans-serif;direction:rtl;text-align:right;min-width:180px">' +
                                '<div style="font-weight:800;color:#1e3a5f;margin-bottom:5px">' + String(item.camp_name || 'المخيم') + '</div>' +
                                '<div>طلبات المساعدات: <strong style="color:#dc2626">' + Number(item.request_count).toLocaleString('ar') + '</strong></div>' +
                                '</div>'
                            );
                            marker.addTo(popupLayer);
                        });
                        popupLayer.addTo(mapInstance);
                    }

                    const control = L.control({ position: 'topright' });
                    control.onAdd = function () {
                        const container = L.DomUtil.create('div', 'camp-aid-heat-control');
                        container.innerHTML = '<button type="button" class="camp-aid-heat-btn active"><span class="camp-aid-heat-dot">🔥</span><span>طلبات المساعدات: تشغيل</span></button>';
                        L.DomEvent.disableClickPropagation(container);
                        L.DomEvent.disableScrollPropagation(container);
                        const button = container.querySelector('.camp-aid-heat-btn');
                        button.addEventListener('click', function () {
                            enabled = !enabled;
                            render();
                            button.classList.toggle('active', enabled);
                            button.innerHTML = '<span class="camp-aid-heat-dot">' + (enabled ? '🔥' : '⚪') + '</span><span>طلبات المساعدات: ' + (enabled ? 'تشغيل' : 'إيقاف') + '</span>';
                        });
                        return container;
                    };
                    control.addTo(mapInstance);
                    render();
                })
                .catch(function (error) {
                    console.warn('Aid request heatmap:', error.message || error);
                });
        });
    }

    waitForMap(init);
})();
