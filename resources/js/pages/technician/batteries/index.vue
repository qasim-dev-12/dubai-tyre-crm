<template>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4>My Assigned Batteries</h4>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Brand</th>
                  <th>Product Name</th>
                  <th>Battery Type</th>
                  <th>Voltage</th>
                  <th>Capacity</th>
                  <th>Total Assigned</th>
                  <th>Available</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="8" class="text-center">Loading...</td>
                </tr>
                <template v-if="!loading">
                  <tr v-for="(stock, index) in stocks" :key="stock.id">
                    <td>{{ index + 1 }}</td>
                    <td>{{ stock.brand_name || '-' }}</td>
                    <td>{{ stock.product_name || 'N/A' }}</td>
                    <td>{{ stock.battery_type || '-' }}</td>
                    <td>{{ stock.voltage || '-' }}</td>
                    <td>{{ stock.capacity || '-' }}</td>
                    <td>{{ stock.quantity }}</td>
                    <td>{{ stock.available_quantity }}</td>
                  </tr>
                  <tr v-if="stocks.length === 0">
                    <td colspan="8" class="text-center">No assigned batteries found.</td>
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
      loading: false,
      pagination: {
        current_page: 1,
        last_page: 1,
      },
    }
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
