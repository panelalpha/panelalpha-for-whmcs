<?php

use GuzzleHttp\Exception\GuzzleException;
use WHMCS\Module\Server\PanelAlpha\Helper;
use WHMCS\Module\Server\PanelAlpha\Lang;
use WHMCS\Module\Server\PanelAlpha\Models\Addon;
use WHMCS\Module\Server\PanelAlpha\Models\CustomField;
use WHMCS\Module\Server\PanelAlpha\Models\EmailTemplate;
use WHMCS\Module\Server\PanelAlpha\Models\Hosting;
use WHMCS\Module\Server\PanelAlpha\Models\Product;
use WHMCS\Module\Server\PanelAlpha\Models\Server;
use WHMCS\Module\Server\PanelAlpha\Models\ServerGroup;
use WHMCS\Module\Server\PanelAlpha\Models\UsageItem;
use WHMCS\Module\Server\PanelAlpha\PanelAlphaClient;
use WHMCS\Module\Server\PanelAlpha\LocalApi;
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\PanelAlpha\MetricsProvider;
use WHMCS\Module\Server\PanelAlpha\View;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (basename($_SERVER['SCRIPT_NAME']) == "configservers.php") {
    $cachedHooks = \WHMCS\Database\Capsule::table('tblconfiguration')
        ->where('setting', '=', 'ModuleHooks')
        ->get(['value'])[0]->value;

    $hooksArray = explode(',', $cachedHooks);
    if (!in_array("panelalpha", $hooksArray)) {
        $hooksArray[] = "panelalpha";
        \WHMCS\Database\Capsule::table('tblconfiguration')
            ->where('setting', '=', 'ModuleHooks')
            ->update(['value' => implode(",", $hooksArray)]);
        header('Location: '.$_SERVER['PHP_SELF'].'?action=manage');
    }
}


/**
 * @return array
 */
function panelalpha_MetaData(): array
{
    return [
        'DisplayName' => 'PanelAlpha',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'ListAccountsUniqueIdentifierField' => 'customfield.Service ID'
    ];
}

/**
 * @param $params
 * @return array
 * @throws GuzzleException
 * @throws Exception
 */
