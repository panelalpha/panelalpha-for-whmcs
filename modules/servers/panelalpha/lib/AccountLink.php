<?php

/**
 * Helpers that decide which PanelAlpha user a WHMCS client is linked to.
 */

use WHMCS\Module\Server\PanelAlpha\Helper;
use WHMCS\Module\Server\PanelAlpha\Models\Service;

/**
 * Whether the trial user email and the client email are the same (case-insensitive).
 *
 * @param string|null $trialUserEmail
 * @param string|null $clientEmail
 * @return bool False when either email is missing or empty.
 */
function panelalpha_trial_owner_matches(?string $trialUserEmail, ?string $clientEmail): bool
{
    if ($trialUserEmail === null || $clientEmail === null) {
        return false;
    }

    $trialUserEmail = trim($trialUserEmail);
    $clientEmail = trim($clientEmail);

    if ($trialUserEmail === '' || $clientEmail === '') {
        return false;
    }

    return mb_strtolower($trialUserEmail) === mb_strtolower($clientEmail);
}

/**
 * Get the single PanelAlpha user ID shared by the client's other services.
 *
 * Empty and non-numeric values are skipped. When the services point to more
 * than one user, nothing is returned and the conflict is logged, so the
 * module does not guess which account to use.
 *
 * @param list<mixed> $existingUserIds
 * @return int|null
 */
function panelalpha_linked_panel_user_id(array $existingUserIds): ?int
{
    $distinct = [];

    foreach ($existingUserIds as $userId) {
        if ($userId === null || $userId === '') {
            continue;
        }

        if (is_int($userId)) {
            $parsed = $userId;
        } else {
            $candidate = trim((string) $userId);
            if ($candidate === '' || !ctype_digit($candidate)) {
                continue;
            }
            $parsed = (int) $candidate;
        }

        if ($parsed > 0) {
            $distinct[$parsed] = true;
        }
    }

    $ids = array_keys($distinct);

    if ($ids === []) {
        return null;
    }

    if (count($ids) === 1) {
        return $ids[0];
    }

    if (function_exists('logActivity')) {
        logActivity(
            'PanelAlpha: WHMCS client has conflicting PanelAlpha User ID custom field values: '
            . implode(', ', $ids)
        );
    }

    return null;
}

/**
 * Collect the "User ID" custom field values of the client's other PanelAlpha
 * services on the same server.
 *
 * @param int $clientId
 * @param int $excludeServiceId Service to leave out, usually the one being created.
 * @param int $serverId
 * @return list<string>
 */
function panelalpha_client_panel_user_ids(int $clientId, int $excludeServiceId, int $serverId): array
{
    if ($serverId <= 0) {
        return [];
    }

    $userIds = [];

    $services = Service::query()
        ->where('userid', $clientId)
        ->where('id', '!=', $excludeServiceId)
        ->where('server', $serverId)
        ->panelalpha()
        ->get();

    foreach ($services as $service) {
        $userId = Helper::getCustomField((int) $service->id, 'User ID');
        if ($userId !== null && $userId !== '') {
            $userIds[] = (string) $userId;
        }
    }

    return $userIds;
}
