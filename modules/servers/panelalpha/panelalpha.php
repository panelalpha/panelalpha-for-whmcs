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
use WHMCS\Module\Server\PanelAlpha\Smarty;
use WHMCS\Service\Status;

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
            $view = new Smarty();
            if (!Helper::isServerGroupWithPanelAlphaServer()) {
                $view->assign('LANG', $LANG);
                $data['content'] = $view->fetch('empty-servers-info.tpl');
                echo json_encode($data);
                die();
            }

            if (!$_POST['servergroup'] && Helper::isServerGroupWithPanelAlphaServer()) {
                $view->assign('LANG', $LANG);
                $data['content'] = $view->fetch('select-server-group-info.tpl');
                echo json_encode($data);
                die();
            }

            $product = Product::findOrFail($_REQUEST['id']);
            $product->createCustomFieldsIfNotExists();
            $product->setConfigOptionsEnabledWhenProductCreated();
            $usageItems = UsageItem::getUsageItems($_REQUEST['id']);

            $serverGroup = ServerGroup::findOrFail((int)$_POST['servergroup']);
            $server = $serverGroup->getFirstServer();
            $api = new PanelAlphaApi($server);
            $plans = $api->getPlans();
            $plans = Helper::getFormattedPlans($plans);
            $selectedPlan = $product->getPlanAssignedToProduct($plans);

            global $CONFIG;
            $view->assign('config', $CONFIG);
            $view->assign('plans', $plans);
            $view->assign('selectedPlan', $selectedPlan);
            $view->assign('product', $product);
            $view->assign('LANG', $LANG);
            $view->assign('usageItems', $usageItems);
            $view->assign('version', Helper::getVersion());
            $data['content'] = $view->fetch('product-module-settings.tpl');
        } catch (\Exception $e) {
            $data['content'] = '<div class="errorbox">' . $e->getMessage() . '</span></div>';
        }
        echo json_encode($data);
        die();
    }

    if ($_REQUEST['action'] === 'save' && basename($_SERVER["SCRIPT_NAME"]) === 'configproducts.php') {
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
            $view = new Smarty();
            if (!$_POST['servergroup'] && Helper::isServerGroupWithPanelAlphaServer()) {
                $view->assign('LANG', $LANG);
                $data['content'] = $view->fetch('select-server-group-info.tpl');
                echo json_encode($data);
                die();
            }

            $serverGroup = ServerGroup::findOrFail((int)$_POST['servergroup']);
            $server = $serverGroup->getFirstServer();
            if (empty($server)) {
                $view->assign('LANG', $LANG);
                $data['content'] = $view->fetch('empty-servers-info.tpl');
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
            $data['content'] = $view->fetch('addon-module-settings.tpl');
        } catch (\Exception $e) {
            $data['content'] = '<div class="errorbox">' . $e->getMessage() . '</span></div>';
        }
        echo json_encode($data);
        die();
    }

    if (
        isset($_POST['panelalpha-package']) && $_POST['panelalpha-package'] !== ''
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
                    'value' => $_POST['panelalpha-package'] ?? ''
                ]
            );
    }
    return [];
}


/**
 * Create service
 *
 * @param array{
 *     model: Service,
 *     serviceid: int,
 *     pid: int,
 *     addonId?: int,
 *     domain: string,
 *     configoptions: array{
 *         sites?: int,
 *         location?: string,
 *     },
 *     clientsdetails: array{
 *         email: string,
 *         firstname: string,
 *         lastname: string,
 *         companyname?: string,
 *     },
 *     configoption1: string,
 * } $params
 * @return string
 */
