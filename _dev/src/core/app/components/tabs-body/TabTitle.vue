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
  <div class="tabs-component">
    <ul role="tablist" class="tabs-component-tabs">
      <li
        v-for="(tab, i) in tabs"
        :key="i"
        :class="{ 'is-active': tab.isActive, 'is-disabled': tab.isDisabled }"
        class="tabs-component-tab tw-w-1/4"
        role="presentation"
        v-show="tab.isVisible"
      >
        <a
          v-html="tab.header"
          :aria-controls="tab.hash"
          :aria-selected="tab.isActive"
          @click="selectTab(tab.hash, tab.to, $event)"
          :href="tab.hash"
          class="tabs-component-tab-a"
          role="tab"
        />
      </li>
    </ul>
    <div class="tabs-component-panels">
      <slot />
    </div>
  </div>
</template>

<script>
import expiringStorage from "./expiringStorage";

export default {
  props: {
    // eslint-disable-next-line vue/require-prop-types
    cacheLifetime: {
      default: 5,
    },
    options: {
      type: Object,
      required: false,
      default: () => ({
        useUrlFragment: true,
        defaultTabHash: null,
      }),
    },
  },
  data: () => ({
    tabs: [],
    activeTabHash: "",
    activeTabIndex: 0,
    lastActiveTabHash: "",
  }),
  computed: {
    storageKey() {
      return `tabs-component.cache.${window.location.host}${window.location.pathname}`;
    },
  },
  created() {
    this.tabs = this.$children;
  },
  mounted() {
    window.addEventListener("hashchange", () =>
      this.selectTab(window.location.hash)
    );
    if (this.findTab(window.location.hash)) {
      this.selectTab(window.location.hash);
      return;
    }
    const previousSelectedTabHash = JSON.parse(
      expiringStorage.get(this.storageKey)
    );
    if (previousSelectedTabHash && this.findTab(previousSelectedTabHash.hash)) {
      this.selectTab(previousSelectedTabHash.hash, previousSelectedTabHash.to);
      return;
    }
    if (
      this.options.defaultTabHash !== null &&
      this.findTab(`#${this.options.defaultTabHash}`)
    ) {
      this.selectTab(`#${this.options.defaultTabHash}`);
      return;
    }
    if (this.tabs.length) {
      this.selectTab(this.tabs[0].hash, this.tabs[0].to);
    }
  },
  methods: {
    findTab(hash) {
      return this.tabs.find((tab) => tab.hash === hash);
    },
    selectTab(selectedTabHash, to, event) {
      // See if we should store the hash in the url fragment.
      if (event && !this.options.useUrlFragment) {
        event.preventDefault();
      }
      const selectedTab = this.findTab(selectedTabHash);
      if (!selectedTab) {
        return;
      }
      if (selectedTab.isDisabled) {
        event.preventDefault();
        return;
      }
      if (this.lastActiveTabHash === selectedTab.hash) {
        this.$emit("clicked", { tab: selectedTab });
        return;
      }
      this.tabs.forEach((tab) => {
        tab.isActive = tab.hash === selectedTab.hash;
      });
      this.$emit("changed", { tab: selectedTab });
      this.activeTabHash = selectedTab.hash;
      this.activeTabIndex = this.getTabIndex(selectedTabHash);
      this.lastActiveTabHash = this.activeTabHash;
      expiringStorage.set(
        this.storageKey,
        JSON.stringify({ hash: selectedTab.hash, to: selectedTab.to }),
        this.cacheLifetime
      );
      this.$router.replace(to);
    },
    setTabVisible(hash, visible) {
      const tab = this.findTab(hash);
      if (!tab) {
        return undefined;
      }
      tab.isVisible = visible;
      if (tab.isActive) {
        // If tab is active, set a different one as active.
        tab.isActive = visible;
        this.tabs.every((tabEvery) => {
          if (tabEvery.isVisible) {
            tabEvery.isActive = true;
            return false;
          }
          return true;
        });
      }
      return undefined;
    },
    getTabIndex(hash) {
      const tab = this.findTab(hash);

      return this.tabs.indexOf(tab);
    },
    getTabHash(index) {
      const tab = this.tabs.find(
        (tabFind) => this.tabs.indexOf(tabFind) === index
      );
      if (!tab) {
        return undefined;
      }
      return tab.hash;
    },
    getActiveTab() {
      return this.findTab(this.activeTabHash);
    },
    getActiveTabIndex() {
      return this.getTabIndex(this.activeTabHash);
    },
  },
};
</script>

<style scoped lang="scss">
ul {
  padding: 0;
}
.tabs-component {
  background-color: #f1f1f1;
  margin: 1em 0;
}
.tabs-component-tabs {
  border: solid 1px #ddd;
  border-radius: 6px;
  margin-bottom: 0px !important;
}
.tabs-component-tab {
  color: #999;
  font-size: 14px;
  font-weight: 600;
  margin-right: 0;
  list-style: none;
  z-index: 1;
  padding-bottom: 5px;
  &:not(:last-child) {
    border-bottom: dotted 0px #ddd;
  }
  &:hover {
    color: #666;
  }
}
.tabs-component-tab.is-active {
  color: #000;
}
.tabs-component-tab.is-disabled {
  * {
    color: #cdcdcd;
    cursor: not-allowed !important;
  }
}
.tabs-component-tab-a {
  align-items: flex-start;
  justify-content: flex-start;
  color: inherit;
  display: flex;
  flex-direction: column;
  padding: 0.75em 1em;
  text-decoration: none !important;
}
.tabs-component-panels {
  padding: 32px;
}
@media (min-width: 700px) {
  .tabs-component-tabs {
    border: 0;
    align-items: stretch;
    display: flex;
    justify-content: flex-start;
    margin-bottom: -1px;
  }
  .tabs-component-tab {
    background-color: transparent;
    flex-grow: 1;
    transform: translateY(0);
    transition: transform 0.3s ease;
    height: 122px;
  }
  .tabs-component-tab.is-active {
    border: solid 0px #ddd;
    border-bottom: solid 1px #fff;
    border-top-width: 3px;
    border-top-color: #34219e;
    border-radius: 3px 3px 0 0;
    z-index: 2;
    transform: translateY(0);
    background-color: #fff;
  }
  .tabs-component-panels {
    border-top-left-radius: 0;
    background-color: #fff;
    border: solid 0px #ddd;
    border-radius: 0 6px 6px 6px;
  }
}
</style>
