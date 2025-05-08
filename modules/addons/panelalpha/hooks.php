<?php

use WHMCS\Module\Server\PanelAlpha\Helper;
use WHMCS\Module\Server\PanelAlpha\Models\Service;

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
        \WHMCS\Session::set('panelalpha_trial_service_id', $id);
        \WHMCS\Session::set('cart', []);
        if (!empty($_REQUEST['trial_email']) && filter_var($_REQUEST['trial_email'], FILTER_VALIDATE_EMAIL)) {
            \WHMCS\Session::set('panelalpha_trial_email', $_REQUEST['trial_email']);
        }
    })();
};

add_hook('AfterShoppingCartCheckout', 1, function ($vars) {
    try {
        $trialServiceId = \WHMCS\Session::get('panelalpha_trial_service_id');
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
            \WHMCS\Session::delete('panelalpha_trial_service_id');
            \WHMCS\Session::delete('panelalpha_trial_email');
            return;
        }
    } catch (\Exception $e) {
        logActivity("PanelAlpha ERROR: Couldn't set trial service id in custom field. " . $e->getMessage());
    }
});

add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    $trialEmail = \WHMCS\Session::get('panelalpha_trial_email');
    if (!$trialEmail) {
        return;
    }
    return <<<HTML
<script type="text/javascript">
    jQuery(document).ready(function() {
        let input = jQuery("form[action$='/cart.php?a=checkout'] input[name=email]");
        if (input.length && !(input.val())) {
            input.val('{$trialEmail}');
        }
    })
</script>
HTML;
});
