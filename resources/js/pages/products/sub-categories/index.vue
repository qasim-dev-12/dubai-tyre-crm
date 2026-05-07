<template>
  <div class="mb-50">
    <!-- breadcrumbs Start -->
    <breadcrumbs :items="breadcrumbs" :current="breadcrumbsCurrent" />
    <!-- breadcrumbs end -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card custom-card w-100">
          <div class="card-header setings-header">
            <div class="col-xl-4 col-4">
              <h3 class="card-title">
                {{ $t("Brands") }}
              </h3>
            </div>
            <div class="col-xl-8 col-8 float-right text-right">
              <div class="btn-group c-w-100">
                <a
                  @click="refreshTable()"
                  href="#"
                  v-tooltip="$t('Refresh')"
                  class="btn btn-success"
                >
                  <i class="fas fa-sync"></i>
                </a>
                <a :href="exportUrl" v-tooltip="$t('Export to Excel')" class="btn btn-info">
                  <i class="fa fa-arrow-circle-down"></i>
                </a>
                <a
                  href="/product-sub-categories/pdf"
                  v-tooltip="$t('Export to PDF')"
                  class="btn btn-secondary"
                >
                  <i class="fas fa-file-export"></i>
                </a>
                <a
                  @click="print"
                  v-tooltip="$t('Print Table')"
                  class="btn btn-info"
                >
                  <i class="fas fa-print"></i>
                </a>
                <router-link
                  v-if="$can('product-sub-category-create')"
                  :to="{ name: 'productSubCats.create' }"
                  class="btn btn-primary"
                >
                  {{ $t("Create") }}
                  <i class="fas fa-plus-circle d-none d-sm-inline-block" />
                </router-link>
              </div>
            </div>
          </div>
          <!-- /.card-header -->
          <div class="card-body position-relative">
            <div class="row">
              <div class="col-md-3 col-xl-4 mb-2">
                <search
                  v-model="query"
                  @reset-pagination="resetPagination()"
                  @reload="reload"
                />
              </div>
              <div class="card-tools col-md-3 col-xl-2 mb-2">
                <select
                  v-model="filterType"
                  class="form-control"
                  id="filterType"
                  name="filterType"
                >
                  <option value="default" selected>
                    {{ $t("All") }}
                  </option>
                  <option value="active">
                    {{ $t("Filter By Active Status") }}
                  </option>
                  <option value="inactive">
                    {{ $t("Filter By Inactive Status") }}
                  </option>
                </select>
              </div>
            </div>
            <table-loading v-show="loading" />
            <div id="printMe" class="table-responsive table-custom mt-3">
              <table class="table">
                <thead>
                  <tr>
                    <th>{{ $t("SL") }}</th>
                    <th>{{ $t("Category") }}</th>
                    <th>{{ $t("Brand Code") }}</th>
                    <th>{{ $t("Brand Name") }}</th>
                    <th>{{ $t("Status") }}</th>
                    <th
                      v-if="
                        $can('product-sub-category-edit') ||
                        $can('product-sub-category-delete')
                      "
                      class="text-right no-print"
                    >
                      {{ $t("Action") }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-show="items.length" v-for="(data, i) in items" :key="i">
                    <td>
                      <span v-if="pagination && pagination.current_page > 1">
                        {{
                          pagination.per_page * (pagination.current_page - 1) +
                          (i + 1)
                        }}</span
                      >
                      <span v-else>{{ i + 1 }}</span>
                    </td>
                    <td>
                      <span v-if="data.category"
                        >{{ data.category.name }} [
                        {{ data.category.code }} ]
                      </span>
                    </td>
                    <td>{{ data.code | withPrefix(subCatPrefix) }}</td>
                    <td>{{ data.name }}</td>
                    <td>
                      <span v-if="data.status === 1" class="badge bg-success">{{
                        $t("Active")
                      }}</span>
                      <span v-else class="badge bg-danger">{{
                        $t("Inactive")
                      }}</span>
                    </td>
                    <td
                      v-if="
                        $can('product-sub-category-edit') ||
                        $can('product-sub-category-delete')
                      "
                      class="text-right no-print"
                    >
                      <div class="btn-group">
                        <router-link
                          v-if="$can('product-sub-category-edit')"
                          v-tooltip="$t('Edit')"
                          :to="{
                            name: 'productSubCats.edit',
                            params: { slug: data.slug },
                          }"
                          class="btn btn-info btn-sm"
                        >
                          <i class="fas fa-edit" />
                        </router-link>
                        <a
                          v-if="$can('product-sub-category-delete')"
                          v-tooltip="$t('Delete')"
                          href="#"
                          class="btn btn-danger btn-sm"
                          @click="deleteData(data.slug)"
                        >
                          <i class="fas fa-trash" />
                        </a>
                      </div>
                    </td>
                  </tr>
                  <tr v-show="!loading && !items.length">
                    <td colspan="6">
                      <EmptyTable />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer">
            <div class="dtable-footer">
              <div class="form-group row display-per-page">
                <label>{{ $t("Per Page") }} </label>
                <div>
                  <select
                    @change="updatePerPager"
                    v-model="perPage"
                    class="form-control form-control-sm ml-1"
                  >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                </div>
              </div>
              <!-- pagination-start -->
              <pagination
                v-if="pagination && pagination.last_page > 1"
                :pagination="pagination"
                :offset="5"
                class="justify-flex-end"
                @paginate="paginate"
              />
              <!-- pagination-end -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
