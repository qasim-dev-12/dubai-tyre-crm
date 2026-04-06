<template>
  <div class="mb-50">
    <div class="row">
      <div class="col-lg-12">
        <div class="card custom-card w-100">

          <!-- Header -->
          <div class="card-header d-flex justify-content-between">
            <h4>Jobs</h4>
          </div>

          <!-- Body -->
          <div class="card-body">

            <table-loading v-show="loading" />

            <div class="table-responsive">
              <table class="table">
              <thead>
  <tr>
    <th>SL</th>
    <th>Name</th>
    <th>Service</th>
    <th>Area</th>
    <th>Price</th>
    <th>Mobile</th>
    <th>Vehicle</th>
    <th>Technician</th>
    <th>ETA</th>
    <th>Location</th>
    <th>Status</th>
    <th>Payment</th>
      <th>Payment Method</th>
    <th>Updated Eta</th>         <!-- Show current -->
    <th>Update Status</th>
     <!-- Dropdown -->
    <th class="text-right">Action</th>
  </tr>
</thead>


                <tbody>
                  <tr v-for="(job, i) in safeItems" :key="job.id">

                    <td>{{ i + 1 }}</td>
                    <td>{{ job.name }}</td>
                    <td>{{ job.service_type?.name }}</td>
                    <td>{{ job.area }}</td>
<td>{{ job.price }}</td>
                    <td>{{ job.mobile }}</td>
                    <td>{{ job.vehicle_number }}</td>
                    <td>{{ job.technician?.name }}</td>
                    <td>
  <div v-if="job.eta_minutes && job.eta_started_at">
    <strong>{{ job.eta_time }}</strong>
    <small>({{ remainingMinutes(job) }} min)</small>
  </div>
  <div v-else>
    -
  </div>
</td>
                    <td>{{ job.location_text }}</td>
        <td>
  <span
    class="badge"
    :class="{
      'bg-secondary': job.status === 'DCC',
      'bg-primary': job.status === 'On The Way',
      'bg-info': job.status === 'Reached',
      'bg-warning': job.status === 'Job Started',
      'bg-success': job.status === 'Job Completed'
    }"
  >
    {{ job.status }}
  </span>

  <!-- Payment Badge -->

</td>

<td>
    <span
    v-if="job.payment_status === 'Partial'"
    class="badge bg-warning ml-1"
  >
    Partial
  </span>

  <span
    v-if="job.payment_status === 'Unpaid'"
    class="badge bg-danger ml-1"
  >
    Unpaid
  </span>

  <span
    v-if="job.payment_status === 'Paid'"
    class="badge bg-success ml-1"
  >
    Paid
  </span>
</td>


<td>
  <span v-if="job?.payment?.payment_method">
    {{ job?.payment?.payment_method }}
  </span>

  <span v-else class="text-muted">
    -
  </span>
</td>

<!-- Technician: Can Edit -->
<td>
  <!-- Technician: Can Edit -->
  <div v-if="isAssignedTechnician(job)">
 <input
  type="number"
  v-model="job.eta_minutes"
  @input="debouncedUpdateEta(job)"
  class="form-control form-control-sm"
  placeholder="Minutes"
/>
  </div>

  <!-- Admin & Others: View Only -->
  <div v-else>
    <span v-if="job.eta_time">
      {{ job.eta_time }}
    </span>
    <span v-else>-</span>
  </div>
</td>
                     <!-- ✅ DROPDOWN TO UPDATE STATUS -->

   <td>
  <button
    v-if="getNextStatus(job)"
    class="btn btn-sm btn-primary"
    @click="updateStatusDirect(job, getNextStatus(job))"
  >
    {{ getNextStatus(job) }}
  </button>

  <button
    v-if="getPreviousStatus(job) && job.payment_status !== 'Paid'"
    class="btn btn-sm btn-warning ml-1"
    @click="updateStatusDirect(job, getPreviousStatus(job))"
  >
    ←
  </button>
</td>

   <td class="text-right">
    <div class="btn-group">

      <!-- View -->
      <router-link
        :to="{ name: 'jobs.show', params: { id: job.id } }"
        class="btn btn-primary btn-sm"
      >
        <i class="fas fa-eye"></i>
      </router-link>

      <!-- Edit -->
      <router-link
        :to="{ name: 'jobs.edit', params: { id: job.id } }"
        class="btn btn-info btn-sm"
      >
        <i class="fas fa-edit"></i>
      </router-link>
