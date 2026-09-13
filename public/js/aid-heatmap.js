(function () {
    'use strict';

    if (!document.getElementById('map')) return;

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

    function init(mapInstance) {
        if (mapInstance.__aidRequestHeatmapReady) return;
        mapInstance.__aidRequestHeatmapReady = true;

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
