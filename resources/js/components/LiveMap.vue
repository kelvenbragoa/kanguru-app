<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    latitude: { type: Number, default: null },
    longitude: { type: Number, default: null },
    updatedAt: { type: String, default: null },
    label: { type: String, default: 'Motorista' },
    height: { type: String, default: '280px' },
});

const MAPUTO = [-25.969248, 32.573176];
const el = ref(null);
let map = null;
let marker = null;

const hasPoint = () => Number.isFinite(props.latitude) && Number.isFinite(props.longitude);

const icon = L.divIcon({
    className: 'kg-map-pin',
    html: '<span></span>',
    iconSize: [18, 18],
    iconAnchor: [9, 9],
});

const sync = () => {
    if (!map) return;
    if (!hasPoint()) return;
    const latlng = [props.latitude, props.longitude];
    if (!marker) {
        marker = L.marker(latlng, { icon }).addTo(map);
    } else {
        marker.setLatLng(latlng);
    }
    marker.bindPopup(props.label);
    map.setView(latlng, Math.max(map.getZoom(), 14));
};

onMounted(() => {
    map = L.map(el.value, { zoomControl: true, attributionControl: true }).setView(
        hasPoint() ? [props.latitude, props.longitude] : MAPUTO,
        hasPoint() ? 14 : 12
    );
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);
    sync();
    setTimeout(() => map?.invalidateSize(), 80);
});

watch(() => [props.latitude, props.longitude, props.label], sync);

onUnmounted(() => {
    map?.remove();
    map = null;
    marker = null;
});
</script>

<template>
    <div class="kg-map">
        <div ref="el" class="kg-map-canvas" :style="{ height }"></div>
        <p v-if="!hasPoint()" class="kg-map-empty">Ainda sem localização GPS deste pedido.</p>
        <p v-else-if="updatedAt" class="kg-map-meta">Actualizado {{ updatedAt }}</p>
    </div>
</template>

<style>
.kg-map {
    position: relative;
}
.kg-map-canvas {
    width: 100%;
    border-radius: 1rem;
    overflow: hidden;
    background: #e5e7eb;
}
.kg-map-empty,
.kg-map-meta {
    margin: 0.6rem 0 0;
    color: #6b7280;
    font-size: 0.85rem;
}
.kg-map-pin span {
    display: block;
    width: 18px;
    height: 18px;
    border-radius: 999px;
    background: #f59e0b;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #111827;
}
</style>
