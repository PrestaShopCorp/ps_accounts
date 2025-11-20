{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 *}
<div id="ps-accounts-container">
 <prestashop-accounts></prestashop-accounts>
</div>

<script src="{$urlAccountsCdn|escape:'htmlall':'UTF-8'}" type="text/javascript"></script>

<script>
  (function() {
    const componentInitParams = {$componentInitParams|json_encode};

    function initPsAccounts() {
      if (window?.psaccountsVue) {
        window.psaccountsVue.init(componentInitParams, "Settings");
      } else {
        // Si le script n'est pas encore chargé, réessayer après un court délai
        setTimeout(initPsAccounts, 100);
      }
    }

    // Attendre que la page et tous les scripts soient complètement chargés
    if (document.readyState === 'complete') {
      initPsAccounts();
    } else {
      window.addEventListener('load', initPsAccounts);
    }
  })();
</script>

<style>
  /** Hide native multistore module activation panel, because of visual regressions on non-bootstrap content */
  #content.bootstrap div.bootstrap.panel {
    display: none;
  }

  #ps-accounts-container {
    max-width: 1024px;
    margin: auto;
  }

  #main {
    background-color: white;
  }
</style>

