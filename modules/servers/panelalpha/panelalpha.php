<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\PanelAlpha\Apis\LocalApi;
use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;
use WHMCS\Module\Server\PanelAlpha\Helper;
use WHMCS\Module\Server\PanelAlpha\Lang;
use WHMCS\Module\Server\PanelAlpha\MetricsProvider;
use WHMCS\Module\Server\PanelAlpha\Models\Addon;
use WHMCS\Module\Server\PanelAlpha\Models\Product;
use WHMCS\Module\Server\PanelAlpha\Models\Server;
use WHMCS\Module\Server\PanelAlpha\Models\ServerGroup;
use WHMCS\Module\Server\PanelAlpha\Models\Service;
use WHMCS\Module\Server\PanelAlpha\Models\UsageItem;
use WHMCS\Module\Server\PanelAlpha\Smarty;
use WHMCS\Service\Status;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/AccountLink.php';
require_once __DIR__ . '/lib/TerminateGuard.php';

// WHMCS only loads hooks.php of server modules listed in the cached "ModuleHooks"
// setting. Add this module to that list when the admin opens the servers page,
// then reload so the hooks are active right away.
if (basename($_SERVER['SCRIPT_NAME']) === 'configservers.php') {
    $cachedHooks = Capsule::table('tblconfiguration')
        ->where('setting', '=', 'ModuleHooks')
        ->get(['value'])[0]->value;

    $hooksArray = explode(',', $cachedHooks);
    if (!in_array('panelalpha', $hooksArray, true)) {
        $hooksArray[] = 'panelalpha';
        Capsule::table('tblconfiguration')
            ->where('setting', '=', 'ModuleHooks')
            ->update(['value' => implode(',', $hooksArray)]);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=manage');
    }
}

/**
 * Return the module metadata used by WHMCS.
 *
 * @return array<string, string|bool>
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
 * Render and save the module settings of a product or a product addon.
 *
 * When WHMCS loads the settings tab over AJAX, this function prints the settings
 * HTML as JSON and stops the request. On save it stores the submitted values
 * itself and returns no WHMCS config options.
 *
 * @param array $params Module parameters passed by WHMCS.
 * @return array Always an empty list; the settings are rendered by the templates.
 * @throws Exception When the product cannot be found while saving.
 */
