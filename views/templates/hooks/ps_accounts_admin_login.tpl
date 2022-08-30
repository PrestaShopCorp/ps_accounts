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
<div id="login-panel">
    <div id="login-header">
        <h1 class="text-center">
            <img id="logo" src="{$img_dir}prestashop@2x.png" width="123px" height="24px" alt="PrestaShop" />
        </h1>
        <div class="text-center">{$ps_version}</div>
        <div id="error" class="hide alert alert-danger">
            {if isset($errors)}
                <h4>
                    {if isset($nbErrors) && $nbErrors > 1}
                        {l s='There are %d errors.' sprintf=[$nbErrors] d='Admin.Notifications.Error'}
                    {else}
                        {l s='There is %d error.' sprintf=[$nbErrors] d='Admin.Notifications.Error'}
                    {/if}
                </h4>
                <ol>
                    {foreach from=$errors item="error"}
                        <li>{$error}</li>
                    {/foreach}
                </ol>
            {/if}
        </div>

        {if isset($warningSslMessage)}
            <div class="alert alert-warning">{$warningSslMessage}</div>
        {/if}
    </div>
    <div id="shop-img"><img src="{$img_dir}preston-login@2x.png" alt="{$shop_name}" width="69.5px" height="118.5px" /></div>
    <div class="flip-container">
        <div class="flipper">
            <div class="front front_login panel">
                <h4 id="shop_name">{$shop_name}</h4>
                {** hook h="displayPsAccountsAdminLogin" *}
                <div class="col-sm">
                    <button id="ps-accounts-login" type="button" tabindex="4" class="btn btn-primary btn-lg btn-block ladda-button" data-style="slide-up" data-spinner-color="white" >
                        <img src="{$pathImg|escape:'htmlall':'UTF-8'}" class="prestashop-accounts picture" />
                        <span class="ladda-label">Log In with PrestaShop Accounts</span>
                    </button>
                    <div class="form-group row">
                        <a href="{$localLoginUri}"  class="col-xs-6 text-left">
                            {l s='Local login' d='Admin.Login.Feature'}
                        </a>
                        <a target="_blank" and rel="noopener noreferrer" href="{$ssoUri}" class="col-xs-6 text-right">
                            {l s='Manage session' d='Admin.Login.Feature'}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a class='login-back' href='{$homeUrl}'><i class="material-icons rtl-flip">arrow_back</i> <span>{l s='Back to' d='Admin.Actions'}</span> <span class="login-back-shop">{$shop_name}</span></a>

    <div id="login-footer">
        <p class="text-center text-muted">
            <a href="https://www.prestashop.com/" onclick="return !window.open(this.href);">
                &copy; PrestaShop&#8482; 2007-{$smarty.now|date_format:"%Y"} - All rights reserved
            </a>
        </p>
        <p class="text-center">
            <a class="link-social link-twitter _blank" href="https://twitter.com/PrestaShop" title="Twitter">
                <i class="icon-twitter"></i>
            </a>
            <a class="link-social link-facebook _blank" href="https://www.facebook.com/prestashop" title="Facebook">
                <i class="icon-facebook"></i>
            </a>
            <a class="link-social link-github _blank" href="https://www.prestashop.com/github" title="Github">
                <i class="icon-github"></i>
            </a>
        </p>
    </div>
</div>
<script src="{$pathVendor|escape:'htmlall':'UTF-8'}"></script>
<script src="{$pathZoid|escape:'htmlall':'UTF-8'}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const error = "{$loginError}";
        if (error !== "") {
            // Function in prestashop core (global actually)
            displayErrors([error]);
        }

        const returnTo = '{$redirect}';
        const redirectUri = "{$redirectUri}";
        const oauth2Uri = redirectUri + '&return_to=' + encodeURIComponent(returnTo);

        // window['signInComponent'].mount("#ps-accounts-login", {
        //     redirectUri: oauth2Uri
        // });

        document.querySelector('#ps-accounts-login').addEventListener('click', function() {
            document.location = oauth2Uri;
        })
    });
</script>
<style>
    .prestashop-accounts.picture {
        margin-right: 10px;
        width: 30px;
        height: 30px;
    }
</style>
