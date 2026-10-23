<?php

/**
 * PanelAlpha addon module: configuration and admin area output.
 */

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\PanelAlpha\Helper;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Returns the addon configuration. Generates an API token on first use.
 *
 * @return array<string, mixed>
 */
function panelalpha_config()
{
    $config = Helper::config();
    if (empty($config['api_token'])) {
        Helper::updateConfig('api_token', Helper::generateRandomString());
    }

    $desc = <<<HTML
<script type="text/javascript">
    function paGenerateApiToken(length = 32) {
        if (!confirm('Are you sure to generate new API token? The current token will be invalidated.')) {
            return;
        }
        const characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const charactersLength = characters.length;
        const randomValues = new Uint8Array(length);
        crypto.getRandomValues(randomValues);
        let token = '';
        for (let i = 0; i < length; i++) {
            token += characters.charAt(randomValues[i] % charactersLength);
        }
        $("input[name='fields[panelalpha][api_token]']").val(token);
    }
</script>
<button type="button" onclick="paGenerateApiToken();" class="btn btn-default btn-xs">Generate new API token</button>
HTML;

    return [
        'name' => 'PanelAlpha WordPress Hosting For WHMCS',
        'description' => 'PanelAlpha WordPress Hosting For WHMCS will let you complete all essential tasks in the provisioning and management of WordPress websites without leaving your WHMCS system.',
        'author' => 'PanelAlpha',
        'version' => '1.7.0',
        'fields' => [
            'api_token' => [
                'FriendlyName' => 'API Token',
                'Type' => 'text',
                'Size' => '25',
                'Default' => '',
                'Description' => $desc,
            ],
            'allow_from' => [
                'FriendlyName' => 'Allow From',
                'Type' => 'text',
                'Size' => '25',
                'Default' => '',
                'Description' => 'IP Addresses, comma separated, use `*` to allow from all.',
            ],
        ],
    ];
}

/**
 * Prints the list of PanelAlpha services in the addon admin page.
 *
 * @param array<string, mixed> $vars Addon parameters passed by WHMCS (not used).
 * @return void
 */
function panelalpha_output($vars)
{
    $services = Capsule::table('tblhosting')
        ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
        ->join('tblclients', 'tblhosting.userid', '=', 'tblclients.id')
        ->where('tblproducts.servertype', 'panelalpha')
        ->select(
            'tblhosting.id',
            'tblhosting.userid',
            'tblhosting.domain',
            'tblhosting.domainstatus',
            'tblproducts.name as productname',
            'tblclients.firstname',
            'tblclients.lastname',
            'tblclients.companyname'
        )
        ->get();

    $count = count($services);

    echo '<div style="margin: 20px 0; font-size: 18px;">Total PanelAlpha Services: <strong>' . $count . '</strong></div>';

    echo '<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Client</th>';
    echo '<th>Product/Service</th>';
    echo '<th>Domain</th>';
    echo '<th>Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($services as $service) {
        $clientName = $service->firstname . ' ' . $service->lastname;
        if ($service->companyname) {
            $clientName .= ' (' . $service->companyname . ')';
        }
        $clientLink = 'clientssummary.php?userid=' . $service->userid;
        $serviceLink = 'clientsservices.php?userid=' . $service->userid . '&id=' . $service->id;

        $statusColor = match ($service->domainstatus) {
            'Active' => '#46a546',
            'Pending' => '#f0ad4e',
            'Suspended', 'Terminated' => '#9d261d',
            'Cancelled' => '#999',
            default => '#000',
        };

        echo '<tr>';
        echo '<td><a href="' . htmlspecialchars($serviceLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string) $service->id, ENT_QUOTES, 'UTF-8') . '</a></td>';
        echo '<td><a href="' . htmlspecialchars($clientLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8') . '</a></td>';
        echo '<td><a href="' . htmlspecialchars($serviceLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($service->productname, ENT_QUOTES, 'UTF-8') . '</a></td>';
        echo '<td><a href="' . htmlspecialchars('http://' . $service->domain, ENT_QUOTES, 'UTF-8') . '" target="_blank">' . htmlspecialchars($service->domain, ENT_QUOTES, 'UTF-8') . '</a></td>';
        echo '<td><span class="label" style="background-color:' . $statusColor . '; color:#fff;">' . htmlspecialchars($service->domainstatus, ENT_QUOTES, 'UTF-8') . '</span></td>';
        echo '</tr>';
    }

    if ($count === 0) {
        echo '<tr><td colspan="5" class="text-center">No services found</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
