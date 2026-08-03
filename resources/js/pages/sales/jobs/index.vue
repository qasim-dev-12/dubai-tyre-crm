<template>
  <div class="mb-50">
    <div class="row">
      <div class="col-lg-12">
        <div class="card custom-card w-100">

          <!-- Header -->
          <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Jobs</h4>
            <router-link
              v-if="!isTechnicianUser"
              :to="{ name: 'jobs.create' }"
              class="btn btn-primary btn-sm ml-auto"
            >
              <i class="fas fa-plus"></i> Create Job
            </router-link>
          </div>

          <!-- Technician: stats + tabs + date filter -->
          <div v-if="isTechnicianUser" class="card-body pb-0">
            <div class="row mb-3">
              <div class="col-6 col-md-3 mb-2">
                <div class="stat-box stat-new"><h5>{{ stats.new }}</h5><small>New</small></div>
              </div>
              <div class="col-6 col-md-3 mb-2">
                <div class="stat-box stat-progress"><h5>{{ stats.in_progress }}</h5><small>Uncompleted</small></div>
              </div>
              <div class="col-6 col-md-3 mb-2">
                <div class="stat-box stat-completed"><h5>{{ stats.completed }}</h5><small>Completed</small></div>
              </div>
              <div class="col-6 col-md-3 mb-2">
                <div class="stat-box stat-total"><h5>{{ stats.total }}</h5><small>Total</small></div>
              </div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 job-filter-bar">
              <ul class="nav nav-pills job-tabs mb-2">
                <li class="nav-item">
                  <a href="#" class="nav-link" :class="{ active: activeTab === 'all' }" @click.prevent="selectTab('all')">All</a>
                </li>
                <li class="nav-item">
                  <a href="#" class="nav-link" :class="{ active: activeTab === 'new' }" @click.prevent="selectTab('new')">New</a>
                </li>
                <li class="nav-item">
                  <a href="#" class="nav-link" :class="{ active: activeTab === 'in_progress' }" @click.prevent="selectTab('in_progress')">Uncompleted</a>
                </li>
                <li class="nav-item">
                  <a href="#" class="nav-link" :class="{ active: activeTab === 'completed' }" @click.prevent="selectTab('completed')">Completed</a>
                </li>
              </ul>
            </div>
          </div>

          <!-- Body -->
          <div class="card-body">

            <div class="d-flex flex-wrap align-items-end job-extra-filters mb-2 job-filter-toolbar">
              <div class="d-flex align-items-center flex-wrap mb-2 mr-2 date-filter-controls">
                <select v-model="dateFilter" @change="onDateFilterChange" class="form-control form-control-sm">
                  <option value="all">All Time</option>
                  <option value="today">Today</option>
                  <option value="week">This Week</option>
                  <option value="month">This Month</option>
                  <option value="custom">Custom Range</option>
                </select>
                <template v-if="dateFilter === 'custom'">
                  <input type="date" v-model="customFrom" class="form-control form-control-sm" />
                  <input type="date" v-model="customTo" class="form-control form-control-sm" />
                  <button class="btn btn-sm btn-primary" @click="applyCustomRange" :disabled="!customFrom || !customTo">Apply</button>
                </template>
              </div>

              <div class="mb-2 mr-2">
                <select v-model="filterField" class="form-control form-control-sm" @change="onFilterFieldChange">
                  <option value="">Filter By...</option>
                  <option value="name">Name</option>
                  <option value="service_type_id">Service Type</option>
                  <option value="area">Area</option>
                  <option value="status">Status</option>
                  <option value="payment_status">Payment Status</option>
                  <option value="price">Price Range</option>
                  <option value="warranty">Warranty Claims</option>
                </select>
              </div>

              <div v-if="filterField === 'service_type_id'" class="mb-2 mr-2">
                <select v-model="filterValue" class="form-control form-control-sm" @change="onFilterChange">
                  <option value="">All Service Types</option>
                  <option v-for="st in serviceTypes" :key="st.id" :value="st.id">{{ st.name }}</option>
                </select>
              </div>

              <div v-else-if="filterField === 'name' || filterField === 'area'" class="mb-2 mr-2">
                <input
                  v-model="filterValue"
                  type="text"
                  class="form-control form-control-sm"
                  :placeholder="filterField === 'name' ? 'Filter by name' : 'Filter by area'"
                  @input="onFilterInput"
                >
              </div>

              <div v-else-if="filterField === 'status'" class="mb-2 mr-2">
                <select v-model="filterValue" class="form-control form-control-sm" @change="onFilterChange">
                  <option value="">All Statuses</option>
                  <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>

              <div v-else-if="filterField === 'payment_status'" class="mb-2 mr-2">
                <select v-model="filterValue" class="form-control form-control-sm" @change="onFilterChange">
                  <option value="">All Payments</option>
                  <option value="Paid">Paid</option>
                  <option value="Partial">Partial</option>
                  <option value="Unpaid">Unpaid</option>
                </select>
              </div>

              <div v-else-if="filterField === 'price'" class="mb-2 mr-2 d-flex price-range-filter">
                <input v-model="priceMin" type="number" min="0" class="form-control form-control-sm mr-1" placeholder="Min" @input="onFilterInput">
                <input v-model="priceMax" type="number" min="0" class="form-control form-control-sm" placeholder="Max" @input="onFilterInput">
              </div>

              <div v-else-if="filterField === 'warranty'" class="mb-2 mr-2">
                <select v-model="filterValue" class="form-control form-control-sm" @change="onFilterChange">
                  <option value="">All Warranty Claims</option>
                  <option value="battery">Battery</option>
                  <option value="tyre_repair">Tyre Repair</option>
                </select>
              </div>

              <span class="badge jobs-count-badge mb-2">
                {{ pagination ? pagination.total : 0 }} Job{{ pagination && pagination.total === 1 ? '' : 's' }}
              </span>
            </div>

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
                    <td data-label="Service">
                      <span class="truncate" :title="job.service_type?.name">{{ job.service_type?.name }}</span>
                      <small v-if="warrantyRefText(job)" class="d-block text-muted warranty-ref">{{ warrantyRefText(job) }}</small>
                    </td>
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