function panelalpha_ConfigOptions($params): ?array
{
    $MGLANG = Lang::getLang();

    if ($_REQUEST['action'] != 'save' && basename($_SERVER["SCRIPT_NAME"]) === 'configproducts.php') {
        try {
            $view = new View();
            if (!Helper::isServerGroupWithPanelAlphaServer()) {
                $data['content'] = $view->fetch('noServerMessage.tpl');
                echo json_encode($data);
                die();
            }

            if (!$_POST['servergroup'] && Helper::isServerGroupWithPanelAlphaServer()) {
                $data['content'] = $view->fetch('selectServerGroupMessage.tpl');
                echo json_encode($data);
                die();
            }

            $usageItems = UsageItem::getUsageItems($_REQUEST['id']);
            if ($usageItems->isEmpty()) {
                UsageItem::createUsageItems($_REQUEST['id']);
            }

            $product = Product::findOrFail($_REQUEST['id']);
            $product->setConfigOptionsEnabledWhenProductCreated();


            $serverGroup = ServerGroup::find((int)$_POST['servergroup']);
            if (!$serverGroup) {
                throw new Exception('No Server Group');
            }
            $server = $serverGroup->getFirstServer();
            $connection = new PanelAlphaClient($server);
            $connection->validate();

            $plans = $connection->getPlans();
            $selectedPlan = $product->getPlanAssignedToProduct($plans);

            $plans = array_map(function ($plan) {
                $accountConfig = "";
                foreach ($plan['account_config'] as $key => $value) {
                    $accountConfig .= $key . ":" . $value . ',';
                }
                $plan['server_config'] = substr($accountConfig, 0, -1) ?? "";
                return $plan;
            }, $plans);

            global $CONFIG;

            $view->assign('config', $CONFIG);
            $view->assign('plans', $plans);
            $view->assign('selectedPlan', $selectedPlan);
            $view->assign('product', $product);
            $view->assign('MGLANG', $MGLANG);
            $view->assign('usageItems', $usageItems);
            $data['content'] = $view->fetch('productModuleSettings.tpl');
        } catch (\Exception $e) {
            $data['content'] = '<div class="errorbox">' . $e->getMessage() . '</span></div>';
        }
        echo json_encode($data);
        die();

    } else if ($_REQUEST['action'] == 'save' && basename($_SERVER["SCRIPT_NAME"]) === 'configproducts.php') {
        foreach ($_POST['metric'] as $key => $value) {
            UsageItem::setHiddenField($key, $value);
        }

        $product = Product::findOrFail($_REQUEST['id']);
        $product->saveConfigOptions($_POST['configoption']);
        CustomField::createProductCustomFieldsIfNotExist($_REQUEST['id']);
        EmailTemplate::createManualServiceTerminationEmailTemplate();
        EmailTemplate::createWelcomeEmailTemplate();

    } else if ($_REQUEST['action'] != 'save' && basename($_SERVER["SCRIPT_NAME"]) === 'configaddons.php') {
        try {
            $view = new View();
            $serverGroup = ServerGroup::find((int)$_POST['servergroup']);
            if (!$serverGroup) {
                throw new Exception('No Server Group');
            }
            $server = $serverGroup->getFirstServer();
            if (empty($server)) {
                $data['content'] = $view->fetch('noServerMessage.tpl');
                echo json_encode($data);
                die();
            }

            $connection = new PanelAlphaClient($server);
            $connection->validate();
            $packages = $connection->getPackages();
            $packages = array_map(function ($package) {
                $themeNames = array_map(function ($theme) {
                    return $theme['name'];
                }, $package['themes']);
                $pluginNames = array_map(function ($plugin) {
                    return $plugin['name'];
                }, $package['plugins']);
                $package['themeNames'] = !empty($themeNames) ? implode("<br>", $themeNames) : "<span style='color: grey;'>No Themes</span>";
                $package['pluginNames'] = !empty($pluginNames) ? implode("<br>", $pluginNames) : "<span style='color: grey;'>No Plugins</span>";
                return $package;
            }, $packages);

            $addon = Addon::findOrFail($_REQUEST['id']);
            $addon->setSelectedPackage($packages);
            $selectedPackage = $addon->getSelectedPackage();
            $selectedPackagePlugins = $addon->getSelectedPackagePlugins();
            $selectedPackageThemes = $addon->getSelectedPackageThemes();

            global $CONFIG;

            $view->assign('config', $CONFIG);
            $view->assign('MGLANG', $MGLANG);
            $view->assign('packages', $packages);
            $view->assign('selectedPackage', $selectedPackage);
            $view->assign('selectedPackagePlugins', $selectedPackagePlugins);
            $view->assign('selectedPackageThemes', $selectedPackageThemes);
            $data['content'] = $view->fetch('addonModuleSettings.tpl');
        } catch (\Exception $e) {
            $data['content'] = '<div class="errorbox">' . $e->getMessage() . '</span></div>';
        }
        echo json_encode($data);
        die();

    } else if ($_REQUEST['action'] == 'save' && basename($_SERVER["SCRIPT_NAME"]) === 'configaddons.php') {
        if ($_POST['panelalpha-package']) {
            Capsule::table('tblmodule_configuration')
                ->updateOrInsert(
                    [
                        'entity_type' => 'addon',
                        'entity_id' => $_REQUEST['id'],
                        'setting_name' => 'configoption1',
                        'friendly_name' => 'Package ID'
                    ],
                    [
                        'value' => $_POST['panelalpha-package']
                    ]
                );
        }
    }
    return [];
}


/**
 * Create service
 *
 * @param array $params
 * @return string
 * @throws GuzzleException
 */
function panelalpha_CreateAccount(array $params): string
{
    try {
        $server = Server::findOrFail($params['serverid'])->toArray();
        $connection = new PanelAlphaClient($server);
        $connection->validate();

        if ($params['addonId']) {
            $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
            $connection->addPackageToService($panelAlphaServiceId, (int)$params['Package ID']);
        } else {
            $user = $connection->getUser($params['clientsdetails']['email']);
            if (!$user) {
                $user = $connection->createUser($params['clientsdetails']);
            }

            $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
            if (!$panelAlphaServiceId) {
                $planId = $params['configoption1'];
                $service = $connection->createService($user, $planId);
                Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'Service ID', $service['id']);
                Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'User ID', $user['id']);

                $automaticInstanceInstalling = $params['configoption2'];
                if ($automaticInstanceInstalling == 'on') {
                    $instanceName = Helper::getCustomField($params['serviceid'], 'Instance Name') ?? "My First Instance";
                    $theme = $params['configoption3'] ?? "";
                    $instance = $connection->createInstance($params, $instanceName, $theme, $service['id'], $user['id']);

                    $hosting = Hosting::find($params['serviceid']);
                    $hosting->domain = $instance['domain'];
                    $hosting->save();
                }
            }
        }
    } catch (Exception $e) {
        logModuleCall(
            'panelalpha',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return $e->getMessage();
    }

    return 'success';
}

/**
 * Suspend service
 *
 * @param array $params
 * @return string
 * @throws GuzzleException
 */
