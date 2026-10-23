<?php

use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;
use WHMCS\Module\Server\PanelAlpha\Helper;
use WHMCS\Module\Server\PanelAlpha\Lang;
use WHMCS\Module\Server\PanelAlpha\Models\Client;
use WHMCS\Module\Server\PanelAlpha\Models\ConfigurableOptionGroup;
use WHMCS\Module\Server\PanelAlpha\Models\CustomField;
use WHMCS\Module\Server\PanelAlpha\Models\CustomFieldValue;
use WHMCS\Module\Server\PanelAlpha\Models\EmailTemplate;
use WHMCS\Module\Server\PanelAlpha\Models\Product;
use WHMCS\Module\Server\PanelAlpha\Models\Server;
use WHMCS\Module\Server\PanelAlpha\Models\Service;
use WHMCS\View\Menu\Item as MenuItem;

require_once __DIR__ . '/lib/AdminConfigRequest.php';

/**
 * On the product edit page of a PanelAlpha product, add the PanelAlpha welcome
 * email to the welcome email list, select it, and hide the "Require Domain" option.
 */
add_hook('AdminAreaFooterOutput', 1, function ($params) {
    if (($_REQUEST['action'] ?? '') !== 'save' && basename($_SERVER['SCRIPT_NAME']) === 'configproducts.php' && isset($_REQUEST['id'])) {
        $product = Product::findOrFail($_REQUEST['id']);
        if ($product->servertype === 'panelalpha') {
            $panelAlphaWelcomeEmail = EmailTemplate::where('name', 'PanelAlpha Welcome Email')->first();
            if ($panelAlphaWelcomeEmail === null) {
                return;
            }
            return <<<HTML
<script type="text/javascript">
    const welcomeEmail = $('[name="welcomeemail"]');
    const option = $('<option>', {
        value: {$panelAlphaWelcomeEmail->id},
        text: "{$panelAlphaWelcomeEmail->name}"
    });

    welcomeEmail.append(option);
    welcomeEmail.val({$panelAlphaWelcomeEmail->id});

    const requireDomainInput = $('[name="showdomainops"]');
    requireDomainInput.parent().parent().parent().hide();
</script>
HTML;
        }
    }
});

/**
 * Add the "PanelAlpha" SSO link to the client area navbar when the client's first
 * PanelAlpha service has the navbar link option enabled.
 */
add_hook('ClientAreaSecondaryNavbar', 1, function (MenuItem $secondaryNavbar) {
    if (isset($GLOBALS['legacyClient'])) {
        $clientId = $GLOBALS['legacyClient']->getID();
        $panelAlphaFirstService = Service::getFirstServiceForClient($clientId);

        global $CONFIG;
        $MGLANG = Lang::getLang();

        if ($panelAlphaFirstService !== null && $panelAlphaFirstService->configoption5 === 'on') {
            $secondaryNavbar->addChild('panelalpha_sso_link', [
                'label' => '<span style="color: #5bc0de;" onMouseOver="this.style.textDecoration=\'underline\'"  onMouseOut="this.style.textDecoration=\'none\'">' . $MGLANG['ca']['general']['panelalpha']['sso_link'] . ' <i class="fas fa-external-link"></i></span>',
                'order' => 1,
                'uri' => $CONFIG['SystemURL'] . '/clientarea.php?action=productdetails&sso=yes&id=' . $panelAlphaFirstService->id,
            ]);
        }
    }
});

/**
 * Add spacing around the SSO link in the client area navbar.
 */
add_hook('ClientAreaHeadOutput', 1, function () {
    return '
    <style>
        #Secondary_Navbar-panelalpha_sso_link {
            margin-left: 16px;
            margin-right: 16px;
        }
    </style>';
});

/**
 * Open the navbar SSO link in a new tab.
 */
add_hook('ClientAreaFooterOutput', 1, function ($params) {
    return "
        <script>
            $('#Secondary_Navbar-panelalpha_sso_link a').attr('target', '_blank');
        </script>
    ";
});

/**
 * Redirect to the WHMCS upgrade page when PanelAlpha sends the client back with
 * ?paupgradeserviceid=<PanelAlpha service ID>.
 */
add_hook('ClientAreaPageHome', 1, function () {
    if (empty($_REQUEST['paupgradeserviceid'])) {
        return;
    }

    $panelAlphaServiceId = (int) $_REQUEST['paupgradeserviceid'];
    $service = Service::getService($panelAlphaServiceId);
    if (!$service) {
        Helper::showPageNotFound();
    }

    global $CONFIG;
    header("Location: {$CONFIG['SystemURL']}/upgrade.php?type=package&id={$service->id}");
    exit();
});

/**
 * On the admin service page of a PanelAlpha service, disable the fields the module
 * does not use. Otherwise load the server settings script for the server config page.
 */