<!-- Edit -->
<router-link
  v-if="!isTechnicianUser"
  :to="{ name: 'jobs.edit', params: { id: job.id } }"
  class="btn btn-warning btn-sm"
  data-bs-toggle="tooltip"
  title="Edit Job"
>
  <i class="fas fa-pen"></i>
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
     this.fetchServiceTypes();
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
      filterField: '',
      filterValue: '',
      priceMin: '',
      priceMax: '',
      statusOptions: ['Assigned', 'DCC', 'On The Way', 'Reached', 'Job Started', 'Job Completed'],
      serviceTypes: [],
      filterDebounceTimer: null,
      activeTab: 'all',
      dateFilter: 'all',
      customFrom: '',
      customTo: '',
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
    stats() {
      const items = this.$store.state.operations.items;
      return (items && items.stats) || { new: 0, in_progress: 0, completed: 0, total: 0 };
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

  let query = `page=${page}&perPage=${this.perPage}`;

  if (this.filterField === 'warranty') {
    query += `&warranty=1`;
    if (this.filterValue) {
      query += `&warranty_type=${this.filterValue}`;
    }
  } else if (this.filterField === 'name' && this.filterValue) {
    query += `&name=${encodeURIComponent(this.filterValue)}`;
  } else if (this.filterField === 'service_type_id' && this.filterValue) {
    query += `&service_type_id=${this.filterValue}`;
  } else if (this.filterField === 'area' && this.filterValue) {
    query += `&area=${encodeURIComponent(this.filterValue)}`;
  } else if (this.filterField === 'status' && this.filterValue) {
    query += `&status=${encodeURIComponent(this.filterValue)}`;
  } else if (this.filterField === 'payment_status' && this.filterValue) {
    query += `&payment_status=${encodeURIComponent(this.filterValue)}`;
  } else if (this.filterField === 'price') {
    if (this.priceMin) query += `&price_min=${this.priceMin}`;
    if (this.priceMax) query += `&price_max=${this.priceMax}`;
  }

  if (this.isTechnicianUser) {
    query += `&tab=${this.activeTab}`;
  }
  query += `&date_filter=${this.dateFilter}`;
  if (this.dateFilter === 'custom' && this.customFrom && this.customTo) {
    query += `&date_from=${this.customFrom}&date_to=${this.customTo}`;
  }

  await this.$store.dispatch("operations/fetchData", {
    path: "/api/jobs?",
    currentPage: query,
  });
},

selectTab(tab) {
  this.activeTab = tab;
  this.getData(true);
},

onFilterFieldChange() {
  this.filterValue = '';
  this.priceMin = '';
  this.priceMax = '';
  this.getData(true);
},

async fetchServiceTypes() {
  try {
    const { data } = await axios.get('/api/service-types');
    this.serviceTypes = data.data || data;
  } catch (error) {
    // silently ignore — filter dropdown just stays empty
  }
},

onFilterChange() {
  this.getData(true);
},

