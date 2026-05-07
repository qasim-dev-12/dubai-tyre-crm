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
              {{ $t('Edit Brand') }}
            </h3>
            <router-link :to="{ name: 'productSubCats.index' }" class="btn btn-dark float-right">
              <i class="fas fa-long-arrow-alt-left" /> {{ $t('Back') }}
            </router-link>
          </div>
          <!-- /.card-header -->
          <!-- form start -->
          <form role="form" @submit.prevent="updateSubCategory" @keydown="form.onKeydown($event)">
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
            </div>
            <!-- /.card-body -->
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
    return { title: this.$t('Edit Brand') }
  },
  data: () => ({
    breadcrumbsCurrent: 'Edit Brand',
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
        name: 'Edit',
        url: '',
      },
    ],
    form: new Form({
      name: '',
      note: '',
      status: 1,
      category: null,
    }),
    loading: true,
  }),
  computed: {
    ...mapGetters('operations', ['items']),
  },
  created() {
    this.getSubCategory()
    this.getCatgories()
  },
  methods: {
    // get all product categories
    async getCatgories() {
      await this.$store.dispatch('operations/allData', {
        path: '/api/all-product-categories',
      })
    },
    // get category
    async getSubCategory() {
      const { data } = await axios.get(
        window.location.origin +
        '/api/product-sub-categories/' +
        this.$route.params.slug
      )
      this.form.name = data.data.name
      this.form.note = data.data.note
      this.form.status = data.data.status
      this.form.category = data.data.category
    },
    // update category
    async updateSubCategory() {
      await this.form
        .patch(
          window.location.origin +
          '/api/product-sub-categories/' +
          this.$route.params.slug
        )
        .then((response) => {
          toast.fire({
            type: 'success',
            title: this.$t('Brand updated successfully'),
          })
          this.$router.push({ name: 'productSubCats.index' })
        })
        .catch(() => {
          toast.fire({
            type: 'error',
            title: this.$t('Opps...something went wrong'),
          })
        })
    },
  },
}
</script>
