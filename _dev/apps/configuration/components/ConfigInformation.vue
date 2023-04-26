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
  <aside class="config-information">
    <div class="titleWrapper">
      <div class="logoWrapper">
        <img src="@/configuration/assets/logo.png" alt="Logo PrestaShop" class="logo" />
      </div>
      <h1 class="title">
        PrestaShop
        <span class="font-normal" style="color: #6b868f">Account</span>
      </h1>
    </div>

    <div class="description">
      <h2 class="text">{{ $t("configure.incentivePanel.howTo") }}</h2>
    </div>
  </aside>
</template>

<script setup lang="ts"></script>

<style lang="scss" scoped>
.config-information {
  @apply psacc-flex psacc-flex-col psacc-justify-around psacc-p-4 psacc-bg-brand-dark psacc-rounded #{!important};

  & .titleWrapper {
    @apply psacc-flex-1 psacc-flex psacc-justify-start psacc-items-center #{!important};
  }

  & .logoWrapper {
    @apply psacc-w-10 psacc-h-10 psacc-p-1 psacc-mr-2  psacc-bg-white psacc-rounded-full #{!important};
  }

  & .title {
    @apply psacc-m-0 psacc-text-white psacc-text-base #{!important};
  }

  & .description {
    @apply psacc-flex-1 psacc-mt-4 #{!important};
  }

  & .text {
    @apply psacc-m-0 psacc-text-sm psacc-text-white #{!important};
  }

  @screen md {
    @apply psacc-flex-row psacc-items-center psacc-p-8 #{!important};

    & .logoWrapper {
      @apply psacc-mr-4 #{!important};
    }

    & .title {
      @apply psacc-flex psacc-flex-col #{!important};
    }

    & .description {
      @apply psacc-mt-0 #{!important};
    }

    & .text {
      @apply psacc-text-2xl #{!important};
    }
  }

  @screen lg {
    @apply psacc-px-12 psacc-py-16 #{!important};

    & .title {
      @apply psacc-block #{!important};
    }
  }
}
</style>
