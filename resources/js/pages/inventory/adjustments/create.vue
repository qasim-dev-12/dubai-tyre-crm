<template>
  <div>
    <!-- breadcrumbs Start -->
    <breadcrumbs :items="breadcrumbs" :current="breadcrumbsCurrent" />
    <!-- breadcrumbs end -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              {{ $t("Create Adjustment") }}
            </h3>
            <router-link :to="{ name: 'adjustments.index' }" class="btn btn-dark float-right">
              <i class="fas fa-long-arrow-alt-left" /> {{ $t("Back") }}
            </router-link>
          </div>
          <!-- /.card-header -->
          <!-- form start -->
          <form role="form" @submit.prevent="saveAdjustment" @keydown="form.onKeydown($event)">
            <div class="card-body">
              <div v-if="products" class="row">
                <div class="form-group col-md-6">
                  <label for="adjustmentReason">{{ $t("Adjustment Reason") }}
                    <span class="required">*</span></label>
                  <input id="adjustmentReason" v-model="form.adjustmentReason" type="text" class="form-control" :class="{
                    'is-invalid': form.errors.has('adjustmentReason'),
                  }" name="adjustmentReason" :placeholder="$t('Enter a reason')" />
                  <has-error :form="form" field="adjustmentReason" />
                </div>
                <div class="form-group col-md-6">
                  <label for="technician">{{ $t("Technician") }}
                    <span class="required">*</span></label>
                  <v-select v-model="form.technician" :options="technicians" label="name" :class="{
                    'is-invalid': form.errors.has('technician'),
                  }" name="technician" :placeholder="$t('Select technician')"
                    @input="onTechnicianChange" />
                  <has-error :form="form" field="technician" />
                </div>
              </div>
              <div v-if="products && form.technician" class="row">
                <div class="form-group col-md-12">
                  <label for="product">{{ $t("Select Products") }}
                    <span class="required">*</span></label>
                  <v-select v-model="form.product" :options="adjustmentProducts" label="label" :class="{
                    'is-invalid': form.errors.has('selectedProducts'),
                  }" name="product" :placeholder="$t('Search products')"
                    @input="storeProduct(form.product)" />
                  <has-error :form="form" field="selectedProducts" />
                </div>
              </div>
              <div v-if="form.selectedProducts && form.selectedProducts.length > 0" class="row mt-3 mb-2">
                <div class="col-md-12 mb-3">
                  <div class="alert alert-info">
                    <strong>{{ $t("Assigned Technician") }}:</strong> {{ form.technician.name }}
                  </div>
                </div>
                <div class="table-responsive table-custom w-95 m-auto table-sm">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>{{ $t("SL") }}</th>
                        <th>{{ $t("Code") }}</th>
                        <th>{{ $t("Name") }}</th>
                        <th>{{ $t("Stock") }}</th>
                        <th class="w-200px">
                          {{ $t("Adjustment Type") }}
                        </th>
                        <th class="w-250px">{{ $t("Quantity") }}</th>
                        <th class="text-right">{{ $t("Action") }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, i) in form.selectedProducts" :key="i">
                        <td>{{ ++i }}</td>
                        <td>{{ item.itemCode | withPrefix(prefix) }}</td>
                        <td>{{ item.name }}</td>
                        <td>
                          <span class="btn btn-warning btn-sm">{{
                            item.stockQty
                          }}</span>
                        </td>
                        <td>
                          <select class="form-control" @change="
                            updateProduct(
                              $event.target.value,
                              'adjustType',
                              i - 1
                            )
                            " :id="`adjustType-${i}`" required>
                            <option value="Decrement">
                              {{ $t("Assign to Technician") }}
                            </option>
                            <option value="Increment">
                              {{ $t("Return to Warehouse") }}
                            </option>
                          </select>
                        </td>
                        <td>
                          <div class="input-group custom-qty-input">
                            <input type="button" value="-" class="button-minus icon-shape icon-sm btn-danger"
                              data-field="quantity" @click="
                                updateProduct(
                                  item.adjustQty > 1
                                    ? parseFloat(item.adjustQty) - 1
                                    : 1,
                                  'quantity',
                                  i - 1
                                )
                                " />
                            <input type="number" step="any" :id="`Qty-${i}`" name="quantity"
                              class="quantity-field border-0 incrementor" @change="
                                updateProduct(
                                  $event.target.value,
                                  'quantity',
                                  i - 1
                                )
                                " @keyup="
    updateProduct(
      $event.target.value,
      'quantity',
      i - 1
    )
    " :placeholder="$t('Quantity')" min="1" :max="item.maxQty" :value="item.adjustQty" required />
                            <input type="button" value="+" class="button-plus icon-shape icon-sm btn-primary"
                              data-field="quantity" @click="
                                updateProduct(
                                  parseFloat(item.adjustQty) + 1,
                                  'quantity',
                                  i - 1
                                )
                                " />
                          </div>
                        </td>
                        <td class="text-right">
                          <button type="button" class="btn btn-danger" @click="removeItem(item)">
                            <i class="fas fa-times"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="form-group">
                <label for="note">{{ $t("Note") }}</label>
                <textarea id="note" v-model="form.note" class="form-control"
                  :class="{ 'is-invalid': form.errors.has('note') }" :placeholder="$t('Write your note here!')" />
                <has-error :form="form" field="note" />
              </div>
              <div class="row">
                <div class="form-group col-md-6">
                  <label for="adjustmentDate">{{
                    $t("Adjustment Date")
                  }}</label>
                  <input id="adjustmentDate" v-model="form.adjustmentDate" type="date" class="form-control"
                    :class="{ 'is-invalid': form.errors.has('adjustmentDate') }" name="adjustmentDate" />
                  <has-error :form="form" field="adjustmentDate" />
                </div>
                <div class="form-group col-md-6">
                  <label for="status">{{ $t("Status") }}</label>
                  <select id="status" v-model="form.status" class="form-control"
                    :class="{ 'is-invalid': form.errors.has('status') }">
                    <option value="1">{{ $t("Active") }}</option>
                    <option value="0">{{ $t("Inactive") }}</option>
                  </select>
                  <has-error :form="form" field="status" />
                </div>
              </div>
            </div>
            <!-- /.card-body -->
            <div class="card-footer">
              <v-button :loading="form.busy" class="btn btn-primary">
                <i class="fas fa-save" /> {{ $t("Save") }}
              </v-button>
              <button type="reset" class="btn btn-secondary float-right" @click="form.reset()">
                <i class="fas fa-power-off" /> {{ $t("Reset") }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Form from "vform";
import axios from "axios";
import { mapGetters } from "vuex";

export default {
  middleware: ["auth", "check-permissions"],
  metaInfo() {
    return { title: this.$t("Create Adjustment") };
  },
  data: () => ({
    breadcrumbsCurrent: "Create Adjustment",
    breadcrumbs: [
      {
        name: "Dashboard",
        url: "home",
      },
      {
        name: "Adjustments",
        url: "adjustments.index",
      },
      {
        name: "Create",
        url: "",
      },
    ],
    form: new Form({
      selectedProducts: [],
      adjustmentReason: "",
      technician: null,
      adjustmentDate: new Date().toISOString().slice(0, 10),
      note: "",
      status: 1,
    }),
    products: null,
    technicians: [],
    prefix: "",
  }),
  computed: {
    ...mapGetters("operations", ["items", "appInfo"]),
    adjustmentProducts() {
      return Array.isArray(this.products) ? this.products : [];
    },
  },
  created() {
    this.getProducts();
    this.getTechnicians();
    this.prefix = this.appInfo.productPrefix;
  },
  methods: {
    // get products
    async getProducts() {
      const { data } = await axios.get(
        window.location.origin + "/api/all-products-not-service"
      );
      this.products = data.data || data || [];
    },

    // get technicians
    async getTechnicians() {
      try {
        const { data } = await axios.get(
          window.location.origin + "/api/technicians"
        );
        this.technicians = data.data || data;
      } catch (error) {
        console.error("Error fetching technicians:", error);
        this.technicians = [];
      }
    },

    // handle technician change
    onTechnicianChange(technician) {
      this.form.technician = technician;
      // Clear selected products when technician changes
      this.form.selectedProducts = [];
    },

    // store item in array
    storeProduct(product) {
      var index = this.form.selectedProducts.findIndex(
        (x) => x.id == product.id
      );
      let quantity = 1;
      if (index === -1) {
        // store product
        this.form.selectedProducts.unshift({
          id: product.id,
          slug: product.slug,
          name: product.name,
          itemCode: product.code,
          purchasePrice: product.avgPurchasePrice,
          stockQty: product.inventoryCount,
          adjustType: "Decrement",
          adjustQty: quantity,
          maxQty: 9999,
        });
      } else {
        // update qty
        if (this.form.selectedProducts[index]) {
          quantity = this.form.selectedProducts[index].adjustQty
            ? this.form.selectedProducts[index].adjustQty + 1
            : 1;
          this.form.selectedProducts[index].adjustQty = quantity;
        }
      }
      return;
    },

    // update array
    updateProduct(value, type, index) {
      let selectedProduct = this.form.selectedProducts[index];
      if (selectedProduct) {
        if (type == "quantity" && selectedProduct.adjustQty >= 1) {
          selectedProduct.adjustQty = value;
        } else if (type == "adjustType") {
          selectedProduct.adjustType = value;
          if (selectedProduct.adjustType == "Decrement") {
            selectedProduct.maxQty = selectedProduct.stockQty;
          } else {
            selectedProduct.maxQty = 9999;
          }
        } else {
          selectedProduct.purchasePrice = value;
          selectedProduct.adjustQty = 1;
        }
      }
      this.form.selectedProducts[index] = selectedProduct;
      return;
    },

    // remove item from array
    removeItem(item) {
      let index = this.form.selectedProducts.indexOf(item);
      if (index > -1) {
        this.form.selectedProducts.splice(index, 1);
      }
      return;
    },

    // save adjustment
    async saveAdjustment() {
      // Validate technician is selected
      if (!this.form.technician) {
        toast.fire({
          type: "error",
          title: this.$t("Please select a technician"),
        });
        return;
      }

      // Validate products are selected
      if (!this.form.selectedProducts || this.form.selectedProducts.length === 0) {
        toast.fire({
          type: "error",
          title: this.$t("Please select at least one product"),
        });
        return;
      }

      // Add technician_id to form data
      this.form.technician_id = this.form.technician.id;

      await this.form
        .post(window.location.origin + "/api/inventory-adjustments")
        .then(() => {
          toast.fire({
            type: "success",
            title: this.$t("Adjustment added successfully"),
          });
          this.$router.push({ name: "adjustments.index" });
        })
        .catch(() => {
          toast.fire({ type: "error", title: this.$t("Opps...something went wrong") });
        });
    },
  },
};
</script>
