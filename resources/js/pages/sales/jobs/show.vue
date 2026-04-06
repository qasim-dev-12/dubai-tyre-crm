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

<div class="mt-4">
  <h5>Payment History</h5>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Amount</th>
        <th>Method</th>
        <th>Reference</th>
        <th>Notes</th>
        <th>Receipt</th>
      </tr>
    </thead>

    <tbody>
      <tr v-for="payment in job?.payments || []" :key="payment.id">
        <td>{{ payment.amount }}</td>
        <td>{{ payment.payment_method }}</td>
        <td>{{ payment.reference_number || '-' }}</td>
        <td>{{ payment.notes || '-' }}</td>

        <!-- RECEIPT -->
        <td>
          <a
            v-if="payment.receipt"
            :href="`/storage/${payment.receipt}?t=${Date.now()}`"
            target="_blank"
            class="btn btn-sm btn-primary"
          >
            View
          </a>

          <span v-else>-</span>
        </td>
        <td>
  <!-- VIEW -->
  <a
    v-if="payment.receipt"
    :href="`/storage/${payment.receipt}?t=${Date.now()}`"
    target="_blank"
    class="btn btn-sm btn-primary"
  >
    View
  </a>

  <!-- EDIT -->
 <button class="btn btn-sm btn-warning" @click="openEdit(payment)">
  Edit
</button>

  <!-- DELETE -->
  <button
    class="btn btn-sm btn-danger"
    @click="deletePayment(payment.id)"
  >
    Delete
  </button>
</td>
      </tr>

      <tr v-if="!job.payments || job.payments.length === 0">
        <td colspan="5" class="text-center">
          No Payments Found
        </td>
      </tr>
    </tbody>
  </table>
  <div v-if="showEditModal" class="modal-overlay">
  <div class="modal-box">

    <h4>Edit Payment</h4>

    <input v-model="editForm.amount" placeholder="Amount" class="form-control mb-2" />

    <select v-model="editForm.payment_method" class="form-control mb-2">
      <option>Cash</option>
      <option>Bank Transfer</option>
      <option>POS</option>
    </select>

    <input v-model="editForm.reference_number" placeholder="Reference" class="form-control mb-2" />

    <textarea v-model="editForm.notes" placeholder="Notes" class="form-control mb-2"></textarea>

    <input type="file" @change="handleFile" class="form-control mb-2" />

    <div class="d-flex justify-content-between mt-3">
      <button class="btn btn-secondary" @click="showEditModal=false">Cancel</button>
      <button class="btn btn-primary" @click="updatePayment">Update</button>
    </div>

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
      job: null,


    showEditModal: false,
    editForm: {
      id: null,
      amount: '',
      payment_method: '',
      reference_number: '',
      notes: '',
      receipt: null
    }
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
  },
  handleFile(e) {
  this.editForm.receipt = e.target.files[0];
},

  // 🟡 DELETE
  async deletePayment(id) {
    if (!confirm("Delete this payment?")) return;

    await axios.delete(`/api/payments/${id}`);

    // reload job
    this.reloadJob();
  },

  // 🟡 EDIT (simple prompt version)
async updatePayment() {
  const formData = new FormData();

  formData.append('amount', this.editForm.amount);
  formData.append('payment_method', this.editForm.payment_method);
  formData.append('reference_number', this.editForm.reference_number || '');
  formData.append('notes', this.editForm.notes || '');

  if (this.editForm.receipt) {
    formData.append('receipt', this.editForm.receipt);
  }

  await axios.post(`/api/payments/${this.editForm.id}`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  });

  this.showEditModal = false;

  // ✅ ADD DELAY HERE
  setTimeout(() => {
    this.reloadJob();
  }, 500);
},
  openEdit(payment) {
  this.editForm.id = payment.id;
  this.editForm.amount = payment.amount;
  this.editForm.payment_method = payment.payment_method;
  this.editForm.reference_number = payment.reference_number;
  this.editForm.notes = payment.notes;
  this.editForm.receipt = null;

  this.showEditModal = true;
},

  // 🔄 reload data
  async reloadJob() {
    const id = this.$route.params.id;
    const { data } = await axios.get(`/api/jobs/${id}`);
    this.job = data.data;
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
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-box {
  background: white;
  padding: 20px;
  width: 400px;
  border-radius: 10px;
}

</style>
