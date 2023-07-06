<?php

namespace WHMCS\Module\Server\PanelAlpha;

class LocalApi
{
    public static function sendAdminEmail(string $templateName, array $params)
    {
        $command = 'SendAdminEmail';
        $postData = [
            'messagename' => $templateName,
            'mergefields' => [
                'client_id' => $params['client_id'],
                'service_id' => $params['service_id'],
                'service_product' => $params['service_product'],
                'service_domain' => $params['service_domain']
            ],
        ];
        return localAPI($command, $postData);
    }

    public static function sendEmail(string $templateName, array $params)
    {
        $command = 'SendEmail';
        $postData = [
            'messagename' => $templateName,
            'id' => $params['service_id'],
        ];

        return localAPI($command, $postData);
    }
}