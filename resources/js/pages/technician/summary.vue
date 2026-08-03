<template>
  <div class="mb-50">
    <div class="row">
      <div class="col-lg-12">
        <div class="card custom-card summary-card">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
              <h4 class="mb-0"><i class="fas fa-file-invoice-dollar mr-2 text-primary"></i>My Job Summary</h4>
            </div>
          </div>

          <div class="card-body">
            <!-- Toolbar -->
            <div class="toolbar d-flex flex-wrap align-items-end mb-4">
              <div class="form-group mb-2 mr-3">
                <label class="small font-weight-bold text-muted mb-1">Period</label>
                <select v-model="period" @change="onPeriodChange" class="form-control form-control-sm">
                  <option value="day">Daily</option>
                  <option value="week">Weekly</option>
                  <option value="month">Monthly</option>
                  <option value="year">Yearly</option>
                  <option value="custom">Custom Range</option>
                </select>
              </div>

              <div class="form-group mb-2 mr-3" v-if="period !== 'custom'">
                <label class="small font-weight-bold text-muted mb-1">{{ period === 'day' ? 'Date' : 'Reference Date' }}</label>
                <input type="date" v-model="date" @change="load" class="form-control form-control-sm" />
              </div>

              <template v-if="period === 'custom'">
                <div class="form-group mb-2 mr-3">
                  <label class="small font-weight-bold text-muted mb-1">From</label>
                  <input type="date" v-model="from" class="form-control form-control-sm" />
                </div>
                <div class="form-group mb-2 mr-3">
                  <label class="small font-weight-bold text-muted mb-1">To</label>
                  <input type="date" v-model="to" class="form-control form-control-sm" />
                </div>
                <button class="btn btn-sm btn-primary mb-2" @click="load" :disabled="!from || !to">
                  <i class="fas fa-check mr-1"></i>Apply
                </button>
              </template>

              <div class="ml-auto mb-2" v-if="summary">
                <span class="badge badge-range"><i class="fas fa-calendar-alt mr-1"></i>{{ summary.from }} &rarr; {{ summary.to }}</span>
              </div>
            </div>

            <div v-if="!summary" class="text-center text-muted py-5">
              <i class="fas fa-spinner fa-spin fa-2x"></i>
            </div>

            <template v-else>
              <!-- Stat tiles -->
              <div class="row mb-2">
                <div class="col-6 col-sm-4 col-lg-2 mb-3" v-for="method in paymentMethods" :key="method">
                  <div class="stat-tile" :class="'accent-' + methodAccent(method)">
                    <i :class="['fas', methodIcon(method), 'stat-icon']"></i>
                    <h5 class="mb-0">{{ summary.payment_totals[method] || 0 }}</h5>
                    <small>{{ method }}</small>
                  </div>
                </div>
                <div class="col-6 col-sm-4 col-lg-2 mb-3">
                  <div class="stat-tile accent-total">
                    <i class="fas fa-layer-group stat-icon"></i>
                    <h5 class="mb-0">{{ summary.total_collected }}</h5>
                    <small>Total Collected</small>
                  </div>
                </div>
                <div class="col-6 col-sm-4 col-lg-2 mb-3">
                  <div class="stat-tile accent-hand">
                    <i class="fas fa-hand-holding-usd stat-icon"></i>
                    <h5 class="mb-0">{{ summary.cash_in_hand }}</h5>
                    <small>Cash In Hand</small>
                  </div>
                </div>
              </div>

              <!-- Cash in hand breakdown banner -->
              <div class="cash-banner d-flex justify-content-between align-items-center flex-wrap mb-4">
                <span>
                  <i class="fas fa-coins mr-1"></i>
                  Cash Collected: <strong>{{ summary.total_cash_collected }}</strong>
                  &nbsp;&minus;&nbsp; Deductions: <strong>{{ summary.total_deductions }}</strong>
                </span>
                <h5 class="mb-0">Cash In Hand: {{ summary.cash_in_hand }}</h5>
              </div>

              <!-- Jobs Completed -->
              <div v-if="summary.jobs_completed.length" class="section-card mb-4">
                <div class="section-header">
                  <i class="fas fa-clipboard-list mr-2"></i>Jobs Completed
                  <small class="text-muted ml-1">({{ summary.jobs_completed.length }} job(s) — full audit trail)</small>
                </div>
                <div class="table-responsive">
                  <table class="table table-sm mb-0 nice-table">
                    <thead>
                      <tr>
                        <th>Job</th><th>Date</th><th>Price</th><th>Collected</th>
                        <th>Method</th><th>Paid By</th><th>Deduction</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="j in summary.jobs_completed" :key="j.id">
                        <td>{{ j.name }}</td>
                        <td>{{ j.date }}</td>
                        <td>{{ j.price }}</td>
                        <td>{{ j.amount_collected }}</td>
                        <td>{{ j.payment_methods.join(', ') }}</td>
                        <td><span v-if="j.paid_by" class="badge badge-bt">{{ j.paid_by }}</span><span v-else>-</span></td>
                        <td>
                          <span v-if="j.deduction_type === 'Cash'" class="badge bg-warning">Cash: {{ j.deduction_amount }}</span>
                          <span v-else-if="j.deduction_type === 'Company'" class="badge bg-info">Company: {{ j.deduction_amount }}</span>
                          <span v-else class="badge bg-secondary">None</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="row">
                <!-- Cash Deductions -->
                <div class="col-lg-6 mb-4" v-if="summary.job_cash_deduction_jobs.length">
                  <div class="section-card h-100">
                    <div class="section-header">
                      <i class="fas fa-minus-circle mr-2 text-warning"></i>Cash Deductions
                      <small class="text-muted d-block font-weight-normal">Tyre/battery cost paid by a technician group/pocket — subtracted from your cash</small>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-sm mb-0 nice-table">
                        <thead><tr><th>Job</th><th>Paid By</th><th>Amount</th></tr></thead>
                        <tbody>
                          <tr v-for="j in summary.job_cash_deduction_jobs" :key="j.id">
                            <td>{{ j.name }}</td>
                            <td><span class="badge badge-bt">{{ j.paid_by }}</span></td>
                            <td>{{ j.amount }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                    <div class="text-right font-weight-bold mt-2">Total: {{ summary.job_cash_deductions }}</div>
                  </div>
                </div>

                <!-- Company Deductions -->
                <div class="col-lg-6 mb-4" v-if="summary.company_deduction_jobs.length">
                  <div class="section-card h-100">
                    <div class="section-header">
                      <i class="fas fa-building mr-2 text-info"></i>Company Deductions
                      <small class="text-muted d-block font-weight-normal">Tyre/battery cost paid by Company — not deducted from your cash, shown for reference</small>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-sm mb-0 nice-table">
                        <thead><tr><th>Job</th><th>Paid By</th><th>Amount</th></tr></thead>
                        <tbody>
                          <tr v-for="j in summary.company_deduction_jobs" :key="j.id">
                            <td>{{ j.name }}</td>
                            <td><span class="badge bg-info">{{ j.paid_by }}</span></td>
                            <td>{{ j.amount }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                    <div class="text-right font-weight-bold mt-2">Total: {{ summary.company_deductions }}</div>
                  </div>
                </div>
              </div>

              <!-- Add Deduction -->
              <div class="section-card mb-4">
                <div class="section-header"><i class="fas fa-plus-circle mr-2 text-primary"></i>Add Deduction <small class="text-muted">(Fuel / Commission / Payout / Other)</small></div>
                <div class="form-row align-items-end">
                  <div class="col-md-3 mb-2">
                    <select v-model="form.category" class="form-control form-control-sm">
                      <option>Fuel</option>
                      <option>Commission</option>
                      <option>Payout</option>
                      <option>Other</option>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <input v-model="form.amount" type="number" placeholder="Amount" class="form-control form-control-sm" />
                  </div>
                  <div class="col-md-4 mb-2">
                    <input v-model="form.note" placeholder="Note (e.g. Job 2 commission)" class="form-control form-control-sm" />
                  </div>
                  <div class="col-md-2 mb-2">
                    <button class="btn btn-primary btn-sm btn-block" @click="addEntry" :disabled="saving">
                      <i class="fas fa-plus mr-1"></i>Add
                    </button>
                  </div>
                </div>

                <div class="table-responsive mt-2" v-if="summary.entries.length">
                  <table class="table table-sm mb-0 nice-table">
                    <thead><tr><th>Category</th><th>Amount</th><th>Note</th><th></th></tr></thead>
                    <tbody>
                      <tr v-for="e in summary.entries" :key="e.id">
                        <td>{{ e.category }}</td>
                        <td>{{ e.amount }}</td>
                        <td>{{ e.note }}</td>
                        <td class="text-right"><button class="btn btn-sm btn-outline-danger" @click="removeEntry(e.id)"><i class="fas fa-trash"></i></button></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data() {
    return {
      paymentMethods: ['Cash', 'Bank Transfer', 'POS', 'POL'],
      period: 'day',
      date: new Date().toISOString().slice(0, 10),
      from: new Date().toISOString().slice(0, 10),
      to: new Date().toISOString().slice(0, 10),
      summary: null,
      saving: false,
      form: { category: 'Fuel', amount: '', note: '' },
    }
  },
  created() { this.load() },
  methods: {
    methodIcon(method) {
      return {
        Cash: 'fa-money-bill-wave',
        'Bank Transfer': 'fa-university',
        POS: 'fa-credit-card',
        POL: 'fa-file-invoice',
      }[method] || 'fa-wallet'
    },
    methodAccent(method) {
      return {
        Cash: 'cash',
        'Bank Transfer': 'bank',
        POS: 'pos',
        POL: 'pol',
      }[method] || 'cash'
    },
    onPeriodChange() {
      if (this.period !== 'custom') this.load()
    },
    async load() {
      const params = { period: this.period }
      if (this.period === 'custom') {
        if (!this.from || !this.to) return
        params.from = this.from
        params.to = this.to
      } else {
        params.date = this.date
      }
      const { data } = await axios.get('/api/technician-cash-summary', { params })
      this.summary = data
    },
    async addEntry() {
      if (!this.form.amount || Number(this.form.amount) <= 0) {
        this.$toast.error('Enter a valid amount')
        return
      }
      this.saving = true
      try {
        await axios.post('/api/technician-cash-entries', {
          entry_date: this.period === 'custom' ? this.to : this.date,
          category: this.form.category,
          amount: this.form.amount,
          note: this.form.note,
        })
        this.form.amount = ''
        this.form.note = ''
        await this.load()
        this.$toast.success('Entry added')
      } catch (e) {
        this.$toast.error(e.response?.data?.message || 'Failed to add entry')
      } finally {
        this.saving = false
      }
    },
    async removeEntry(id) {
      await axios.delete(`/api/technician-cash-entries/${id}`)
      await this.load()
    },
  },
}
</script>

<style scoped>
.summary-card .card-header {
  background: #fff;
  border-bottom: 1px solid #edeff2;
}

.toolbar {
  background: #f8f9fb;
  border: 1px solid #edeff2;
  border-radius: 10px;
  padding: 14px 16px 4px;
}

.badge-range {
  background: #eef1ff;
  color: #4c51bf;
  font-weight: 600;
  padding: 0.5em 0.8em;
  border-radius: 20px;
}

.badge-bt {
  background: #ffc107;
  color: #212529;
}

.stat-tile {
  border-radius: 10px;
  padding: 16px 12px;
  text-align: center;
  background: #fff;
  border: 1px solid #edeff2;
  border-top: 3px solid #ccc;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  height: 100%;
}

.stat-tile:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}

