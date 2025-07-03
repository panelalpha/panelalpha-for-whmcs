<?php

use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;
use WHMCS\Module\Server\PanelAlpha\Helper;
use WHMCS\Module\Server\PanelAlpha\Models\Product;
use WHMCS\Module\Server\PanelAlpha\Models\Server;
use WHMCS\Module\Server\PanelAlpha\Models\Service;
use WHMCS\Module\Server\PanelAlpha\Models\ConfigurableOption;
use WHMCS\Session;

if (defined('CLIENTAREA')) {
    (function () {
        if (empty($_REQUEST['trial_service_id'])) {
            return;
        }
        logActivity("PanelAlpha: client area hook request: " . var_export($_REQUEST, true));
        if (!is_numeric($_REQUEST['trial_service_id'])) {
            return;
        }
        $id = (int)$_REQUEST['trial_service_id'];
        if ($id < 1) {
            return;
        }
        Session::set('panelalpha_trial_service_id', $id);
        Session::set('cart', []);
        if (!empty($_REQUEST['trial_email']) && filter_var($_REQUEST['trial_email'], FILTER_VALIDATE_EMAIL)) {
            Session::set('panelalpha_trial_email', $_REQUEST['trial_email']);
        }

        if (!empty($_REQUEST['trial_instance_count'] && is_numeric($_REQUEST['trial_instance_count']))) {
            Session::set('panelalpha_trial_service_instance_count', $_REQUEST['trial_instance_count']);
        }

        if (!empty($_SERVER['HTTP_REFERER'])) {
            Session::set('panelalpha_trial_referer', $_SERVER['HTTP_REFERER']);
        }
    })();
};

