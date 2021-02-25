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
  <div
    class="tab-pane"
    v-show="active"
    :id="id || label"
    :class="{ active: active }"
    :aria-expanded="active"
    :is-external="isExternal"
    :material-icon="materialIcon"
  >
    <router-view v-if="active" />
  </div>
</template>
<script>
export default {
  name: "TabPane",
  props: ["label", "id", "to", "isExternal", "materialIcon"],
  inject: ["addTab", "removeTab"],
  data() {
    return {
      active: false,
    };
  },
  mounted() {
    this.addTab(this);
  },
  destroyed() {
    if (this.$el && this.$el.parentNode) {
      this.$el.parentNode.removeChild(this.$el);
    }
    this.removeTab(this);
  },
};
</script>
<style></style>