.stat-tile h5 {
  font-weight: 700;
  margin-top: 6px;
}

.stat-tile small {
  color: #6b7280;
  text-transform: uppercase;
  font-size: 0.68rem;
  letter-spacing: 0.03em;
}

.stat-icon {
  font-size: 1.1rem;
  opacity: 0.8;
}

.accent-cash { border-top-color: #28a745; }
.accent-cash .stat-icon { color: #28a745; }
.accent-bank { border-top-color: #0d6efd; }
.accent-bank .stat-icon { color: #0d6efd; }
.accent-pos { border-top-color: #6f42c1; }
.accent-pos .stat-icon { color: #6f42c1; }
.accent-pol { border-top-color: #fd7e14; }
.accent-pol .stat-icon { color: #fd7e14; }
.accent-total { border-top-color: #6c757d; }
.accent-total .stat-icon { color: #6c757d; }
.accent-hand { border-top-color: #198754; background: #f4fbf6; }
.accent-hand .stat-icon { color: #198754; }

.cash-banner {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  color: #fff;
  border-radius: 10px;
  padding: 16px 20px;
}

.section-card {
  background: #fff;
  border: 1px solid #edeff2;
  border-radius: 10px;
  padding: 16px 18px;
}

.section-header {
  font-weight: 700;
  margin-bottom: 12px;
}

.nice-table thead th {
  background: #f8f9fb;
  color: #6b7280;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 2px solid #edeff2;
}

.nice-table td {
  vertical-align: middle;
}

@media (max-width: 576px) {
  .toolbar {
    padding: 12px 12px 2px;
  }
  .cash-banner {
    text-align: center;
  }
  .cash-banner h5 {
    margin-top: 8px;
    width: 100%;
  }
}
</style>
