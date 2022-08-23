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