<button
  class="btn btn-success btn-sm"
  @click="openPaymentModal(job)"
  :disabled="job.payment_status === 'Paid'"
>
  <i class="fas fa-money-bill"></i>
</button>
      <!-- Delete -->
      <button
        class="btn btn-danger btn-sm"
        @click="deleteJob(job.id)"
      >
        <i class="fas fa-trash"></i>
      </button>

    </div>
  </td>
  <td>
  {{ job.technician_id }} -
  {{ $store.state.auth.employeeId }}
</td>
<td>
  {{ isAssignedTechnician(job) }}
</td>


                  </tr>

                  <tr v-if="!loading && safeItems.length === 0">
                    <td colspan="7" class="text-center">
                      No Jobs Found
                    </td>
                  </tr>

                </tbody>
              </table>
            </div>

          </div>

        </div>
      </div>
    </div>
    <!-- Payment Modal -->
<div v-if="showPaymentModal" class="payment-modal">
  <div class="modal-content">
    <h5>Add Payment</h5>

   <div class="row mb-3">
  <div class="col">
    <small>Total</small>
    <h6>{{ selectedJob.price }}</h6>
  </div>
  <div class="col">
    <small>Paid</small>
    <h6>{{ selectedJob.paid_amount }}</h6>
  </div>
  <div class="col">
    <small>Due</small>
    <h6 class="text-danger">{{ selectedJob.due_amount }}</h6>
  </div>
</div>

    <input v-model="paymentForm.amount" type="number" class="form-control mb-2" placeholder="Amount">

    <select v-model="paymentForm.payment_method" class="form-control mb-2">
      <option value="">Select Payment Mode</option>
      <option>Cash</option>
      <option>Bank Transfer</option>
      <option>POS</option>
      <option>POL</option>
    </select>

    <input v-model="paymentForm.reference_number" class="form-control mb-2" placeholder="Reference (optional)">
    <textarea v-model="paymentForm.notes" class="form-control mb-2" placeholder="Notes"></textarea>
    <input type="file" @change="handleFileUpload" class="form-control mb-2">

   <div class="modal-actions">
  <button class="btn btn-secondary" @click="closePaymentModal">
    Cancel
  </button>

  <button class="btn btn-primary" @click="submitPayment">
    Submit
  </button>
</div>
  </div>
</div>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import axios from "axios";
const STATUS_FLOW = [
  "Assigned",
  "DCC",
  "On The Way",
  "Reached",
  "Job Started",
  "Job Completed"
];

