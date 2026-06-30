<template>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="mb-0">My Assigned Batteries</h4>
          <span class="badge badge-primary badge-pill" v-if="!loading">
            {{ stocks.length }} {{ stocks.length === 1 ? 'Battery Type' : 'Battery Types' }}
          </span>
        </div>

        <!-- Summary Cards -->
        <div class="card-body pb-0" v-if="!loading && stocks.length > 0">
          <div class="row">
            <div class="col-md-4">
              <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-battery-full"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Total Assigned</span>
                  <span class="info-box-number">{{ totalQuantity }}</span>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-tools"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Used Batteries</span>
                  <span class="info-box-number">{{ totalUsed }}</span>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Available</span>
                  <span class="info-box-number">{{ totalAvailable }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
              <thead class="thead-dark">
                <tr>
                  <th>#</th>
                  <th>Brand</th>
                  <th>Product Code</th>
                  <th>Product Name</th>
                  <th>Battery Type</th>
                  <th>Voltage</th>
                  <th>Amp (Capacity)</th>
                  <th>Warranty</th>
                  <th>Total Assigned</th>
                  <th>Used Batteries</th>
                  <th>Available</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="11" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin"></i> Loading batteries...
                  </td>
                </tr>
                <template v-else>
                  <tr v-for="(stock, index) in stocks" :key="stock.id">
                    <td>{{ (pagination.current_page - 1) * 20 + index + 1 }}</td>
                    <td>{{ stock.brand_name || '-' }}</td>
                    <td>
                      <span class="badge badge-secondary">{{ stock.product_code || '-' }}</span>
                    </td>
                    <td>{{ stock.product_name || 'N/A' }}</td>
                    <td>{{ stock.battery_type || '-' }}</td>
                    <td>{{ stock.voltage ? stock.voltage + 'V' : '-' }}</td>
                    <td>{{ stock.capacity ? stock.capacity + ' Ah' : '-' }}</td>
                    <td>{{ stock.warranty ? stock.warranty + ' months' : '-' }}</td>
                    <td>
                      <span class="badge badge-info">{{ stock.quantity }}</span>
                    </td>
                    <td>
                      <span class="badge badge-warning">{{ stock.used_quantity }}</span>
                    </td>
                    <td>
                      <span
                        class="badge"
                        :class="stock.available_quantity > 0 ? 'badge-success' : 'badge-danger'"
                      >{{ stock.available_quantity }}</span>
                    </td>
                  </tr>
                  <tr v-if="stocks.length === 0">
                    <td colspan="11" class="text-center py-4">
                      <i class="fas fa-battery-empty fa-2x text-muted mb-2 d-block"></i>
                      No batteries have been assigned to you yet.
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="d-flex justify-content-end mt-3">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="getStocks(pagination.current_page - 1)">Prev</a>
              </li>
              <li
                v-for="page in pagination.last_page"
                :key="page"
                class="page-item"
                :class="{ active: page === pagination.current_page }"
              >
                <a class="page-link" href="#" @click.prevent="getStocks(page)">{{ page }}</a>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <a class="page-link" href="#" @click.prevent="getStocks(pagination.current_page + 1)">Next</a>
              </li>
            </ul>
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
      stocks: [],
      loading: true,
      pagination: {
        current_page: 1,
        last_page: 1,
      },
    }
  },
  computed: {
    totalQuantity() {
      return this.stocks.reduce((sum, s) => sum + parseFloat(s.quantity || 0), 0)
    },
    totalUsed() {
      return this.stocks.reduce((sum, s) => sum + parseFloat(s.used_quantity || 0), 0)
    },
    totalAvailable() {
      return this.stocks.reduce((sum, s) => sum + parseFloat(s.available_quantity || 0), 0)
    },
  },
  mounted() {
    this.getStocks()
  },
  methods: {
    async getStocks(page = 1) {
      this.loading = true
      try {
        const res = await axios.get('/api/technician-battery-stocks', { params: { page, perPage: 20 } })
        this.stocks = res.data.data || []
        if (res.data.meta) {
          this.pagination.current_page = res.data.meta.current_page
          this.pagination.last_page = res.data.meta.last_page
        }
      } catch (error) {
        this.$toast.error(error.response?.data?.message || 'Unable to load battery assignments')
      } finally {
        this.loading = false
      }
    },
  },
}
</script>
