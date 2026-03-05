@php
    $zone = $getRecord();
    $coordinates = $zone?->getPolygonCoordinates() ?? [];
    $mapId = 'zone-map-' . ($zone?->id ?? uniqid());
@endphp
<div wire:ignore class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900">
    @if(empty($coordinates))
        <div class="flex items-center justify-center h-64 text-gray-500 dark:text-gray-400">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                <p class="mt-2 text-sm">{{ __('No polygon data available') }}</p>
            </div>
        </div>
    @else
        <div id="{{ $mapId }}" class="w-full zone-map-container" style="height: 400px;" data-coordinates="{{ json_encode($coordinates) }}"></div>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            (function() {
                function initZoneMap() {
                    const container = document.getElementById('{{ $mapId }}');
                    if (!container || typeof L === 'undefined') return;
                    const coords = JSON.parse(container.dataset.coordinates || '[]');
                    if (!coords || coords.length < 3) return;
                    const map = L.map('{{ $mapId }}').setView([24.7136, 46.6753], 10);
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    }).addTo(map);
                    const polygon = L.polygon(coords, {
                        color: '#3388ff',
                        fillColor: '#3388ff',
                        fillOpacity: 0.3,
                        weight: 2
                    }).addTo(map);
                    map.fitBounds(polygon.getBounds(), { padding: [30, 30] });
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initZoneMap);
                } else {
                    initZoneMap();
                }
            })();
        </script>
    @endif
</div>