function panelalpha_ConfigOptions($params): ?array
{
    $LANG = Lang::getLang();

    if (($_REQUEST['action'] ?? '') !== 'save' && basename($_SERVER['SCRIPT_NAME']) === 'configproducts.php') {
        try {
            $view = new Smarty();
            if (!Helper::isServerGroupWithPanelAlphaServer()) {
                $view->assign('LANG', $LANG);
                $data['content'] = $view->fetch('empty-servers-info.tpl');
                echo json_encode($data);
                die();
            }

            if (empty($_POST['servergroup']) && Helper::isServerGroupWithPanelAlphaServer()) {
                $view->assign('LANG', $LANG);
                $data['content'] = $view->fetch('select-server-group-info.tpl');
                echo json_encode($data);
                die();
            }

            $product = Product::findOrFail($_REQUEST['id']);
            $product->createCustomFieldsIfNotExists();
            $product->setConfigOptionsEnabledWhenProductCreated();
            $usageItems = UsageItem::getUsageItems($_REQUEST['id']);

            $serverGroup = ServerGroup::findOrFail((int) $_POST['servergroup']);
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
        } catch (Exception $e) {
            $data['content'] = '<div class="errorbox">' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</div>';
        }
        echo json_encode($data);
        die();
    }

    if (($_REQUEST['action'] ?? '') === 'save' && basename($_SERVER['SCRIPT_NAME']) === 'configproducts.php') {
        $product = Product::findOrFail($_REQUEST['id']);

        foreach ($_POST['metric'] ?? [] as $metric => $status) {
            $product->setUsageItemHiddenStatus($metric, $status);
        }

        foreach ($_POST['configoption'] ?? [] as $key => $value) {
            $product->saveConfigOption($key, $value);
        }
        return [];
    }

    if (($_REQUEST['action'] ?? '') !== 'save' && basename($_SERVER['SCRIPT_NAME']) === 'configaddons.php') {
        try {
            $view = new Smarty();
            if (empty($_POST['servergroup']) && Helper::isServerGroupWithPanelAlphaServer()) {
                $view->assign('LANG', $LANG);
                $data['content'] = $view->fetch('select-server-group-info.tpl');
                echo json_encode($data);
                die();
            }

            $serverGroup = ServerGroup::findOrFail((int) $_POST['servergroup']);
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
                $package['themeNames'] = !empty($themeNames) ? implode('<br>', $themeNames) : "<span style='color: grey;'>No Themes</span>";
                $package['pluginNames'] = !empty($pluginNames) ? implode('<br>', $pluginNames) : "<span style='color: grey;'>No Plugins</span>";
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
        } catch (Exception $e) {
            $data['content'] = '<div class="errorbox">' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</div>';
        }
        echo json_encode($data);
        die();
    }

    if (
        isset($_POST['panelalpha-package']) && $_POST['panelalpha-package'] !== ''
        && ($_REQUEST['action'] ?? '') === 'save'
        && basename($_SERVER['SCRIPT_NAME']) === 'configaddons.php'
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
 * Create the PanelAlpha service, or add a package to it when called for an addon.
 *
 * Also handles three special cases: upgrading a PanelAlpha trial service owned by
 * the same client, reusing a PanelAlpha user that is already linked to the WHMCS
 * client, and refusing to reuse a PanelAlpha user that only shares the email.
 *
 * @param array{
 *     serviceid: int,
 *     userid: int,
 *     serverid?: int,
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
 *         language?: string,
 *     },
 *     configoption1: string,
 *     'Package ID'?: string,
 * } $params
 * @return string 'success' or an error message.
 */
function panelalpha_CreateAccount(array $params): string
{
    try {
        $LANG = Lang::getLang();
        $service = Service::findOrFail($params['serviceid']);
        $product = $service->product;
        $api = PanelAlphaApi::fromModel($service->serverModel);

        // Upgrade from a PanelAlpha trial: the service already exists and is only moved to the paid plan.
        $panelAlphaService = [];
        $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
        if ($panelAlphaServiceId) {
            $panelAlphaService = $api->getService($panelAlphaServiceId);
        }
        if (!empty($panelAlphaService['is_trial'])) {
            $trialUser = $api->getUserById((int) $panelAlphaService['user_id']);
            $trialOwnerEmail = is_array($trialUser) ? ($trialUser['email'] ?? null) : null;
            $clientEmail = $params['clientsdetails']['email'] ?? null;

            if (panelalpha_trial_owner_matches($trialOwnerEmail, $clientEmail)) {
                Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'User ID', $panelAlphaService['user_id']);
                if ($panelAlphaService['status'] === 'suspended') {
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
                $api->unsetServiceAsTrial((int) $panelAlphaServiceId);

                // Fill in profile fields the trial user left empty, using the WHMCS client details.
                if ($trialUser) {
                    $data = [];
                    if (empty($trialUser['first_name'])) {
                        $data['first_name'] = $params['clientsdetails']['firstname'];
                    }
                    if (empty($trialUser['last_name'])) {
                        $data['last_name'] = $params['clientsdetails']['lastname'];
                    }
                    if (empty($trialUser['company_name'])) {
                        $data['company_name'] = $params['clientsdetails']['companyname'] ?? '';
                    }
                    if (!empty($data)) {
                        $data['email'] = $trialUser['email'];
                        $api->updateUser((int) $panelAlphaService['user_id'], $data);
                    }
                }
                return 'success';
            }

            // The trial belongs to someone else. Clear the link so this service cannot take it over.
            logActivity(
                'PanelAlpha: refused trial upgrade for WHMCS service #'
                . (int) $params['serviceid']
                . ': trial PanelAlpha service #'
                . (int) $panelAlphaServiceId
                . ' is not owned by the ordering client.'
            );
            Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'Service ID', '');
            throw new Exception(sprintf($LANG['messages.trial_owner_mismatch'], (int) $panelAlphaServiceId, (int) $panelAlphaServiceId));
        }

        // Addon: add the PanelAlpha package to the parent service.
        if ($params['addonId']) {
            $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
            if (!$panelAlphaServiceId) {
                throw new Exception($LANG['messages.no_service_from_panelalpha']);
            }
            $api->addPackageToService($panelAlphaServiceId, (int) $params['Package ID']);
            return 'success';
        }

        // New service: find the PanelAlpha user to own it. Use the "User ID" set on this
        // service first, then a user already linked to the same WHMCS client.
        $ownUserId = Helper::getCustomField((int) $params['serviceid'], 'User ID');
        if ($ownUserId !== null && $ownUserId !== '') {
            if (!ctype_digit(trim((string) $ownUserId)) || (int) $ownUserId <= 0) {
                throw new Exception(sprintf($LANG['messages.invalid_user_id'], $ownUserId));
            }
            $panelAlphaUser = $api->getUserById((int) $ownUserId);
            if (!$panelAlphaUser) {
                throw new Exception(sprintf($LANG['messages.user_id_not_found'], (int) $ownUserId));
            }
        } else {
            $linkedPanelUserId = panelalpha_linked_panel_user_id(
                panelalpha_client_panel_user_ids(
                    (int) $params['userid'],
                    (int) $params['serviceid'],
                    (int) ($params['serverid'] ?? 0)
                )
            );
            $panelAlphaUser = $linkedPanelUserId !== null ? $api->getUserById($linkedPanelUserId) : null;
        }
        // Never attach the service to a PanelAlpha user only because the email matches:
        // that account may belong to another person.
        if (!$panelAlphaUser) {
            $existingPanelUser = $api->getUser($params['clientsdetails']['email']);
            if ($existingPanelUser) {
                throw new Exception(sprintf($LANG['messages.user_email_exists_not_linked'], (int) ($existingPanelUser['id'] ?? 0)));
            }
        }
        if (!$panelAlphaUser) {
            $data = [
                'first_name' => $params['clientsdetails']['firstname'],
                'last_name' => $params['clientsdetails']['lastname'],
                'company_name' => $params['clientsdetails']['companyname'] ?? '',
                'email' => $params['clientsdetails']['email'],
                'password' => Helper::generateSecurePassword()
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

        // The service was already created, for example by an earlier run of this action.
        $panelAlphaServiceId = Helper::getCustomField($params['serviceid'], 'Service ID');
        if ($panelAlphaServiceId) {
            return 'success';
        }

        $planId = $service->product->getPanelAlphaPlanId();
        $plan = $api->getPlan($planId);
        $servers = $api->getServers($plan['server_group_id'] ?? null);

        $instanceLimit = Helper::getInstanceLimit($params);
        $serverLocation = Helper::getServerLocation($params, $servers);
        $hostingAccountConfig = Helper::getHostingAccountConfig($params);

        $panelAlphaService = $api->createService($panelAlphaUser['id'], $planId, $instanceLimit, $serverLocation, $hostingAccountConfig);
        if (!$panelAlphaService) {
            throw new Exception($LANG['messages.no_service_from_panelalpha']);
        }

        $product->createCustomFieldsIfNotExists();
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'Service ID', $panelAlphaService['id']);
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'User ID', $panelAlphaUser['id']);

        $service->username = '';
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
 * Suspend the PanelAlpha service. Not supported for addons.
 *
 * @param array $params Module parameters passed by WHMCS.
 * @return string 'success' or an error message.
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
 * Unsuspend the PanelAlpha service. Not supported for addons.
 *
 * @param array $params Module parameters passed by WHMCS.
 * @return string 'success' or an error message.
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
 * Terminate the PanelAlpha service, or remove the package when called for an addon.
 *
 * With "manual termination" enabled, only a termination started by an admin on
 * the service page runs; any other one (for example from the cron) sends an
 * email to the admins and fails. The PanelAlpha user is deleted too when it
 * has no other services left.
 *
 * @param array{
 *     serverid: int,
 *     addonId?: int,
 *     configoption4: string,
 *     userid: int,
 *     serviceid: int,
 *     pid: int,
 *     domain: string,
 *     clientsdetails?: array{fullname?: string},
 *     customfields: array<string, string>,
 *     'Package ID'?: string,
 * } $params
 * @return string 'success' or an error message.
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

        $manualTermination = $params['configoption4'] === 'on';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $panelAlphaUserId = $params['customfields']['User ID'] ?? null;
        $terminateAction = panelalpha_terminate_action($manualTermination, $scriptName, $panelAlphaUserId);

        if ($terminateAction === 'manual_review') {
            $emailParams = [
                'client_id' => $params['userid'],
                'client_name' => $params['clientsdetails']['fullname'] ?? '',
                'service_id' => $params['serviceid'],
                'service_product' => \WHMCS\Product\Product::find($params['pid'])->name,
                'service_domain' => $params['domain']
            ];
            LocalApi::sendAdminEmail('PanelAlpha Service Termination', $emailParams);
            throw new Exception($LANG['messages.service_deleted_manually']);
        }

        if ($terminateAction === 'missing_user') {
            throw new Exception($LANG['messages.no_user_from_panelalpha']);
        }

        $panelAlphaServiceId = $params['customfields']['Service ID'] ?? null;
        if (!$panelAlphaServiceId) {
            throw new Exception($LANG['messages.no_service_from_panelalpha']);
        }

        // Older panels return the list of active instances and need them deleted first.
        // Current panels return a count here and delete the instances with the service.
        // Errors are ignored so the service itself is still deleted.
        try {
            $stats = $api->getServiceStats($panelAlphaServiceId);
            if (is_array($stats['active_instances'] ?? null)) {
                foreach ($stats['active_instances'] as $instance) {
                    $api->deleteInstance($instance['id']);
                }
            }
        } catch (Exception $e) {
        }

        $api->deleteService($panelAlphaServiceId);
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'Service ID', '');
        Helper::setServiceCustomFieldValue($params['pid'], $params['serviceid'], 'User ID', '');

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
 * Move the PanelAlpha service to the plan of the new product. Not supported for addons.
 *
 * @param array $params Module parameters passed by WHMCS.
 * @return string 'success' or an error message.
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
 * Test the connection to the PanelAlpha server from the WHMCS server settings.
 *
 * @param array $params Server parameters passed by WHMCS.
 * @return array{success: bool, error: string}
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
        'error' => $errorMsg ?? ''
    ];
}

