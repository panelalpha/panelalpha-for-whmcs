<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\PanelAlpha\Helper;
use WHMCS\Module\Server\PanelAlpha\Models\EmailTemplate;
use WHMCS\Module\Server\PanelAlpha\Models\Hosting;
use WHMCS\Module\Server\PanelAlpha\Models\Server;
use WHMCS\Module\Server\PanelAlpha\Models\Service;
use WHMCS\Module\Server\PanelAlpha\Lang;
use WHMCS\View\Menu\Item as MenuItem;


add_hook('AdminAreaFooterOutput', 1, function ($params) {
    if ($_REQUEST['action'] !== 'save' && basename($_SERVER["SCRIPT_NAME"]) === 'configproducts.php' && isset($_REQUEST['id'])) {
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
</script>
HTML;
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
                'uri' => $CONFIG['SystemURL'] . '/modules/servers/panelalpha/lib/SsoLogin.php?id=' . $panelAlphaFirstService->id,
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
    $hosting = Hosting::getService($panelAlphaServiceId);
    if (!$hosting) {
        Helper::showPageNotFound();
    }

    global $CONFIG;
    header("Location: {$CONFIG['SystemURL']}/upgrade.php?type=package&id={$hosting->id}");
    exit();
});


add_hook('AdminAreaHeadOutput', 1, function ($params) {
    if (isset($params['filename']) && $params['filename'] != 'configservers' && (isset($_GET['action']) && $_GET['action'] != "manage" || !isset($_GET['id']))) {
        return;
    }
    $jsFile = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "servers" . DIRECTORY_SEPARATOR . "panelalpha" . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . "server.js";
    return '<script type="text/javascript"> ' . file_get_contents($jsFile) . '</script>';
});

add_hook('ServerAdd', 1, function ($params) {
    $server = Server::findOrFail($params['serverid']);
    $server->setDefaultPort();
});

add_hook('ServerEdit', 1, function ($params) {
    $server = Server::findOrFail($params['serverid']);
    $server->setDefaultPort();
});