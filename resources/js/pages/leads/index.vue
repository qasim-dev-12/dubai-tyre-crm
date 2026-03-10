<template>
  <div class="mb-50">
    <!-- Breadcrumbs -->

    <div class="row">
      <div class="col-lg-12" v-if="$can('lead-list')">
        <div class="card custom-card w-100">
          <!-- Header -->
          <div
            class="card-header setings-header d-flex justify-content-between"
          >
            <div class="col-lg-4 col-4">
              <breadcrumbs :items="breadcrumbs" :current="breadcrumbsCurrent" />
            </div>

            <div class="btn-group col-lg-8 col-8">
              <button @click="refreshTable" class="btn btn-success">
                <i class="fas fa-sync"></i>
              </button>

              <a :href="exportUrl" class="btn btn-info">
                <i class="fa fa-arrow-circle-down"></i>
              </a>

              <button @click="print" class="btn btn-secondary">
                <i class="fas fa-print"></i>
              </button>

              <router-link
                v-if="$can('lead-create')"
                :to="{ name: 'leads.create' }"
                class="btn btn-primary"
              >
                Create
                <i class="fas fa-plus-circle d-none d-sm-inline-block" />
              </router-link>



            </div>
          </div>

          <!-- Body -->
          <div class="card-body position-relative">
            <!-- Filters -->
            <div class="row mb-2">
              <div class="col-md-6 col-xl-4">
                <search
                  v-model="query"
                  @reload="search"
                  @reset-pagination="search"
                />
              </div>

              <div class="col-md-3 col-xl-2">
                <select
                  v-model="statusFilter"
                  class="form-control"
                  @change="search"
                >
                  <option value="all">All Status</option>
                  <option value="New">New</option>
                  <option value="In Progress">In Progress</option>
                  <option value="Converted">Converted</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
              </div>
            </div>

            <table-loading v-show="loading" />

            <!-- Table -->
            <div class="table-responsive table-custom" id="printMe">
              <table class="table">
                <thead>
                  <tr>
                    <th>SL</th>
                    <th>Salutation</th>
                    <th>Name</th>
                    <th>Service Type</th>
                    <th>Area</th>
                    <th>Price</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th>Vehicle No</th>
                    <th class="text-right">Action</th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="(lead, i) in items" :key="lead.slug">
                    <td>
                      <span v-if="pagination && pagination.current_page > 1">
                        {{
                          pagination.per_page * (pagination.current_page - 1) +
                          (i + 1)
                        }}
                      </span>
                      <span v-else>{{ i + 1 }}</span>
                    </td>
                     <td>{{ lead.salutation }}</td>
                       <td>
                      <router-link
                        v-if="$can('lead-view')"
                        :to="{
                          name: 'leads.show',
                          params: { slug: lead.slug },
                        }"
                      >
                        {{ lead.name }}
                      </router-link>
                      <span v-else>{{ lead.name }}</span>
                    </td>
 <td>{{ lead.service_type?.name }}</td>
 <td>{{ lead.area }}</td>
  <td>{{ lead.price }}</td>
                    <td>{{ lead.mobile }}</td>








                    <td>
                      <span
                        v-if="lead.status === 'New'"
                        class="badge bg-primary"
                        >New</span
                      >
                      <span
                        v-else-if="lead.status === 'In Progress'"
                        class="badge bg-warning"
                      >
                        In Progress
                      </span>
                      <span
                        v-else-if="lead.status === 'Converted'"
                        class="badge bg-success"
                      >
                        Converted
                      </span>
                      <span v-else class="badge bg-danger">Cancelled</span>
                    </td>

                    <td>{{ lead.vehicle_number }}</td>

                    <td class="text-right">
                      <div class="btn-group">
                        <a
                          v-if="
                            $can('lead-convert') && lead.status !== 'Converted'
                          "
                          class="btn btn-success btn-sm mr-4"
                          @click="$router.push({ name: 'leads.convert', params: { slug: lead.slug } })"
                        >
                          <i class="fas fa-sync"></i>
                        </a>
                        <router-link
                          v-if="$can('lead-view')"
                          :to="{
                            name: 'leads.show',
                            params: { slug: lead.slug },
                          }"
                          class="btn btn-primary btn-sm"
                        >
                          <i class="fas fa-eye" />
                        </router-link>

                        <router-link
                          v-if="$can('lead-edit')"
                          :to="{
                            name: 'leads.edit',
                            params: { slug: lead.slug },
                          }"
                          class="btn btn-info btn-sm"
                        >
                          <i class="fas fa-edit" />
                        </router-link>

                        <button
                          v-if="$can('lead-delete')"
                          class="btn btn-danger btn-sm"
                          @click.prevent="deleteData(lead.slug)"
                        >
                          <i class="fas fa-trash" />
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr v-if="!loading && !items.length">
                    <td colspan="10">
                      <EmptyTable />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Footer -->
          <div class="card-footer">
            <div
              class="dtable-footer d-flex justify-content-between align-items-center"
            >
              <!-- Per Page -->
              <div class="form-group row display-per-page mb-0">
                <label class="mb-0">Per Page</label>
                <select
                  v-model="perPage"
                  @change="updatePerPage"
                  class="form-control form-control-sm ml-1"
                >
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
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import axios from "axios";

export default {
  middleware: ["auth", "check-permissions"],

  data() {
    return {
      breadcrumbsCurrent: "Leads",
      breadcrumbs: [
        { name: "Dashboard", url: "home" },
        { name: "Leads", url: "" },
      ],
      query: "",
      statusFilter: "all",
      perPage: 10,
    };
  },

  computed: {
    ...mapGetters("operations", ["items", "loading", "pagination"]),

    exportUrl() {
      return `/leads/export/excel?term=${this.query}&status=${this.statusFilter}`;
    },
  },

  created() {
    this.getData();
  },

  methods: {
    async getData(resetPage = false) {
      const page = resetPage ? 1 : this.pagination?.current_page || 1;

      await this.$store.dispatch("operations/fetchData", {
        path: "/api/leads?",
        currentPage:
          `page=${page}` +
          `&perPage=${this.perPage}` +
          `&term=${this.query}` +
          `&status=${this.statusFilter}`,
      });
    },
    async convertLead(slug) {
      if (!confirm("Convert this lead to Converted status?")) return;

      try {
        const res = await axios.post(`/api/leads/${slug}/convert`);

        this.$toast.success(res.data?.message || "Lead converted successfully");

        // reload table
        this.getData();
      } catch (error) {
        this.$toast.error(
          error?.response?.data?.message || "Failed to convert lead"
        );
      }
    },

    search() {
      this.getData(true); // reset to page 1 and apply search
    },
    paginate() {
      this.getData();
    },

    updatePerPage() {
      this.getData(true);
    },

    resetPagination() {
      this.getData(true);
    },

    async deleteData(slug) {
      if (!confirm("Delete this lead?")) return;
      await axios.delete(`/api/leads/${slug}`);
      this.getData();
    },

    refreshTable() {
      this.query = "";
      this.statusFilter = "all";
      this.getData(true);
    },

    reload() {
      this.getData(true);
    },

    async print() {
      await this.$htmlToPaper("printMe");
    },
  },
};
</script>