function panelalpha_CreateAccount(array $params): string
{
    try {
        $LANG = Lang::getLang();
        $service = Service::findOrFail($params['serviceid']);
        $product = $service->product;
        $api = PanelAlphaApi::fromModel($service->serverModel);

        /**
         * Upgrade from trial
         */
        $panelAlphaService = [];
        $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
        if ($panelAlphaServiceId) {
            $panelAlphaService = $api->getService($panelAlphaServiceId);
        }
        if (!empty($panelAlphaService['is_trial'])) {
            Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'User ID', $panelAlphaService['user_id']);
            if ($panelAlphaService['status'] == 'suspended') {
                $api->unsuspendAccount($panelAlphaService['user_id'], $panelAlphaServiceId);
            }

            $instanceLimit = Helper::getInstanceLimit($params);
            $hostingAccountConfig = Helper::getHostingAccountConfig($params);
            $api->changePlan(
                $panelAlphaService['user_id'],
                $panelAlphaServiceId,
                $params['configoption1'],
                $instanceLimit,
                $hostingAccountConfig
            );
            $user = $api->getUserById((int)$panelAlphaService['user_id']);
            if ($user) {
                $data = [];
                if (empty($user['first_name'])) {
                    $data['first_name'] = $params['clientsdetails']['firstname'];
                }
                if (empty($user['last_name'])) {
                    $data['last_name'] = $params['clientsdetails']['lastname'];
                }
                if (empty($user['company_name'])) {
                    $data['company_name'] = $params['clientsdetails']['companyname'] ?? "";
                }
                if (!empty($data)) {
                    $data['email'] = $user['email'];
                    $api->updateUser((int)$panelAlphaService['user_id'], $data);
                }
            }
            return 'success';
        }

        /**
         * Create Addon
         */
        if ($params['addonId']) {
            $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
            if (!$panelAlphaServiceId) {
                throw new Exception($LANG['messages.no_service_from_panelalpha']);
            }
            $api->addPackageToService($panelAlphaServiceId, (int)$params['Package ID']);
            return 'success';
        }

        /**
         * Create Service
         */
        $panelAlphaUser = $api->getUser($params['clientsdetails']['email']);
        if (!$panelAlphaUser) {
            $data = [
                'first_name' => $params['clientsdetails']['firstname'],
                'last_name' => $params['clientsdetails']['lastname'],
                'company_name' => $params['clientsdetails']['companyname'] ?? "",
                'email' => $params['clientsdetails']['email'],
                'password' => Helper::generateRandomString(8)
            ];
            $panelAlphaUser = $api->createUser($data);

            $dataUrl = $api->getLoginUrl();
            $mailParams = [
                'user_email' => $data['email'],
                'user_password' => $data['password'],
                'login_url' => $dataUrl['url'],
                'service_id' => $params['serviceid'],
            ];
            LocalApi::sendUserEmail('PanelAlpha Welcome New User Email', $params['clientsdetails']['language'], $mailParams);
        }
        if (!$panelAlphaUser) {
            throw new Exception($LANG['messages.no_user_from_panelalpha']);
        }

        $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
        if ($panelAlphaServiceId) {
            return 'success';
        }

        $planId = $service->product->getPanelAlphaPlanId();

        $instanceLimit = Helper::getInstanceLimit($params);
        $serverLocation = Helper::getServerLocation($params);
        $hostingAccountConfig = Helper::getHostingAccountConfig($params);

        $panelAlphaService = $api->createService($panelAlphaUser['id'], $planId, $instanceLimit, $serverLocation, $hostingAccountConfig);
        if (!$panelAlphaService) {
            throw new Exception($LANG['messages.no_service_from_panelalpha']);
        }

        $product->createCustomFieldsIfNotExists();
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'Service ID', $panelAlphaService['id']);
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'User ID', $panelAlphaUser['id']);

        $service->username = "";
        $service->save();

        if ($product->isAutomaticInstallInstanceEnabled()) {
            $instanceName = Helper::getInstanceName($params);
            $theme = $service->product->getThemeName();

            $instanceDetails = $api->createInstance($params['domain'], $instanceName, $theme, $panelAlphaService['id'], $panelAlphaUser['id']);
            $service->domain = $instanceDetails['domain'];
            $service->save();
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
        $LANG = Lang::getLang();

        if ($params['addonId']) {
            throw new Exception($LANG['messages.suspend_not_supported']);
        }

        $panelAlphaUserId = $params['customfields']['User ID'] ?? null;
        if (!$panelAlphaUserId) {
            throw new Exception($LANG['messages.no_user_from_panelalpha']);
        }

        $panelAlphaServiceId = $params['customfields']['Service ID'] ?? null;
        if (!$panelAlphaServiceId) {
            throw new Exception($LANG['messages.no_service_from_panelalpha']);
        }

        $server = Server::findOrFail($params['serverid']);
        $api = PanelAlphaApi::fromModel($server);
        $api->suspendAccount($panelAlphaUserId, $panelAlphaServiceId);
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
 */
function panelalpha_UnsuspendAccount(array $params): string
{
    try {
        $LANG = Lang::getLang();

        if ($params['addonId']) {
            throw new Exception($LANG['messages.unsuspend_not_supported']);
        }

        $panelAlphaUserId = $params['customfields']['User ID'] ?? null;
        if (!$panelAlphaUserId) {
            throw new Exception($LANG['messages.no_user_from_panelalpha']);
        }

        $panelAlphaServiceId = $params['customfields']['Service ID'] ?? null;
        if (!$panelAlphaServiceId) {
            throw new Exception($LANG['messages.no_service_from_panelalpha']);
        }

        $server = Server::findOrFail($params['serverid']);
        $api = PanelAlphaApi::fromModel($server);
        $api->unsuspendAccount($panelAlphaUserId, $panelAlphaServiceId);
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}


/**
 * Terminate service
 *
 * @param array{
 *     serverid: int,
 *     addonId?: int,
 *     configoption4: string,
 *     userid: int,
 *     serviceid: int,
 *     pid: int,
 *     domain: string,
 *     customfields: array,
 * } $params
 * @return string
 * @throws GuzzleException
 */
function panelalpha_TerminateAccount(array $params): string
{
    try {
        $LANG = Lang::getLang();
        $server = Server::findOrFail($params['serverid']);
        $api = PanelAlphaApi::fromModel($server);

        if ($params['addonId']) {
            $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
            if (!$panelAlphaServiceId) {
                throw new Exception($LANG['messages.no_service_from_panelalpha']);
            }

            $api->deletePackageFromService($panelAlphaServiceId, $params['Package ID']);

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
            throw new Exception($LANG['messages.service_deleted_manually']);
        }

        $panelAlphaServiceId = $params['customfields']['Service ID'] ?? null;
        if (!$panelAlphaServiceId) {
            throw new Exception($LANG['messages.no_service_from_panelalpha']);
        }

        try {
            $stats = $api->getServiceStats($panelAlphaServiceId);
            foreach ($stats['active_instances'] as $instance) {
                $api->deleteInstance($instance['id']);
            }
        } catch (Exception $e) {
        }

        $api->deleteService($panelAlphaServiceId);
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'Service ID', '');
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'User ID', '');

        $panelAlphaUserId = $params['customfields']['User ID'] ?? null;
        if (!$panelAlphaUserId) {
            throw new Exception($LANG['messages.no_user_from_panelalpha']);
        }

        $otherServices = $api->getUserServices($panelAlphaUserId);
        if (empty($otherServices)) {
            $api->deleteUser($panelAlphaUserId);
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
        $LANG = Lang::getLang();

        if ($params['addonId']) {
            throw new Exception($LANG['messages.change_plan_for_addons_not_supported']);
        }

        $upgradedProduct = Product::findOrFail($params['pid']);

        $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
        if (!$panelAlphaServiceId) {
            throw new Exception($LANG['messages.no_service_from_panelalpha']);
        }

        $panelAlphaUserId = Helper::getCustomField($params['serviceid'], 'User ID');
        if (!$panelAlphaUserId) {
            throw new Exception($LANG['messages.no_user_from_panelalpha']);
        }

        $server = Server::findOrFail($params['serverid']);
        $api = PanelAlphaApi::fromModel($server);

        $newPlanId = $upgradedProduct->getPanelAlphaPlanId();
        $instanceLimit = Helper::getInstanceLimit($params);
        $hostingAccountConfig = Helper::getHostingAccountConfig($params);
        $api->changePlan(
            $panelAlphaUserId,
            $panelAlphaServiceId,
            $newPlanId,
            $instanceLimit,
            $hostingAccountConfig
        );
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
