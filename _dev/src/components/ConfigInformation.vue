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
      <img src="@/assets/prestashop-logo-2.png" class="logo" />
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
  @apply flex flex-col justify-around p-4 bg-brand-dark rounded #{!important};

  & .titleWrapper {
    @apply flex-1 flex justify-start items-center #{!important};
  }

  & .logo {
    @apply w-10 h-10 mr-2 #{!important};
  }

  & .title {
    @apply m-0 text-white text-base #{!important};
  }

  & .description {
    @apply flex-1 mt-4 #{!important};
  }

  & .text {
    @apply m-0 text-sm text-white #{!important};
  }

  @screen md {
    @apply flex-row items-center p-8 #{!important};

    & .logo {
      @apply mr-4 #{!important};
    }

    & .title {
      @apply flex flex-col #{!important};
    }

    & .description {
      @apply mt-0 #{!important};
    }

    & .text {
      @apply text-2xl #{!important};
    }
  }

  @screen lg {
    @apply px-12 py-16 #{!important};

    & .title {
      @apply block #{!important};
    }
  }
}
</style>
