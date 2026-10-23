<?php

/**
 * Check that an admin request may change the product configuration.
 *
 * Only a POST request with a valid WHMCS admin CSRF token is allowed, so a link
 * or a page on another site cannot start the action.
 *
 * @return bool True when the request is a POST with a valid CSRF token.
 */
function panelalpha_admin_config_write_allowed(): bool
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return false;
    }

    if (!function_exists('check_token')) {
        return false;
    }

    return check_token('WHMCS.admin.default') === true;
}
