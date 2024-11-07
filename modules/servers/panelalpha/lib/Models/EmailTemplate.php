<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

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

    private static array $emailTemplates = [
        'PanelAlpha Service Termination' => [
            'type' => 'admin',
            'name' => 'PanelAlpha Service Termination',
            'message' => 'serviceTerminationEmail.html',
            'subject' => 'Manual Service Termination',

        ],
        'PanelAlpha Welcome Email' => [
            'type' => 'product',
            'name' => 'PanelAlpha Welcome Email',
            'message' => 'welcomeEmail.html',
            'subject' => 'Welcome to PanelAlpha - Your Ultimate Solution for WordPress Management!',
        ],
        'PanelAlpha Welcome New User Email' => [
            'type' => 'product',
            'name' => 'PanelAlpha Welcome New User Email',
            'message' => 'welcomeNewUser.html',
            'subject' => 'Welcome to PanelAlpha  - Your Login Credentials',
        ]
    ];

    /**
     * @return void
     */
    public static function createEmailTemplatesIfNotExist(): void
    {
        foreach (self::$emailTemplates as $template) {
            $emailTemplate = self::where('name', $template['name'])
                ->where('type', $template['type'])
                ->first();

            if (!$emailTemplate) {
                $emailTemplateFile = ROOTDIR . DIRECTORY_SEPARATOR .
                    "modules" . DIRECTORY_SEPARATOR .
                    "servers" . DIRECTORY_SEPARATOR .
                    "panelalpha" . DIRECTORY_SEPARATOR .
                    "templates" . DIRECTORY_SEPARATOR .
                    "emails" . DIRECTORY_SEPARATOR .
                    $template['message'];


                self::insert([
                    'type' => $template['type'],
                    'name' => $template['name'],
                    'message' => file_get_contents($emailTemplateFile),
                    'subject' => $template['subject'],
                    'attachments' => '',
                    'fromname' => '',
                    'fromemail' => '',
                    'disabled' => 0,
                    'custom' => 0,
                    'copyto' => '',
                    'blind_copy_to' => '',
                    'plaintext' => 0
                ]);
            }
        }
    }
}