/**
 * Show the service overview in the client area, or redirect to PanelAlpha when the client asks for SSO.
 *
 * @param array $params Module parameters passed by WHMCS.
 * @return array Template name and template variables for WHMCS.
 */
function panelalpha_ClientArea(array $params)
{
    try {
        if (isset($_REQUEST['sso']) && $_REQUEST['sso'] === 'yes') {
            $LANG = Lang::getLang();

            $service = Service::find($params['serviceid']);
            if ($service === null) {
                throw new Exception($LANG['ca']['service']['panelalpha']['sso_error']);
            }

            $userId = Helper::getCustomField($service->id, 'User ID');
            if (empty($userId)) {
                throw new Exception($LANG['ca']['service']['panelalpha']['sso_error']);
            }

            $api = PanelAlphaApi::fromModel($service->serverModel);
            $result = $api->getSsoToken((int) $userId);

            if (empty($result['url']) || empty($result['token'])) {
                throw new Exception($LANG['ca']['service']['panelalpha']['sso_error']);
            }

            header("Location: {$result['url']}/sso-login?token={$result['token']}");
            exit();
        }

        global $CONFIG;
        $url = $CONFIG['SystemURL'] . '/clientarea.php?action=productdetails&sso=yes&id=' . $params['serviceid'];

        return [
            'tabOverviewModuleOutputTemplate' => 'resources/templates/clientarea.tpl',
            'templateVariables' => [
                'url' => $url,
                'MGLANG' => Lang::getLang()
            ],
        ];
    } catch (Exception $e) {
        return [
            'tabOverviewReplacementTemplate' => 'resources/templates/error.tpl',
            'templateVariables' => [
                'usefulErrorHelper' => $e->getMessage(),
            ],
        ];
    }
}