export default {
  middleware: ["auth", "check-permissions"],
  metaInfo() {
    return { title: this.$t("Brands") };
  },
  data: () => ({
    breadcrumbsCurrent: "Brands",
    breadcrumbs: [
      {
        name: "Dashboard",
        url: "home",
      },
      {
        name: "Items",
        url: "products.index",
      },
      {
        name: "Brands",
        url: "",
      },
    ],
    query: "",
    filterType: "default",
    perPage: 10,
    catPrefix: "",
    subCatPrefix: "",
  }),
  // Map Getters
  computed: {
    ...mapGetters("operations", ["items", "loading", "pagination", "appInfo"]),
    exportUrl() {
      // Create a dynamic export URL with query parameters
      return `/product-sub-categories/export/excel?term=${this.query}`;
    },
  },
  watch: {
    // watch search data
    query: function (newQ, oldQ) {
      if (newQ === "") {
        this.getData();
      } else {
        this.searchData();
      }
    },
    filterType: function (newQ, oldQ) {
      if (newQ === "") {
        this.getData();
      } else {
        this.searchData();
      }
    },
  },
  created() {
    this.getData();
    this.catPrefix = this.appInfo.proCatPrefix;
    this.subCatPrefix = this.appInfo.proSubCatPrefix;
  },
  methods: {
    // update per page count
    updatePerPager() {
      this.pagination.current_page = 1;
      this.query === "" ? this.getData() : this.searchData();
    },
    // get data
    async getData() {
      this.$store.state.operations.loading = true;
      let currentPage = this.pagination ? this.pagination.current_page : 1;
      await this.$store.dispatch("operations/fetchData", {
        path: "/api/product-sub-categories?page=",
        currentPage: currentPage + "&perPage=" + this.perPage,
      });
    },

    // Pagination
    async paginate() {
      this.query === "" ? this.getData() : this.searchData();
    },

    // Reset pagination
    async resetPagination() {
      this.pagination.current_page = 1;
    },

    // search data
    async searchData() {
      this.$store.state.operations.loading = true;
      let currentPage = this.pagination ? this.pagination.current_page : 1;
      await this.$store.dispatch("operations/searchDataWithFilterType", {
        path: "/api/product-sub-categories/search",
        term: this.query,
        currentPage: currentPage + "&perPage=" + this.perPage,
        filterType: this.filterType,
      });
    },

    // Reload after search
    async reload() {
      this.query = "";
      this.filterType = "default";
    },

    // print table
    async print() {
      await this.$htmlToPaper("printMe");
    },

    // refresh table
    refreshTable() {
      this.query = "";
      this.filterType = "default";
      this.query === "" ? this.getData() : this.searchData();
    },

    // delete data
    async deleteData(slug) {
      Swal.fire({
        title: this.$t("Are you sure?"),
        text: this.$t("All items for this category will be deleted!"),
        type: "warning",
        showCancelButton: true,
        confirmButtonText: this.$t("Are you sure?"),
      }).then((result) => {
        // Send request to the server
        if (result.value) {
          this.$store
            .dispatch("operations/deleteData", {
              path: "/api/product-sub-categories/",
              slug: slug,
            })
            .then((response) => {
              if (response === true) {
                Swal.fire(
                  this.$t("Deleted!"),
                  this.$t("Deleted successfully"),
                  "success"
                );
              } else {
                Swal.fire(
                  this.$t("Failed!"),
                  this.$t("There was something wrong"),
                  "warning"
                );
              }
            });
        }
      });
    },
  },
};
</script>