add_hook('AfterShoppingCartCheckout', 1, function ($vars) {
    try {
        $trialServiceId = Session::get('panelalpha_trial_service_id');
        if (!$trialServiceId) {
            return;
        }

        $services = Service::with('product')->whereIn('id', $vars['ServiceIDs'])->get();
        foreach ($services as $service) {
            if ($service->product->servertype != 'panelalpha') {
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

add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    $trialEmail = Session::get('panelalpha_trial_email');
    if (!$trialEmail) {
        return;
    }

    $script = <<<HTML
jQuery(document).ready(function() {
    let input = jQuery("form[action$='/cart.php?a=checkout'] input[name=email]");
    if (input.length && !(input.val())) {
        input.val('{$trialEmail}');
    }
});
HTML;

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


add_hook('ShoppingCartValidateProductUpdate', 1, function ($vars) {
    try {
        $trialServiceId = Session::get('panelalpha_trial_service_id');

        if (!$trialServiceId || empty($vars['configoption'])) {
            return;
        }

        // get the number of instances assigned to the service
        $numberOfInstancesInService = (int)Session::get('panelalpha_trial_service_instance_count');
        if (!$numberOfInstancesInService) {
            $server = null;

            $trialReferer = Session::get('panelalpha_trial_referer');
            if ($trialReferer) {
                $hostname = parse_url($trialReferer, PHP_URL_HOST);
                if ($hostname) {
                    $server = Server::getServerByHostname($hostname);
                }
            }
            if ($server === null) {
                $server = Server::where('type', 'panelalpha')->first();
            }
            if ($server === null) {
                throw new \Exception('No PanelAlpha server found');
            }

            $api = PanelAlphaApi::fromModel($server);
            $serviceDetails = $api->getService($trialServiceId);
            $numberOfInstancesInService = (int)$serviceDetails['instances_count'];
            Session::set('panelalpha_trial_service_instance_count', $numberOfInstancesInService);
        }

        // get selected by user number of sites
        $sitesConfigurableOptionValue = null;
        foreach ($vars['configoption'] as $id => $value) {
            $configOption = ConfigurableOption::find($id);
            if (!$configOption) {
                continue;
            }
            if (str_contains($configOption->optionname, 'sites')) {
                $sitesConfigurableOptionValue = (int)$value;
                break;
            }
        }
        if ($sitesConfigurableOptionValue === null) {
            throw new \Exception('No sites config option found');
        }

        // check if a trial service has more sites than the selected value
        if ($numberOfInstancesInService > $sitesConfigurableOptionValue) {
            return "Your trial service has more sites ({$numberOfInstancesInService}) than the Number of Sites you selected ({$sitesConfigurableOptionValue}).";
        }

        // find an ordered product
        $selectedProduct = null;
        foreach (($_SESSION['cart']['products'] ?? []) as $product) {
            $product = Product::find($product['pid']);
            if (!$product && $product->servertype !== 'panelalpha') {
                continue;
            }
            $selectedProduct = $product;
            break;
        }
        if ($selectedProduct === null) {
            throw new \Exception('No product found');
        }

        // check if trial service has different number of sites than selected value by user
        if ($selectedProduct->hasAutomaticallySetNumberOfSitesOnUpgradeFromTrialOption() && $sitesConfigurableOptionValue !== $numberOfInstancesInService) {
            return "Your trial service must have the same number of sites ({$numberOfInstancesInService}) as the Number of Sites you selected ({$sitesConfigurableOptionValue}).";
        }
    } catch (\Exception $e) {
        logActivity("PanelAlpha ERROR: Couldn't validate `number of sites` configurable option. " . $e->getMessage());
    }
});


add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
    try {
        $trialServiceId = Session::get('panelalpha_trial_service_id');

        if (!$trialServiceId || empty($vars['configoption'])) {
            return;
        }

        // get the number of instances assigned to the service
        $numberOfInstancesInService = (int)Session::get('panelalpha_trial_service_instance_count');
        if (!$numberOfInstancesInService) {
            $server = null;

            $trialReferer = Session::get('panelalpha_trial_referer');
            if ($trialReferer) {
                $hostname = parse_url($trialReferer, PHP_URL_HOST);
                if ($hostname) {
                    $server = Server::getServerByHostname($hostname);
                }
            }
            if ($server === null) {
                $server = Server::where('type', 'panelalpha')->first();
            }
            if ($server === null) {
                throw new \Exception('No PanelAlpha server found');
            }

            $api = PanelAlphaApi::fromModel($server);
            $serviceDetails = $api->getService($trialServiceId);
            $numberOfInstancesInService = (int)$serviceDetails['instances_count'];
            Session::set('panelalpha_trial_service_instance_count', $numberOfInstancesInService);
        }

        // get selected by user number of sites
        $sitesConfigurableOptionValue = null;
        foreach ($vars['configoption'] as $id => $value) {
            $configOption = ConfigurableOption::find($id);
            if (!$configOption) {
                continue;
            }
            if (str_contains($configOption->optionname, 'sites')) {
                $sitesConfigurableOptionValue = (int)$value;
                break;
            }
        }
        if ($sitesConfigurableOptionValue === null) {
            throw new \Exception('No sites config option found');
        }

        // check if a trial service has more sites than the selected value
        if ($numberOfInstancesInService > $sitesConfigurableOptionValue) {
            return "Your trial service has more sites ({$numberOfInstancesInService}) than the Number of Sites you selected ({$sitesConfigurableOptionValue}).";
        }

        // find an ordered product
        $selectedProduct = null;
        foreach (($_SESSION['cart']['products'] ?? []) as $product) {
            $product = Product::find($product['pid']);
            if (!$product && $product->servertype !== 'panelalpha') {
                continue;
            }
            $selectedProduct = $product;
            break;
        }
        if ($selectedProduct === null) {
            throw new \Exception('No product found');
        }

        // check if trial service has different number of sites than selected value by user
        if ($selectedProduct->hasAutomaticallySetNumberOfSitesOnUpgradeFromTrialOption() && $sitesConfigurableOptionValue !== $numberOfInstancesInService) {
            return "Your trial service must have the same number of sites ({$numberOfInstancesInService}) as the Number of Sites you selected ({$sitesConfigurableOptionValue}).";
        }
    } catch (\Exception $e) {
        logActivity("PanelAlpha ERROR: Couldn't validate `number of sites` configurable option. " . $e->getMessage());
    }
});
