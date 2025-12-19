<?php

use WHMCS\Module\Addon\PanelAlpha\Helper;
use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


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
        let token = '';
        let characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        let charactersLength = characters.length;
        for ( let i = 0; i < length; i++ ) {
            token += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        $("input[name='fields[panelalpha][api_token]'").val(token);
    }
</script>
<button type="button" onclick="paGenerateApiToken();" class="btn btn-default btn-xs">Generate new API token</button>
HTML;

    return [
        'name' => 'PanelAlpha WordPress Hosting For WHMCS',
        'description' => 'PanelAlpha WordPress Hosting For WHMCS will let you complete all essential tasks in the provisioning and management of WordPress websites without leaving your WHMCS system.',
        'author' => 'PanelAlpha',
        'version' => '1.6.1',
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
        
        $statusColor = '#000';
        if ($service->domainstatus == 'Active') $statusColor = '#46a546';
        elseif ($service->domainstatus == 'Pending') $statusColor = '#f0ad4e';
        elseif ($service->domainstatus == 'Suspended') $statusColor = '#9d261d';
        elseif ($service->domainstatus == 'Terminated') $statusColor = '#9d261d';
        elseif ($service->domainstatus == 'Cancelled') $statusColor = '#999';
        elseif ($service->domainstatus == 'Fraud') $statusColor = '#000';

        echo '<tr>';
        echo '<td><a href="' . $serviceLink . '">' . $service->id . '</a></td>';
        echo '<td><a href="' . $clientLink . '">' . $clientName . '</a></td>';
        echo '<td><a href="' . $serviceLink . '">' . $service->productname . '</a></td>';
        echo '<td><a href="http://' . $service->domain . '" target="_blank">' . $service->domain . '</a></td>';
        echo '<td><span class="label" style="background-color:'.$statusColor.'; color:#fff;">' . $service->domainstatus . '</span></td>';
        echo '</tr>';
    }

    if ($count == 0) {
        echo '<tr><td colspan="5" class="text-center">No services found</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
