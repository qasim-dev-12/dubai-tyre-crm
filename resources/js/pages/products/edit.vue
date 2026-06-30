<template>
  <div>
    <!-- breadcrumbs Start -->
    <breadcrumbs :items="breadcrumbs" :current="breadcrumbsCurrent" />
    <!-- breadcrumbs end -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header">
            <h3 v-if="form.itemName" class="card-title">
              {{ $t('Edit Item') }}
            </h3>
            <router-link :to="{ name: 'products.index' }" class="btn btn-dark float-right">
              <i class="fas fa-long-arrow-alt-left" /> {{ $t('Back') }}
            </router-link>
          </div>
          <!-- /.card-header -->
          <!-- form start -->
          <form role="form" @submit.prevent="updateProduct" @keydown="form.onKeydown($event)">
            <div class="card-body">
              <div class="row">
                <div class="form-group col-md-12 col-xl-12">
                  <div class="btn-group btn-group-toggle w-25" data-toggle="buttons">
                    <label
                      class="btn btn-outline-custom"
                      :class="{ 'btn-custom-active': form.itemType === 'product' }">
                      <input type="radio" id="product" name="itemType" v-model="form.itemType" value="product" autocomplete="off">
                      {{ $t('Product') }}
                    </label>

                    <label
                      class="btn btn-outline-custom"
                      :class="{ 'btn-custom-active': form.itemType === 'service' }">
                      <input type="radio" id="service" name="itemType" v-model="form.itemType" value="service" autocomplete="off">
                      {{ $t('Service') }}
                    </label>
                  </div>
                </div>
                <div class="form-group col-md-6 col-xl-6">
                  <label for="itemName">{{ $t('Item Name') }}
                    <span class="required">*</span></label>
                  <input id="itemName" v-model="form.itemName" type="text" class="form-control"
                    :class="{ 'is-invalid': form.errors.has('itemName') }" name="itemName"
                    :placeholder="$t('Enter a name')" />
                  <has-error :form="form" field="itemName" />
                </div>
                <div class="form-group col-md-6 col-xl-3">
                  <label for="itemModel">{{ $t('Item Model') }}</label>
                  <input id="itemModel" v-model="form.itemModel" type="text" class="form-control"
                    :class="{ 'is-invalid': form.errors.has('itemModel') }" name="itemModel"
                    :placeholder="$t('Enter a model')" />
                  <has-error :form="form" field="itemModel" />
                </div>
                <div class="form-group col-md-6 col-xl-3">
                  <label>Product Type</label>
                  <select v-model="form.productType" class="form-control">
                    <option value="tyre">Tyre</option>
                    <option value="battery">Battery</option>
                  </select>
                </div>

                <!-- Battery-specific fields -->
                <template v-if="form.productType === 'battery'">
                  <div class="form-group col-md-6 col-xl-3">
                    <label>Battery Type</label>
                    <input v-model="form.batteryType" class="form-control" placeholder="e.g. Dry, Wet" />
                  </div>
                  <div class="form-group col-md-6 col-xl-2">
                    <label>Voltage (V)</label>
                    <input v-model="form.voltage" class="form-control" placeholder="e.g. 12" />
                  </div>
                  <div class="form-group col-md-6 col-xl-2">
                    <label>Amp / Capacity (Ah)</label>
                    <input v-model="form.capacity" class="form-control" placeholder="e.g. 74" />
                  </div>
                  <div class="form-group col-md-6 col-xl-2">
                    <label>Warranty (months)</label>
                    <input v-model="form.warranty" class="form-control" placeholder="e.g. 12" />
                  </div>
                </template>

                <div class="form-group col-md-6 col-xl-3">
                  <div class="input-group">
                    <label for="itemCode" class="col-md-12">{{ $t('Item code') }}"
                      <span class="required">*</span></label>
                    <div class="input-group-prepend">
                      <span v-if="prefix" class="input-group-text" id="basic-addon1">{{ prefix }}</span>
                    </div>
                    <input v-model="form.itemCode" type="text" class="form-control"
                      :class="{ 'is-invalid': form.errors.has('itemCode') }" name="itemCode"
                      :placeholder="$t('Enter item code')" aria-label="itemCode"
                      aria-describedby="basic-addon1" />
                    <has-error :form="form" field="itemCode" />
                  </div>
                </div>

                <div class="form-group col-md-6 col-xl-4">
                  <label for="barcodeSymbology">{{ $t('Barcode Symbology') }}
                    <span class="required">*</span></label>
                  <select id="barcodeSymbology" v-model="form.barcodeSymbology" class="form-control" :class="{
                    'is-invalid': form.errors.has('barcodeSymbology'),
                  }">
                    <option value="CODE128">CODE128</option>
                    <option value="CODE39">CODE39</option>
                    <option value="EAN8">EAN8</option>
                    <option value="EAN13">EAN13</option>
                    <option value="UPC">UPC</option>
                  </select>
                  <has-error :form="form" field="barcodeSymbology" />
                </div>

                <div v-if="items" class="form-group col-md-6 col-xl-4">
                  <label for="subCategory">{{ $t('Sub Category') }}</label>
                  <v-select v-model="form.subCategory" :options="items" label="name"
                    :class="{ 'is-invalid': form.errors.has('subCategory') }" name="subCategory"
                    :placeholder="$t('Select a sub category')" />
                  <has-error :form="form" field="subCategory" />
                </div>
                <!-- <div v-if="brands" class="form-group col-md-6 col-xl-4">
                  <label for="brand">{{ $t('Brand') }}</label>
                  <v-select v-model="form.brand" :options="brands" label="name"
                    :class="{ 'is-invalid': form.errors.has('brand') }" name="brand"
                    :placeholder="$t('Select a brand')" />
                  <has-error :form="form" field="brand" />
                </div> -->
                <div v-if="units" class="form-group col-md-6 col-xl-4">
                  <label for="itemUnit">{{ $t('Unit') }}
                    <span class="required">*</span></label>
                  <v-select v-model="form.itemUnit" :options="units" label="name"
                    :class="{ 'is-invalid': form.errors.has('itemUnit') }" name="itemUnit"
                    :placeholder="$t('Select a unit')" />
                  <has-error :form="form" field="itemUnit" />
                </div>
                <div v-if="taxes" class="form-group col-md-6 col-xl-4">
                  <label for="productTax">{{ $t('Item Tax') }}
                    <span class="required">*</span></label>
                  <v-select v-model="form.productTax" :options="taxes" label="name"
                    :class="{ 'is-invalid': form.errors.has('productTax') }" name="productTax"
                    :placeholder="$t('Select a tax')" @input="calculatePrice" />
                  <has-error :form="form" field="productTax" />
                </div>
                <div class="form-group col-md-6 col-xl-4">
                  <label for="taxType">{{ $t('Tax Type') }}
                    <span class="required">*</span></label>
                  <select id="taxType" v-model="form.taxType" class="form-control"
                    :class="{ 'is-invalid': form.errors.has('taxType') }" @change="calculatePrice">
                    <option value="Exclusive">
                      {{ $t('Exclusive') }}
                    </option>
                    <option value="Inclusive">
                      {{ $t('Inclusive') }}
                    </option>
                  </select>
                  <has-error :form="form" field="taxType" />
                </div>
                <div class="form-group col-md-6" :class="form.itemType === 'service' ? 'col-xl-3' : 'col-xl-4'">
                  <label for="purchasePrice">{{ $t('Purchase Price') }}
                    <span class="required">*</span></label>
                  <input id="purchasePrice" v-model="form.purchasePrice" type="number" step="any" min="0"
                    class="form-control" :class="{ 'is-invalid': form.errors.has('purchasePrice') }" name="purchasePrice"
                    :placeholder="$t('Enter purchase price')" />
                  <has-error :form="form" field="purchasePrice" />
                </div>
                <div class="form-group col-md-6" :class="form.itemType === 'service' ? 'col-xl-3' : 'col-xl-4'">
                  <label for="regularPrice">{{ $t('Regular Price') }}
                    <span class="required">*</span></label>
                  <input id="regularPrice" v-model="form.regularPrice" type="number" step="any" min="0"
                    class="form-control" :class="{ 'is-invalid': form.errors.has('regularPrice') }" name="regularPrice"
                    :placeholder="$t('Enter regular price')
                      " @change="calculatePrice" @keyup="calculatePrice" />
                  <has-error :form="form" field="regularPrice" />
                </div>
                <div class="form-group col-md-6" :class="form.itemType === 'service' ? 'col-xl-3' : 'col-xl-4'">
                  <div class="input-group">
                    <label for="discount" class="col-md-12">{{
                      $t('Discount')
                    }}</label>
                    <input v-model="form.discount" type="number" min="0" max="100" class="form-control"
                      :class="{ 'is-invalid': form.errors.has('discount') }" name="discount"
                      :placeholder="$t('Enter discount')" aria-label="discount"
                      aria-describedby="basic-addon1" @change="calculatePrice" @keyup="calculatePrice" />
                    <div class="input-group-prepend">
                      <span class="input-group-text" id="basic-addon1">%</span>
                    </div>
                    <has-error :form="form" field="discount" />
                  </div>
                </div>
                <div class="form-group col-md-6" :class="form.itemType === 'service' ? 'col-xl-3' : 'col-xl-4'">
                  <label for="sellingPrice">{{
                    $t('Selling Price')
                  }}</label>
                  <input id="sellingPrice" v-model="form.sellingPrice" type="number" class="form-control" readonly
                    :class="{ 'is-invalid': form.errors.has('sellingPrice') }" name="sellingPrice" :placeholder="$t('Enter sale price')
                        " />
                  <has-error :form="form" field="sellingPrice" />
                </div>

                <div v-if="form.itemType == 'service'" class="form-group col-md-12 col-xl-3">
                  <label for="servicePurchasePrice">{{ $t('Service Cost') }}
                    <span class="required">*</span></label>
                  <input id="servicePurchasePrice" v-model="form.servicePurchasePrice" type="number" step="any" min="0"
                    class="form-control" :class="{ 'is-invalid': form.errors.has('servicePurchasePrice') }" name="servicePurchasePrice"
                    :placeholder="$t('Enter Service Cost')"/>
                  <has-error :form="form" field="servicePurchasePrice" />
                </div>

                <div v-if="form.itemType == 'product'" class="form-group col-md-12">
                  <div class="form-check">
                    <input v-model="form.isOpeningStock" type="checkbox" class="form-check-input" id="isOpeningStock" />
                    <label for="isOpeningStock">{{
                      $t('Add Opening Stock?')
                    }}</label>
                  </div>
                </div>

                <div v-if="form.isOpeningStock" class="row col-md-12">
                  <div class="form-group col-md-6 col-xl-6">
                    <label for="openingStockCount">{{ $t('Opening Stock Quantity') }}
                      <span class="required">*</span></label>
                    <input id="openingStockCount" v-model="form.openingStockCount" type="number" step="any" min="0"
                      class="form-control" :class="{ 'is-invalid': form.errors.has('openingStockCount') }" name="openingStockCount"
                      :placeholder="$t('Enter opening stock quantity')" />
                    <has-error :form="form" field="openingStockCount" />
                  </div>
                  <div class="form-group col-md-6 col-xl-6">
                    <label for="openingStockUnitPrice">{{ $t('Opening Stock Unit Price') }}
                      <span class="required">*</span></label>
                    <input id="openingStockUnitPrice" v-model="form.openingStockUnitPrice" type="number" step="any" min="0"
                      class="form-control" :class="{ 'is-invalid': form.errors.has('openingStockUnitPrice') }" name="openingStockUnitPrice"
                      :placeholder="$t('Enter opening stock unit price')" />
                    <has-error :form="form" field="openingStockUnitPrice" />
                  </div>
                </div>

                <div class="form-group col-md-12">
                  <label for="note">{{ $t('Note') }}</label>
                  <textarea id="note" v-model="form.note" type="text" class="form-control"
                    :class="{ 'is-invalid': form.errors.has('note') }" name="companyName"
                    :placeholder="$t('Write your note here!')"></textarea>
                  <has-error :form="form" field="note" />
                </div>
                <div v-if="form.itemType == 'product'" class="form-group col-md-6 col-xl-4">
                  <label for="alertQuantity">{{
                    $t('Alert Quantity')
                  }}</label>
                  <input id="alertQuantity" v-model="form.alertQuantity" type="number" min="0" max="1000"
                    class="form-control" :class="{ 'is-invalid': form.errors.has('alertQuantity') }" name="alertQuantity"
                    :placeholder="$t('Enter alert quantity')
                        " />
                  <has-error :form="form" field="alertQuantity" />
                </div>
                <div class="form-group" :class="form.itemType === 'service' ? 'col-md-12 col-xl-6' : 'col-md-6 col-xl-4'">
                  <label for="status">{{ $t('Status') }}</label>
                  <select id="status" v-model="form.status" class="form-control"
                    :class="{ 'is-invalid': form.errors.has('status') }">
                    <option value="1">
                      {{ $t('Active') }}
                    </option>
                    <option value="0">
                      {{ $t('Inactive') }}
                    </option>
                  </select>
                  <has-error :form="form" field="status" />
                </div>
                <div class="form-group col-md-12" :class="form.itemType === 'service' ? 'col-xl-6' : 'col-xl-4'">
                  <label for="image">{{ $t('Image') }}</label>
                  <div class="custom-file">
                    <input id="image" type="file" class="custom-file-input" name="image"
                      :class="{ 'is-invalid': form.errors.has('image') }" @change="onFileChange" />
                    <label class="custom-file-label" for="image">{{
                      $t('Choose file')
                    }}</label>
                  </div>
                  <has-error :form="form" field="image" />
                  <div class="bg-light mt-4 w-25">
                    <img v-if="url" :src="url" class="img-fluid" :alt="$t('Attached Image')" />
                  </div>
                </div>
              </div>
            </div>
            <div class="card-footer">
              <v-button :loading="form.busy" class="btn btn-primary">
                <i class="fas fa-edit" /> {{ $t('Save changes') }}
              </v-button>
              <button type="reset" class="btn btn-secondary float-right" @click="form.reset()">
                <i class="fas fa-power-off" /> {{ $t('Reset') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import Form from 'vform'
import axios from 'axios'

export default {
  middleware: ['auth', 'check-permissions'],
  metaInfo() {
    return { title: this.$t('Edit Item') }
  },
  data: () => ({
    breadcrumbsCurrent: 'Edit Item',
    breadcrumbs: [
      {
        name: 'Dashboard',
        url: 'home',
      },
      {
        name: 'Items',
        url: 'products.index',
      },
      {
        name: 'Edit',
        url: '',
      },
    ],
    url: null,
    form: new Form({
      itemType: 'product',
      itemName: '',
      itemCode: '',
      itemModel: '',
      barcodeSymbology: 'CODE128',
      subCategory: '',
      brand: '',
      itemUnit: '',
      productType: 'tyre',
      batteryType: '',
      voltage: '',
      capacity: '',
      warranty: '',
      productTax: '',
      taxType: 'Exclusive',
      purchasePrice: '',
      regularPrice: '',
      servicePurchasePrice: '',
      discount: '',
      sellingPrice: '',
      openingStockCount: '',
      openingStockUnitPrice: '',
      isOpeningStock: false,
      note: '',
      alertQuantity: 1,
      status: 1,
      image: '',
    }),
    options: [],
    prefix: '',
    units: [],
    brands: [],
    taxes: [],
  }),
  computed: {
    ...mapGetters('operations', ['items', 'appInfo']),
  },
  created() {
    this.getSubCategories()
    this.getUnits()
    this.getProduct()
    this.getBrands()
    this.getTaxes()
    this.prefix = this.appInfo.productPrefix
  },
  methods: {
    // get all product categories
    async getSubCategories() {
      await this.$store.dispatch('operations/allData', {
        path: '/api/all-product-sub-categories',
      })
    },

    // get all brands
    async getBrands() {
      const { data } = await axios.get(
        window.location.origin + '/api/all-brands'
      )
      this.brands = data.data
    },

    // get all units
    async getUnits() {
      const { data } = await axios.get(
        window.location.origin + '/api/all-units'
      )
      this.units = data.data
    },

    // get all taxes
    async getTaxes() {
      const { data } = await axios.get(
        window.location.origin + '/api/all-vat-rates'
      )
      this.taxes = data.data
    },

    // get product
    async getProduct() {
      const { data } = await axios.get(
        window.location.origin + '/api/products/' + this.$route.params.slug
      )
      this.form.itemType = data.data.itemType
      this.form.itemName = data.data.name
      this.form.itemCode = data.data.code
      this.form.itemModel = data.data.itemModel
      this.form.barcodeSymbology = data.data.symbology
      // this.form.brand = data.data.itemBrand
      this.form.productType = data.data.productType || 'tyre'
      this.form.batteryType = data.data.batteryType || ''
      this.form.voltage = data.data.voltage || ''
      this.form.capacity = data.data.capacity || ''
      this.form.warranty = data.data.warranty || ''
      this.form.productTax = data.data.itemTax
      this.form.taxType = data.data.taxType
      this.form.subCategory = data.data.subCategory
      this.form.itemUnit = data.data.itemUnit
      this.form.purchasePrice = data.data.purchasePrice
      this.form.regularPrice = data.data.regularPrice
      this.form.servicePurchasePrice = data.data.servicePurchasePrice
      this.form.sellingPrice = data.data.sellingPrice
      this.form.isOpeningStock = data.data.openingStockCount ? true : false,
      this.form.openingStockCount = data.data.openingStockCount
      this.form.openingStockUnitPrice = data.data.openingStockUnitPrice
      this.form.discount = data.data.discount
      this.form.note = data.data.note
      this.form.status = data.data.status
      this.form.alertQuantity = data.data.alertQty
      this.url = data.data.image
    },

    // calculate selling price
    calculatePrice() {
      if (this.form.sellingPrice && this.form.productTax && this.form.taxType) {
        let discount = 0
        if (this.form.discount && this.form.discount > 0) {
          discount = (this.form.discount / 100) * this.form.regularPrice
        }
        let currentPrice = this.form.regularPrice - discount

        let taxAmount = 0
        let totalTax = 0
        if (this.form.productTax.rate > 0) {
          taxAmount = this.form.productTax.rate / 100
        }
        if (this.form.taxType == 'Exclusive') {
          totalTax = currentPrice * taxAmount
        } else {
          totalTax = currentPrice - currentPrice / (1 + taxAmount)
        }

        if (this.form.taxType == 'Exclusive') {
          this.form.sellingPrice = this.form.regularPrice - discount + totalTax
        } else {
          this.form.sellingPrice =
            (this.form.regularPrice - discount) / (1 + taxAmount) + totalTax
        }
        return
      }
      return (this.form.sellingPrice = this.form.regularPrice)
    },

    // vue file upload
    onFileChange(e) {
      const file = e.target.files[0]
      const reader = new FileReader()
      if (
        file.size < 2111775 &&
        (file.type === 'image/jpeg' ||
          file.type === 'image/png' ||
          file.type === 'image/gif')
      ) {
        reader.onloadend = (file) => {
          this.form.image = reader.result
        }
        reader.readAsDataURL(file)
        this.url = URL.createObjectURL(file)
      } else {
        Swal.fire(
          'error!',
          'Please select a valid thumbnail with size less than 2 MB',
          'error'
        )
      }
    },

    // update product
    async updateProduct() {
      await this.form
        .patch(
          window.location.origin + '/api/products/' + this.$route.params.slug
        )
        .then(() => {
          toast.fire({
            type: 'success',
            title: 'Product updated successfully 👍',
          })
          this.$router.push({ name: 'products.index' })
        })
        .catch(() => {
          toast.fire({
            type: 'error',
            title: 'Opps...something is wrong 😔',
          })
        })
    },
  },
}
</script>

<style>
.btn-outline-custom {
  color: #6366f1;
  border-color: #6366f1;
  transition: background-color 0.3s, color 0.3s, border-color 0.3s;
}
.btn-outline-custom:hover {
  background-color: rgba(99, 102, 241, 0.2);
  color: #6366f1;
}
.btn-custom-active, .btn-custom-active:hover {
  background-color: #6366f1;
  color: #fff;
}
</style>