export default {
   mounted() {
    console.log("AUTH STATE:", this.$store.state.auth);
    console.log("USER:", this.$store.state.auth.user);
     this.getData();

 setInterval(() => {
    this.$forceUpdate();
  }, 1000);
  },

  data() {


    return {
      perPage: 10,
        debounceTimer: null,
      showPaymentModal: false,
      selectedJob: null,
      paymentForm: {
        amount: '',
        payment_method: '',
        reference_number: '',
        notes: '',
         receipt: null
      }
    };
  },

  computed: {
    ...mapGetters("operations", ["items", "loading"]),
    safeItems() {
    return this.items || [];
  }
  },

  beforeRouteEnter(to, from, next) {
  next(vm => {
    vm.getData(true);
  });
},


  methods: {
    handleFileUpload(event) {
  this.paymentForm.receipt = event.target.files[0];
},


openPaymentModal(job) {
  this.selectedJob = job;
  this.showPaymentModal = true;
},

closePaymentModal() {
  this.showPaymentModal = false;
  this.paymentForm = {
    amount: '',
    payment_method: '',
    reference_number: '',
    notes: ''
  };
},

async submitPayment() {
  try {
    const formData = new FormData();

    formData.append("amount", this.paymentForm.amount);
    formData.append("payment_method", this.paymentForm.payment_method);
    formData.append("reference_number", this.paymentForm.reference_number);
    formData.append("notes", this.paymentForm.notes);

    if (this.paymentForm.receipt) {
      formData.append("receipt", this.paymentForm.receipt);
    }

    await axios.post(
      `/api/jobs/${this.selectedJob.id}/payments`,
      formData,
      {
        headers: {
          "Content-Type": "multipart/form-data"
        }
      }
    );

    this.$toast.success("Payment added");
    this.closePaymentModal();
    this.getData();

  } catch (error) {
    this.$toast.error(error.response?.data?.message || "Payment failed");
  }
},




    async getData() {
      await this.$store.dispatch("operations/fetchData", {
        path: "/api/jobs?",
        currentPage: `perPage=${this.perPage}`,
      });
    },

async deleteJob(id) {
  if (!confirm("Delete this job?")) return;

  try {
    await axios.delete(`/api/jobs/${id}`);
    this.getData();
    this.$toast.success("Job deleted");
  } catch (error) {
    this.$toast.error("Failed to delete job");
  }
},

getNextStatus(job) {
  const index = STATUS_FLOW.indexOf(job.status);
  return STATUS_FLOW[index + 1] || null;
},

getPreviousStatus(job) {
  const index = STATUS_FLOW.indexOf(job.status);
  return STATUS_FLOW[index - 1] || null;
},

async updateStatusDirect(job, newStatus) {
  try {
    await axios.put(`/api/jobs/${job.id}/status`, {
      status: newStatus
    });

    job.status = newStatus;
    this.$toast.success("Status updated");

  } catch (error) {
    this.$toast.error(error.response?.data?.message || "Failed");
  }
},
timeAgo(date) {
  if (!date) return '';

  const seconds = Math.floor((new Date() - new Date(date)) / 1000);

  const intervals = {
    year: 31536000,
    month: 2592000,
    day: 86400,
    hour: 3600,
    minute: 60
  };

  for (let key in intervals) {
    const interval = Math.floor(seconds / intervals[key]);
    if (interval >= 1) {
      return interval + " " + key + (interval > 1 ? "s" : "") + " ago";
    }
  }

  return "just now";
},
isAssignedTechnician(job) {
  const user = this.$store.state.auth.user

  if (!user?.employee) return false

  // Admin can see all
  // if (user.roles.includes('super-admin')) return true

  // Technician logic
  return user.employee &&
         Number(job.technician_id) === Number(user.employee.id)
},

async updateEta(job) {

  try {

    const res = await axios.post(`/api/jobs/${job.id}/update-eta`, {
      eta_minutes: job.eta_minutes
    });

    // update values returned from backend
    job.eta_time = res.data.data.eta_time;
    job.eta_minutes = res.data.data.eta_minutes;
    job.eta_started_at = res.data.data.eta_started_at;

    this.$toast.success("ETA updated");

  } catch (error) {

    this.$toast.error(error.response?.data?.message || "Failed");

  }

},
remainingMinutes(job) {

  if (!job.eta_started_at || !job.eta_minutes) return "-";

  const start = new Date(job.eta_started_at);
  const now = new Date();

  const diff = Math.floor((now.getTime() - start.getTime()) / 60000);

  const remaining = job.eta_minutes - diff;

  if (remaining <= 0) return "Arrived";

  return remaining;
},
debouncedUpdateEta(job) {

  clearTimeout(this.debounceTimer);

  this.debounceTimer = setTimeout(() => {

    if (job._lastEta === job.eta_minutes) return;

    job._lastEta = job.eta_minutes;

    this.updateEta(job);

  }, 600);

}



  }
};


</script>
<style scoped>
.payment-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.45); /* dark overlay */

  display: flex;
  align-items: center;
  justify-content: center;

  z-index: 9999;
}

.modal-content {
  background: white;
  padding: 25px;
  border-radius: 8px;
  width: 420px;
  max-width: 95%;
  box-shadow: 0 10px 30px rgba(0,0,0,0.25);
}

.modal-content h5 {
  margin-bottom: 15px;
  font-weight: 600;
}

.modal-content input,
.modal-content select,
.modal-content textarea {
  margin-bottom: 10px;
}

.modal-content button {
  width: 100%;
  margin-top: 5px;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 15px;
}

.modal-actions button {
  min-width: 100px;
}
</style>
