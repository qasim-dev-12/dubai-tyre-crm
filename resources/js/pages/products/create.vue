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
              {{ $t('Create Item') }}
            </h3>
            <router-link :to="{ name: 'products.index' }" class="btn btn-dark float-right">
              <i class="fas fa-long-arrow-alt-left" /> {{ $t('Back') }}
            </router-link>
          </div>
          <!-- /.card-header -->
          <!-- form start -->
          <form role="form" @submit.prevent="saveProduct" @keydown="form.onKeydown($event)">
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
                  <label for="itemModel">{{ $t('Dimensions') }}</label>
                  <input id="itemModel" v-model="form.itemModel" type="text" class="form-control"
                    :class="{ 'is-invalid': form.errors.has('itemModel') }" name="itemModel"
                    :placeholder="$t('Enter dimensions')" />
                  <has-error :form="form" field="itemModel" />
                </div>
                <div class="form-group col-md-6 col-xl-4">
                  <label>{{ $t('Sub Category') }}</label>
                  <v-select
                    v-model="form.subCategory"
                    :options="items"
                    label="name"
                    :class="{ 'is-invalid': form.errors.has('subCategory') }"
                    name="subCategory"
                    :placeholder="$t('Select a sub category')"
                  />
                  <has-error :form="form" field="subCategory" />
                </div>
                <!-- 🔋 PRODUCT TYPE -->
<div class="form-group col-md-6 col-xl-3">
  <label>Product Type</label>
  <select v-model="form.productType" class="form-control" @change="calculatePrice">
    <option value="tyre">Tyre</option>
    <option value="battery">Battery</option>
  </select>
</div>

<!-- 🔋 BATTERY FIELDS -->
<div v-if="form.productType === 'battery'" class="row col-md-12">


  <div class="form-group col-md-3">
    <label>Product Code</label>
    <input v-model="form.productCode" class="form-control" />
  </div>

  <div class="form-group col-md-2">
    <label>Amp</label>
    <input v-model="form.amp" class="form-control" />
  </div>

  <div class="form-group col-md-2">
    <label>CCA</label>
    <input v-model="form.cca" class="form-control" />
  </div>

  <div class="form-group col-md-2">
    <label>Dimension</label>
    <input v-model="form.dimension" class="form-control" />
  </div>

<div class="form-group col-md-2">
    <label>Net Amount</label>
    <input v-model="form.netAmount" readonly class="form-control" />
  </div>

  <div class="form-group col-md-2">
    <label>VAT Amount</label>
    <input v-model="form.vatAmount" readonly class="form-control" />
  </div>

  <div class="form-group col-md-3">
    <label>{{ $t('Call Center Amount') }}</label>
    <input v-model="form.costPrice" class="form-control" :readonly="form.productType === 'battery'" />
  </div>
  <div class="form-group col-md-2">
    <label>10% Price</label>
    <input v-model="form.price10" readonly class="form-control" />
  </div>

  <div class="form-group col-md-2">
    <label>15% Price</label>
    <input v-model="form.price15" readonly class="form-control" />
  </div>

  <div class="form-group col-md-2">
    <label>5 AED</label>
    <input v-model="form.price5AED" readonly class="form-control" />
  </div>

  <div class="form-group col-md-2">
    <label>H.P</label>
    <input v-model="form.hpPrice" readonly class="form-control" />
  </div>

  <div class="form-group col-md-2">
    <label>Salt</label>
    <input v-model="form.salt" class="form-control" @change="calculatePrice" @keyup="calculatePrice" />
  </div>

  <div class="form-group col-md-2">
    <label>5 Salt</label>
    <input v-model="form.fiveSalt" readonly class="form-control" />
  </div>

  <div class="form-group col-md-3">
    <label>Start Price</label>
    <input v-model="form.startPrice" readonly class="form-control" />
  </div>

