<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use \Illuminate\Database\Eloquent\Model;


class EmailTemplate extends Model
{
    protected $table = 'tblemailtemplates';
    protected $fillable = [
        'type',
        'name',
        'subject',
        'message',
        'attachments',
        'fromname',
        'fromemail',
        'disabled',
        'custom',
        'language',
        'copyto',
        'plaintext'
    ];

    public static function createManualServiceTerminationEmailTemplate()
    {
        self::updateOrInsert(
            [
                'type' => 'admin',
                'name' => 'PanelAlpha Service Termination',

            ],
            [
                'message' => '<p>This product/service should be terminated.</p><p>Client ID: {$client_id}<br />Service ID: {$service_id}<br />Product/Service: {$service_product}<br />Domain: {$service_domain}</p><p><a href="{$whmcs_admin_url}clientsservices.php?userid={$client_id}&id={$service_id}">{$whmcs_admin_url}clientsservices.php?userid={$client_id}&id={$service_id}</a></p>',
                'subject' => 'Manual Service Termination',
                'attachments' => '',
                'fromname' => '',
                'fromemail' => '',
                'disabled' => 0,
                'custom' => 0,
                'copyto' => '',
                'blind_copy_to' => '',
                'plaintext' => 0
            ]
        );
    }

    public static function createWelcomeEmailTemplate()
    {
        self::updateOrInsert(
            [
                'type' => 'product',
                'name' => 'PanelAlpha Welcome Email',
            ],
            [
                'message' => '<p>Dear {$client_name},</p><p>We are thrilled to welcome you to PanelAlpha, the leading-edge platform that offers complete WordPress automation and a powerful control panel for your clients. With PanelAlpha, you can gain a competitive advantage and take your WordPress hosting services to new heights.</p><p>Your account is now created and you can start managing your WordPress instances.</p><p>To get started, log in to our Client Area and follow the link to access PanelAlpha <a href=”{$whmcs_url}clientarea.php?action=productdetails&id={$service_id}” target="_blank">{$whmcs_url}clientarea.php?action=productdetails&id={$service_id}</a></p><p>We look forward to supporting you on your journey with PanelAlpha. Should you have any questions or need assistance, please don\'t hesitate to reach out to our dedicated support team.</p><p>{$signature}<p/>',
                'subject' => 'Welcome to PanelAlpha - Your Ultimate Solution for WordPress Management!',
                'attachments' => '',
                'fromname' => '',
                'fromemail' => '',
                'disabled' => 0,
                'custom' => 0,
                'copyto' => '',
                'blind_copy_to' => '',
                'plaintext' => 0
            ]
        );
    }
}