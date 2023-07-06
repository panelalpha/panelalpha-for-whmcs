<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\PanelAlpha\Models\EmailTemplate;
use WHMCS\Module\Server\PanelAlpha\Models\Service;
use WHMCS\View\Menu\Item as MenuItem;


add_hook('AdminAreaFooterOutput', 1, function ($params) {
    if ($_REQUEST['action'] !== 'save' && basename($_SERVER["SCRIPT_NAME"]) === 'configproducts.php' && isset($_REQUEST['id'])) {
        $panelAlphaWelcomeEmail = EmailTemplate::where('name', 'PanelAlpha Welcome Email')->first();
        $product = \WHMCS\Module\Server\PanelAlpha\Models\Product::findOrFail($_REQUEST['id']);
        $selected = $product->welcomeemail == $panelAlphaWelcomeEmail->id;
        return <<<HTML
<script type="text/javascript">
	const welcomeEmail = $('[name="welcomeemail"]');
	console.log(welcomeEmail.val())
	const option = $('<option>', {
      value: {$panelAlphaWelcomeEmail->id},
      text: "{$panelAlphaWelcomeEmail->name}"
    });

    welcomeEmail.append(option);
    if ({$selected}) {
      welcomeEmail.val({$panelAlphaWelcomeEmail->id})	
	}
</script>
HTML;
    }
});

add_hook('ClientAreaSecondaryNavbar', 1, function (MenuItem $secondaryNavbar) {
    if (isset($GLOBALS['legacyClient'])) {
        $clientId = $GLOBALS['legacyClient']->getID();
        $panelAlphaFirstService = Service::getFirstServiceForClient($clientId);
        global $CONFIG;

        if ($panelAlphaFirstService->configoption5 === 'on') {
            $secondaryNavbar->addChild('panelalpha_sso_link', array(
                'label' => '<span style="margin-right: 12px; color: #5bc0de;">Manage Wordpress <i class="fas fa-external-link"></i></span>',
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


add_hook('ClientAreaPageHome', 1, function ($params) {
    $panelAlphaServiceId = $_REQUEST['paupgradeserviceid'] ?? "";
    if ($panelAlphaServiceId) {
        $clientId = $params['client']->id;
        $service = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
            ->join('tblcustomfields', 'tblcustomfields.relid', '=', 'tblproducts.id')
            ->join('tblcustomfieldsvalues', 'tblcustomfieldsvalues.fieldid', '=', 'tblcustomfields.id')
            ->where('tblcustomfields.type', 'product')
            ->where('tblcustomfields.fieldname', 'Service ID')
            ->where('tblproducts.servertype', 'panelalpha')
            ->where('tblhosting.userid', $clientId)
            ->where('tblcustomfieldsvalues.value', $panelAlphaServiceId)
            ->select([
                'tblhosting.*'
            ])
            ->first();
        global $CONFIG;
        header("Location: {$CONFIG['SystemURL']}/upgrade.php?type=package&id={$service->id}");
        die();
    }
});

add_hook('AdminAreaHeadOutput', 1, function ($params) {
    if (isset($params['filename']) && $params['filename'] != 'configservers' && (isset($_GET['action']) && $_GET['action'] != "manage" || !isset($_GET['id']))) {
        return;
    }
    $jsFile = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "servers" . DIRECTORY_SEPARATOR . "panelalpha" . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . "server.js";
    return '<script type="text/javascript"> ' . file_get_contents($jsFile) . '</script>';
});