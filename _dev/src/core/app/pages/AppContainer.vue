<!--**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *-->
<template>
  <router-view />
</template>

<script>
// import * as Sentry from "@sentry/browser";

export default {
  created() {
    this.updateCustomerAppContainer();
  },
  methods: {
    updateCustomerAppContainer() {
      if (this.$router.history.current.fullPath === "/") {
        this.$router.replace(this.$store.state.app.app).catch(() => {}); // send by module php for change
        // if (this.$segment !== undefined) {
        //   this.$segment.identify(this.$store.state.app.shop.domain, {
        //     name: this.$store.state.app.shop.url,
        //     email: this.$store.state.app.email,
        //     language: this.$store.state.app.i18n.isoCode,
        //     version_ps: this.$store.state.app.version_ps,
        //     version_module: this.$store.state.app.version_module,
        //     module: "ps_accounts",
        //   });
        //   Sentry.configureScope(function(scope) {
        //     scope.setTag("version_module", this.$store.state.app.version_module);
        //     scope.setTag("version_ps", this.$store.state.app.version_module);
        //     scope.setTag("language", this.$store.state.app.i18n.isoCode);
        //     scope.setUser({
        //       email: this.$store.state.app.email,
        //       domain_shop: this.$store.state.app.shop.domain,
        //       id: this.$store.state.app.shopId,
        //     });
        //   });
        // }
      }
    },
  },
  watch: {
    $route() {
      this.updateCustomerAppContainer();
    },
  },
};
</script>

<style lang="scss">
.nobootstrap {
  background-color: unset !important;
  padding: 100px 10px 100px;
  min-width: unset !important;
  .form-group {
    > div {
      float: unset;
    }
  }
  fieldset {
    background-color: unset;
    border: unset;
    color: unset;
    margin: unset;
    padding: unset;
  }
  label {
    color: unset;
    float: unset;
    font-weight: unset;
    padding: unset;
    text-align: unset;
    text-shadow: unset;
    width: unset;
  }
  .table {
    tr {
      th {
        background-color: unset;
        color: unset;
        font-size: unset;
      }
      td {
        border-bottom: unset;
        color: unset;
      }
    }
    background-color: unset;
    border: unset;
    border-radius: unset;
    padding: unset;
  }
  .table.table-hover {
    tbody {
      tr {
        &:hover {
          color: #fff;
          a {
            color: #fff !important;
          }
        }
      }
    }
  }
}

.modal-bg-coming-soon {
  height: 24px;
  width: 75px;
  border-radius: 2px;
  background-color: #fafbfc;
}
.modal-coming-soon {
  color: #ff0076;
  font-family: "Open Sans";
  font-size: 8px;
  font-weight: bold;
  letter-spacing: 0;
  line-height: 11px;
  text-align: center;
}
.modal-card-description-footer {
  color: #363a41;
  font-family: "Open Sans";
  font-size: 0.875em;
  letter-spacing: 0;
  line-height: 1.188em;
  vertical-align: middle;
  text-align: center;
}

.modal-upcoming-container {
  display: flex;
  flex-direction: column;
  flex-wrap: wrap;
  justify-content: space-around;
  align-content: flex-start;
  align-items: center;
  margin: 24px;
}

.modal-upcoming-item {
  &:nth-child(1) {
    order: 0;
    flex: 0 1 auto;
    align-self: auto;
    align-content: center;
    justify-content: center;
    align-items: center;
  }
  &:nth-child(2) {
    order: 0;
    flex: 0 1 auto;
    align-self: auto;
    align-content: center;
    justify-content: center;
    align-items: center;
  }
  &:nth-child(3) {
    order: 0;
    flex: 0 1 auto;
    align-self: auto;
    align-content: center;
    justify-content: center;
    align-items: center;
  }
}
</style>
