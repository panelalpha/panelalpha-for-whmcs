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

    public static function createManualServiceTerminationEmailTemplate(): void
    {
        $emailFile = ROOTDIR . DIRECTORY_SEPARATOR .
            "modules" . DIRECTORY_SEPARATOR .
            "servers" . DIRECTORY_SEPARATOR .
            "panelalpha" . DIRECTORY_SEPARATOR .
            "templates" . DIRECTORY_SEPARATOR .
            "emails" . DIRECTORY_SEPARATOR .
            "serviceTerminationEmail.html";

        self::updateOrInsert(
            [
                'type' => 'admin',
                'name' => 'PanelAlpha Service Termination',

            ],
            [
                'message' => file_get_contents($emailFile),
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

    public static function createWelcomeEmailTemplate(): void
    {
        $emailFile = ROOTDIR . DIRECTORY_SEPARATOR .
            "modules" . DIRECTORY_SEPARATOR .
            "servers" . DIRECTORY_SEPARATOR .
            "panelalpha" . DIRECTORY_SEPARATOR .
            "templates" . DIRECTORY_SEPARATOR .
            "emails" . DIRECTORY_SEPARATOR .
            "welcomeEmail.html";


        self::updateOrInsert(
            [
                'type' => 'product',
                'name' => 'PanelAlpha Welcome Email',
            ],
            [
                'message' => file_get_contents($emailFile),
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