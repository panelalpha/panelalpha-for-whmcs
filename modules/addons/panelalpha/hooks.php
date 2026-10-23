<?php

/**
 * PanelAlpha addon hooks: trial upgrade flow in the client area and cart.
 */

require_once __DIR__ . '/lib/TrialEmailScript.php';
require_once __DIR__ . '/lib/TrialCartValidation.php';

use WHMCS\Module\Server\PanelAlpha\Helper;
use WHMCS\Module\Server\PanelAlpha\Models\Product;
use WHMCS\Module\Server\PanelAlpha\Models\Service;
use WHMCS\Session;

// When PanelAlpha sends a client here to upgrade a trial, remember the trial
// details in the session so the cart and checkout hooks below can use them.
if (defined('CLIENTAREA')) {
    (function () {
        if (empty($_REQUEST['trial_service_id'])) {
            return;
        }
        if (!is_numeric($_REQUEST['trial_service_id'])) {
            return;
        }
        $id = (int)$_REQUEST['trial_service_id'];
        logActivity('PanelAlpha: client area hook request trial_service_id=' . $id);
        if ($id < 1) {
            return;
        }
        Session::set('panelalpha_trial_service_id', $id);
        Session::set('cart', []);
        if (!empty($_REQUEST['trial_email']) && filter_var($_REQUEST['trial_email'], FILTER_VALIDATE_EMAIL)) {
            Session::set('panelalpha_trial_email', $_REQUEST['trial_email']);
        }

        if (!empty($_REQUEST['trial_instance_count']) && is_numeric($_REQUEST['trial_instance_count'])) {
            Session::set('panelalpha_trial_service_instance_count', $_REQUEST['trial_instance_count']);
        }

        if (!empty($_SERVER['HTTP_REFERER'])) {
            Session::set('panelalpha_trial_referer', $_SERVER['HTTP_REFERER']);
        }
    })();
}

/**
 * Links the first new PanelAlpha service to the trial service and clears the trial session data.
 *
 * @param array<string, mixed> $vars WHMCS hook parameters.
 * @return void
 */
add_hook('AfterShoppingCartCheckout', 1, function ($vars) {
    try {
        $trialServiceId = Session::get('panelalpha_trial_service_id');
        if (!$trialServiceId) {
            return;
        }

        $services = Service::with('product')->whereIn('id', $vars['ServiceIDs'])->get();
        foreach ($services as $service) {
            if ($service->product->servertype !== 'panelalpha') {
                continue;
            }
            Helper::setServiceCustomFieldValue($service->product->id, $service->id, 'Service ID', $trialServiceId);
            $service->notes = "Upgrade from PanelAlpha trial service.\n" . $service->notes;
            $service->save();
            Session::delete('panelalpha_trial_service_id');
            Session::delete('panelalpha_trial_email');
            Session::delete('panelalpha_trial_service_instance_count');
            Session::delete('panelalpha_trial_referer');
            return;
        }
    } catch (\Exception $e) {
        logActivity("PanelAlpha ERROR: Couldn't set trial service id in custom field. " . $e->getMessage());
    }
});

/**
 * Prefills the trial email at checkout and locks the "sites" option to the trial site count.
 *
 * @param array<string, mixed> $vars WHMCS hook parameters.
 * @return string|null Script tag, or null when no trial upgrade is in progress.
 */
add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    $trialEmail = Session::get('panelalpha_trial_email');
    if (!$trialEmail) {
        return;
    }

    $script = panelalpha_trial_email_prefill_script($trialEmail);

    $product = Product::find($vars['productinfo']['pid'] ?? 0);
    if (
        $product
        && $product->servertype === 'panelalpha'
        && $product->hasAutomaticallySetNumberOfSitesOnUpgradeFromTrialOption()
    ) {
        $sitesConfigurableOption = $product->getConfigurableOptionByName('sites');

        if ($sitesConfigurableOption !== null) {
            if (empty(Session::get('panelalpha_redirected'))) {
                $instanceCounter = Session::get('panelalpha_trial_service_instance_count');
                $productConfigurableOptions = $product->getConfigurableOptions();

                global $CONFIG;
                $location = "Location: {$CONFIG['SystemURL']}/cart.php?a=add&pid={$product->id}&configoption[{$sitesConfigurableOption->id}]={$instanceCounter}";

                if ($productConfigurableOptions->count() === 1) {
                    $location .= '&skipconfig=1';
                }

                Session::set('panelalpha_redirected', 1);
                header($location);
                exit();
            }
            Session::delete('panelalpha_redirected');

            $script .= <<<HTML
jQuery(document).ready(function() {
    if (jQuery("#inputConfigOption{$sitesConfigurableOption->id}").length && jQuery("#inputConfigOption{$sitesConfigurableOption->id}").data("ionRangeSlider")) {
        jQuery("#inputConfigOption{$sitesConfigurableOption->id}").data("ionRangeSlider").update({
            disable: true
        });
    }
    
    jQuery("input[name='configoption[{$sitesConfigurableOption->id}]']").prop('disabled', true);
});
HTML;
        }
    }
    return '<script type="text/javascript">' . $script . '</script>';
});

/**
 * Validates the "sites" option when the client updates a product in the cart.
 *
 * @param array<string, mixed> $vars WHMCS hook parameters.
 * @return string|null Error message, or null when valid.
 */
add_hook('ShoppingCartValidateProductUpdate', 1, function ($vars) {
    return panelalpha_validate_trial_cart_number_of_sites($vars);
});

/**
 * Validates the "sites" option again at checkout.
 *
 * @param array<string, mixed> $vars WHMCS hook parameters.
 * @return string|null Error message, or null when valid.
 */
add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
    return panelalpha_validate_trial_cart_number_of_sites($vars);
});
