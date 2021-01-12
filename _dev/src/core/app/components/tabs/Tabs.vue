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
  <div>
    <div
      :class="[
        { 'col-md-4': vertical && !tabNavWrapperClasses },
        { 'col-12': centered && !tabNavWrapperClasses },
        tabNavWrapperClasses,
      ]"
    >
      <ul
        class="nav nav-pills"
        role="tablist"
        :class="[
          `nav-pills-${type}`,
          { 'nav-pills-icons': square },
          { 'flex-column': vertical },
          { 'justify-content-center': centered },
          tabNavClasses,
        ]"
      >
        <li
          v-for="tab in tabs"
          v-bind="tabAttributes(tab.isExternal)"
          aria-expanded="true"
          :key="tab.id"
        >
          <router-link
            v-if="!tab.isExternal"
            active-class="active"
            data-toggle="tab"
            role="tablist"
            @click.prevent="activateTab(tab)"
            :aria-expanded="tab.active"
            class="nav-link"
            :to="tab.to"
          >
            <TabItemContent :tab="tab" />
          </router-link>
          <b-button
            v-else
            variant="link"
            class="tw-no-underline"
            :href="tab.to"
          >
            <span v-if="tab.materialIcon" class="material-icons">
              {{ tab.materialIcon }}
            </span>
            {{ tab.label }}
          </b-button>
        </li>
      </ul>
    </div>
    <div
      class="tab-content"
      :class="[
        { 'tab-space': !vertical },
        { 'col-md-8': vertical && !tabContentClasses },
        tabContentClasses,
      ]"
    >
      <slot />
    </div>
  </div>
</template>

<script>
/* eslint-disable */
export default {
  name: "Tabs",
  components: {
    TabItemContent: {
      props: ["tab"],
      render(h) {
        return h("div", [this.tab.$slots.label || this.tab.label]);
      },
    },
  },
  provide() {
    return {
      addTab: this.addTab,
      removeTab: this.removeTab,
    };
  },
  props: {
    type: {
      type: String,
      default: "primary",
      validator: (value) => {
        const acceptedValues = [
          "primary",
          "info",
          "success",
          "warning",
          "danger",
        ];
        return acceptedValues.indexOf(value) !== -1;
      },
    },
    activeTab: {
      type: String,
      default: "",
    },
    tabNavWrapperClasses: {
      type: [String, Object],
      default: "",
    },
    tabNavClasses: {
      type: [String, Object],
      default: "",
    },
    tabContentClasses: {
      type: [String, Object],
      default: "",
    },
    vertical: Boolean,
    square: Boolean,
    centered: Boolean,
    value: String,
  },
  data() {
    return {
      tabs: [],
    };
  },
  methods: {
    tabAttributes(isExternal) {
      return isExternal
        ? {
            class: "nav-item-external",
          }
        : {
            class: "nav-item active",
            "data-toggle": "tab",
            role: "tablist",
          };
    },
    findAndActivateTab(label) {
      const tabToActivate = this.tabs.find((t) => t.label === label);
      if (tabToActivate) {
        this.activateTab(tabToActivate);
      }
    },
    activateTab(tab) {
      if (this.handleClick) {
        this.handleClick(tab);
      }
      this.deactivateTabs();
      tab.active = true;
      this.$router.replace(tab.to);
    },
    deactivateTabs() {
      this.tabs.forEach((tab) => {
        tab.active = false;
      });
    },
    addTab(tab) {
      const index = this.$slots.default.indexOf(tab.$vnode);
      if (!this.activeTab && index === 0) {
        tab.active = true;
      }
      if (this.activeTab === tab.name) {
        tab.active = true;
      }
      this.tabs.splice(index, 0, tab);
    },
    removeTab(tab) {
      const { tabs } = this;
      const index = tabs.indexOf(tab);
      if (index > -1) {
        tabs.splice(index, 1);
      }
    },
  },
  mounted() {
    this.$nextTick(() => {
      if (this.value) {
        this.findAndActivateTab(this.value);
      }
    });
  },
  watch: {
    value(newVal) {
      this.findAndActivateTab(newVal);
    },
  },
};
</script>

<style scoped lang="scss">
#settingsApp {
  ul {
    background-color: #fff;
    width: 100%;
    position: fixed;
    margin-top: 0px;
    z-index: 499;
    border-top: 1px solid #dfdfdf;
    border-bottom: 1px solid #dfdfdf;

    li {
      cursor: pointer;

      a {
        color: #6c868e !important;

        &.active {
          color: #363a41 !important;
        }
        &:hover {
          color: #25b9d7 !important;
        }
      }
    }
  }
}
</style>
