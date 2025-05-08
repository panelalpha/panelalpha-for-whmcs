<?php

use WHMCS\Module\Addon\PanelAlpha\Helper;

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
        'version' => '1.2.0',
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