add_hook('AdminAreaHeadOutput', 1, function ($params) {
    if (
        isset($params['filename'])
        && $params['filename'] === 'clientsservices'
        && isset($_GET['id'])
    ) {
        $service = Service::find($_GET['id']);
        if ($service === null) {
            return '';
        }

        $product = $service->product;
        if ($product->servertype !== 'panelalpha') {
            return '';
        }

        return <<<HTML
<script type="text/javascript">
$(document).ready(function () {
	$('#inputDedicatedip').prop('disabled', true).val("");
	$('#inputUsername').prop('disabled', true).val("");
	$('#inputPassword').prop('disabled', true).val("");
});
</script>
HTML;
    }

    // The server form script is only needed on the Servers page.
    $page = $params['filename'] ?? basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
    if ($page !== 'configservers') {
        return '';
    }

    $jsFile = ROOTDIR . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'servers' . DIRECTORY_SEPARATOR . 'panelalpha' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'server.js';
    return '<script type="text/javascript"> ' . file_get_contents($jsFile) . '</script>';
});

/**
 * After a PanelAlpha product is saved, show the "Instance Name" field on the order
 * form only when automatic instance install and that field option are both enabled.
 */
add_hook('ProductEdit', 1, function ($params) {
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

/**
 * Set up a new PanelAlpha server: save its hostname and create the email templates.
 */
add_hook('ServerAdd', 1, function ($params) {
    $server = Server::findOrFail($params['serverid']);
    if ($server->type === 'panelalpha') {
        $server->setHostname();

        EmailTemplate::createPanelalphaEmailTemplates();
    }
});

/**
 * Update the hostname of a PanelAlpha server after it is edited.
 */
add_hook('ServerEdit', 1, function ($params) {
    $server = Server::findOrFail($params['serverid']);
    if ($server->type === 'panelalpha') {
        $server->setHostname();
    }
});

/**
 * When an admin sets the "Service ID" field of a PanelAlpha service, fill in the
 * related WHMCS data from PanelAlpha: the "User ID" and "Instance Name" fields,
 * placeholder client names, and a product name that is still the plan ID.
 *
 * Returns the field value unchanged, so the save in WHMCS is never blocked.
 */
add_hook('CustomFieldSave', 1, function ($params) {
    $serviceCustomField = CustomField::with(['product'])
        ->whereHas('product', function ($query) {
            $query->where('servertype', 'panelalpha');
        })
        ->where('fieldname', 'Service ID')
        ->where('type', 'product')
        ->find($params['fieldid']);

    if (!$serviceCustomField) {
        return [
            'value' => $params['value']
        ];
    }

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

    if (empty($params['value'])) {
        return [
            'value' => $params['value']
        ];
    }

    // Best-effort backfill: a PanelAlpha error is logged and must not block the WHMCS save.
    $api = new PanelAlphaApi($service->serverModel->toArray());
    try {
        $panelalphaService = $api->getService((int) $params['value']);
    } catch (Exception $e) {
        logModuleCall(
            'panelalpha',
            'CustomFieldSave',
            [
                'whmcs_service_id' => $service->id,
                'panelalpha_service_id' => $params['value'],
            ],
            $e->getMessage(),
        );

        return [
            'value' => $params['value'],
        ];
    }

    $product = $service->product;

    $userCustomField = CustomField::where('fieldname', 'User ID')
        ->where('type', 'product')
        ->where('relid', $product->id)
        ->first();

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

    $userCustomFieldValue = CustomFieldValue::where('fieldid', $userCustomField->id)
        ->where('relid', $service->id)
        ->first();

    if (!$userCustomFieldValue) {
        CustomFieldValue::create([
            'fieldid' => $userCustomField->id,
            'relid' => $service->id,
            'value' => $panelalphaService['user_id'],
        ]);
    }

    $instanceNameCustomField = CustomField::where('fieldname', 'Instance Name')
        ->where('type', 'product')
        ->where('relid', $product->id)
        ->first();

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

    $instanceCustomFieldValue = CustomFieldValue::where('fieldid', $instanceNameCustomField->id)
        ->where('relid', $service->id)
        ->first();

    if (!$instanceCustomFieldValue) {
        CustomFieldValue::create([
            'fieldid' => $instanceNameCustomField->id,
            'relid' => $service->id,
            'value' => '',
        ]);
    }

    // If the client still has the placeholder name "Name Placeholder", use the name of the PanelAlpha user.
    $client = Client::where('firstname', 'Name')
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

    // If the product is still named after the plan ID, rename it to the plan name.
    if ((int) $product->name === $panelalphaService['plan']['id']) {
        $product->name = $panelalphaService['plan']['name'];
        $product->save();
    }
});

/**
 * Handle the buttons on the module settings tab of a PanelAlpha product: generate
 * configurable options from the assigned plan, or create the "Location" custom field.
 *
 * Both actions change data, so they only run for a POST request with a valid CSRF token.
 */
add_hook('AdminProductConfigFields', 1, function ($params) {
    $product = Product::findOrFail($params['pid']);

    if (
        $product->servertype === 'panelalpha'
        && ($_REQUEST['custom'] ?? '') === 'generate-configurable-options'
        && panelalpha_admin_config_write_allowed()
    ) {
        $server = $product->getServer();
        if ($server === null) {
            throw new Exception('No server assigned to this product.');
        }
        $api = PanelAlphaApi::fromModel($server);
        $plans = $api->getPlans();
        $plans = Helper::getFormattedPlans($plans);
        $selectedPlan = $product->getPlanAssignedToProduct($plans);

        if (!$selectedPlan) {
            throw new Exception('No plan assigned to this service.');
        }

        $configurableOptionGroup = ConfigurableOptionGroup::updateOrCreate(
            ['name' => "Configurable Options for {$product->name} product"],
            ['description' => "Auto generated by module panelalpha"]
        );

        $configurableOptionGroup->products()->sync([$product->id]);

        foreach ($selectedPlan['configurable_options'] as $configurableOptionName) {
            $field = Helper::getConfig('configurable-options.' . $configurableOptionName);
            $optionName = $field['name'] ?? $configurableOptionName;
            $optionType = $field['type'] ?? 1;

            $configurableOption = $configurableOptionGroup->configurableOptions()->updateOrCreate(
                [
                    'optionname' => $optionName,
                    'optiontype' => $optionType,
                ],
                [
                    'qtyminimum' => 0,
                    'qtymaximum' => 0,
                    'order' => 0,
                ]
            );

            if (
                $configurableOptionName === 'server_location'
                && ($selectedPlan['server_assign_rule'] === 'random' || $selectedPlan['server_assign_rule'] === 'least_accounts')
            ) {
                foreach ($selectedPlan['hosting_server_group']['servers'] as $server) {
                    $configurableOption->options()->updateOrCreate(
                        [
                            'optionname' => $server['id'] . '|' . $server['name'],
                        ],
                        [
                            'sortorder' => 0,
                            'hidden' => 0,
                        ]
                    );
                }
            } elseif (isset($field['options'])) {
                foreach ($field['options'] as $option) {
                    $configurableOption->options()->updateOrCreate(
                        [
                            'optionname' => $option,
                        ],
                        [
                            'sortorder' => 0,
                            'hidden' => 0,
                        ]
                    );
                }
            }
        }
    }

    if (
        $product->servertype === 'panelalpha'
        && ($_REQUEST['custom'] ?? '') === 'create-location-custom-field'
        && panelalpha_admin_config_write_allowed()
    ) {
        $server = $product->getServer();
        if ($server === null) {
            throw new Exception('No server assigned to this product.');
        }
        $api = PanelAlphaApi::fromModel($server);

        if (empty($_REQUEST['plan_id'])) {
            $plans = $api->getPlans();
            $selectedPlan = $product->getPlanAssignedToProduct($plans);
        } else {
            $selectedPlan = $api->getPlan((int) $_REQUEST['plan_id']);
        }

        if (!$selectedPlan) {
            throw new Exception('No plan assigned to this service.');
        }

        if ($selectedPlan['server_assign_rule'] === 'specific_server') {
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

/**
 * Fix the greeting in the termination email template of existing installs.
 */
add_hook('AfterCronJob', 1, function ($params) {
    try {
        EmailTemplate::fixTerminationEmailGreeting();
    } catch (Exception $exception) {
        logModuleCall('panelalpha', 'fixTerminationEmailGreeting', '', $exception->getMessage());
    }
});

/**
 * Copy the domain from PanelAlpha to active PanelAlpha services that have no domain
 * in WHMCS. Checks at most 25 services per cron run.
 */
add_hook('AfterCronJob', 1, function ($params) {
    try {
        $services = Service::active()
            ->panelalpha()
            ->where(function ($query) {
                $query->whereNull('domain')->orWhere('domain', '');
            })
            ->orderBy('id')
            ->limit(25)
            ->get();

        $services->each(function (Service $service) {
            if ($service->panelalphaServiceId === null) {
                return;
            }

            $product = $service->product;
            $server = $product->getServer();
            if ($server === null) {
                return;
            }
            $api = PanelAlphaApi::fromModel($server);

            try {
                $result = $api->getService($service->panelalphaServiceId);
            } catch (Exception $e) {
                return;
            }

            $domain = $result['domain'] ?? null;

            if ($domain !== null) {
                $service->domain = $domain;
                $service->save();
            }
        });
    } catch (Exception $exception) {
        logModuleCall(
            'panelalpha',
            'AfterCronJob',
            'Domain service synchronization exception.',
            $exception->getMessage(),
        );
    }
});

