<template>
  <div>
    <div class="form-group">
      <input class="form-control"
             max="255" min="0" pattern="^[a-zA-Z0-9][a-zA-Z0-9@:.+_-]*$"
             placeholder="Package name" type="text"
             v-model="query"/>
    </div>
    <table class="table table-striped table-bordered table-sm">
      <thead>
      <tr>
        <th scope="col">Package</th>
        <th scope="col">Popularity</th>
      </tr>
      </thead>
      <tbody>
      <tr :key="id" v-for="(pkg, id) in packagePopularities">
        <td class="text-nowrap">
          <router-link :to="{name: 'package', params: {package: pkg.name}}">{{ pkg.name }}</router-link>
        </td>
        <td class="w-75">
          <div :title="pkg.popularity+'%'" class="progress bg-transparent">
            <div :aria-valuenow="pkg.popularity" :style="'width:'+pkg.popularity+'%'"
                 aria-valuemax="100" aria-valuemin="0" class="progress-bar bg-primary"
                 role="progressbar">
              {{ pkg.popularity > 5 ? pkg.popularity + '%' : ''}}
            </div>
          </div>
        </td>
      </tr>
      </tbody>
    </table>
    <div class="alert alert-danger" role="alert" v-if="error">{{ error }}</div>

    <infinite-loading :identifier="infiniteId" @infinite="infiniteHandler">
      <div slot="spinner">
        <div class="spinner-border text-primary" role="status">
          <span class="sr-only">Loading...</span>
        </div>
      </div>

      <div class="alert alert-info" role="alert" slot="no-more">{{ packagePopularities.length }} packages found</div>

      <div class="alert alert-warning" role="alert" slot="no-results">No packages found</div>
    </infinite-loading>
  </div>
</template>

<script>
import InfiniteLoading from 'vue-infinite-loading'

export default {
  name: 'PackageList',
  inject: ['apiPackagesService'],
  props: {
    initialQuery: {
      type: String,
      required: false
    },
    limit: {
      type: Number,
      required: false
    }
  },
  components: {
    InfiniteLoading
  },
  data () {
    return {
      packagePopularities: [],
      query: this.initialQuery,
      error: '',
      offset: 0,
      infiniteId: +new Date()
    }
  },
  watch: {
    query () {
      if (this.query.length > 255) {
        this.query = this.query.substring(0, 255)
      }
      this.query = this.query.replace(/(^[^a-zA-Z0-9]|[^a-zA-Z0-9@:.+_-]+)/, '')

      this.offset = 0
      this.fetchData().then(data => {
        this.offset += this.limit
        this.packagePopularities = data.packagePopularities
        this.infiniteId++
      })
    }
  },
  methods: {
    fetchData () {
      this.loading = true
      return this.apiPackagesService
        .fetchPackageList({
          query: this.query,
          limit: this.limit,
          offset: this.offset
        })
        .catch(error => { this.error = error })
        .finally(() => { this.loading = false })
    },
    infiniteHandler ($state) {
      this.fetchData()
        .then(data => {
          if (data.count > 0) {
            this.offset += this.limit
            this.packagePopularities.push(...data.packagePopularities)
            $state.loaded()
          } else {
            $state.complete()
          }
        })
    }
  },
  metaInfo () {
    if (this.packagePopularities.length < 1 || this.error) {
      return { meta: [{ vmid: 'robots', name: 'robots', content: 'noindex' }] }
    }
  }
}
</script>
