<template>
  <div>
    <!-- breadcrumbs Start -->
    <breadcrumbs :items="breadcrumbs" :current="breadcrumbsCurrent" />
    <!-- breadcrumbs end -->

    <div class="row no-print mb-2">
      <div class="w-100 text-right float-right">
        <div class="d-flex justify-content-between" v-if="allData">
          <div class="btn-group">
            <ul class="nav nav-pills">
              <li class="nav-item">
                <a
                  class="nav-link active"
                  href="#details"
                  data-toggle="tab"
                  @click="getProduct"
                >
                  <i class="fa fa-info"></i>
                  {{ $t("Details") }}</a
                >
              </li>
              <li class="nav-item">
                <a
                  @click="getActivity"
                  class="nav-link"
                  href="#activity-log"
                  data-toggle="tab"
                >
                  <i class="nav-icon fa fa-bell" aria-hidden="true"></i>
                  {{ $t("Activity log") }}</a
                >
              </li>
            </ul>
          </div>
          <div class="btn-group">
            <a @click="generatePDF()" href="#" class="btn btn-primary">
              <i class="fas fa-download"></i> {{ $t("Download") }}
            </a>
            <a @click="printWindow()" href="#" class="btn btn-secondary">
              <i class="fas fa-print"></i> {{ $t("Print") }}
            </a>
            <router-link
              v-if="$can('product-edit')"
              :to="{
                name: 'products.edit',
                params: { slug: allData.slug },
              }"
              class="btn btn-info"
            >
              <i class="fas fa-edit" /> {{ $t("Edit") }}
            </router-link>
            <router-link
              :to="{ name: 'products.index' }"
              class="btn btn-dark float-right"
            >
              <i class="fas fa-long-arrow-alt-left" /> {{ $t("Back") }}
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-content">
      <div class="tab-pane active" id="details">
        <div class="row">
          <table-loading v-show="loading" />
          <div
            v-if="!loading"
            class="invoice p-3 mb-3 w-100"
            id="content-to-pdf"
          >
            <div class="row align-items-center flex-row-reverse">
              <div class="col-lg-6">
                <table class="table table-hover table-bordered table-md">
                  <tbody>
                    <tr>
                      <th>{{ $t("Barcode") }}</th>
                      <td>
                        <barcode
                          :format="allData.symbology"
                          width="1"
                          height="25"
                          fontSize="15"
                          :value="allData.code"
                        >
                          {{ $t("Rendering fails.") }}
                        </barcode>
                      </td>
                    </tr>
                    <tr>
                      <th>{{ $t("Item Type") }}</th>
                      <td class="text-capitalize">{{ allData.itemType }}</td>
                    </tr>
                    <tr>
                      <th>{{ $t("Name") }}</th>
                      <td>{{ allData.name }}</td>
                    </tr>
                    <tr>
                      <th>{{ $t("Code") }}</th>
                      <td>{{ allData.code | withPrefix(prefix) }}</td>
                    </tr>
                    <tr>
                      <th>{{ $t("Item Model") }}</th>
                      <td>{{ allData.itemModel }}</td>
                    </tr>
                    <tr>
                      <th>
                        {{ $t("Barcode Symbology") }}
                      </th>
                      <td>{{ allData.symbology }}</td>
                    </tr>
                    <tr v-if="allData.category">
                      <th>{{ $t("Category") }}</th>
                      <td>
                        {{ allData.category.name }} [{{
                          allData.category.code | withPrefix(catPrefix)
                        }}]
                      </td>
                    </tr>
                    <tr v-if="allData.subCategory">
                      <th>{{ $t("Brand") }}</th>
                      <td>
                        {{ allData.subCategory.name }} [{{
                          allData.subCategory.code | withPrefix(subCatPrefix)
                        }}]
                      </td>
                    </tr>
                    <tr v-if="allData.itemBrand">
                      <th>{{ $t("Brand") }}</th>
                      <td>{{ allData.itemBrand.name }}</td>
                    </tr>
                    <tr v-if="allData.itemUnit">
                      <th>{{ $t("Unit") }}</th>
                      <td>{{ allData.itemUnit.code }}</td>
                    </tr>
                    <tr v-if="allData.itemTax">
                      <th>{{ $t("Item Tax") }}</th>
                      <td>
                        {{ allData.itemTax.code }}
                        <span
                          v-if="
                            allData.itemTax &&
                            allData.itemTax.groupTaxDetails &&
                            allData.itemTax.groupTaxDetails.length
                          "
                        >
                          (<span
                            v-for="(tax, index) in allData.itemTax
                              .groupTaxDetails"
                            :key="tax.id"
                          >
                            {{ tax.rate }}%<span
                              v-if="
                                index <
                                allData.itemTax.groupTaxDetails.length - 1
                              "
                            >
                              +</span
                            > </span
                          >)
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <th>{{ $t("Tax Type") }}</th>
                      <td>{{ allData.taxType }}</td>
                    </tr>
                    <tr>
                      <th>{{ $t("Tax Amount") }}</th>
                      <td>{{ allData.taxAmount }}</td>
                    </tr>
                    <tr>
                      <th>{{ $t("Regular Price") }}</th>
                      <td>{{ allData.regularPrice | withCurrency }}</td>
                    </tr>
                    <tr>
                      <th>{{ $t("Selling Price") }}</th>
                      <td>
                        <span v-if="allData.discount > 0">
                          <del>{{ allData.regularPrice | withCurrency }}</del>
                          {{ allData.sellingPrice | withCurrency }} ({{
                            allData.discount
                          }}%)
                        </span>
                        <span v-else
                          >{{ allData.regularPrice | withCurrency }}
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <th>
                        {{ $t("Avg. Purchase Price") }}
                      </th>
                      <td>{{ allData.avgPurchasePrice | withCurrency }}</td>
                    </tr>
                    <tr v-if="allData.itemUnit">
                      <th>{{ $t("Stock") }}</th>
                      <td>
                        {{ allData.availableQty }} {{ allData.itemUnit.code }}
                      </td>
                    </tr>
                    <tr>
                      <th>{{ $t("Inventory Value") }}</th>
                      <td>
                        {{
                          (allData.avgPurchasePrice * allData.availableQty)
                            | withCurrency
                        }}
                      </td>
                    </tr>
                    <tr>
                      <th>{{ $t("Alert Quantity") }}</th>
                      <td>
                        {{ allData.alertQty }}
                        <span v-if="allData.itemUnit">{{
                          allData.itemUnit.code
                        }}</span>
                      </td>
                    </tr>
                    <tr v-if="allData.openingStockCount">
                      <th>{{ $t("Opening Stock Quantity") }}</th>
                      <td>
                        {{ allData.openingStockCount }} <span v-if="allData.itemUnit">{{
                          allData.itemUnit.code
                        }}</span>
                      </td>
                    </tr>
                    <tr v-if="allData.openingStockUnitPrice">
                      <th>{{ $t("Opening Stock Unit Price") }}</th>
                      <td>
                        {{ allData.openingStockUnitPrice | withCurrency }}
                      </td>
                    </tr>
                    <tr>
                      <th>{{ $t("Note") }}</th>
                      <td>{{ allData.note }}</td>
                    </tr>
                    <tr>
                      <th>{{ $t("Status") }}</th>
                      <td>
                        <span
                          v-if="allData.status === 1"
                          class="badge bg-success"
                          >{{ $t("Active") }}</span
                        >
                        <span v-else class="badge bg-danger">{{
                          $t("Inactive")
                        }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="col-lg-6 no-print">
                <div class="about-avatar text-center">
                  <img
                    v-if="allData.image"
                    :src="allData.image"
                    class="img-fluid"
                    loading="lazy"
                  />
                  <img
                    v-else
                    src="https://via.placeholder.com/800x1000"
                    class="img-fluid"
                    loading="lazy"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!--  activity logs -->
      <div class="tab-pane" id="activity-log">
        <div class="card custom-card w-100 mt-5 no-print">
          <div class="card-header setings-header">
            <div class="col-xl-4 col-4">
              <h3 class="card-title">
                {{ $t("Activity log") }}
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
                <a
                  @click="print"
                  v-tooltip="$t('Print Table')"
                  class="btn btn-info"
                >
                  <i class="fas fa-print"></i>
                </a>
              </div>
            </div>
          </div>
          <table-loading v-show="loading" />
          <div class="card-body position-relative">
            <div class="row">
              <div class="col-6 col-xl-4 mb-2">
                <search
                  v-model="query"
                  @reset-pagination="resetPagination()"
                  @reload="reload"
                />
              </div>
            </div>
            <div id="printMe" class="table-responsive table-custom mt-3">
              <div
                v-show="items.length > 0"
                v-for="(data, i) in items"
                :key="i"
              >
                <div class="card mb-0 border border-gray">
                  <div class="card-body py-1">
                    <div class="row">
                      <div
                        class="col-1 d-flex justify-content-center align-items-center"
                      >
                        <i
                          v-if="data.event == 'Update'"
                          class="fa fa-magic"
                          aria-hidden="true"
                        ></i>
                        <i
                          v-if="data.event == 'Create'"
                          class="fa fa-plus-circle"
                          aria-hidden="true"
                        ></i>
                        <i
                          v-if="data.event == 'Delete'"
                          class="fa fa-trash"
                          aria-hidden="true"
                        ></i>
                      </div>
                      <div class="col-11">
                        <div class="row">
                          <div class="col-12">
                            <p class="text-bold mb-0">{{ data.causer_name }}</p>
                          </div>
                          <div class="col-12">
                            <p class="mb-0">{{ data.description }}</p>
                          </div>
                          <div class="col-12">
                            <p class="mb-0">{{ data.performedAt }}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="text-center" v-show="!loading && !items.length">
                <EmptyTable />
              </div>
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
import VueBarcode from "vue-barcode";
import { mapGetters } from "vuex";
import axios from "axios";
import html2pdf from "html2pdf.js";

export default {
  middleware: ["auth", "check-permissions"],
  metaInfo() {
    return { title: this.$t("Item Details") };
  },
  components: {
    barcode: VueBarcode,
  },
  data: () => ({
    breadcrumbsCurrent: "Item Details",
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
        name: "Details",
        url: "",
      },
    ],
    allData: "",
    loading: false,
    query: "",
    perPage: 10,
  }),
  // Map Getters
  computed: {
    ...mapGetters("operations", ["appInfo", "items", "loading", "pagination"]),
  },

  watch: {
    // watch search data
    query: function (newQ, oldQ) {
      if (newQ === "") {
        this.getActivity();
      } else {
        this.searchData();
      }
    },
  },

  created() {
    this.getProduct();
    this.prefix = this.appInfo.productPrefix;
    this.catPrefix = this.appInfo.proCatPrefix;
    this.subCatPrefix = this.appInfo.proSubCatPrefix;
  },

  methods: {
    // print
    printWindow() {
      window.print();
    },
    // get the product
    async getProduct() {
      this.loading = true;
      const { data } = await axios.get(
        window.location.origin + "/api/products/" + this.$route.params.slug
      );
      this.allData = data.data;
      this.loading = false;
    },
    // download pdf
    generatePDF() {
      // Get the HTML content to be converted
      const element = document.getElementById("content-to-pdf");
      // Options for PDF generation
      const options = {
        margin: 5,
        filename: "Product-" + this.$route.params.slug + ".pdf",
        image: { type: "jpeg", quality: 0.98 },
        pagebreak: { mode: "avoid-all", before: "#page-break" },
        html2canvas: { scale: 2 },
        jsPDF: { unit: "mm", format: "a4", orientation: "landscape" },
      };
      // Generate PDF from HTML content
      html2pdf().from(element).set(options).save();
    },

    // print table
    async print() {
      await this.$htmlToPaper("printMe");
    },

    // get activity logs
    async getActivity() {
      let currentPage = this.pagination ? this.pagination.current_page : 1;
      this.$store.state.operations.loading = true;
      let slug = this.$route.params.slug;
      let modelName = "Product";
      await this.$store.dispatch("operations/fetchSpecificLogs", {
        path: "/api/activity-log-specific?page=",
        currentPage: currentPage + "&perPage=" + this.perPage,
        slug: slug,
        modelName: modelName,
      });
    },

    // search data
    async searchData() {
      this.$store.state.operations.loading = true;
      let slug = this.$route.params.slug;
      let modelName = "Product";
      await this.$store.dispatch("operations/fetchSpecificLogs", {
        path: "/api/activity-log-specific?page=",
        currentPage: this.pagination.current_page + "&perPage=" + this.perPage,
        term: this.query,
        slug: slug,
        modelName: modelName,
      });
    },

    // pagination
    async paginate() {
      this.getActivity();
    },

    updatePerPager() {
      this.pagination.current_page = 1;
      this.query === "" ? this.getActivity() : this.searchData();
    },

    // reload after search
    async reload() {
      this.query = "";
    },

    // refresh table
    refreshTable() {
      this.query = "";
      this.query === "" ? this.getActivity() : this.searchData();
    },

    // reset pagination
    async resetPagination() {
      this.pagination.current_page = 1;
    },
  },
};
</script>
<style scoped>
.nav-pills .nav-item {
  background: #ddd;
  margin: 2px;
  border-radius: 0.25rem;
}
</style>
