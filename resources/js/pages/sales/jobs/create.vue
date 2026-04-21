<template>
  <div class="mb-50">
    <div class="row">
      <div class="col-lg-12">
        <div class="card custom-card">
          <div class="card-header d-flex justify-content-between">
            <h4>Create Job</h4>
            <router-link
              :to="{ name: 'jobs.index' }"
              class="btn btn-secondary btn-sm"
            >
              <i class="fas fa-long-arrow-alt-left"></i> Back
            </router-link>
          </div>

          <div class="card-body">
            <form @submit.prevent="saveJob">
              <!-- Row 1: Name, Service Type, Area -->
              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label">Name <span class="text-danger">*</span></label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.name }"
                    placeholder="Customer Name"
                  />
                  <div v-if="errors.name" class="invalid-feedback d-block">
                    {{ errors.name[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Service Type <span class="text-danger">*</span></label>
                  <v-select
                    v-model="form.service_type"
                    :options="serviceTypes"
                    label="name"
                    :class="{ 'is-invalid': errors.service_type_id }"
                    placeholder="Select Service"
                    @input="onServiceTypeChange"
                  />
                  <div v-if="errors.service_type_id" class="invalid-feedback d-block">
                    {{ errors.service_type_id[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Area <span class="text-danger">*</span></label>
                  <v-select
                    :options="areas"
                    v-model="form.area"
                    placeholder="Search area..."
                    :filterable="true"
                    :clearable="true"
                  />
                </div>
              </div>

              <!-- Row 2: Mobile, Price, Technician -->
              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label">Mobile <span class="text-danger">*</span></label>
                  <input
                    v-model="form.mobile"
                    type="tel"
                    class="form-control"
                    :class="{ 'is-invalid': errors.mobile }"
                    placeholder="Mobile Number"
                  />
                  <div v-if="errors.mobile" class="invalid-feedback d-block">
                    {{ errors.mobile[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Price</label>
                  <input
                    v-model="form.price"
                    type="number"
                    class="form-control"
                    placeholder="Job Price"
                    step="0.01"
                    :readonly="isNewTyre"
                  />
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Technician</label>
                  <v-select
                    v-model="form.technician"
                    :options="technicians"
                    label="name"
                    placeholder="Select Technician"
                  />
                </div>
              </div>

              <!-- Row 3: Vehicle Number, Status -->
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Vehicle Number <span class="text-danger">*</span></label>
                  <input
                    v-model="form.vehicle_number"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.vehicle_number }"
                    placeholder="Vehicle Number"
                  />
                  <div v-if="errors.vehicle_number" class="invalid-feedback d-block">
                    {{ errors.vehicle_number[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Status</label>
                  <select v-model="form.status" class="form-control">
                    <option value="Assigned">Assigned</option>
                    <option value="DCC">DCC</option>
                    <option value="On The Way">On The Way</option>
                    <option value="Reached">Reached</option>
                    <option value="Job Started">Job Started</option>
                    <option value="Job Completed">Job Completed</option>
                  </select>
                </div>
              </div>

              <!-- New Tyre Extra Fields -->
              <div v-if="isNewTyre" class="row mt-3 p-3 bg-light rounded">
                <h6 class="w-100 mb-3">Tyre Details</h6>

                <div class="col-md-3 mb-3">
                  <label class="form-label">Brand</label>
                  <input
                    v-model="form.brand"
                    type="text"
                    class="form-control"
                    placeholder="Tyre Brand"
                  />
                </div>

                <div class="col-md-3 mb-3">
                  <label class="form-label">Size</label>
                  <input
                    v-model="form.size"
                    type="text"
                    class="form-control"
                    placeholder="e.g., 175/65R14"
                  />
                </div>

                <div class="col-md-3 mb-3">
                  <label class="form-label">Buying Price</label>
                  <input
                    v-model="form.buying_price"
                    type="number"
                    class="form-control"
                    placeholder="Cost Price"
                    step="0.01"
                  />
                </div>

                <div class="col-md-3 mb-3">
                  <label class="form-label">Selling Price</label>
                  <input
                    v-model="form.selling_price"
                    type="number"
                    class="form-control"
                    placeholder="Selling Price"
                    step="0.01"
                    @input="calculateTyrePrice"
                  />
                </div>

                <div class="col-md-3 mb-3">
                  <label class="form-label">Service Charges</label>
                  <input
                    v-model="form.service_charges"
                    type="number"
                    class="form-control"
                    placeholder="Service Charges"
                    step="0.01"
                    @input="calculateTyrePrice"
                  />
                </div>
              </div>

              <!-- Actions -->
              <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  {{ loading ? 'Saving...' : 'Create Job' }}
                </button>
                <router-link
                  :to="{ name: 'jobs.index' }"
                  class="btn btn-light ms-2"
                >
                  Cancel
                </router-link>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import vSelect from 'vue-select'

export default {
  components: { vSelect },
  middleware: ['auth'],
  data() {
    return {
      loading: false,
      serviceTypes: [],
      technicians: [],
      errors: {},
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
      form: {
        name: '',
        mobile: '',
        service_type: null,
        area: '',
        vehicle_number: '',
        price: '',
        technician: null,
        status: 'Assigned',
        brand: '',
        size: '',
        buying_price: '',
        selling_price: '',
        service_charges: ''
      }
    }
  },
  computed: {
    isNewTyre() {
      return this.form.service_type && this.form.service_type.name.toLowerCase() === 'new tyre'
    }
  },
  async mounted() {
    await this.fetchServiceTypes()
    await this.fetchTechnicians()
  },
  methods: {
    onServiceTypeChange() {
      if (!this.isNewTyre) {
        // Clear tyre fields if service type is not "New Tyre"
        this.form.brand = ''
        this.form.size = ''
        this.form.buying_price = ''
        this.form.selling_price = ''
        this.form.service_charges = ''
        this.form.price = ''
      }
    },
    calculateTyrePrice() {
      if (this.isNewTyre) {
        const selling = parseFloat(this.form.selling_price) || 0
        const service = parseFloat(this.form.service_charges) || 0
        this.form.price = selling + service
      }
    },
    async fetchServiceTypes() {
      try {
        const { data } = await axios.get('/api/service-types')
        this.serviceTypes = data.data || data
      } catch (error) {
        console.error('Failed to fetch service types', error)
      }
    },
    async fetchTechnicians() {
      try {
        const { data } = await axios.get('/api/all-employees')
        this.technicians = data.data || data
      } catch (error) {
        console.error('Failed to fetch technicians', error)
      }
    },
    async saveJob() {
      this.loading = true
      this.errors = {}

      try {
        const payload = {
          name: this.form.name,
          mobile: this.form.mobile,
          service_type_id: this.form.service_type?.id,
          area: this.form.area,
          vehicle_number: this.form.vehicle_number,
          price: this.form.price || null,
          technician_id: this.form.technician?.id || null,
          status: this.form.status,
          brand: this.form.brand || null,
          size: this.form.size || null,
          buying_price: this.form.buying_price || null,
          selling_price: this.form.selling_price || null,
          service_charges: this.form.service_charges || null
        }

        await axios.post('/api/jobs', payload)

        this.$toast.success('Job created successfully')
        this.$router.push({ name: 'jobs.index' })
      } catch (error) {
        if (error.response?.data?.errors) {
          this.errors = error.response.data.errors
        } else {
          this.$toast.error(error.response?.data?.message || 'Failed to create job')
        }
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.is-invalid {
  border-color: #dc3545 !important;
}

.invalid-feedback {
  color: #dc3545;
  font-size: 0.875em;
  margin-top: 0.25rem;
}
</style>
