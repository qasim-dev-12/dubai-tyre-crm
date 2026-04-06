
<template>
  <div>
    <!-- Page Header -->
    <div class="page-header">
      <h4>Create Lead</h4>
      <router-link to="/leads" class="btn btn-secondary">
        Back
      </router-link>
    </div>

    <!-- Form Card -->
    <div class="card">
      <div class="card-body">
        <form @submit.prevent="submit">

          <div class="row">

            <!-- Salutation -->
            <div class="col-md-2">
              <label>Salutation</label>
              <select v-model="form.salutation" class="form-control">
                <option value="">Select</option>
                <option>Mr</option>
                <option>Mrs</option>
                <option>Ms</option>
              </select>
            </div>

            <!-- Name -->
            <div class="col-md-4">
              <label>Name <span class="text-danger">*</span></label>
              <input
                type="text"
                v-model="form.name"
                class="form-control"
                required
              />
            </div>

            <!-- Service Type -->
            <div class="col-md-3">
              <label>Service Type <span class="text-danger">*</span></label>
              <select
                v-model="form.service_type_id"
                class="form-control"
                required
              >
                <option value="">Select Service</option>
                <option
                  v-for="service in serviceTypes"
                  :key="service.id"
                  :value="service.id"
                >
                  {{ service.name }}
                </option>
              </select>
            </div>

            <!-- Area -->
           <div class="col-md-3">
  <label>Area</label>
  <v-select
    :options="areas"
    v-model="form.area"
    placeholder="Search Dubai area..."
    :filterable="true"
    :clearable="true"
  />
</div>
  <!-- Vehicle Number -->
<div class="col-md-3 mt-3">
  <label>Vehicle Number</label>
  <input
    v-model="form.vehicle_number"
    type="text"
    class="form-control text-uppercase"
    placeholder="ABC-1234"
    style="letter-spacing:1px;"
  />
  <small class="text-muted">Example: ABC-1234</small>
</div>

            <!-- Charges -->
            <div class="col-md-3 mt-3">
              <label>Charges</label>
              <input
                type="number"
                v-model="form.price"
                class="form-control"
                 :readonly="isNewTyre"
              />
            </div>

            <!-- Mobile -->
            <div class="col-md-3 mt-3">
              <label>Mobile <span class="text-danger">*</span></label>
              <input
                type="text"
                v-model="form.mobile"
                class="form-control"
                required
              />
            </div>

            <!-- Status -->
            <div class="col-md-3 mt-3">
              <label>Status</label>
              <select v-model="form.status" class="form-control">
               <option value="New">New</option>
<option value="In Progress">In Progress</option>

<option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <!-- New Tyre Fields -->
<div v-if="isNewTyre" class="row mt-3">

  <div class="col-md-3">
    <label>Brand</label>
    <input v-model="form.brand" type="text" class="form-control" />
  </div>

  <div class="col-md-3">
    <label>Size</label>
    <input v-model="form.size" type="text" class="form-control" />
  </div>

  <div class="col-md-3">
    <label>Buying Price</label>
    <input v-model="form.buying_price" type="number" class="form-control" />
  </div>

  <div class="col-md-3">
    <label>Selling Price</label>
    <input v-model="form.selling_price" type="number" class="form-control" />
  </div>

  <div class="col-md-3 mt-3">
    <label>Service Charges</label>
    <input v-model="form.service_charges" type="number" class="form-control" />
  </div>

</div>

          </div>

          <!-- Submit -->
          <div class="mt-4">
        <button
  type="submit"
  class="btn btn-primary"
  :disabled="loading"
>
  {{ loading ? 'Saving...' : 'Save Lead' }}
</button>
            <router-link to="/leads" class="btn btn-light ml-2">
              Cancel
            </router-link>
          </div>

        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import vSelect from 'vue-select'
import 'vue-select/dist/vue-select.css'

