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
  <section
    v-show="isActive"
    :aria-hidden="!isActive"
    class="tabs-component-panel"
    :id="computedId"
    role="tabpanel"
  >
    <router-view v-if="isActive" />
  </section>
</template>

<script>
/* eslint-disable */
export default {
  props: {
    id: { default: null },
    name: { required: true },
    value: { default: "" },
    otherValue: { default: "" },
    source: { default: "" },
    isDisabled: { default: false },
    to: { default: "" },
    loading: { default: true },
    tooltip: { default: "" },
  },
  data: () => ({
    isActive: false,
    isVisible: true,
  }),
  watch: {
    value(newVal) {
      this.value = newVal;
    },
    otherValue(newVal) {
      this.otherValue = newVal;
    },
    loading(newVal) {
      this.loading = newVal;
    },
  },
  computed: {
    header() {
      let value = "";
      if (!this.loading) {
        value = `<span class="valueTab">${this.value}</span>`;
      } else {
        value = `
        <svg width="48" height="12" viewBox="0 0 120 30" xmlns="http://www.w3.org/2000/svg" fill="#363a41">
          <circle cx="15" cy="15" r="15">
              <animate attributeName="r" from="15" to="15"
                        begin="0s" dur="0.8s"
                        values="15;9;15" calcMode="linear"
                        repeatCount="indefinite" />
              <animate attributeName="fill-opacity" from="1" to="1"
                        begin="0s" dur="0.8s"
                        values="1;.5;1" calcMode="linear"
                        repeatCount="indefinite" />
          </circle>
          <circle cx="60" cy="15" r="9" fill-opacity="0.3">
              <animate attributeName="r" from="9" to="9"
                        begin="0s" dur="0.8s"
                        values="9;15;9" calcMode="linear"
                        repeatCount="indefinite" />
              <animate attributeName="fill-opacity" from="0.5" to="0.5"
                        begin="0s" dur="0.8s"
                        values=".5;1;.5" calcMode="linear"
                        repeatCount="indefinite" />
          </circle>
          <circle cx="105" cy="15" r="15">
              <animate attributeName="r" from="15" to="15"
                        begin="0s" dur="0.8s"
                        values="15;9;15" calcMode="linear"
                        repeatCount="indefinite" />
                <animate attributeName="fill-opacity" from="1" to="1"
                        begin="0s" dur="0.8s"
                        values="1;.5;1" calcMode="linear"
                        repeatCount="indefinite" />
          </circle>
        </svg>`;
      }
      return `
      <div class="flex-item">
        <span class="titleTab">
          ${this.name.toUpperCase()}
          <div class="tooltip-tab">
            <i class="material-icons info">info_outlined</i>
            <span class="tooltip-text-tab">${this.tooltip}</span>
          </div>
        </span>
      </div>
      <div class="flex-item">
        ${value}
      </div>
      <div class="flex-item otherValueTab" :class={ 'display': ${
        this.otherValue !== "" ? "block" : "none"
      }}>
        <span class="otherValueTab">${this.otherValue}</span>
      </div>
      <div class="flex-item sourceTab">
        <span class="sourceTab">${this.source}</span>
      </div>`;
    },
    computedId() {
      return this.id ? this.id : this.name.toLowerCase().replace(/ /g, "-");
    },
    hash() {
      if (this.isDisabled) {
        return "#";
      }
      return `#${this.computedId}`;
    },
  },
};
</script>

<style lang="scss">
.flex-item {
  &:nth-child(1) {
    order: 0;
    flex: 0 1 auto;
    align-self: flex-start;
    justify-content: baseline;
    align-items: baseline;
  }
  &:nth-child(2) {
    order: 0;
    flex: 0 1 auto;
    align-self: flex-start;
  }
  &:nth-child(3) {
    order: 0;
    flex: 0 1 auto;
    align-self: flex-start;
  }
  &:nth-child(4) {
    order: 0;
    flex: 0 1 auto;
    align-self: flex-start;
    flex-grow: 1;
    margin-top: 20px;
  }
}

.titleTab {
  color: #363a41 !important;
  font-family: "Open Sans";
  font-size: 10px;
  letter-spacing: 0;
  line-height: 14px;
}
.valueTab {
  color: #363a41 !important;
  font-family: "Open Sans";
  font-size: 24px;
  font-weight: bold;
  letter-spacing: 0;
  line-height: 33px;
}
.otherValueTab {
  height: 14px;
  color: #363a41 !important;
  font-family: "Open Sans";
  font-size: 10px;
  letter-spacing: 0;
  line-height: 14px;
}
.sourceTab {
  color: #6c868e !important;
  font-family: "Open Sans";
  font-size: 10px;
  letter-spacing: 0;
  line-height: 14px;
  width: 100%;
  white-space: nowrap;
  overflow: hidden;
  width: 100%;
  display: inline-block;
  text-overflow: ellipsis;
}
.tabs-component-panel {
  align-items: flex-start;
  justify-content: flex-start;
  color: inherit;
  display: flex;
  flex-direction: column;
}
.tooltip-tab {
  position: relative;
  display: inline-block;
  .tooltip-text-tab {
    visibility: hidden;
    width: 300px;
    background-color: #363a41;
    color: #fff;
    text-align: center;
    border-radius: 3px;
    padding: 20px;
    position: absolute;
    z-index: 9999999;
    bottom: 200%;
    left: 50%;
    margin-left: -60px;
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 1em;
    font: 400 12px/1.42857 Open Sans, Helvetica, Arial, sans-serif;
    &::after {
      content: "";
      position: absolute;
      top: 100%;
      left: 50%;
      margin-left: -5px;
      border-width: 5px;
      border-style: solid;
      border-color: transparent transparent transparent transparent;
    }
  }
  &:hover {
    .tooltip-text-tab {
      visibility: visible;
      opacity: 1;
    }
  }
}

.info {
  font-size: 1.7em !important;
  vertical-align: middle !important;
  color: #7b9399;
}

@media (max-width: 1400px) {
  .titleTab {
    color: #363a41 !important;
    font-family: "Open Sans";
    font-size: 10px !important;
    letter-spacing: 0;
    line-height: 14px;
  }
  .info {
    font-size: 0.875em !important;
    vertical-align: middle !important;
    color: #7b9399;
  }
  .valueTab {
    color: #363a41 !important;
    font-family: "Open Sans";
    font-size: 1em;
    font-weight: bold;
    letter-spacing: 0;
    line-height: 33px;
  }
}
@media (max-width: 1024px) {
  .titleTab {
    color: #363a41 !important;
    font-family: "Open Sans";
    font-size: 7px !important;
    letter-spacing: 0;
    line-height: 14px;
  }
  .info {
    font-size: 1em !important;
    vertical-align: middle !important;
    color: #7b9399;
  }
  .valueTab {
    color: #363a41 !important;
    font-family: "Open Sans";
    font-size: 1em;
    font-weight: bold;
    letter-spacing: 0;
    line-height: 33px;
  }
}
</style>
