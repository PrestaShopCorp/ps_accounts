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
<div class="psacc-grid psacc-h-screen lg:psacc-grid-cols-2">
    <div
        class="psacc-hidden lg:psacc-flex psacc-flex-col psacc-items-center psacc-justify-center psacc-bg-primary-purple-50">
        <section id="psacc_slider" class="splide psacc-max-w-xl">
            <div
                class="splide__arrows psacc-flex psacc-justify-between psacc-absolute psacc-bottom-12 psacc-translate-y-2/4 psacc-w-full psacc-z-10">
                <button
                    class="splide__arrow splide__arrow--prev psacc-font-materialIcons psacc-bg-white psacc-opacity-100 psacc-rounded psacc-border psacc-border-border-main psacc-text-font-main psacc-w-9 psacc-h-9">
                    chevron_left
                </button>
                <button
                    class="splide__arrow splide__arrow--next psacc-font-materialIcons psacc-bg-white psacc-opacity-100 psacc-rounded psacc-border psacc-border-border-main psacc-text-font-main psacc-w-9 psacc-h-9">
                    chevron_right
                </button>
            </div>
            <div class="splide__track">
                <ul class="splide__list">
                    <li class="splide__slide psacc-flex psacc-flex-col psacc-items-center psacc-justify-end">
                        <img class="psacc-mb-12" src="/modules/ps_accounts/views/img/slide1.png" />
                        <div class="psacc-max-w-sm">
                            <h2 class="puik-h2 psacc-font-primary psacc-text-center psacc-mb-4 psacc-text-font-main">
                                {l s='An easy-to-use back office' mod='ps_accounts'}
                            </h2>
                            <p class="puik-body-default psacc-font-secondary psacc-text-center psacc-text-base">
                                {l s='Manage your entire business in one place: product catalog, orders, payments, deliveries and much more.' mod='ps_accounts'}
                            </p>
                        </div>
                    </li>
                    <li class="splide__slide psacc-flex psacc-flex-col psacc-items-center psacc-justify-end">
                        <img class="psacc-mb-12" src="/modules/ps_accounts/views/img/slide2.png" />
                        <div class="psacc-max-w-sm">
                            <h2 class="puik-h2 psacc-font-primary psacc-text-center psacc-mb-4 psacc-text-font-main">
                                {l s='All the essentials for your business' mod='ps_accounts'}
                            </h2>
                            <p class="puik-body-default psacc-font-secondary psacc-text-center psacc-text-base">
                                {l s='Marketing, payment and performance analysis: the PrestaShop Essentials suite includes all the features you need to make your store successful.' mod='ps_accounts'}
                            </p>
                        </div>
                    </li>
                    <li class="splide__slide psacc-flex psacc-flex-col psacc-items-center psacc-justify-end">
                        <img class="psacc-mb-12" src="/modules/ps_accounts/views/img/slide3.png" />
                        <div class="psacc-max-w-sm">
                            <h2 class="puik-h2 psacc-font-primary psacc-text-center psacc-mb-4 psacc-text-font-main">
                                {l s='A 100% customizable solution' mod='ps_accounts'}
                            </h2>
                            <p class="puik-body-default psacc-font-secondary psacc-text-center psacc-text-base">
                                {l s='PrestaShop accompanies your growth. Find our modules and those of our partners on PrestaShop Addons Marketplace to customize and develop your store' mod='ps_accounts'}
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>
    </div>
    <div
        class="psacc-flex psacc-flex-col psacc-items-center psacc-justify-between psacc-bg-white psacc-py-16 psacc-shadow-[0_6px_12px_rgba(0, 0, 0, 0.1)]">
        {if $loginError ne ''}
            <div class="psacc-flex psacc-flex-col psacc-space-y-4 psacc-mb-4 psacc-px-4">
                <div class="puik-alert puik-alert--danger" aria-live="polite">
                    <div class="puik-alert__content">
                        <span class="puik-alert__icon">error</span>
                        <div class="puik-alert__text">
                            <span class="puik-alert__description">
                                {if $loginError eq 'employee_not_found'}
                                    {l
                                        s='You cannot access the back office with this account. Try another account or contact your administrator.'
                                        mod='ps_accounts'
                                    }
                                {elseif $loginError eq 'email_not_verified'}
                                    {l
                                        s='You need to activate your account first by clicking the link in the email. If you need to receive a new activation link,[1]please click here[/1]'
                                        tags=["<a class=\"puik-link\" href=\"{$ssoResendVerificationEmail}\" target=\"_blank\">"] mod='ps_accounts'
                                    }
                                {elseif $loginError eq 'error_from_hydra' or $loginError eq 'error_other'}
                                    {l s='An error occured during login, please contact PrestaShop support' mod='ps_accounts'}
                                {else}
                                    {$loginError}
                                {/if}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        {/if}
        {if !isset($wrong_folder_name) && !isset($wrong_install_name)}
            <div class="psacc-flex psacc-flex-col psacc-items-center psacc-justify-between psacc-h-full">
                <h1 class="psacc-m-0">
                    <img id="logo" src="{$img_dir}prestashop@2x.png" width="123px" height="24px" alt="PrestaShop" />
                </h1>

                <div class="psacc-flex psacc-flex-col psacc-items-center psacc-max-w-xl">
                    <h2 class="puik-h2 psacc-font-primary psacc-text-center psacc-mb-12">
                        {l s='Welcome,' mod='ps_accounts'}</br>
                        {l s='Access your back office to manage your store.' mod='ps_accounts'}
                    </h2>
                    <button id="ps-accounts-login" class="puik-button puik-button--primary puik-button--lg">
                        {l s='Go to the back office' mod='ps_accounts'}
                    </button>
                </div>
                <a class="puik-link" href="{$legacyLoginUri}">
                    {l s='Connect with another method' mod='ps_accounts'}
                </a>
            </div>
        {else}
            <div class="puik-alert puik-alert--danger" aria-live="polite">
                <div class="puik-alert__content">
                    <span class="puik-alert__icon">error</span>
                    <div class="puik-alert__text">
                        <p class="puik-alert__title">
                            {l s='For security reasons, you cannot connect to the back office until you have:' d='Admin.Login.Notification'}
                        </p>
                        <span class="puik-alert__description">
                            <ul class="psacc-list-disc psacc-pl-10">
                                {if isset($wrong_install_name) && $wrong_install_name == true}
                                    <li>{l s='deleted the /install folder' d='Admin.Login.Notification'}</li>
                                {/if}
                                {if isset($wrong_folder_name) && $wrong_folder_name == true}
                                    <li>{l s='renamed the /admin folder (e.g. %s)' sprintf=[$randomNb] d='Admin.Login.Notification'}
                                    </li>
                                {/if}
                            </ul>
                            <a class="puik-link" href="{$adminUrl|escape:'html':'UTF-8'}">
                                {l s='Please then access this page by the new URL (e.g. %s)' sprintf=[$adminUrl] d='Admin.Login.Notification'}
                            </a>
                        </span>
                    </div>
                </div>
            </div>
        {/if}
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const returnTo = '{$redirect}';
        const redirectUri = "{$oauthRedirectUri}";
        const locale = (navigator.language || navigator.userLanguage || 'en').slice(0, 2);
        const oauth2Uri = redirectUri + '&return_to=' + encodeURIComponent(returnTo) + '&locale=' + encodeURIComponent(locale);

        document.querySelector('#ps-accounts-login').addEventListener('click', function() {
            document.location = oauth2Uri;
        })
    });
</script>