</div>
                <div class="form-group col-md-6 col-xl-3">
                  <div class="input-group">
                    <label for="itemCode" class="col-md-12">{{ $t('Item code') }}
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
                  <v-select v-model="form.productTax" :options="taxes" label="code"
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
                    :placeholder="$t('Enter purchase price')" @change="calculatePrice" @keyup="calculatePrice" />
                  <has-error :form="form" field="purchasePrice" />
                </div>
                <div class="form-group col-md-6" :class="form.itemType === 'service' ? 'col-xl-3' : 'col-xl-4'">
                  <label for="regularPrice">{{ $t('Call Center Price') }}
                    <span class="required">*</span></label>
                  <input id="regularPrice" v-model="form.regularPrice" type="number" step="any" min="0"
                    class="form-control" :class="{ 'is-invalid': form.errors.has('regularPrice') }" name="regularPrice"
                    :placeholder="$t('Enter call center price')
                      " @change="calculatePrice" @keyup="calculatePrice" :readonly="form.productType === 'battery'" />
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
                  <label for="alertQuantity">{{ $t('Alert Quantity') }}
                  </label>
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
                <i class="fas fa-save" /> {{ $t('Save') }}
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
    return { title: this.$t('Create Item') }
  },
  data: () => ({
  brands: [],
    breadcrumbsCurrent: 'Create Item',
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
        name: 'Create',
        url: '',
      },
    ],
    form: new Form({
      itemType: 'product',
      itemName: '',
      itemCode: '',
      itemModel: '',
      barcodeSymbology: 'CODE128',
      subCategory: '',
      // brand: '',
      itemUnit: '',
      productTax: '',
      taxType: 'Exclusive',
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
      productType: 'battery',
      voltage: '',
      capacity: '',
      warranty: '',
      batteryType: '',
      purchasePrice: '',
        brand: '',
  productCode: '',
  amp: '',
  cca: '',
  dimension: '',
  unitPrice: '',
  vat: '',
  vatAmount: '',
  netAmount: '',
  costPrice: '',
  price10: '',
  price15: '',
  price5AED: '',
  hpPrice: '',
  salt: '',
  fiveSalt: '',
  startPrice: '',
    }),
    options: [],
    units: [],
    // brands: [],
    taxes: [],
    prefix: '',
    url: null,
  }),
  computed: {
    ...mapGetters('operations', ['items', 'appInfo']),
  },
  created() {
    this.getSubCategories()
    this.getUnits()
    // this.getBrands()
    this.getTaxes()
    this.getItemCode()
    this.getBrands()
  },
  methods: {
    // get all product categories
    async getSubCategories() {
      await this.$store.dispatch('operations/allData', {
        path: '/api/all-product-sub-categories',
      })
    },
    //get all brands
    async getBrands() {
  const { data } = await axios.get('/api/all-brands')
  this.brands = data
},

    // get all brands
    // async getBrands() {
    //   const { data } = await axios.get(
    //     window.location.origin + '/api/all-brands'
    //   )
    //   this.brands = data.data
    // },

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
      // assign default vat rate
      if (this.taxes && this.taxes.length > 0) {
        let defaultVatRateSlug = this.appInfo.defaultVatRateSlug;
        this.form.productTax = this.taxes.find(
          tax => tax.slug === defaultVatRateSlug
        )
      }
      this.calculatePrice()
    },

    // get item code
    async getItemCode() {
      const { data } = await axios.get(
        window.location.origin + '/api/generate-itemcode'
      )
      this.form.itemCode = data.code
      this.prefix = data.prefix
    },

    // calculate selling price
    calculatePrice() {
      if (this.form.productType === 'battery') {
        const purchasePrice = Number(this.form.purchasePrice) || 0
        const salt = Number(this.form.salt) || 0
        const taxRate = Number(this.form.productTax?.rate || 0)
        const taxFactor = taxRate / 100

        // Step 1: Purchase Price + VAT = Unit/Net Price
        const vatAmount = purchasePrice * taxFactor
        const netAmount = purchasePrice + vatAmount

        // Step 2: Unit Price + Salt = Cost Price
        const costPrice = netAmount + salt

        // Step 3: Cost Price + VAT on Cost = Call Center Price
        const costVat = costPrice * taxFactor
        const callCenterPrice = costPrice + costVat

        // Discount applied on Call Center Price
        const discount = Number(this.form.discount) || 0
        const discountAmount = (callCenterPrice * discount) / 100
        const sellingPrice = callCenterPrice - discountAmount

        // All output fields
        this.form.vat = taxRate
        this.form.vatAmount = Number(vatAmount.toFixed(2))
        this.form.unitPrice = Number(netAmount.toFixed(2))
        this.form.costPrice = Number(costPrice.toFixed(2))
        this.form.netAmount = Number(netAmount.toFixed(2))
        this.form.regularPrice = Number(callCenterPrice.toFixed(2))
        this.form.sellingPrice = Number(sellingPrice.toFixed(2))

        // Derived prices based on Call Center Price
        this.form.price10 = Number((callCenterPrice * 0.9).toFixed(2))
        this.form.price15 = Number((callCenterPrice * 0.85).toFixed(2))
        this.form.price5AED = Number((callCenterPrice + (salt / 7)).toFixed(2))
        this.form.hpPrice = Number(((callCenterPrice + (salt / 7)) * 1.3).toFixed(2))

        // Salt-based pricing
        this.form.fiveSalt = Number((salt / 7).toFixed(2))
        this.form.startPrice = Number((netAmount + 100).toFixed(2))
        return
      }

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
          this.$t('Error!'),
          this.$t('Please select a valid thumbnail with size less than 2 MB'),
          'error'
        )
      }
    },

    // save product
    async saveProduct() {
      await this.form
        .post(window.location.origin + '/api/products')
        .then(() => {
          toast.fire({
            type: 'success',
            title: this.$t('Product added successfully'),
          })
          this.$router.push({ name: 'products.index' })
        })
        .catch(() => {
          toast.fire({ type: 'error', title: this.$t('Opps...something went wrong') })
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
