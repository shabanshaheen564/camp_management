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

        $response->setContent(str_replace('</body>', $assets . "\n</body>", $html));
        return $response;
    }
}
