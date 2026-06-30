<template>
  <div class="mb-50">
    <div class="card custom-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Job Details</h4>
        <router-link :to="{ name: 'jobs.index' }" class="btn btn-secondary btn-sm">
          Back
        </router-link>
      </div>

      <div class="card-body" v-if="job">

        <!-- Job Info Grid -->
        <div class="excel-style">
          <div class="row mb-2">
            <div class="col-md-2 label">Name</div>
            <div class="col-md-4 value">{{ job.name }}</div>
            <div class="col-md-2 label">Area</div>
            <div class="col-md-4 value">{{ job.area }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-md-2 label">Mobile</div>
            <div class="col-md-4 value">{{ job.mobile }}</div>
            <div class="col-md-2 label">Vehicle</div>
            <div class="col-md-4 value">{{ job.vehicle_number }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-md-2 label">Service Type</div>
            <div class="col-md-4 value">{{ job.service_type?.name }}</div>
            <div class="col-md-2 label">Technician</div>
            <div class="col-md-4 value">{{ job.technician?.name }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-md-2 label">Price</div>
            <div class="col-md-4 value">{{ job.price }}</div>
            <div class="col-md-2 label">Status</div>
            <div class="col-md-4 value">
              <span :class="['badge', statusClass(job.status)]">{{ job.status }}</span>
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-md-2 label">Payment</div>
            <div class="col-md-4 value">
              <span class="badge" :class="paymentBadge(job.payment_status)">
                {{ job.payment_status }}
              </span>
              &nbsp;Paid: {{ job.paid_amount }} | Due: {{ job.due_amount }}
            </div>
            <div class="col-md-2 label">Location</div>
            <div class="col-md-4 value">
              <a v-if="job.location_url" :href="job.location_url" target="_blank">View on Map</a>
              <span v-else>-</span>
            </div>
          </div>
        </div>

        <!-- ETA Update (assigned technician only) -->
        <div v-if="isAssignedTechnician" class="mt-3 p-3 bg-light rounded">
          <h6 class="mb-2">Update ETA</h6>
          <div class="d-flex align-items-center" style="gap:10px">
            <input
              type="number"
              v-model="etaMinutes"
              class="form-control form-control-sm"
              style="width:130px"
              placeholder="Minutes"
            />
            <button class="btn btn-sm btn-info" @click="submitEta" :disabled="savingEta">
              <i class="fas fa-clock"></i> {{ savingEta ? 'Saving...' : 'Set ETA' }}
            </button>
            <span v-if="job.eta_time" class="text-muted small">
              Current ETA: <strong>{{ job.eta_time }}</strong>
            </span>
          </div>
        </div>

        <!-- Status Controls (assigned technician only) -->
        <div v-if="isAssignedTechnician && job.status !== 'Job Completed'" class="mt-3 p-3 bg-light rounded">
          <h6 class="mb-2">Change Status</h6>
          <div class="d-flex" style="gap:8px">
            <button
              v-if="prevStatus"
              class="btn btn-sm btn-warning"
              :disabled="job.payment_status === 'Paid'"
              @click="changeStatus(prevStatus)"
            >
              ← {{ prevStatus }}
            </button>
            <button
              v-if="nextStatus"
              class="btn btn-sm btn-primary"
              @click="handleNextStatus"
            >
              {{ nextStatus }} →
            </button>
          </div>
        </div>

        <!-- Pay Remaining (after job completed, payment still partial/unpaid) -->
        <div
          v-if="isAssignedTechnician && job.status === 'Job Completed' && job.payment_status !== 'Paid'"
          class="mt-3 p-3 rounded"
          style="background:#fff3cd; border:1px solid #ffc107"
        >
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Payment Due</strong>
              <span class="ml-2 text-dark">Remaining: <strong>{{ job.due_amount }}</strong></span>
            </div>
            <button class="btn btn-sm btn-success" @click="openPayRemaining">
              <i class="fas fa-money-bill-wave mr-1"></i> Pay Remaining
            </button>
          </div>
        </div>

      </div>
      <div v-else class="card-body text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
      </div>
    </div>

    <!-- Job Journey Timeline -->
    <h5 class="mt-4">Job Journey</h5>
    <div class="timeline">
      <div class="timeline-item" v-for="step in job?.journeys" :key="step.id">
        <div class="timeline-icon"></div>
        <div class="timeline-content">
          <strong>{{ step.status }}</strong>
          <div class="text-muted">{{ step.message }}</div>
          <small>{{ new Date(step.created_at).toLocaleString() }}</small>
        </div>
      </div>
    </div>

    <!-- Payment History -->
    <div class="mt-4">
      <h5>Payment History</h5>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Amount</th>
            <th>Method</th>
            <th>Reference</th>
            <th>Notes</th>
            <th>Battery Info</th>
            <th>Receipt</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="payment in job?.payments || []" :key="payment.id">
            <td>{{ payment.amount }}</td>
            <td>{{ payment.payment_method }}</td>
            <td>{{ payment.reference_number || '-' }}</td>
            <td>{{ payment.notes || '-' }}</td>
            <td>
              <span v-if="payment.battery_details">
                <strong v-if="payment.battery_details.battery_name">{{ payment.battery_details.battery_name }}</strong>
                <span v-if="payment.battery_details.battery_brand"> ({{ payment.battery_details.battery_brand }})</span>
                <br v-if="payment.battery_details.battery_name || payment.battery_details.battery_brand" />
                {{ payment.battery_details.battery_type || '-' }} |
                {{ payment.battery_details.voltage ? payment.battery_details.voltage + 'V' : '-' }} |
                {{ payment.battery_details.capacity ? payment.battery_details.capacity + 'Ah' : '-' }} |
                {{ payment.battery_details.warranty ? payment.battery_details.warranty + ' mo' : '-' }}
              </span>
              <span v-else>-</span>
            </td>
            <td>
              <a v-if="payment.receipt" :href="`/storage/${payment.receipt}`" target="_blank" class="btn btn-sm btn-primary">View</a>
              <span v-else>-</span>
            </td>
            <td>
              <button class="btn btn-sm btn-warning mr-1" @click="openEdit(payment)">Edit</button>
              <button class="btn btn-sm btn-danger" @click="deletePayment(payment.id)">Delete</button>
            </td>
          </tr>
          <tr v-if="!job?.payments || job.payments.length === 0">
            <td colspan="7" class="text-center">No Payments Found</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Battery Completion Popup -->
    <div v-if="showBatteryModal" class="modal-overlay" @click.self="closeBatteryModal">
      <div class="modal-box" style="width:540px">
        <h5 class="mb-3"><i class="fas fa-battery-full mr-2"></i>Complete Battery Job</h5>
        <p class="text-muted small mb-3">Select the battery used, fill payment details, and submit to complete.</p>

        <!-- Battery Stock Dropdown -->
        <div class="mb-3">
          <label class="small font-weight-bold">Select Battery from Your Stock <span class="text-danger">*</span></label>
          <select v-model="batteryForm.selected_stock_id" @change="onBatterySelect" class="form-control form-control-sm" :class="{ 'is-invalid': formErrors.selected_stock_id }">
            <option value="">-- Select Battery --</option>
            <option v-for="b in technicianBatteries" :key="b.id" :value="b.id">
              {{ b.product_name }}{{ b.brand_name ? ' — ' + b.brand_name : '' }}
              ({{ b.battery_type || '-' }}, {{ b.voltage ? b.voltage + 'V' : '-' }}, {{ b.capacity ? b.capacity + 'Ah' : '-' }})
              &nbsp;[Qty: {{ b.quantity }}]
            </option>
          </select>
          <div v-if="formErrors.selected_stock_id" class="invalid-feedback d-block">{{ formErrors.selected_stock_id }}</div>
          <small v-if="technicianBatteries.length === 0" class="text-danger">No batteries assigned to you.</small>
        </div>

        <div class="row">
          <div class="col-md-6">
            <label class="small font-weight-bold">Battery Name <span class="text-danger">*</span></label>
            <input v-model="batteryForm.battery_name" class="form-control form-control-sm mb-1" :class="{ 'is-invalid': formErrors.battery_name }" placeholder="Auto-filled or enter manually" />
            <div v-if="formErrors.battery_name" class="invalid-feedback d-block mb-1">{{ formErrors.battery_name }}</div>
          </div>
          <div class="col-md-6">
            <label class="small font-weight-bold">Battery Brand <span class="text-danger">*</span></label>
            <input v-model="batteryForm.battery_brand" class="form-control form-control-sm mb-1" :class="{ 'is-invalid': formErrors.battery_brand }" placeholder="Auto-filled or enter manually" />
            <div v-if="formErrors.battery_brand" class="invalid-feedback d-block mb-1">{{ formErrors.battery_brand }}</div>
          </div>
          <div class="col-md-6">
            <label class="small font-weight-bold">Battery Type <span class="text-danger">*</span></label>
            <input v-model="batteryForm.battery_type" class="form-control form-control-sm mb-1" :class="{ 'is-invalid': formErrors.battery_type }" placeholder="e.g. Dry, Wet, AGM" />
            <div v-if="formErrors.battery_type" class="invalid-feedback d-block mb-1">{{ formErrors.battery_type }}</div>
          </div>
          <div class="col-md-6">
            <label class="small font-weight-bold">Voltage (V) <span class="text-danger">*</span></label>
            <input v-model="batteryForm.voltage" class="form-control form-control-sm mb-1" :class="{ 'is-invalid': formErrors.voltage }" placeholder="e.g. 12" />
            <div v-if="formErrors.voltage" class="invalid-feedback d-block mb-1">{{ formErrors.voltage }}</div>
          </div>
          <div class="col-md-6">
            <label class="small font-weight-bold">Capacity / Amp (Ah) <span class="text-danger">*</span></label>
            <input v-model="batteryForm.capacity" class="form-control form-control-sm mb-1" :class="{ 'is-invalid': formErrors.capacity }" placeholder="e.g. 74" />
            <div v-if="formErrors.capacity" class="invalid-feedback d-block mb-1">{{ formErrors.capacity }}</div>
          </div>
          <div class="col-md-6">
            <label class="small font-weight-bold">Warranty (months) <span class="text-danger">*</span></label>
            <input v-model="batteryForm.warranty" class="form-control form-control-sm mb-1" :class="{ 'is-invalid': formErrors.warranty }" placeholder="e.g. 12" />
            <div v-if="formErrors.warranty" class="invalid-feedback d-block mb-1">{{ formErrors.warranty }}</div>
          </div>
        </div>

        <hr />

        <!-- Payment info summary -->
        <div class="alert alert-info py-2 px-3 mb-3 small">
          <strong>Job Price:</strong> {{ job.price }} &nbsp;|&nbsp;
          <strong>Already Paid:</strong> {{ job.paid_amount }} &nbsp;|&nbsp;
          <strong>Due:</strong> {{ job.due_amount }}
          <span v-if="batteryForm.amount && Number(batteryForm.amount) < Number(job.due_amount)" class="ml-2 text-warning font-weight-bold">
            — Partial payment (remaining: {{ (job.due_amount - batteryForm.amount).toFixed(2) }})
          </span>
        </div>

        <div class="row">
          <div class="col-md-6">
            <label class="small font-weight-bold">Payment Amount <span class="text-danger">*</span></label>
            <input v-model="batteryForm.amount" type="number" class="form-control form-control-sm mb-1" :class="{ 'is-invalid': formErrors.amount }" :placeholder="`Due: ${job.due_amount}`" />
            <div v-if="formErrors.amount" class="invalid-feedback d-block mb-1">{{ formErrors.amount }}</div>
          </div>
          <div class="col-md-6">
            <label class="small font-weight-bold">Payment Method <span class="text-danger">*</span></label>
            <select v-model="batteryForm.payment_method" class="form-control form-control-sm mb-1" :class="{ 'is-invalid': formErrors.payment_method }">
              <option value="">Select Method</option>
              <option>Cash</option>
              <option>Bank Transfer</option>
              <option>POS</option>
              <option>POL</option>
            </select>
            <div v-if="formErrors.payment_method" class="invalid-feedback d-block mb-1">{{ formErrors.payment_method }}</div>
          </div>
          <div class="col-md-6">
            <label class="small font-weight-bold">Reference Number</label>
            <input v-model="batteryForm.reference_number" class="form-control form-control-sm mb-2" placeholder="Optional" />
          </div>
          <div class="col-md-6">
            <label class="small font-weight-bold">Receipt (photo/pdf) <span class="text-danger">*</span></label>
            <input type="file" @change="handleReceiptFile" class="form-control form-control-sm mb-1" :class="{ 'is-invalid': formErrors.receipt }" accept="image/*,.pdf" />
            <div v-if="formErrors.receipt" class="invalid-feedback d-block mb-1">{{ formErrors.receipt }}</div>
          </div>
          <div class="col-12">
            <label class="small font-weight-bold">Notes</label>
            <textarea v-model="batteryForm.notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Optional notes"></textarea>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
          <small class="text-muted">Partial payment is accepted — due balance will remain.</small>
          <div style="gap:10px; display:flex">
            <button class="btn btn-secondary btn-sm" @click="closeBatteryModal">Cancel</button>
            <button class="btn btn-success btn-sm" @click="submitBatteryCompletion" :disabled="submittingBattery">
              <i class="fas fa-check mr-1"></i>
              {{ submittingBattery ? 'Saving...' : 'Complete Job & Save Payment' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pay Remaining Modal -->
    <div v-if="showPayRemainingModal" class="modal-overlay" @click.self="showPayRemainingModal = false">
      <div class="modal-box" style="width:460px">
        <h5 class="mb-3"><i class="fas fa-money-bill-wave mr-2"></i>Pay Remaining Amount</h5>

        <div class="alert alert-warning py-2 px-3 mb-3 small">
          <strong>Job Price:</strong> {{ job.price }} &nbsp;|&nbsp;
          <strong>Paid:</strong> {{ job.paid_amount }} &nbsp;|&nbsp;
          <strong>Due:</strong> {{ job.due_amount }}
        </div>

        <div class="form-group">
          <label class="small font-weight-bold">Payment Amount <span class="text-danger">*</span></label>
          <input v-model="payRemainingForm.amount" type="number" class="form-control form-control-sm mb-1"
            :placeholder="`Max due: ${job.due_amount}`" />
          <small v-if="payRemainingForm.amount && Number(payRemainingForm.amount) < Number(job.due_amount)" class="text-warning font-weight-bold">
            Partial — remaining after this: {{ (job.due_amount - payRemainingForm.amount).toFixed(2) }}
          </small>
        </div>
        <div class="form-group">
          <label class="small font-weight-bold">Payment Method <span class="text-danger">*</span></label>
          <select v-model="payRemainingForm.payment_method" class="form-control form-control-sm mb-2">
            <option value="">Select Method</option>
            <option>Cash</option>
            <option>Bank Transfer</option>
            <option>POS</option>
            <option>POL</option>
          </select>
        </div>
        <div class="form-group">
          <label class="small font-weight-bold">Receipt (photo/pdf) <span class="text-danger">*</span></label>
          <input type="file" @change="handlePayRemainingFile" class="form-control form-control-sm mb-2" accept="image/*,.pdf" />
        </div>
        <div class="form-group">
          <label class="small font-weight-bold">Reference Number</label>
          <input v-model="payRemainingForm.reference_number" class="form-control form-control-sm mb-2" placeholder="Optional" />
        </div>
        <div class="form-group">
          <label class="small font-weight-bold">Notes</label>
          <textarea v-model="payRemainingForm.notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Optional"></textarea>
        </div>

        <div class="d-flex justify-content-between mt-3">
          <button class="btn btn-secondary btn-sm" @click="showPayRemainingModal = false">Cancel</button>
          <button class="btn btn-success btn-sm" @click="submitPayRemaining" :disabled="submittingPayRemaining">
            {{ submittingPayRemaining ? 'Saving...' : 'Submit Payment' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Payment Modal -->
    <div v-if="showEditModal" class="modal-overlay" @click.self="showEditModal = false">
      <div class="modal-box">
        <h5>Edit Payment</h5>
        <div class="alert alert-info py-2 px-3 mb-3 small" v-if="job">
          <strong>Job Price:</strong> {{ job.price }} &nbsp;|&nbsp;
          <strong>Paid:</strong> {{ job.paid_amount }} &nbsp;|&nbsp;
          <strong>Due:</strong> {{ job.due_amount }}
        </div>
        <label class="small font-weight-bold">Amount <span class="text-danger">*</span></label>
        <input v-model="editForm.amount" placeholder="Amount" class="form-control mb-2" />
        <label class="small font-weight-bold">Payment Method <span class="text-danger">*</span></label>
        <select v-model="editForm.payment_method" class="form-control mb-2">
          <option>Cash</option>
          <option>Bank Transfer</option>
          <option>POS</option>
          <option>POL</option>
        </select>
        <label class="small font-weight-bold">Reference Number</label>
        <input v-model="editForm.reference_number" placeholder="Reference" class="form-control mb-2" />
        <label class="small font-weight-bold">Notes</label>
        <textarea v-model="editForm.notes" placeholder="Notes" class="form-control mb-2"></textarea>
        <label class="small font-weight-bold">Replace Receipt (optional)</label>
        <input type="file" @change="handleFile" class="form-control mb-2" accept="image/*,.pdf" />
        <div class="d-flex justify-content-between mt-3">
          <button class="btn btn-secondary" @click="showEditModal = false">Cancel</button>
          <button class="btn btn-primary" @click="updatePayment">Update</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'

const STATUS_FLOW = ['Assigned', 'DCC', 'On The Way', 'Reached', 'Job Started', 'Job Completed']

export default {
  data() {
    return {
      job: null,
      etaMinutes: '',
      savingEta: false,
      submittingBattery: false,
      showBatteryModal: false,
      technicianBatteries: [],
      formErrors: {},
      batteryForm: {
        selected_stock_id: '',
        battery_name: '',
        battery_brand: '',
        battery_type: '',
        voltage: '',
        capacity: '',
        warranty: '',
        amount: '',
        payment_method: '',
        reference_number: '',
        notes: '',
        receipt: null,
      },
      showPayRemainingModal: false,
      submittingPayRemaining: false,
      payRemainingForm: {
        amount: '',
        payment_method: '',
        reference_number: '',
        notes: '',
        receipt: null,
      },
      showEditModal: false,
      editForm: {
        id: null,
        amount: '',
        payment_method: '',
        reference_number: '',
        notes: '',
        receipt: null,
      },
    }
  },

  computed: {
    isAssignedTechnician() {
      const user = this.$store.state.auth.user
      if (!user || !this.job) return false
      return user.employee && Number(this.job.technician_id) === Number(user.employee.id)
    },
    isBatteryJob() {
      return this.job?.service_type?.name?.toLowerCase().includes('battery') ?? false
    },
    currentIndex() {
      return STATUS_FLOW.indexOf(this.job?.status)
    },
    nextStatus() {
      return STATUS_FLOW[this.currentIndex + 1] || null
    },
    prevStatus() {
      return STATUS_FLOW[this.currentIndex - 1] || null
    },
  },

  async created() {
    await this.loadJob()
  },

  methods: {
    async loadJob() {
      const { data } = await axios.get(`/api/jobs/${this.$route.params.id}`)
      this.job = data.data
      this.etaMinutes = this.job.eta_minutes || ''
    },

    statusClass(status) {
      return {
        Assigned: 'badge-secondary',
        DCC: 'badge-dark',
        'On The Way': 'badge-primary',
        Reached: 'badge-info',
        'Job Started': 'badge-warning',
        'Job Completed': 'badge-success',
      }[status] || 'badge-secondary'
    },

    paymentBadge(status) {
      return { Paid: 'badge-success', Partial: 'badge-warning', Unpaid: 'badge-danger' }[status] || 'badge-secondary'
    },

    // ETA
    async submitEta() {
      if (!this.etaMinutes) return
      this.savingEta = true
      try {
        const { data } = await axios.post(`/api/jobs/${this.job.id}/update-eta`, {
          eta_minutes: this.etaMinutes,
        })
        this.job.eta_time = data.data.eta_time
        this.job.eta_minutes = data.data.eta_minutes
        this.$toast.success('ETA updated')
      } catch (e) {
        this.$toast.error(e.response?.data?.message || 'Failed to update ETA')
      } finally {
        this.savingEta = false
      }
    },

    // Status: if battery job and completing → show popup, otherwise direct update
    async handleNextStatus() {
      if (this.nextStatus === 'Job Completed' && this.isBatteryJob) {
        this.batteryForm.amount = this.job.due_amount || ''
        await this.loadTechnicianBatteries()
        this.showBatteryModal = true
      } else {
        this.changeStatus(this.nextStatus)
      }
    },

    async loadTechnicianBatteries() {
      try {
        const { data } = await axios.get('/api/technician-battery-stocks', { params: { perPage: 100 } })
        this.technicianBatteries = data.data || []
      } catch (e) {
        this.technicianBatteries = []
      }
    },

    onBatterySelect() {
      const stock = this.technicianBatteries.find(b => b.id == this.batteryForm.selected_stock_id)
      if (!stock) return
      this.batteryForm.battery_name  = stock.product_name || ''
      this.batteryForm.battery_brand = stock.brand_name  || ''
      this.batteryForm.battery_type  = stock.battery_type || ''
      this.batteryForm.voltage       = stock.voltage      || ''
      this.batteryForm.capacity      = stock.capacity     || ''
      this.batteryForm.warranty      = stock.warranty     || ''
    },

    closeBatteryModal() {
      this.showBatteryModal = false
      this.resetBatteryForm()
    },

    async changeStatus(newStatus) {
      try {
        await axios.put(`/api/jobs/${this.job.id}/status`, { status: newStatus })
        this.$toast.success('Status updated to ' + newStatus)
        await this.loadJob()
      } catch (e) {
        this.$toast.error(e.response?.data?.message || 'Failed')
      }
    },

    validateBatteryForm() {
      const errors = {}
      const f = this.batteryForm
      if (!f.selected_stock_id) errors.selected_stock_id = 'Please select a battery'
      if (!f.battery_name?.trim()) errors.battery_name = 'Battery name is required'
      if (!f.battery_brand?.trim()) errors.battery_brand = 'Battery brand is required'
      if (!f.battery_type?.trim()) errors.battery_type = 'Battery type is required'
      if (!f.voltage) errors.voltage = 'Voltage is required'
      if (!f.capacity) errors.capacity = 'Capacity is required'
      if (!f.warranty) errors.warranty = 'Warranty is required'
      if (!f.amount || Number(f.amount) <= 0) errors.amount = 'Payment amount is required'
      if (!f.payment_method) errors.payment_method = 'Payment method is required'
      if (!f.receipt) errors.receipt = 'Receipt upload is required'
      this.formErrors = errors
      return Object.keys(errors).length === 0
    },

    // Battery completion: update status + save payment
    async submitBatteryCompletion() {
      if (!this.validateBatteryForm()) {
        this.$toast.error('Please fill all required fields')
        return
      }
      // Full payment required to complete the job
      if (Number(this.batteryForm.amount) < Number(this.job.due_amount)) {
        this.$toast.error(
          `Full payment of ${this.job.due_amount} is required to complete this job. ` +
          `Please enter the full due amount to mark the job as completed.`
        )
        return
      }
      this.submittingBattery = true
      try {
        // Save payment — backend automatically sets Job Completed when fully paid
        const fd = new FormData()
        fd.append('amount', this.batteryForm.amount)
        fd.append('payment_method', this.batteryForm.payment_method)
        fd.append('reference_number', this.batteryForm.reference_number || '')
        fd.append('notes', this.batteryForm.notes || '')
        fd.append('battery_details', JSON.stringify({
          selected_stock_id: this.batteryForm.selected_stock_id,
          battery_name:  this.batteryForm.battery_name,
          battery_brand: this.batteryForm.battery_brand,
          battery_type:  this.batteryForm.battery_type,
          voltage:       this.batteryForm.voltage,
          capacity:      this.batteryForm.capacity,
          warranty:      this.batteryForm.warranty,
        }))
        fd.append('receipt', this.batteryForm.receipt)

        await axios.post(`/api/jobs/${this.job.id}/payments`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })

        this.$toast.success('Job completed and full payment saved!')
        this.showBatteryModal = false
        this.resetBatteryForm()
        await this.loadJob()
      } catch (e) {
        this.$toast.error(e.response?.data?.message || 'Failed to save')
      } finally {
        this.submittingBattery = false
      }
    },

    resetBatteryForm() {
      this.batteryForm = {
        selected_stock_id: '', battery_name: '', battery_brand: '',
        battery_type: '', voltage: '', capacity: '', warranty: '',
        amount: '', payment_method: '', reference_number: '', notes: '', receipt: null,
      }
      this.formErrors = {}
    },

    handleReceiptFile(e) {
      this.batteryForm.receipt = e.target.files[0]
      if (this.formErrors.receipt) this.formErrors.receipt = null
    },

    // Pay Remaining
    openPayRemaining() {
      this.payRemainingForm = {
        amount: this.job.due_amount,
        payment_method: '',
        reference_number: '',
        notes: '',
        receipt: null,
      }
      this.showPayRemainingModal = true
    },

    handlePayRemainingFile(e) {
      this.payRemainingForm.receipt = e.target.files[0]
    },

    async submitPayRemaining() {
      const f = this.payRemainingForm
      if (!f.amount || Number(f.amount) <= 0) {
        this.$toast.error('Amount is required')
        return
      }
      if (!f.payment_method) {
        this.$toast.error('Payment method is required')
        return
      }
      if (!f.receipt) {
        this.$toast.error('Receipt upload is required')
        return
      }
      this.submittingPayRemaining = true
      try {
        const fd = new FormData()
        fd.append('amount', f.amount)
        fd.append('payment_method', f.payment_method)
        fd.append('reference_number', f.reference_number || '')
        fd.append('notes', f.notes || '')
        fd.append('receipt', f.receipt)

        await axios.post(`/api/jobs/${this.job.id}/payments`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })

        const isPartial = Number(f.amount) < Number(this.job.due_amount)
        this.$toast.success(isPartial
          ? `Payment recorded. Remaining due: ${(this.job.due_amount - f.amount).toFixed(2)}`
          : 'Payment complete — no balance remaining!')
        this.showPayRemainingModal = false
        await this.loadJob()
      } catch (e) {
        this.$toast.error(e.response?.data?.message || 'Failed to submit payment')
      } finally {
        this.submittingPayRemaining = false
      }
    },

    // Payment edit/delete
    openEdit(payment) {
      this.editForm = {
        id: payment.id,
        amount: payment.amount,
        payment_method: payment.payment_method,
        reference_number: payment.reference_number,
        notes: payment.notes,
        receipt: null,
      }
      this.showEditModal = true
    },

    handleFile(e) {
      this.editForm.receipt = e.target.files[0]
    },

    async updatePayment() {
      if (!this.editForm.amount || !this.editForm.payment_method) {
        this.$toast.error('Amount and payment method are required')
        return
      }
      const fd = new FormData()
      fd.append('amount', this.editForm.amount)
      fd.append('payment_method', this.editForm.payment_method)
      fd.append('reference_number', this.editForm.reference_number || '')
      fd.append('notes', this.editForm.notes || '')
      if (this.editForm.receipt) fd.append('receipt', this.editForm.receipt)
      try {
        await axios.post(`/api/payments/${this.editForm.id}`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        this.$toast.success('Payment updated')
        this.showEditModal = false
        await this.loadJob()
      } catch (e) {
        this.$toast.error(e.response?.data?.message || 'Failed to update payment')
      }
    },

    async deletePayment(id) {
      if (!confirm('Delete this payment?')) return
      await axios.delete(`/api/payments/${id}`)
      await this.loadJob()
    },
  },
}
</script>

<style scoped>
.timeline {
  position: relative;
  padding-left: 35px;
  margin-top: 20px;
}
.timeline::before {
  content: '';
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
.timeline-content strong { display: block; font-weight: 600; }
.timeline-content small { color: #777; }

.modal-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.modal-box {
  background: white;
  padding: 24px;
  width: 420px;
  max-width: 95%;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.25);
  max-height: 90vh;
  overflow-y: auto;
}

.excel-style { background: #fff; padding: 10px; }
.label {
  font-weight: 600;
  background: #f1f1f1;
  padding: 10px;
  border: 1px solid #ddd;
}
.value {
  padding: 8px;
  border: 1px solid #ddd;
  background: #fff;
}
</style>
