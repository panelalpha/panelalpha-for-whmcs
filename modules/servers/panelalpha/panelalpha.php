<?php

use GuzzleHttp\Exception\GuzzleException;
use WHMCS\Module\Server\PanelAlpha\Helper;
use WHMCS\Module\Server\PanelAlpha\Lang;
use WHMCS\Module\Server\PanelAlpha\Models\Addon;
use WHMCS\Module\Server\PanelAlpha\Models\Product;
use WHMCS\Module\Server\PanelAlpha\Models\Server;
use WHMCS\Module\Server\PanelAlpha\Models\ServerGroup;
use WHMCS\Module\Server\PanelAlpha\Models\Service;
use WHMCS\Module\Server\PanelAlpha\Models\UsageItem;
use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;
use WHMCS\Module\Server\PanelAlpha\Apis\LocalApi;
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\PanelAlpha\MetricsProvider;
use WHMCS\Module\Server\PanelAlpha\View;
use WHMCS\Service\Status;
use GuzzleHttp\Client;

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
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=manage');
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
        'DefaultNonSSLPort' => '8443',
        'DefaultSSLPort' => '8443',
        'ListAccountsUniqueIdentifierDisplayName' => 'Service ID',
        'ListAccountsUniqueIdentifierField' => 'customfield.Service ID',
        'ListAccountsProductField' => 'configoption1',
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
    $LANG = Lang::getLang();

    if ($_REQUEST['action'] !== 'save' && basename($_SERVER["SCRIPT_NAME"]) === 'configproducts.php') {
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

            /** @var Product $product */
            $product = Product::findOrFail($_REQUEST['id']);
            $product->createCustomFieldsIfNotExists();
            $product->setConfigOptionsEnabledWhenProductCreated();
            $usageItems = UsageItem::getUsageItems($_REQUEST['id']);

            $serverGroup = ServerGroup::findOrFail((int)$_POST['servergroup']);
            $server = $serverGroup->getFirstServer();
            $connection = new PanelAlphaApi($server);

            $plans = $connection->getPlans();
            $selectedPlan = $product->getPlanAssignedToProduct($plans);

            $plans = array_map(function ($plan) use ($LANG) {
                $accountConfig = [];
                foreach ($plan['account_config'] as $key => $value) {
                    $key = !empty($LANG['aa']['product']['server']['config']['key'][$key])
                        ? $LANG['aa']['product']['server']['config']['key'][$key]
                        : $key;

                    $value = !empty($LANG['aa']['product']['server']['config']['value'][$value])
                        ? $LANG['aa']['product']['server']['config']['value'][$value]
                        : $value;

                    $accountConfig[$key] = $value;
                }
                $plan['server_config'] = json_encode($accountConfig);
                return $plan;
            }, $plans);

            global $CONFIG;

            $view->assign('config', $CONFIG);
            $view->assign('plans', $plans);
            $view->assign('selectedPlan', $selectedPlan);
            $view->assign('product', $product);
            $view->assign('MGLANG', $LANG);
            $view->assign('usageItems', $usageItems);
            $view->assign('version', Helper::getVersion());
            $data['content'] = $view->fetch('productModuleSettings.tpl');
        } catch (\Exception $e) {
            $data['content'] = '<div class="errorbox">' . $e->getMessage() . '</span></div>';
        }
        echo json_encode($data);
        die();

    }

    if ($_REQUEST['action'] === 'save' && basename($_SERVER["SCRIPT_NAME"]) === 'configproducts.php') {
        /** @var Product $product */
        $product = Product::findOrFail($_REQUEST['id']);

        foreach ($_POST['metric'] as $metric => $status) {
            $product->setUsageItemHiddenStatus($metric, $status);
        }

        foreach ($_POST['configoption'] as $key => $value) {
            $product->saveConfigOption($key, $value);
        }

        return [];
    }

    if ($_REQUEST['action'] !== 'save' && basename($_SERVER["SCRIPT_NAME"]) === 'configaddons.php') {
        try {
            $view = new View();
            $serverGroup = ServerGroup::findOrFail((int)$_POST['servergroup']);
            $server = $serverGroup->getFirstServer();
            if (empty($server)) {
                $data['content'] = $view->fetch('noServerMessage.tpl');
                echo json_encode($data);
                die();
            }

            $connection = new PanelAlphaApi($server);
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
            $view->assign('MGLANG', $LANG);
            $view->assign('packages', $packages);
            $view->assign('selectedPackage', $selectedPackage);
            $view->assign('selectedPackagePlugins', $selectedPackagePlugins);
            $view->assign('selectedPackageThemes', $selectedPackageThemes);
            $view->assign('version', Helper::getVersion());
            $data['content'] = $view->fetch('addonModuleSettings.tpl');
        } catch (\Exception $e) {
            $data['content'] = '<div class="errorbox">' . $e->getMessage() . '</span></div>';
        }
        echo json_encode($data);
        die();
    }

    if (
        $_POST['panelalpha-package']
        && $_REQUEST['action'] === 'save'
        && basename($_SERVER["SCRIPT_NAME"]) === 'configaddons.php'
    ) {
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
        $connection = new PanelAlphaApi($server);

        if ($params['addonId']) {
            $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
            if (!$panelAlphaServiceId) {
                throw new Exception('No service id from PanelAlpha');
            }
            $connection->addPackageToService($panelAlphaServiceId, (int)$params['Package ID']);
            return 'success';
        }

        $user = $connection->getUser($params['clientsdetails']['email']);
        if (!$user) {
            $data = [
                'first_name' => $params['clientsdetails']['firstname'],
                'last_name' => $params['clientsdetails']['lastname'],
                'company_name' => $params['clientsdetails']['companyname'] ?? "",
                'email' => $params['clientsdetails']['email'],
                'password' => Helper::generateRandomString(8)
            ];
            $user = $connection->createUser($data);

            $dataUrl = $connection->getLoginUrl();
            $mailParams = [
                'user_email' => $data['email'],
                'user_password' => $data['password'],
                'login_url' => $dataUrl['url'],
                'service_id' => $params['serviceid'],
            ];
            LocalApi::sendUserEmail('PanelAlpha Welcome New User Email', $params['clientsdetails']['language'], $mailParams);
        }
        if (!$user) {
            throw new Exception('No user from PanelAlpha');
        }

        $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
        if ($panelAlphaServiceId) {
            return 'success';
        }

        $planId = $params['configoption1'];
        $service = $connection->createService($user, $planId);
        if (!$service) {
            throw new Exception('No service from PanelAlpha');
        }

        /** @var Product $product */
        $product = Product::findOrFail($params['pid']);
        $product->createCustomFieldsIfNotExists();
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'Service ID', $service['id']);
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'User ID', $user['id']);

        $serviceModel = Service::find($params['serviceid']);
        $serviceModel->domain = "";
        $serviceModel->username = "";
        $serviceModel->save();

        $automaticInstanceInstalling = $params['configoption2'];
        if ($automaticInstanceInstalling == 'on') {
            $instanceName = Helper::getInstanceName($params);
            $theme = $params['configoption3'] ?? "";
            $location = Helper::getCustomField($params['serviceid'], 'location|Location');
            $connection->createInstance($params, $instanceName, $theme, $service['id'], $user['id'], $location);
        }
    } catch (Exception $e) {
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
            throw new Exception('Suspend action for addons is not supported.');
        }

        $panelAlphaUserId = $params['customfields']['User ID'] ?? null;
        if (!$panelAlphaUserId) {
            throw new Exception('No user id from PanelAlpha');
        }

        $panelAlphaServiceId = $params['customfields']['Service ID'] ?? null;
        if (!$panelAlphaServiceId) {
            throw new Exception('No service id from PanelAlpha');
        }

        $server = Server::findOrFail($params['serverid'])->toArray();
        $connection = new PanelAlphaApi($server);
        $connection->suspendAccount($panelAlphaUserId, $panelAlphaServiceId);
    } catch (Exception $e) {
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
        }

        $panelAlphaUserId = $params['customfields']['User ID'] ?? null;
        if (!$panelAlphaUserId) {
            throw new Exception('No user id from PanelAlpha');
        }

        $panelAlphaServiceId = $params['customfields']['Service ID'] ?? null;
        if (!$panelAlphaServiceId) {
            throw new Exception('No service id from PanelAlpha');
        }

        $server = Server::findOrFail($params['serverid'])->toArray();
        $connection = new PanelAlphaApi($server);
        $connection->unsuspendAccount($panelAlphaUserId, $panelAlphaServiceId);
    } catch (Exception $e) {
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
        $connection = new PanelAlphaApi($server);

        if ($params['addonId']) {
            $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
            if (!$panelAlphaServiceId) {
                throw new Exception('No service id from PanelAlpha');
            }

            $connection->deletePackageFromService($panelAlphaServiceId, $params['Package ID']);

            return 'success';
        }

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
        }

        $panelAlphaServiceId = $params['customfields']['Service ID'] ?? null;
        if (!$panelAlphaServiceId) {
            throw new Exception('No service id from PanelAlpha');
        }

        $stats = $connection->getServiceStats($panelAlphaServiceId);
        foreach ($stats['active_instances'] as $instance) {
            $connection->deleteInstance($instance['id']);
        }
        $connection->deleteService($panelAlphaServiceId);
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'Service ID', '');
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'User ID', '');

        $panelAlphaUserId = $params['customfields']['User ID'] ?? null;
        if (!$panelAlphaUserId) {
            throw new Exception('No user id from PanelAlpha');
        }

        $otherServices = $connection->getUserServices($panelAlphaUserId);
        if (empty($otherServices)) {
            $connection->deleteUser($panelAlphaUserId);
        }
    } catch (Exception $e) {
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
        }

        $upgradeProduct = \WHMCS\Product\Product::find($params['pid']);

        $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
        if (!$panelAlphaServiceId) {
            throw new Exception('No service id from PanelAlpha');
        }

        $panelAlphaUserId = Helper::getCustomField($params['serviceid'], 'User ID');
        if (!$panelAlphaUserId) {
            throw new Exception('No user id from PanelAlpha');
        }

        $newPlanId = $upgradeProduct->configoption1;

        $server = Server::findOrFail($params['serverid'])->toArray();
        $connection = new PanelAlphaApi($server);
        $connection->changePlan($panelAlphaUserId, $panelAlphaServiceId, $newPlanId);
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}


