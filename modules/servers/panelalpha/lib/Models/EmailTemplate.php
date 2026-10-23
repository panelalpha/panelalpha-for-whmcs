<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * WHMCS email template (tblemailtemplates).
 *
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string $subject
 * @property string $message
 *
 * @method static Builder where(mixed $column, mixed $operator = null, mixed $value = null)
 * @method static bool insert(array $values)
 */
class EmailTemplate extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblemailtemplates';

    /**
     * @var string[]
     */
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
     * Templates created by createPanelalphaEmailTemplates(), keyed by template name.
     * The "template" path is relative to the WHMCS root directory.
     *
     * @var array<string, array{type: string, name: string, subject: string, template: string}>
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
     * Replace the old client greeting in the termination email template.
     *
     * Older installs greet the admin with the client name in the termination email.
     * Only the stock greeting line is replaced, so other edits made by the admin stay.
     *
     * @return void
     */
    public static function fixTerminationEmailGreeting(): void
    {
        $oldGreeting = '<p>Dear {$client_name},</p>';

        self::where('name', 'PanelAlpha Service Termination')
            ->where('type', 'admin')
            ->where('message', 'like', '%' . $oldGreeting . '%')
            ->get()
            ->each(function (self $template) use ($oldGreeting) {
                $template->message = str_replace($oldGreeting, '<p>Hello,</p>', $template->message);
                $template->save();
            });
    }

    /**
     * Create the PanelAlpha email templates that do not exist yet.
     *
     * Existing templates are never overwritten, so admin edits are kept.
     *
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
