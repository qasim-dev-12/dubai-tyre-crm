<template>
  <div v-if="lat && lng" ref="map" style="width:100%; height:250px; border-radius:8px;"></div>
</template>

<script>
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const icon = L.icon({
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41]
})

export default {
  name: 'LocationViewer',
  props: {
    lat: { type: [Number, String], default: null },
    lng: { type: [Number, String], default: null },
    label: { type: String, default: null }
  },
  mounted () {
    if (!this.lat || !this.lng) return
    const point = [Number(this.lat), Number(this.lng)]
    const map = L.map(this.$refs.map, { scrollWheelZoom: false }).setView(point, 15)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map)
    const marker = L.marker(point, { icon }).addTo(map)
    if (this.label) {
      marker.bindPopup(this.label).openPopup()
    }
  }
}
</script>