onFilterInput() {
  clearTimeout(this.filterDebounceTimer);
  this.filterDebounceTimer = setTimeout(() => {
    this.getData(true);
  }, 400);
},

onDateFilterChange() {
  if (this.dateFilter !== 'custom') {
    this.getData(true);
  }
},

applyCustomRange() {
  if (this.customFrom && this.customTo) {
    this.getData(true);
  }
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
warrantyRefText(job) {
  if (job.warranty_claim_source_payment?.job) {
    return `from Job #${job.warranty_claim_source_payment.job.id}`;
  }
  const claimedPayment = (job.payments || []).find(p => p.replacement_job);
  if (claimedPayment) {
    return `→ Replaced by Job #${claimedPayment.replacement_job.id}`;
  }
  return '';
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

  .jobs-table th:nth-child(1), .jobs-table td:nth-child(1) { width: 5%; }
  .jobs-table th:nth-child(2), .jobs-table td:nth-child(2) { width: 17%; }
  .jobs-table th:nth-child(3), .jobs-table td:nth-child(3) { width: 14%; }
  .jobs-table th:nth-child(4), .jobs-table td:nth-child(4) { width: 12%; }
  .jobs-table th:nth-child(5), .jobs-table td:nth-child(5) { width: 8%; }
  .jobs-table th:nth-child(6), .jobs-table td:nth-child(6) { width: 11%; }
  .jobs-table th:nth-child(7), .jobs-table td:nth-child(7) { width: 9%; }
  .jobs-table th:nth-child(8), .jobs-table td:nth-child(8) { width: 24%; }
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
  padding: 0.25rem 0.4rem;
  margin-right: 2px;
  transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.jobs-table .btn-group .btn:last-child {
  margin-right: 0;
}

.jobs-table .btn-group .btn:not(:disabled):hover {
  transform: translateY(-2px) scale(1.08);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
}

.jobs-table .btn-group .btn:not(:disabled):active {
  transform: translateY(0) scale(0.96);
}

.jobs-table .btn-group .btn i {
  transition: transform 0.15s ease;
}

.jobs-table .btn-group .btn:not(:disabled):hover i {
  transform: scale(1.1);
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

.card.custom-card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(20, 20, 43, 0.06);
}

.stat-box {
  border-radius: 8px;
  padding: 12px 14px;
  text-align: center;
  background: #f8f9fb;
  border: 1px solid #edeff2;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.stat-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}

.stat-box h5 {
  margin: 0;
  font-weight: 700;
}

.stat-box small {
  color: #6b7280;
  text-transform: uppercase;
  font-size: 0.7rem;
  letter-spacing: 0.03em;
}

.stat-new { border-top: 3px solid #6c757d; }
.stat-progress { border-top: 3px solid #ffc107; }
.stat-completed { border-top: 3px solid #28a745; }
.stat-total { border-top: 3px solid #0d6efd; }

.job-tabs .nav-link {
  cursor: pointer;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.85rem;
  color: #495057;
  margin-right: 6px;
  background: #f1f2f6;
}

.job-tabs .nav-link.active {
  background: #0d6efd;
  color: #fff;
}

.date-filter-controls select,
.date-filter-controls input {
  margin-right: 8px;
  width: auto;
}

.job-filter-toolbar {
  gap: 10px;
  background: #f8f9fc;
  border: 1px solid #e9ecf3;
  border-radius: 10px;
  padding: 12px 14px;
}

.job-extra-filters select,
.job-extra-filters input[type="text"],
.job-extra-filters input[type="number"] {
  width: auto;
  min-width: 160px;
  border-radius: 6px;
}

.price-range-filter input[type="number"] {
  min-width: 90px;
  width: 90px;
}

.job-extra-filters select:focus,
.job-extra-filters input:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.15);
}

.jobs-count-badge {
  margin-left: auto;
  background: #0d6efd;
  color: #fff;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 20px;
}

/* Narrow screens: min-width:160px per control (plus the nested date-range
   controls) can add up to more than the viewport, pushing the whole page
   wider than the screen. Stack everything full-width instead of wrapping
   fixed-width items. */
@media (max-width: 768px) {
  .job-filter-toolbar,
  .date-filter-controls {
    flex-direction: column;
    align-items: stretch;
  }

  .job-extra-filters select,
  .job-extra-filters input[type="text"],
  .job-extra-filters input[type="number"],
  .date-filter-controls input[type="date"] {
    width: 100%;
    min-width: 0;
  }

  .price-range-filter {
    flex-direction: column;
  }

  .price-range-filter input[type="number"] {
    width: 100%;
  }

  .jobs-count-badge {
    margin-left: 0;
  }
}
</style>
