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
              {{ $t('Create Brand') }}
            </h3>
            <router-link :to="{ name: 'productSubCats.index' }" class="btn btn-dark float-right">
              <i class="fas fa-long-arrow-alt-left" /> {{ $t('Back') }}
            </router-link>
          </div>
          <!-- /.card-header -->
          <!-- form start -->
          <form role="form" @submit.prevent="saveSubCategory" @keydown="form.onKeydown($event)">
            <div class="card-body">
              <div class="form-group">
                <label for="name">{{ $t('Name') }}
                  <span class="required">*</span></label>
                <input id="name" v-model="form.name" type="text" class="form-control"
                  :class="{ 'is-invalid': form.errors.has('name') }" name="name"
                  :placeholder="$t('Enter a name')" />
                <has-error :form="form" field="name" />
              </div>
              <div class="row">
                <div v-if="items" class="form-group col-md-6">
                  <label for="category">{{ $t('Category Name') }}
                    <span class="required">*</span></label>
                  <v-select v-model="form.category" :options="items" label="name"
                    :class="{ 'is-invalid': form.errors.has('category') }" name="category"
                    :placeholder="$t('Select a category')" />
                  <has-error :form="form" field="category" />
                </div>
                <div class="form-group col-md-6">
                  <label for="status">{{ $t('Status') }}</label>
                  <select id="status" v-model="form.status" class="form-control"
                    :class="{ 'is-invalid': form.errors.has('status') }">
                    <option value="1">{{ $t('Active') }}</option>
                    <option value="0">{{ $t('Inactive') }}</option>
                  </select>
                  <has-error :form="form" field="status" />
                </div>
              </div>
              <div class="form-group">
                <label for="note">{{ $t('Note') }}</label>
                <textarea id="note" v-model="form.note" class="form-control"
                  :class="{ 'is-invalid': form.errors.has('note') }" :placeholder="$t('Write your note here!')" />
                <has-error :form="form" field="note" />
              </div>
              <div class="form-group">
  <label for="code">
    {{ $t('Brand Code') }}
    <span class="required">*</span>
  </label>

  <input
    id="code"
    v-model="form.code"
    type="text"
    class="form-control"
    :class="{ 'is-invalid': form.errors.has('code') }"
    name="code"
    placeholder="Enter code (e.g. APS-10)"
  />

  <has-error :form="form" field="code" />
</div>
            </div>
            <!-- /.card-body -->
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
export default {
  middleware: ['auth', 'check-permissions'],
  metaInfo() {
    return { title: this.$t('Create Brand') }
  },
  data: () => ({
    breadcrumbsCurrent: 'Create Brand',
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
        name: 'Brands',
        url: 'productSubCats.index',
      },
      {
        name: 'Create',
        url: '',
      },
    ],
    form: new Form({
      name: '',
      note: '',
      status: 1,
      category: null,
        code: '',   // 👈 ADD THIS
    }),
    options: [],
  }),
  computed: {
    ...mapGetters('operations', ['items']),
  },
  created() {
    this.getCatgories()
  },
  methods: {
    // get all product categories
    async getCatgories() {
      await this.$store.dispatch('operations/allData', {
        path: '/api/all-product-categories',
      })
    },
    // save sub category
    async saveSubCategory() {
      await this.form
        .post(window.location.origin + '/api/product-sub-categories')
        .then(() => {
          toast.fire({
            type: 'success',
            title: this.$t('Brand added successfully'),
          })
          this.$router.push({ name: 'productSubCats.index' })
        })
        .catch(() => {
          toast.fire({ type: 'error', title: this.$t('Opps...something went wrong') })
        })
    },
  },
}
</script>

