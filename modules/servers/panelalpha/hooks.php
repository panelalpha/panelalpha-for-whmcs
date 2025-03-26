<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;
use WHMCS\Module\Server\PanelAlpha\Helper;
use WHMCS\Module\Server\PanelAlpha\Models\CustomField;
use WHMCS\Module\Server\PanelAlpha\Models\CustomFieldValue;
use WHMCS\Module\Server\PanelAlpha\Models\EmailTemplate;
use WHMCS\Module\Server\PanelAlpha\Models\Product;
use WHMCS\Module\Server\PanelAlpha\Models\Server;
use WHMCS\Module\Server\PanelAlpha\Models\Service;
use WHMCS\Module\Server\PanelAlpha\Lang;
use WHMCS\View\Menu\Item as MenuItem;

add_hook('AdminAreaFooterOutput', 1, function ($params) {
    if ($_REQUEST['action'] !== 'save' && basename($_SERVER["SCRIPT_NAME"]) === 'configproducts.php' && isset($_REQUEST['id'])) {
        $product = Product::findOrFail($_REQUEST['id']);
        if ($product->servertype === 'panelalpha') {
            $panelAlphaWelcomeEmail = EmailTemplate::where('name', 'PanelAlpha Welcome Email')->first();
            return <<<HTML
<script type="text/javascript">
	const welcomeEmail = $('[name="welcomeemail"]');
	const option = $('<option>', {
      value: {$panelAlphaWelcomeEmail->id},
      text: "{$panelAlphaWelcomeEmail->name}"
    });
    
    welcomeEmail.append(option);
	welcomeEmail.val({$panelAlphaWelcomeEmail->id});
	
	const requireDomainInput = $('[name="showdomainops"');
	requireDomainInput.parent().parent().parent().hide();
</script>
HTML;
        }
    }
});

add_hook('ClientAreaSecondaryNavbar', 1, function (MenuItem $secondaryNavbar) {
    if (isset($GLOBALS['legacyClient'])) {
        $clientId = $GLOBALS['legacyClient']->getID();
        $panelAlphaFirstService = Service::getFirstServiceForClient($clientId);

        global $CONFIG;
        $MGLANG = Lang::getLang();

        if ($panelAlphaFirstService->configoption5 === 'on') {
            $secondaryNavbar->addChild('panelalpha_sso_link', array(
                'label' => '<span style="margin-right: 12px; color: #5bc0de;" onMouseOver="this.style.textDecoration=\'underline\'"  onMouseOut="this.style.textDecoration=\'none\'">' . $MGLANG['ca']['general']['panelalpha']['sso_link'] . ' <i class="fas fa-external-link"></i></span>',
                'order' => 1,
                'uri' => $CONFIG['SystemURL'] . '/clientarea.php?action=productdetails&sso=yes&id=' . $panelAlphaFirstService->id,
            ));
        }
    }
});


add_hook('ClientAreaFooterOutput', 1, function ($params) {
    $customJS = "
        <script>
            $('#Secondary_Navbar-panelalpha_sso_link a').attr('target', '_blank');
        </script>
    ";
    return $customJS;
});


add_hook('ClientAreaPageHome', 1, function () {
    if (empty($_REQUEST['paupgradeserviceid'])) {
        return;
    }

    $panelAlphaServiceId = $_REQUEST['paupgradeserviceid'];
    $service = Service::getService($panelAlphaServiceId);
    if (!$service) {
        Helper::showPageNotFound();
    }

    global $CONFIG;
    header("Location: {$CONFIG['SystemURL']}/upgrade.php?type=package&id={$service->id}");
    exit();
});


add_hook('AdminAreaHeadOutput', 1, function ($params) {
    if (isset($params['filename']) && $params['filename'] != 'configservers' && ((isset($_GET['action']) && $_GET['action'] != "manage") || !isset($_GET['id']))) {
        return;
    }
    $jsFile = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "servers" . DIRECTORY_SEPARATOR . "panelalpha" . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . "server.js";
    return '<script type="text/javascript"> ' . file_get_contents($jsFile) . '</script>';
});

add_hook('ProductEdit', 1, function ($params) {
    /** @var Product $product */
    $product = Product::findOrFail($params['pid']);

    if ($product->servertype === 'panelalpha') {
        $isAutoInstallInstance = $product->configoption2 === 'on';
        $showInstanceNameFieldOnOrderForm = $product->configoption8 === 'on';

        CustomField::where([
            ['type', 'product'],
            ['relid', $params['pid']],
            ['fieldname', 'Instance Name']
        ])->update([
            'showorder' => $isAutoInstallInstance && $showInstanceNameFieldOnOrderForm ? 'on' : ''
        ]);

        $product->setShowDomainOption();
    }
});


add_hook('ServerAdd', 1, function ($params) {
    $server = Server::findOrFail($params['serverid']);
    if ($server->type === 'panelalpha') {
        $server->setHostname();

        EmailTemplate::createEmailTemplatesIfNotExist();
    }
});

add_hook('ServerEdit', 1, function ($params) {
    $server = Server::findOrFail($params['serverid']);
    if ($server->type === 'panelalpha') {
        $server->setHostname();
    }
});

