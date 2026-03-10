<template>
  <div>
    <!-- Page Header -->
    <div class="page-header">
      <h4>Convert Lead To Job</h4>
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
              <input
                type="text"
                v-model="form.area"
                class="form-control"
              />
            </div>

            <!-- Vehicle -->
            <div class="col-md-6 mt-3">
              <label>Vehicle Number</label>
              <input
                v-model="form.vehicle_number"
                type="text"
                class="form-control"
              />
            </div>

            <!-- Price -->
            <div class="col-md-3 mt-3">
              <label>Price</label>
              <input
                type="number"
                v-model="form.price"
                class="form-control"
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

            <!-- Technician -->
            <div class="col-md-6 mt-3">
              <label>Assign Technician <span class="text-danger">*</span></label>
              <select
                v-model="form.technician_id"
                class="form-control"
                required
              >
                <option value="">Select Technician</option>
                <option
                  v-for="tech in technicians"
                  :key="tech.id"
                  :value="tech.id"
                >
                  {{ tech.name }}
                </option>
              </select>
            </div>
            <!-- Job Status -->
<!-- <div class="col-md-3 mt-3">
  <label>Status <span class="text-danger">*</span></label>
  <select v-model="form.status" class="form-control" required>
    <option value="">Select Status</option>
    <option value="Assigned">Assigned</option>
    <option value="Started">Started</option>
    <option value="In Progress">In Progress</option>
    <option value="Completed">Completed</option>
    <option value="Cancelled">Cancelled</option>
  </select>
</div> -->


            <!-- Embedded Map -->
            <div class="col-md-12 mt-4">
              <label>Location Preview (Dubai)</label>
              <iframe
                width="100%"
                height="350"
                style="border:1px solid #ddd; border-radius:8px;"
                loading="lazy"
                allowfullscreen
                src="https://www.google.com/maps?q=Dubai&output=embed">
              </iframe>
            </div>

            <!-- Google Maps Link Input -->
            <div class="col-md-12 mt-3">
              <label>Google Maps Location Link</label>
              <input
                v-model="form.location_url"
                type="text"
                class="form-control"
                placeholder="Paste Google Maps link here"
              />
            </div>

          </div>

          <!-- Submit -->
          <div class="mt-4">
            <button
              type="submit"
              class="btn btn-success"
              :disabled="loading"
            >
              {{ loading ? 'Converting...' : 'Convert To Job' }}
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

export default {
  data () {
    return {
      loading: false,
      serviceTypes: [],
      technicians: [],
      form: {}
    }
  },

  async mounted () {
    await this.fetchLead()
    await this.fetchServiceTypes()
    await this.fetchTechnicians()
  },

  methods: {

    async fetchLead () {
      try {
        const res = await axios.get(`/api/leads/${this.$route.params.slug}`)
        const lead = res.data.data

        this.form = {
          salutation: lead.salutation,
          name: lead.name,
          service_type_id: lead.service_type_id,
          area: lead.area,
          price: lead.price,
          mobile: lead.mobile,
          vehicle_number: lead.vehicle_number,
          technician_id: '',
          location_url: '',
           status: 'Assigned' // default
        }
      } catch (e) {
        this.$toast.error('Failed to load lead')
      }
    },

    async fetchServiceTypes () {
      try {
        const res = await axios.get('/api/service-types')
        this.serviceTypes = res.data.data ?? res.data
      } catch (e) {
        this.$toast.error('Failed to load service types')
      }
    },

  async fetchTechnicians () {
  try {
    const res = await axios.get('/api/technicians')
    this.technicians = res.data
  } catch (e) {
    this.$toast.error('Failed to load technicians')
  }
},

async submit() {
  try {

    const res = await axios.post(
      `/api/leads/${this.$route.params.slug}/convert`,
      this.form
    );

    // Always show main success
    this.$toast.success(res.data.message || "Lead converted successfully");

    // 🔥 CLIENT LOGIC
    if (res.data.client_status === 'created') {

      // 🟢 Client created
      this.$toast.success(res.data.client_message);

    } else {

      // 🔵 Client already exists → show client details
      const client = res.data.client;

      this.$toast.info(
        `Client already exists | Name: ${client.name} | Phone: ${client.phone} | Client ID: ${client.client_id}`
      );
    }

    // Redirect after small delay
    setTimeout(() => {
      this.$router.push({ name: "jobs.index" });
    }, 1500);

  } catch (error) {

    this.$toast.error(
      error?.response?.data?.message || "Conversion failed"
    );

  }
}

  }
}
</script>
