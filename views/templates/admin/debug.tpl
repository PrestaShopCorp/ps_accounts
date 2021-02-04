{**
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
 *}
<div id="psaccounts">
    <h2>Shop & module information</h2>
    <ul>
        <li>Shop ID : {$config.shopId}</li>
        <li>Module version : {$config.moduleVersion}</li>
        <li>Prestashop version : {$config.psVersion}</li>
        <li>PHP version : {$config.phpVersion}</li>
        <li>Shop UID : {$config.shopUuidV4}</li>
        <li>Firebase email : {$config.firebase_email}</li>
        <li>Is Firebase email verified : {$config.firebase_email_is_verified}</li>
        <li>Firebase ID token : {$config.firebase_id_token}</li>
        <li>Firebase refresh token : {$config.firebase_refresh_token}</li>
    </ul>
    <div class="unlink-shop">
        {if $config.isShopLinked}
            <button onclick="unlinkShop()">Unlink shop</button>
        {else}
            <div>This shop is not linked</div>
        {/if}
    </div>
    <div class="unlink-message"></div>
</div>

<script>
    function unlinkShop()
    {
        $.ajax({
            type: 'POST',
            url: '{$config.unlinkShopUrl}',
            dataType: 'json',
            success: function (response) {
                $('.unlink-message').html('The shop (with uid : ' + response.uid + ') has been successfully unlinked.');
                $('.unlink-shop').hide();
            },
            error: function (response) {
                $('.unlink-message').html(response.error + '. The response code is :' + response.statusCode);
            }
        });
    }
</script>

<style>
    /** Hide native multistore module activation panel, because of visual regressions on non-bootstrap content */
    #psaccounts ul li {
        word-break: break-all;
        margin: 5px 0px;
    }
</style>
