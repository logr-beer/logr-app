/**
 * Shared Leaflet map helpers.
 *
 * Provides loadLeaflet() and fitMapBounds() utilities for Alpine map components.
 */
window.LeafletMap = {
    loadLeaflet(callback) {
        if (!document.querySelector('link[href*="leaflet"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            link.crossOrigin = '';
            document.head.appendChild(link);
        }

        if (!window.L) {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.crossOrigin = '';
            script.onload = callback;
            document.head.appendChild(script);
        } else {
            callback();
        }
    },

    createMap(elementId, options = {}) {
        const el = document.getElementById(elementId);
        if (!el || !window.L) return null;

        if (el._leaflet_id) {
            el._leaflet_id = null;
            el.innerHTML = '';
        }

        const defaultView = options.center || [39.8, -98.5];
        const defaultZoom = options.zoom || 4;

        const map = L.map(el, { scrollWheelZoom: false, dragging: true })
            .setView(defaultView, defaultZoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        return map;
    },

    fitBounds(map, points) {
        if (!points.length) return;

        const lats = points.map(p => p.lat).sort((a, b) => a - b);
        const lngs = points.map(p => p.lng).sort((a, b) => a - b);
        const q1Lat = lats[Math.floor(lats.length * 0.05)];
        const q3Lat = lats[Math.floor(lats.length * 0.95)];
        const q1Lng = lngs[Math.floor(lngs.length * 0.05)];
        const q3Lng = lngs[Math.floor(lngs.length * 0.95)];
        const iqrLat = (q3Lat - q1Lat) || 1;
        const iqrLng = (q3Lng - q1Lng) || 1;

        const inliers = points.filter(p =>
            p.lat >= q1Lat - iqrLat * 1.5 && p.lat <= q3Lat + iqrLat * 1.5 &&
            p.lng >= q1Lng - iqrLng * 1.5 && p.lng <= q3Lng + iqrLng * 1.5
        );

        const fitPoints = inliers.length > 0 ? inliers : points;
        const bounds = L.latLngBounds(fitPoints.map(p => [p.lat, p.lng]));
        map.fitBounds(bounds.pad(0.1));
    },

    createMarker(map, point, { color = '#f59e0b', size = 12, label = '', popup = '' }) {
        const icon = L.divIcon({
            className: '',
            html: `<div style="background:${color};width:${size}px;height:${size}px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;color:#fff;font-size:${size > 14 ? '9' : '0'}px;font-weight:700">${label}</div>`,
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
            popupAnchor: [0, -(size / 2 + 2)],
        });

        return L.marker([point.lat, point.lng], { icon })
            .addTo(map)
            .bindPopup(popup);
    },
};
