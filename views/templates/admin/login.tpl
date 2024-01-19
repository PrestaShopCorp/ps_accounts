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
        class="psacc-hidden lg:psacc-flex psacc-flex-col psacc-items-center psacc-justify-end psacc-bg-cover psacc-bg-no-repeat psacc-bg-bicycle"
    >
        <section
            id="psacc_slider"
            class="splide psacc-mb-10 psacc-w-11/12 psacc-bg-quote psacc-flex psacc-flex-col"
        >
            <div
                class="splide__arrows psacc-flex psacc-justify-between psacc-w-full psacc-my-5"
            >
                <img src="{$shopUrl}/modules/ps_accounts/views/img/quote-mark.svg" class="psacc-ml-10">
                <div class="psacc-flex psacc-justify-center psacc-items-center psacc-mr-10">
                    <button
                        class="splide__arrow splide__arrow--prev psacc-font-materialIcons psacc-opacity-100 psacc-rounded psacc-text-font-main psacc-text-4xl psacc-mr-5"
                    >
                        chevron_left
                    </button>
                    <button
                        class="splide__arrow splide__arrow--next psacc-font-materialIcons psacc-opacity-100 psacc-rounded psacc-text-font-main psacc-text-4xl"
                    >
                        chevron_right
                    </button>
                </div>
            </div>
            <div class="splide__track psacc-mb-5">
                <ul class="splide__list psacc-w-full">
                    {foreach from=$testimonials item=testimonial}
                        <li class="splide__slide">
                            <div class="psacc-w-10/12 psacc-ml-10 psacc-font-primary">
                                <p class="psacc-mb-4 psacc-text-lg">
                                    {$testimonial[$isoCode]['sentence']|default:$testimonial[$defaultIsoCode]['sentence']}
                                </p>
                                <p class="puik-body-default psacc-font-primary">
                                    <span class="psacc-font-bold">{$testimonial[$isoCode]['name']|default:$testimonial[$defaultIsoCode]['name']}</span>, {$testimonial[$isoCode]['enterprise']|default:$testimonial[$defaultIsoCode]['enterprise']}
                                </p>
                            </div>
                        </li>
                    {/foreach}
                </ul>
            </div>
            <ul class="splide__pagination"></ul>
        </section>
    </div>
    <div
        class="psacc-flex psacc-flex-col psacc-items-center psacc-justify-between psacc-bg-white psacc-py-24 psacc-shadow-[0_6px_12px_rgba(0, 0, 0, 0.1)]">
        {if $loginError ne ''}
            <div class="psacc-flex psacc-flex-col psacc-space-y-4 psacc-mb-4 psacc-px-4">
                <div class="puik-alert puik-alert--danger" aria-live="polite">
                    <div class="puik-alert__content">
                        <span class="psacc-font-materialIcons">error</span>
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
            <div class="psacc-flex psacc-flex-col psacc-items-start psacc-justify-start psacc-h-full">
                <h1 class="psacc-m-0 psacc-font-secondary psacc-text-5xl psacc-font-black psacc-mb-10">
                    PRESTASHOP
                </h1>

                <div class="psacc-flex psacc-flex-col psacc-items-start psacc-max-w-xl psacc-font-primary">
                    <h2 class="puik-h2 psacc-mb-10">
                        {l s='Welcome,' mod='ps_accounts'}</br>
                    </h2>
                    <p class="psacc-mb-10 ">
                        {l s='Access your back office to manage your store.' mod='ps_accounts'}
                    </p>
                    <button id="ps-accounts-login" class="puik-button puik-button--primary puik-button--lg psacc-w-full">
                        {l s='Go to the back office' mod='ps_accounts'}
                    </button>
                </div>
                <a class="puik-link psacc-mt-auto psacc-self-center" href="{$legacyLoginUri}">
                    {l s='Connect with another method' mod='ps_accounts'}
                </a>
            </div>
        {else}
            <div class="puik-alert puik-alert--danger" aria-live="polite">
                <div class="puik-alert__content">
                    <span class="psacc-font-materialIcons">error</span>
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