export default {
  components: { vSelect },

  data () {
    return {
      loading: false,
      serviceTypes: [],
      errors: {},

      form: {
        salutation: '',
        name: '',
        service_type_id: '',
        area: '',
        price: '',
        mobile: '',
        status: 'New',
        vehicle_number: '',

        brand: '',
        size: '',
        buying_price: '',
        selling_price: '',
        service_charges: ''
      },

         areas: [
  "Al Barsha",
  "Al Barsha 1",
  "Al Barsha 2",
  "Al Barsha 3",
  "Al Barsha South",
  "Al Furjan",
  "Al Garhoud",
  "Al Jaddaf",
  "Al Karama",
  "Al Khawaneej",
  "Al Mamzar",
  "Al Mizhar",
  "Al Nahda",
  "Al Quoz",
  "Al Quoz 1",
  "Al Quoz 2",
  "Al Quoz 3",
  "Al Quoz 4",
  "Al Rashidiya",
  "Al Rigga",
  "Al Safa",
  "Al Satwa",
  "Al Sufouh",
  "Al Twar",
  "Arabian Ranches",
  "Arabian Ranches 2",
  "Arabian Ranches 3",
  "Barsha Heights",
  "Bluewaters Island",
  "Business Bay",
  "City Walk",
  "DAMAC Hills",
  "DAMAC Hills 2",
  "Deira",
  "Discovery Gardens",
  "Downtown Dubai",
  "Dubai Creek Harbour",
  "Dubai Festival City",
  "Dubai Hills Estate",
  "Dubai Investment Park",
  "Dubai Marina",
  "Dubai Silicon Oasis",
  "Dubai South",
  "Emirates Hills",
  "Garhoud",
  "Green Community",
  "International City",
  "Jebel Ali",
  "JLT",
  "Jumeirah",
  "Jumeirah 1",
  "Jumeirah 2",
  "Jumeirah 3",
  "Jumeirah Beach Residence",
  "Jumeirah Golf Estates",
  "Jumeirah Islands",
  "Jumeirah Park",
  "Jumeirah Village Circle",
  "Jumeirah Village Triangle",
  "Knowledge Park",
  "Liwan",
  "Majan",
  "Meydan",
  "Motor City",
  "Mirdif",
  "Mudon",
  "Nad Al Hamar",
  "Nad Al Sheba",
  "Palm Jumeirah",
  "Remraam",
  "Silicon Oasis",
  "Sports City",
  "The Greens",
  "The Lakes",
  "The Meadows",
  "The Springs",
  "The Sustainable City",
  "Town Square",
  "Umm Suqeim",
  "Wasl Gate",
  "World Trade Centre"
],
    }
  },

  // ✅ computed
  computed: {
    isNewTyre () {
      const selected = this.serviceTypes.find(
        s => s.id === this.form.service_type_id
      )
      return selected && selected.name.toLowerCase() === 'new tyre'
    }
  },

  // ✅ mounted (INSIDE component)
  mounted () {
    this.fetchServiceTypes()
    this.areas = [...this.areas]
  },

  // ✅ watch (SEPARATE, not inside computed)
  watch: {
    'form.selling_price': 'calculateTotal',
    'form.service_charges': 'calculateTotal',

    'form.service_type_id' () {
      if (!this.isNewTyre) {
        this.form.price = ''
      }
    }
  },

  methods: {
    async fetchServiceTypes () {
      try {
        const res = await axios.get('/api/service-types')
        this.serviceTypes = res.data.data ?? res.data
      } catch (e) {
        this.safeToast('Failed to load service types', 'error')
      }
    },

    calculateTotal () {
      const sell = parseFloat(this.form.selling_price) || 0
      const service = parseFloat(this.form.service_charges) || 0
      this.form.price = sell + service
    },

    async submit () {
      if (this.loading) return
      this.loading = true

      try {
        await axios.post('/api/leads', this.form)
        this.safeToast('Lead created successfully', 'success')

        setTimeout(() => {
          this.$router.push({ name: 'leads.index' })
        }, 400)

      } catch (err) {
        const message =
          err?.response?.data?.message || 'Validation failed'

        this.safeToast(message, 'error')
      } finally {
        this.loading = false
      }
    },

    safeToast (message, type = 'success') {
      this.$nextTick(() => {
        if (this.$toast && typeof this.$toast[type] === 'function') {
          this.$toast[type](message)
        }
      })
    }
  }
}
</script>