function panelalpha_SuspendAccount(array $params): string
{
    try {
        if ($params['addonId']) {
            throw new Exception('Suspend for addons is not supported.');
        } else {
            $server = Server::findOrFail($params['serverid'])->toArray();
            $connection = new PanelAlphaClient($server);
            $connection->validate();
            $connection->suspendAccount($params['customfields']['User ID'], $params['customfields']['Service ID']);
        }
    } catch (Exception $e) {
        logModuleCall(
            'panelalpha',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Unsuspend service
 *
 * @param array $params
 * @return string
 * @throws GuzzleException
 */
function panelalpha_UnsuspendAccount(array $params): string
{
    try {
        if ($params['addonId']) {
            throw new Exception('Unsuspend for addons is not supported.');
        } else {
            $server = Server::findOrFail($params['serverid'])->toArray();
            $connection = new PanelAlphaClient($server);
            $connection->validate();
            $connection->unsuspendAccount($params['customfields']['User ID'], $params['customfields']['Service ID']);
        }
    } catch (Exception $e) {
        logModuleCall(
            'panelalpha',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}


/**
 * Terminate service
 *
 * @param array $params
 * @return string
 * @throws GuzzleException
 */
function panelalpha_TerminateAccount(array $params): string
{
    try {
        $server = Server::findOrFail($params['serverid'])->toArray();
        $connection = new PanelAlphaClient($server);
        $connection->validate();

        if ($params['addonId']) {
            $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
            if ($panelAlphaServiceId) {
                $connection->deletePackageFromService($panelAlphaServiceId, $params['Package ID']);
            }
        } else {
            $manualTermination = $params['configoption4'];
            if ($manualTermination === 'on' && basename($_SERVER['REQUEST_URI']) !== 'clientsservices.php') {
                $params = [
                    'client_id' => $params['userid'],
                    'service_id' => $params['serviceid'],
                    'service_product' => \WHMCS\Product\Product::find($params['pid'])->name,
                    'service_domain' => $params['domain']
                ];
                LocalApi::sendAdminEmail('PanelAlpha Service Termination', $params);
                return 'The account must be deleted manually';
            } else {
                $instances = $connection->getInstancesAssignedToService($params['customfields']['Service ID']);
                foreach ($instances['instances'] as $instanceId) {
                    $connection->deleteInstance($instanceId);
                }
                $connection->deleteService($params['customfields']['Service ID']);
                Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'Service ID', '');
                Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'User ID', '');

                $otherServices = $connection->getUserServices($params['customfields']['User ID']);
                if (empty($otherServices)) {
                    $connection->deleteUser($params['customfields']['User ID']);
                }
            }
        }
    } catch (Exception $e) {
        logModuleCall(
            'panelalpha',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}


/**
 * Change plan
 *
 * @param array $params
 * @return string
 * @throws GuzzleException
 */
function panelalpha_ChangePackage(array $params): string
{
    try {
        if ($params['addonId']) {
            throw new Exception('Change package for addons is not supported');
        } else {
            $upgradeProduct = \WHMCS\Product\Product::find($params['pid']);
            $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
            $panelAlphaUserId = Helper::getCustomField($params['serviceid'], 'User ID');
            $newPlanId = $upgradeProduct->configoption1;

            $server = Server::findOrFail($params['serverid'])->toArray();
            $connection = new PanelAlphaClient($server);
            $connection->validate();
            $connection->changePlan($panelAlphaUserId, $panelAlphaServiceId, $newPlanId);
        }
    } catch (Exception $e) {
        logModuleCall(
            'panelalpha',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}


/**
 * Test connection
 *
 * @param array $params
 * @return array
 * @throws GuzzleException
 */
function panelalpha_TestConnection(array $params): array
{
    try {
        $connection = new PanelAlphaClient($params);
        $connection->validate();
        $connection->testConnection();

        $success = true;

    } catch (Exception $e) {
        logModuleCall(
            'panelalpha',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $success = false;
        $errorMsg = $e->getMessage();
    }

    return [
        'success' => $success,
        'error' => $errorMsg ?? ""
    ];
}

/**
 * Client area output logic handling.
 */
function panelalpha_ClientArea(array $params)
{
    global $CONFIG;
    $url = $CONFIG['SystemURL'] . '/clientarea.php?action=productdetails&sso=yes&id=' . $params['serviceid'];

    try {
        return array(
            'tabOverviewModuleOutputTemplate' => 'templates/clientarea.tpl',
            'templateVariables' => [
                'url' => $url,
                'MGLANG' => Lang::getLang()
            ],
        );
    } catch (Exception $e) {

        logModuleCall(
            'provisioningmodule',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => [
                'usefulErrorHelper' => $e->getMessage(),
            ],
        );
    }
}


/**
 * Usage Billing
 *
 * @param $params
 * @return MetricsProvider
 */
function panelalpha_MetricProvider($params): MetricsProvider
{
    return new MetricsProvider($params);
}