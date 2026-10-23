<?php

/**
 * Decide what TerminateAccount should do with a PanelAlpha service.
 *
 * With manual termination enabled, only a termination started from the admin
 * service page (clientsservices.php) may delete the service. Any other caller,
 * such as the WHMCS cron, gets 'manual_review' so an admin can check it first.
 *
 * @param bool $manualTermination Whether the product has manual termination enabled.
 * @param string $scriptName The current script path, usually $_SERVER['SCRIPT_NAME'].
 * @param string|null $userId The PanelAlpha user ID stored on the service.
 * @return string One of 'manual_review', 'missing_user' or 'delete'.
 */
function panelalpha_terminate_action(bool $manualTermination, string $scriptName, ?string $userId): string
{
    if ($manualTermination && basename($scriptName) !== 'clientsservices.php') {
        return 'manual_review';
    }

    if ($userId === null || $userId === '') {
        return 'missing_user';
    }

    return 'delete';
}
