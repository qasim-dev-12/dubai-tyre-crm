<template>
  <div class="mb-50">
    <div class="row">
      <div class="col-lg-12">
        <div class="card custom-card w-100">

          <!-- Header -->
          <div class="card-header d-flex justify-content-between">
            <h4>Jobs</h4>
            <router-link
              v-if="!isTechnicianUser"
              :to="{ name: 'jobs.create' }"
              class="btn btn-primary btn-sm"
            >
              <i class="fas fa-plus"></i> Create Job
            </router-link>
          </div>

          <!-- Body -->
          <div class="card-body">

            <table-loading v-show="loading" />

            <div class="table-responsive">
              <table class="table jobs-table">
              <thead>
  <tr>
    <th>SL</th>
    <th>Name</th>
    <th>Service</th>
    <th>Area</th>
    <th>Price</th>
    <th>Status</th>
    <th>Payment</th>
    <th class="text-right">Action</th>
  </tr>
</thead>


                <tbody>
                  <tr v-for="(job, i) in safeItems" :key="job.id">

                    <td data-label="SL">
  <span v-if="pagination && pagination.current_page > 1">
    {{
      pagination.per_page * (pagination.current_page - 1) + (i + 1)
    }}
  </span>
  <span v-else>{{ i + 1 }}</span>
</td>
                    <td data-label="Name"><span class="truncate" :title="job.name">{{ job.name }}</span></td>
                    <td data-label="Service"><span class="truncate" :title="job.service_type?.name">{{ job.service_type?.name }}</span></td>
                    <td data-label="Area"><span class="truncate" :title="job.area">{{ job.area }}</span></td>
<td data-label="Price">{{ job.price }}</td>
        <td data-label="Status">
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
</td>

<td data-label="Payment">
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

   <td class="text-right" data-label="Action">
    <div class="btn-group">

     <!-- View -->
<router-link
  :to="{ name: 'jobs.show', params: { id: job.id } }"
  class="btn btn-primary btn-sm"
  data-bs-toggle="tooltip"
  title="View full details"
>
  <i class="fas fa-eye"></i>
</router-link>

<!-- Payment -->
<button
  v-if="!isTechnicianUser"
  class="btn btn-success btn-sm"
  @click="openPaymentModal(job)"
  :disabled="job.payment_status === 'Paid'"
  data-bs-toggle="tooltip"
  title="Add Payment"
>
  <i class="fas fa-money-bill"></i>
</button>

<!-- Delete -->
<button
  v-if="!isTechnicianUser"
  class="btn btn-danger btn-sm"
  @click="deleteJob(job.id)"
  data-bs-toggle="tooltip"
  title="Delete"
>
  <i class="fas fa-trash"></i>
</button>

    </div>
  </td>


                  </tr>

                  <tr v-if="!loading && safeItems.length === 0">
                    <td colspan="8" class="text-center">
                      No Jobs Found
                    </td>
                  </tr>

                </tbody>
              </table>
            </div>

          </div>
          <div class="card-footer">
  <div class="d-flex justify-content-between align-items-center">

    <!-- Per Page -->
    <div>
      <label>Per Page</label>
      <select v-model="perPage" @change="updatePerPage" class="form-control form-control-sm">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </div>

    <!-- Pagination -->
    <pagination
      v-if="pagination && pagination.last_page > 1"
      :pagination="pagination"
      :offset="5"
      @paginate="paginate"
    />
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

export default {
   mounted() {
    console.log("AUTH STATE:", this.$store.state.auth);
    console.log("USER:", this.$store.state.auth.user);
     this.getData(true);
      this.$nextTick(() => {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el))
  })

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
    ...mapGetters("operations", ["items", "loading", "pagination"]),
    safeItems() {
      return this.items || [];
    },
    isTechnicianUser() {
      const user = this.$store.state.auth.user;
      return (
        user &&
        (user.account_role == 0 || (Array.isArray(user.roles) && user.roles.includes("technician"))) &&
        !(Array.isArray(user.roles) && user.roles.includes("super-admin"))
      );
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




   async getData(resetPage = false) {
  const page = resetPage ? 1 : this.pagination?.current_page || 1;

  await this.$store.dispatch("operations/fetchData", {
    path: "/api/jobs?",
    currentPage:
      `page=${page}` +
      `&perPage=${this.perPage}`,
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
,paginate() {
  this.getData();
},

updatePerPage() {
  this.getData(true);
},


  }
};


</script>
<style scoped>
/* Fixed layout so the table always fits the container width, no matter
   how long a name/service/area value is — overflow truncates, it never
   pushes the table wider than the screen. Desktop only; mobile switches
   to stacked cards below (which need full-width blocks instead). */
@media (min-width: 769px) {
  .jobs-table {
    table-layout: fixed;
    width: 100%;
  }

  .jobs-table th:nth-child(1), .jobs-table td:nth-child(1) { width: 6%; }
  .jobs-table th:nth-child(2), .jobs-table td:nth-child(2) { width: 22%; }
  .jobs-table th:nth-child(3), .jobs-table td:nth-child(3) { width: 16%; }
  .jobs-table th:nth-child(4), .jobs-table td:nth-child(4) { width: 14%; }
  .jobs-table th:nth-child(5), .jobs-table td:nth-child(5) { width: 10%; }
  .jobs-table th:nth-child(6), .jobs-table td:nth-child(6) { width: 12%; }
  .jobs-table th:nth-child(7), .jobs-table td:nth-child(7) { width: 10%; }
  .jobs-table th:nth-child(8), .jobs-table td:nth-child(8) { width: 10%; }
}

.jobs-table .truncate {
  display: block;
  white-space: normal;
  overflow-wrap: break-word;
  word-break: break-word;
  max-width: 100%;
}

.jobs-table thead th {
  background: #f8f9fb;
  color: #6b7280;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  font-weight: 700;
  border-bottom: 2px solid #edeff2;
  white-space: nowrap;
}

.jobs-table tbody tr {
  transition: background-color 0.15s ease;
}

.jobs-table tbody tr:hover {
  background-color: #f5f6ff;
}

.jobs-table td {
  vertical-align: middle;
}

.jobs-table .badge {
  font-weight: 600;
  padding: 0.4em 0.7em;
  border-radius: 6px;
}

.jobs-table .btn-group .btn {
  border-radius: 6px !important;
  margin-right: 4px;
}

/* Mobile: stack each row as a card instead of scrolling horizontally */
@media (max-width: 768px) {
  .jobs-table thead {
    display: none;
  }

  .jobs-table,
  .jobs-table tbody,
  .jobs-table tr,
  .jobs-table td {
    display: block;
    width: 100%;
  }

  .jobs-table tr {
    margin-bottom: 14px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  }

  .jobs-table td {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border: none !important;
    text-align: right;
  }

  .jobs-table td::before {
    content: attr(data-label);
    font-weight: 600;
    color: #6b7280;
    text-align: left;
    margin-right: 10px;
    flex-shrink: 0;
  }

  .jobs-table .truncate {
    flex: 1;
    min-width: 0;
  }

  .jobs-table td.text-right {
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 6px;
  }

  .jobs-table td.text-right::before {
    content: none;
  }

  .jobs-table .btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    width: 100%;
  }
}

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
