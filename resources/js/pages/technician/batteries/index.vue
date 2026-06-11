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
                  <th>Product</th>
                  <th>Battery Type</th>
                  <th>Voltage</th>
                  <th>Capacity</th>
                  <th>Total Qty</th>
                  <th>Reserved Qty</th>
                  <th>Available Qty</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="8" class="text-center">Loading...</td>
                </tr>
                <tr v-for="(stock, index) in stocks" :key="stock.id">
                  <td>{{ index + 1 }}</td>
                  <td>{{ stock.product_name || 'N/A' }}</td>
                  <td>{{ stock.battery_type || '-' }}</td>
                  <td>{{ stock.product_details?.voltage || '-' }}</td>
                  <td>{{ stock.product_details?.capacity || '-' }}</td>
                  <td>{{ stock.quantity }}</td>
                  <td>{{ stock.reserved_quantity }}</td>
                  <td>{{ stock.available_quantity }}</td>
                </tr>
                <tr v-if="!loading && stocks.length === 0">
                  <td colspan="8" class="text-center">No assigned batteries found.</td>
                </tr>
              </tbody>
            </table>
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
    }
  },
  mounted() {
    this.getStocks()
  },
  methods: {
    async getStocks() {
      this.loading = true
      try {
        const res = await axios.get('/api/technician-battery-stocks')
        this.stocks = res.data.data || []
      } catch (error) {
        this.$toast.error(error.response?.data?.message || 'Unable to load battery assignments')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