/**
 * Return the provider of usage metrics for WHMCS usage billing.
 *
 * @param array $params Module parameters passed by WHMCS.
 * @return MetricsProvider
 */
function panelalpha_MetricProvider($params): MetricsProvider
{
    return new MetricsProvider($params);
}

/**
 * List the PanelAlpha services for the WHMCS "Sync Accounts" tool.
 *
 * @param array $params Server parameters passed by WHMCS.
 * @return array{success: bool, accounts?: list<array<string, mixed>>, error?: string}
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
                'username' => '',
                'domain' => '',
                'uniqueIdentifier' => $service['id'],
                'product' => $service['plan_id'],
                'primaryip' => '',
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

/**
 * Add the SSO buttons to the admin service page and handle the SSO redirects they start.
 *
 * @param array $params Module parameters passed by WHMCS.
 * @return array<string, string> Field label mapped to its HTML.
 * @throws Exception When the PanelAlpha SSO request fails.
 */
function panelalpha_AdminServicesTabFields($params): array
{
    if (isset($_REQUEST['sso']) && $_REQUEST['sso'] === 'yes') {
        try {
            $service = Service::findOrFail($params['serviceid']);
            $userId = (int) Helper::getCustomField($service->id, 'User ID');
            if ($userId <= 0) {
                throw new Exception('This service has no PanelAlpha user in the "User ID" field.');
            }

            $result = PanelAlphaApi::fromModel($service->serverModel)->getLoginAsUserSsoToken($userId);
            if (empty($result['url']) || empty($result['token'])) {
                throw new Exception('PanelAlpha did not return a login link.');
            }

            header("Location: {$result['url']}/sso-login?token={$result['token']}");
        } catch (Exception $e) {
            echo 'PanelAlpha: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES);
        }
        exit();
    }

    // SSO to the hosting control panel (cPanel, DirectAdmin or Plesk).
    if (isset($_REQUEST['hosting_sso']) && $_REQUEST['hosting_sso'] === 'yes' && isset($_REQUEST['account_id'])) {
        try {
            $service = Service::find($params['serviceid']);
            $server = $service->serverModel;
            $userId = Helper::getCustomField($service->id, 'User ID');
            $accountId = (int) $_REQUEST['account_id'];

            $api = PanelAlphaApi::fromModel($server);
            $result = $api->getControlPanelSsoUrl($accountId, $userId);

            if (!empty($result['url'])) {
                header("Location: {$result['url']}");
                exit();
            }
            echo 'PanelAlpha: the control panel did not return a login link.';
        } catch (Exception $e) {
            echo 'PanelAlpha: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES);
        }
        exit();
    }

    $LANG = Lang::getLang();

    try {
        $service = Service::find($params['serviceid']);
        $server = $service->serverModel;
        $panelAlphaServiceId = Helper::getCustomField($service->id, 'Service ID');

        if (!$panelAlphaServiceId) {
            return [
                $LANG['aa']['service']['panelalpha']['sso'] =>
                    '<a class="btn btn-default" onclick="window.open(window.location + \'&sso=yes\', \'_blank\')">' .
                    $LANG['aa']['service']['panelalpha']['login_to_panelalpha_as_user'] . '</a>'
            ];
        }

        $api = PanelAlphaApi::fromModel($server);
        $panelAlphaService = $api->getService($panelAlphaServiceId);

        $serverType = $panelAlphaService['plan']['server_type'] ?? null;
        $supportedTypes = ['cpanel', 'directadmin', 'plesk'];
        $serverAccounts = $panelAlphaService['server_accounts'] ?? [];
        $showHostingSSO = in_array($serverType, $supportedTypes, true) && !empty($serverAccounts);
    } catch (Exception $e) {
        $serverAccounts = [];
        $showHostingSSO = false;
    }

    $view = new Smarty();
    $view->assign('LANG', $LANG);
    $view->assign('serverAccountsJson', json_encode($serverAccounts));
    $view->assign('showHostingSSO', $showHostingSSO);

    return [
        $LANG['aa']['service']['panelalpha']['sso'] => $view->fetch('admin-sso-buttons.tpl')
    ];
}
