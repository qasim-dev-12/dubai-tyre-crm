<template>
  <div>
    <input
      v-model="query"
      type="text"
      class="form-control mb-2"
      placeholder="Search location (e.g. Dubai Marina)"
      @keydown.enter.prevent="search"
    />
    <div ref="map" style="width:100%; height:350px; border-radius:8px;"></div>
    <small class="text-muted d-block mt-1">Click on the map to drop/move the pin.</small>
  </div>
</template>

<script>
import axios from 'axios'
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
  name: 'LocationPicker',
  props: {
    lat: { type: [Number, String], default: null },
    lng: { type: [Number, String], default: null },
    label: { type: String, default: null }
  },
  data () {
    return { query: '', map: null, marker: null }
  },
  mounted () {
    const start = (this.lat && this.lng) ? [Number(this.lat), Number(this.lng)] : [25.2048, 55.2708]

    this.map = L.map(this.$refs.map).setView(start, this.lat ? 15 : 10)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(this.map)

    if (this.lat && this.lng) {
      this.marker = L.marker(start, { icon }).addTo(this.map)
      if (this.label) this.marker.bindPopup(this.label).openPopup()
    }

    this.map.on('click', (e) => this.setPoint(e.latlng.lat, e.latlng.lng))
  },
  beforeDestroy () {
    if (this.map) this.map.remove()
  },
  methods: {
    setPoint (lat, lng) {
      if (this.marker) {
        this.marker.setLatLng([lat, lng])
      } else {
        this.marker = L.marker([lat, lng], { icon }).addTo(this.map)
      }
      if (this.label) this.marker.bindPopup(this.label).openPopup()
      this.$emit('picked', { lat, lng })
    },
    async search () {
      if (!this.query) return
      try {
        const { data } = await axios.get('https://nominatim.openstreetmap.org/search', {
          params: { q: this.query, format: 'json', limit: 1 }
        })
        if (!data.length) {
          this.$toast.error('Location not found')
          return
        }
        const lat = parseFloat(data[0].lat)
        const lng = parseFloat(data[0].lon)
        this.map.setView([lat, lng], 15)
        this.setPoint(lat, lng)
      } catch (e) {
        this.$toast.error('Search failed')
      }
    }
  }
}
</script>
