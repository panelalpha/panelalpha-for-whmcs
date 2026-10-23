<?php

namespace WHMCS\Module\Server\PanelAlpha\Apis;

use WHMCS\Module\Server\PanelAlpha\Models\EmailTemplate;

/**
 * Sends emails through the WHMCS local API.
 */
class LocalApi
{
    /**
     * Send an admin notification email from a WHMCS email template.
     *
     * @param string $templateName Name of the WHMCS email template.
     * @param array $params Merge fields: client_id, client_name, service_id, service_product, service_domain.
     * @return void
     */
    public static function sendAdminEmail(string $templateName, array $params): void
    {
        $command = 'SendAdminEmail';
        $postData = [
            'messagename' => $templateName,
            // WHMCS 9.1 logs "Undefined variable" warnings when these optional fields are missing.
            'type' => 'system',
            'deptid' => 0,
            'custommessage' => '',
            'mergefields' => [
                'client_id' => $params['client_id'],
                'client_name' => $params['client_name'] ?? '',
                'service_id' => $params['service_id'],
                'service_product' => $params['service_product'],
                'service_domain' => $params['service_domain'],
            ],
        ];
        localAPI($command, $postData);
    }

    /**
     * Send a product email to the client with the PanelAlpha login details.
     *
     * Uses the template in the client language and falls back to the default
     * template. Logs an activity entry when no template is found.
     *
     * @param string $templateName Name of the WHMCS email template.
     * @param string $language Client language.
     * @param array $params service_id, user_email, user_password and login_url.
     * @return void
     */
    public static function sendUserEmail(string $templateName, string $language, array $params): void
    {
        /** @var EmailTemplate|null $emailTemplate */
        $emailTemplate = EmailTemplate::where('name', $templateName)
            ->where('language', $language)
            ->first();

        if ($emailTemplate === null) {
            // An empty language is the default template.
            /** @var EmailTemplate|null $emailTemplate */
            $emailTemplate = EmailTemplate::where('name', $templateName)
                ->where('language', '')
                ->first();
        }

        if ($emailTemplate === null) {
            logActivity("PanelAlpha: Email Sending Failed - Service ID: {$params['service_id']} - {$templateName}");
            return;
        }

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
                'login_url' => $params['login_url'],
            ])),
        ];
        localAPI($command, $postData);
    }
}
