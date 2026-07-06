<template>
  <div class="mb-50">
    <div class="row">
      <div class="col-lg-12">
        <div class="card custom-card w-100">

          <!-- Header -->
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Warranty Claims</h4>
            <input
              v-model="term"
              @input="debouncedSearch"
              type="text"
              class="form-control form-control-sm"
              style="max-width: 260px"
              placeholder="Search name, mobile, vehicle..."
            />
          </div>

          <!-- Body -->
          <div class="card-body">

            <table-loading v-show="loading" />

            <div class="table-responsive">
              <table class="table warranty-table">
                <thead>
                  <tr>
                    <th>SL</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Battery</th>
                    <th>Installed On</th>
                    <th>Expires On</th>
                    <th>Days Left</th>
                    <th>Status</th>
                    <th class="text-right">Action</th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="(row, i) in safeItems" :key="row.id">
                    <td data-label="SL">
                      <span v-if="pagination && pagination.current_page > 1">
                        {{ pagination.per_page * (pagination.current_page - 1) + (i + 1) }}
                      </span>
                      <span v-else>{{ i + 1 }}</span>
                    </td>
                    <td data-label="Customer">
                      <span class="truncate" :title="row.job?.name">{{ row.job?.name }}</span>
                      <br><small class="text-muted">{{ row.job?.mobile }}</small>
                    </td>
                    <td data-label="Vehicle">{{ row.job?.vehicle_number }}</td>
                    <td data-label="Battery">
                      {{ row.battery_details?.battery_name || row.battery_details?.battery_type || '-' }}
                      <br><small class="text-muted">{{ row.warranty_months }} mo warranty</small>
                    </td>
                    <td data-label="Installed On">{{ formatDate(row.job?.job_completed_at) }}</td>
                    <td data-label="Expires On">{{ formatDate(row.warranty_expires_at) }}</td>
                    <td data-label="Days Left">{{ daysLeft(row) }}</td>
                    <td data-label="Status">
                      <span
                        class="badge"
                        :class="{
                          'bg-success': row.warranty_status === 'Active',
                          'bg-secondary': row.warranty_status === 'Expired',
                          'bg-info': row.warranty_status === 'Claimed'
                        }"
                      >
                        {{ row.warranty_status }}
                      </span>
                      <br v-if="row.replacement_job_id">
                      <router-link
                        v-if="row.replacement_job_id"
                        :to="{ name: 'jobs.show', params: { id: row.replacement_job_id } }"
                        class="small"
                      >
                        View replacement job
                      </router-link>
                    </td>
                    <td class="text-right" data-label="Action">
                      <button
                        v-if="row.warranty_status === 'Active'"
                        class="btn btn-primary btn-sm"
                        @click="claimWarranty(row)"
                        :disabled="claimingId === row.id"
                      >
                        <i class="fas fa-shield-alt"></i> Claim Warranty
                      </button>
                      <span v-else>-</span>
                    </td>
                  </tr>

                  <tr v-if="!loading && safeItems.length === 0">
                    <td colspan="9" class="text-center">
                      No Warranty Records Found
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
          <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <label>Per Page</label>
                <select v-model="perPage" @change="updatePerPage" class="form-control form-control-sm">
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>

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
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import axios from "axios";

export default {
  data() {
    return {
      perPage: 10,
      term: '',
      debounceTimer: null,
      claimingId: null
    };
  },

  computed: {
    ...mapGetters("operations", ["items", "loading", "pagination"]),
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
    async getData(resetPage = false) {
      const page = resetPage ? 1 : this.pagination?.current_page || 1;

      await this.$store.dispatch("operations/fetchData", {
        path: "/api/warranty-claims?",
        currentPage: `page=${page}&perPage=${this.perPage}&term=${encodeURIComponent(this.term)}`,
      });
    },

    debouncedSearch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => this.getData(true), 400);
    },

    formatDate(date) {
      if (!date) return '-';
      return new Date(date).toLocaleDateString();
    },

    daysLeft(row) {
      if (!row.warranty_expires_at) return '-';
      if (row.warranty_status !== 'Active') return '-';
      const diff = Math.ceil((new Date(row.warranty_expires_at) - new Date()) / 86400000);
      return diff > 0 ? diff : 0;
    },

    async claimWarranty(row) {
      if (!confirm('Claim warranty and create a replacement job? This can only be done once.')) return;

      this.claimingId = row.id;
      try {
        await axios.post(`/api/warranty-claims/${row.id}/claim`);
        this.$toast.success('Warranty claimed, replacement job created');
        this.getData();
      } catch (error) {
        this.$toast.error(error.response?.data?.message || 'Failed to claim warranty');
      } finally {
        this.claimingId = null;
      }
    },

    paginate() {
      this.getData();
    },

    updatePerPage() {
      this.getData(true);
    }
  }
};
</script>

<style scoped>
.warranty-table thead th {
  background: #f8f9fb;
  color: #6b7280;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  font-weight: 700;
  border-bottom: 2px solid #edeff2;
  white-space: nowrap;
}

.warranty-table td {
  vertical-align: middle;
}

.warranty-table .badge {
  font-weight: 600;
  padding: 0.4em 0.7em;
  border-radius: 6px;
}

@media (max-width: 768px) {
  .warranty-table thead {
    display: none;
  }

  .warranty-table,
  .warranty-table tbody,
  .warranty-table tr,
  .warranty-table td {
    display: block;
    width: 100%;
  }

  .warranty-table tr {
    margin-bottom: 14px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  }

  .warranty-table td {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border: none !important;
    text-align: right;
  }

  .warranty-table td::before {
    content: attr(data-label);
    font-weight: 600;
    color: #6b7280;
    text-align: left;
    margin-right: 10px;
    flex-shrink: 0;
  }

  .warranty-table td.text-right {
    justify-content: flex-start;
  }

  .warranty-table td.text-right::before {
    content: none;
  }
}
</style>
