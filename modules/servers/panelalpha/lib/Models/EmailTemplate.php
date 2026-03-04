<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $subject
 * @property string $message
 */
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

    /**
     * @var array<array>
     */
    private static array $emailTemplates = [
        'PanelAlpha Service Termination' => [
            'type' => 'admin',
            'name' => 'PanelAlpha Service Termination',
            'subject' => 'Manual Service Termination',
            'template' => '/modules/servers/panelalpha/resources/templates/emails/serviceTerminationEmail.html',

        ],
        'PanelAlpha Welcome Email' => [
            'type' => 'product',
            'name' => 'PanelAlpha Welcome Email',
            'subject' => 'Welcome to PanelAlpha - Your Ultimate Solution for WordPress Management!',
            'template' => '/modules/servers/panelalpha/resources/templates/emails/welcomeEmail.html',
        ],
        'PanelAlpha Welcome New User Email' => [
            'type' => 'product',
            'name' => 'PanelAlpha Welcome New User Email',
            'subject' => 'Welcome to PanelAlpha  - Your Login Credentials',
            'template' => '/modules/servers/panelalpha/resources/templates/emails/welcomeNewUser.html',
        ]
    ];

    /**
     * @return void
     */
    public static function createPanelalphaEmailTemplates(): void
    {
        foreach (self::$emailTemplates as $template) {
            /** @var EmailTemplate|null $emailTemplate */
            $emailTemplate = self::where('name', $template['name'])
                ->where('type', $template['type'])
                ->first();

            if ($emailTemplate !== null) {
                continue;
            }

            $path = ROOTDIR . $template['template'];
            if (!file_exists($path)) {
                logActivity("PanelAlpha: Email template file missing: {$path}");
                continue;
            }

            $message = file_get_contents($path);
            if ($message === false) {
                logActivity("PanelAlpha: Email template file missing: {$path}");
                continue;
            }

            self::insert([
                'type' => $template['type'],
                'name' => $template['name'],
                'message' => $message,
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
            logActivity("PanelAlpha: Email template created: {$template['name']}");
        }
    }
}
