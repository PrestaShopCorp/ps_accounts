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
<link href="{$pathVendor|escape:'htmlall':'UTF-8'}" rel=preload as=script>
<link href="{$pathZoid|escape:'htmlall':'UTF-8'}" rel=preload as=script>

<div class="col-sm">
    <button id="ps-accounts-login" type="button" tabindex="4" class="btn btn-primary btn-lg btn-block ladda-button" data-style="slide-up" data-spinner-color="white" >
        <img src="{$pathImg|escape:'htmlall':'UTF-8'}" class="prestashop-accounts picture" />
        <span class="ladda-label">
            Log In with PrestaShop Accounts
        </span>
    </button>
</div>

<script src="{$pathVendor|escape:'htmlall':'UTF-8'}"></script>
<script src="{$pathZoid|escape:'htmlall':'UTF-8'}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const returnTo = document
            .querySelector("input#redirect")
            .getAttribute("value");

        const stayLoggedIn = document
            .querySelector("#stay_logged_in:checked") ? 1 : 0;

        const redirectUri = "{$redirectUri}";
        const oauth2Uri = redirectUri + '&return_to=' + encodeURIComponent(returnTo) + '&stay_logged_in=' + stayLoggedIn;

        console.log(oauth2Uri);

        window['signInComponent'].mount("#ps-accounts-login", {
            redirectUri: oauth2Uri
        });
    });
</script>
<style>
    .prestashop-accounts.picture {
        margin-right: 10px;
        width: 30px;
        height: 30px;
    }
</style>
