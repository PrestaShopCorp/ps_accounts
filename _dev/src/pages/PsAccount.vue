<template>
  <div id="app" class="ps_account text-center">
    <button @click="launchSvcUiUrl()" class="btn btn-primary">
      {{ $t('general.startOnboarding') }}
    </button>
    <div
      class="container"
      v-if="!isShopContext"
    >
      <b-alert
        variant="warning"
        show
      >
        <h2>{{ $t('general.multiShop.title') }}</h2>
        <p>{{ $t('general.multiShop.subtitle') }}</p>
        <p>{{ $t('general.multiShop.chooseOne') }}</p>
        <b-list-group
          v-for="group in shopsTree"
          :key="group.id"
          class="mt-3 mb-3 col-4"
        >
          <p class="text-muted">
            {{ $t('general.multiShop.group') }} {{ group.name }}
          </p>
          <b-list-group-item
            v-for="shop in group.shops"
            :key="shop.id"
            :href="shop.url"
          >
            {{ shop.name }}
          </b-list-group-item>
        </b-list-group>
        <p>{{ $t('general.multiShop.tips') }}</p>
      </b-alert>
    </div>
  </div>
</template>

<script>
import Vuex from 'vuex'
import store from '../store/index'

export default {
  store,
  name: 'PsAccount',
  created() {
    this.$store.watch(
      (state, getters) => getters.svcUiUrl,
      (newValue, oldValue) => {
        if (null !== newValue) {
          this.connectSvcUi(newValue)
        }
      }
    )
  },
  computed: {
    isShopContext() {
      console.log(this.$store.state.psaccounts.isShopContext)
      return this.$store.state.psaccounts.isShopContext;
    },
    shopsTree() {
      return this.$store.state.psaccounts.shopsTree;
    },
  },
  methods: {
    connectSvcUi(url) {
      window.location.replace(url)
    },
    launchSvcUiUrl() {
      this.$store.dispatch({
        type: 'setSvcUiUrl',
        svcUiDomainName: process.env.VUE_APP_UI_SVC_URL,
        protocolBo: window.location.protocol.slice(0, -1),
        domainNameBo: window.location.host,
      })
    },
  },
}
</script>

<style lang="scss">
  .bootstrap.panel{
    display: none!important;
  }
  #app {
    @import '~bootstrap-vue/dist/bootstrap-vue';
    @import '~prestakit/dist/css/bootstrap-prestashop-ui-kit';
  }
  #app {
    margin: 0;
    font-family: Open Sans,Helvetica,Arial,sans-serif;
    font-size: 14px;
    font-size: .875rem;
    font-weight: 400;
    line-height: 1.5;
    color: #363a41;
    text-align: left;
  }
  #app .card-header, .card-header .card-header-title {
    font-weight: 600;
    line-height: 24px;
    line-height: 1.5rem;
  }
  #app .card-header .main-header #header-search-container .input-group:before,
  .card-header .material-icons, .card-header .ps-tree-items .tree-name button:before,
  .main-header #header-search-container .card-header .input-group:before,
  .ps-tree-items .tree-name .card-header button:before {
    color: #6c868e;
    margin-right: 5px;
  }
  #app .form-group.has-danger:after, #app .form-group.has-success:after,
  #app .form-group.has-warning:after {
    right: 10px;
  }
  .nobootstrap {
    background-color: unset !important;
    padding: 100px 10px 100px;
    min-width: unset !important;
  }
  .nobootstrap .form-group>div {
    float: unset;
  }
  .nobootstrap fieldset {
    background-color: unset;
    border: unset;
    color: unset;
    margin: unset;
    padding: unset;
  }
  .nobootstrap label {
    color: unset;
    float: unset;
    font-weight: unset;
    padding: unset;
    text-align: unset;
    text-shadow: unset;
    width: unset;
  }
  .nobootstrap .table tr th {
    background-color: unset;
    color: unset;
    font-size: unset;
  }
  .nobootstrap .table.table-hover tbody tr:hover {
      color: #fff;
  }
  .nobootstrap .table.table-hover tbody tr:hover a {
      color: #fff !important;
  }
  .nobootstrap .table tr td {
      border-bottom: unset;
      color: unset;
  }
  .nobootstrap .table {
    background-color: unset;
    border: unset;
    border-radius: unset;
    padding: unset;
  }
  .page-sidebar.mobile #content.nobootstrap {
    margin-left: unset;
  }
  .page-sidebar-closed:not(.mobile) #content.nobootstrap {
    padding-left: 50px;
  }
  .material-icons.js-mobile-menu {
    display: none !important
  }
  @import url('https://fonts.googleapis.com/icon?family=Material+Icons');
</style>