/**
 * Test connection
 *
 * @param array $params
 * @return array
 */
function panelalpha_TestConnection(array $params): array
{
    if (!empty($params['serverusername'])) {
        $params['serverhttpprefix'] = $params['serverusername'];
    }

    try {
        $connection = new PanelAlphaApi($params);
        $connection->testConnection();

        $success = true;

    } catch (Exception $e) {
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
    if ($_REQUEST['sso'] === 'yes') {
        $service = Service::find($params['serviceid']);

        $userId = Helper::getCustomField($service->id, 'User ID');

        $api = PanelAlphaApi::fromModel($service->serverModel);
        $result = $api->getSsoToken($userId);

        header("Location: {$result['url']}/sso-login?token={$result['token']}");
        exit();
    }

    global $CONFIG;
    $url = $CONFIG['SystemURL'] . '/clientarea.php?action=productdetails&sso=yes&id=' . $params['serviceid'];
    try {
        return [
            'tabOverviewModuleOutputTemplate' => 'templates/clientarea.tpl',
            'templateVariables' => [
                'url' => $url,
                'MGLANG' => Lang::getLang()
            ],
        ];
    } catch (Exception $e) {
        return [
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => [
                'usefulErrorHelper' => $e->getMessage(),
            ],
        ];
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

/**
 * List Services from Panelalpha
 *
 * @param array $params
 * @return array
 */
function panelalpha_ListAccounts(array $params): array
{
    $services = [];
    try {
        $server = Server::findOrFail($params['serverid'])->toArray();
        $connection = new PanelAlphaApi($server);
        $data = $connection->getServices();

        foreach ($data as $service) {
            $services[] = [
                'email' => $service['user_email'],
                'username' => "",
                'domain' => "",
                'uniqueIdentifier' => $service['id'],
                'product' => $service['plan_id'],
                'primaryip' => "",
                'created' => (new DateTime($service['created_at']))->format('Y-m-d H:i:s'),
                'status' => $service['status'] === 'active'
                    ? Status::ACTIVE
                    : Status::SUSPENDED,
            ];
        }

        return [
            'success' => true,
            'accounts' => $services,
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

function panelalpha_AdminServicesTabFields($params): array
{
    if ($_REQUEST['sso'] === 'yes') {
        $service = Service::find($params['serviceid']);

        $server = $service->serverModel;
        $userId = Helper::getCustomField($service->id, 'User ID');

        $api = PanelAlphaApi::fromModel($server);
        $result = $api->getLoginAsUserSsoToken($userId);

        header("Location: {$result['url']}/sso-login?token={$result['token']}");
        exit();
    }

    $LANG = Lang::getLang();

    return [
        $LANG['aa']['service']['panelalpha']['sso'] => '<a class="btn btn-default" onclick="window.open(window.location + \'&sso=yes\', \'_blank\')">' . $LANG['aa']['service']['panelalpha']['login_to_panelalpha_as_user'] . '</a>',
    ];
}
