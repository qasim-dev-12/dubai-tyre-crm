<template>
  <div class="mb-50">
    <div class="card custom-card">
      <div class="card-header d-flex justify-content-between">
        <h4>Job Details</h4>

        <router-link
          :to="{ name: 'jobs.index' }"
          class="btn btn-secondary btn-sm"
        >
          Back
        </router-link>
      </div>

      <div class="card-body" v-if="job">

      <div class="row">

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="info-box">
      <p class="label">Name</p>
      <div class="value">{{ job.name }}</div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="info-box">
      <div class="label">Mobile</div>
      <div class="value">{{ job.mobile }}</div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="info-box">
      <div class="label">Service</div>
      <div class="value">{{ job.service_type?.name }}</div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6   mb-4">
    <div class="info-box">
      <div class="label">Area</div>
      <div class="value">{{ job.area }}</div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="info-box">
      <div class="label">Vehicle</div>
      <div class="value">{{ job.vehicle_number }}</div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="info-box">
      <div class="label">Technician</div>
      <div class="value">{{ job.technician?.name }}</div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="info-box">
      <div class="label">Price</div>
      <div class="value">{{ job.price }}</div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="info-box">
      <div class="label">Status</div>
      <div class="value">
        <span :class="['badge', statusClass(job.status)]">
          {{ job.status }}
        </span>
      </div>
    </div>
  </div>

  <div class="col-12" v-if="job.location_url">
    <div class="info-box">
      <div class="label">Location</div>
      <div class="value">
        <a :href="job.location_url" target="_blank">
          View on Map
        </a>
      </div>
    </div>
  </div>

</div>



      </div>

      <div v-else class="card-body text-center">
        Loading...
      </div>

    </div>
    <h5 class="mt-4">Job Journey</h5>

<div class="timeline">

<div
  class="timeline-item"
  v-for="step in job?.journeys"
  :key="step.id"
>

  <div class="timeline-icon"></div>

  <div class="timeline-content">

    <strong>{{ step.status }}</strong>

    <div class="text-muted">
      {{ step.message }}
    </div>

    <small>
      {{ new Date(step.created_at).toLocaleString() }}
    </small>

  </div>

</div>

</div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      job: null
    };
  },

  async created() {
    const id = this.$route.params.id;

    const { data } = await axios.get(`/api/jobs/${id}`);

    this.job = data.data;
  },

  methods: {
    statusClass(status) {
      const map = {
        Assigned: "bg-primary",
        Started: "bg-info",
        "In Progress": "bg-warning",
        Completed: "bg-success",
        Cancelled: "bg-danger"
      };
      return map[status] || "bg-secondary";
    }
  }
};
</script>
<style scoped>

.timeline {
  position: relative;
  padding-left: 35px;
  margin-top: 20px;
}

.timeline::before {
  content: "";
  position: absolute;
  left: 10px;
  top: 0;
  bottom: 0;
  width: 3px;
  background: #ddd;
}

.timeline-item {
  position: relative;
  margin-bottom: 20px;
}

.timeline-icon {
  position: absolute;
  left: -25px;
  top: 5px;
  width: 18px;
  height: 18px;
  background: rgb(99, 102, 241);
  border-radius: 50%;
}

.timeline-content {
  background: #f8f9fa;
  padding: 10px 15px;
  border-radius: 6px;
  border: 1px solid #eee;
}

.timeline-content strong {
  display: block;
  font-weight: 600;
}

.timeline-content small {
  color: #777;
}

</style>