add_hook('CustomFieldSave', 1, function ($params) {
    // check if service_id custom field belongs to panelalpha product
    $serviceCustomField = CustomField::with(['product'])
        ->whereHas('product', function ($query) {
            $query->where('servertype', 'panelalpha');
        })
        ->where('fieldname', 'Service ID')
        ->where('type', 'product')
        ->find($params['fieldid']);

    // return if no server id custom field for panelalpha
    if (!$serviceCustomField) {
        return [
            'value' => $params['value']
        ];
    }

    // check if service_id belongs to panelalpha service
    $service = Service::with(['serverModel', 'product'])
        ->whereHas('serverModel', function ($query) {
            $query->where('type', 'panelalpha');
        })
        ->find($params['relid']);

    if (!$service) {
        return [
            'value' => $params['value']
        ];
    }

    // check if custom field value is not empty
    if (empty($params['value'])) {
        return [
            'value' => $params['value']
        ];
    }

    // get service details from panelalpha
    $api = new PanelAlphaApi($service->serverModel->toArray());
    $panelalphaService = $api->getService($params['value']);

    $product = $service->product;

    // find user custom field for product
    $userCustomField = CustomField::where('fieldname', 'User ID')
        ->where('type', 'product')
        ->where('relid', $product->id)
        ->first();

    // create user custom field if not exists
    if (!$userCustomField) {
        $userCustomField = CustomField::create([
            'type' => 'product',
            'relid' => $product->id,
            'fieldname' => 'User ID',
            'fieldtype' => 'text',
            'showorder' => '',
            'adminonly' => 'on'
        ]);
    }

    // find user custom field value for whmcs service
    $userCustomFieldValue = CustomFieldValue::where('fieldid', $userCustomField->id)
        ->where('relid', $service->id)
        ->first();

    // create user custom field for whmcs service if not exists
    if (!$userCustomFieldValue) {
        CustomFieldValue::create([
            'fieldid' => $userCustomField->id,
            'relid' => $service->id,
            'value' => $panelalphaService['user_id'],
        ]);
    }

    // find instance name custom field for product
    $instanceNameCustomField = CustomField::where('fieldname', 'Instance Name')
        ->where('type', 'product')
        ->where('relid', $product->id)
        ->first();

    // create instance name custom field for product if not exists
    if (!$instanceNameCustomField) {
        $instanceNameCustomField = CustomField::create([
            'type' => 'product',
            'relid' => $product->id,
            'fieldname' => 'Instance Name',
            'fieldtype' => 'text',
            'showorder' => 'on',
            'adminonly' => ''
        ]);
    }

    // find instance name custom field for whmcs service
    $instanceCustomFieldValue = CustomFieldValue::where('fieldid', $instanceNameCustomField->id)
        ->where('relid', $service->id)
        ->first();

    // create instance name custom field value for whmcs service if not exists
    if (!$instanceCustomFieldValue) {
        CustomFieldValue::create([
            'fieldid' => $instanceNameCustomField->id,
            'relid' => $service->id,
            'value' => '',
        ]);
    }


    //change client name if it has placeholders
    $client = \WHMCS\Module\Server\PanelAlpha\Models\Client::where('firstname', 'Name')
        ->where('lastname', 'Placeholder')
        ->find($service->userid);

    if ($client) {
        [$firstName, $lastName] = explode(' ', $panelalphaService['user_name']) + ['', ''];
        $client->firstname = $firstName;
        $client->lastname = $lastName;
        $client->save();

        $user = $client->users()
            ->where('first_name', 'Name')
            ->where('last_name', 'Placeholder')
            ->first();

        if ($user) {
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            $user->save();
        }
    }


    //change product name if it is plan_id
    if ((int)$product->name === $panelalphaService['plan']['id']) {
        $product->name = $panelalphaService['plan']['name'];
        $product->save();
    }
});

add_hook('AdminProductConfigFields', 1, function ($params) {
    /** @var Product $product */
    $product = Product::findOrFail($params['pid']);

    if ($product->servertype === 'panelalpha' && $_REQUEST['custom'] === 'create-location-custom-field') {
        $server = $product->getServer();
        $api = PanelAlphaApi::fromModel($server);

        if (empty($_REQUEST['plan_id'])) {
            $plans = $api->getPlans();
            $selectedPlan = $product->getPlanAssignedToProduct($plans);
        } else {
            $selectedPlan = $api->getPlan($_REQUEST['plan_id']);
        }

        if (!$selectedPlan) {
            throw new Exception('No plan assigned to this service.');
        }

        if ($selectedPlan['server_assign_rule'] === 'specific server') {
            $serverId = $selectedPlan['server_id'];
        } else {
            $servers = $api->getServers($selectedPlan['server_group_id']);

            if (empty($servers)) {
                throw new Exception('No servers assigned to plan.');
            }
            $serverId = $servers[0]['id'];
        }

        $serverConfig = $api->getServerConfig($serverId);

        if (empty($serverConfig['geo_affinity'])) {
            return;
        }

        $options = [];
        foreach ($serverConfig['geo_affinity'] as $option) {
            $options[] = $option['value'] . '|' . str_replace(',', '', $option['text']);
        }

        if (CustomField::where('type', 'product')->where('relid', $product->id)->where('fieldname', 'location|Location')->first()) {
            return;
        }

        CustomField::create([
            'type' => 'product',
            'relid' => $product->id,
            'fieldname' => 'location|Location',
            'fieldtype' => 'dropdown',
            'fieldoptions' => implode(',', $options),
            'required' => 'on',
            'showorder' => 'on',
        ]);
    }
});

