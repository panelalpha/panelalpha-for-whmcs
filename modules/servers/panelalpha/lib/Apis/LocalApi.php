<?php

namespace WHMCS\Module\Server\PanelAlpha\Apis;

use WHMCS\Module\Server\PanelAlpha\Models\EmailTemplate;

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

    public static function sendUserEmail(string $templateName, array $params)
    {
        $emailTemplate = EmailTemplate::where('name', $templateName)->first();


        $command = 'SendEmail';
        $postData = [
            'messagename' => $templateName,
            'id' => $params['service_id'],
            'customtype' => 'product',
            'customsubject' => $emailTemplate->subject,
            'custommessage' => $emailTemplate->message,
            'customvars' => base64_encode(serialize([
                'user_email' => $params['user_email'],
                'user_password' => $params['user_password'],
                'login_url' => $params['login_url']
            ]))
        ];
        return localAPI($command, $postData);
    }
}