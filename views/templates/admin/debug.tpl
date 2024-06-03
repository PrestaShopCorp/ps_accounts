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
        <li>Shop Linked : {if $config.isShopLinked}YES{else}NO{/if}</li>
    </ul>
    <div>
        <button onclick="callAction('unlinkShop', 'The shop has been successfully unlinked.')">Unlink shop</button>
        <button onclick="callAction('resetLinkAccount', 'Link account data has been cleared.')">Clear link account data</button>
    </div>
    <div id="action-message"></div>
</div>

<script>
    /**
     *
     * @param action unlinkShop | resetLinkAccount
     */
    adminAjaxCall = async (action) => {
        return new Promise((resolve, reject) => {
            $.ajax({
                type: 'POST',
                url: '{$config.adminAjaxUrl}&action=' + action,
                dataType: 'json',
                success: (data) => resolve(data),
                error: (xhr) => reject(xhr),
            });
        });
    }

    function callAction(action, successMessage='Action succeeded !') {
        $('#action-message').html('Processing request...');
        adminAjaxCall(action)
            .then(() => {
                $('#action-message').html(successMessage);
            })
            .catch((xhr) => {
                $('#action-message').html('An error occurred : ' + xhr.status + ' : ' + xhr.statusText);
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
