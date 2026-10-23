<?php

namespace WHMCS\Module\Server\PanelAlpha\Apis;

use Exception;
use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi\Request;

/**
 * Client for the PanelAlpha admin API.
 */
class PanelAlphaApi
{
    /** Number of items requested per page when fetching a full list. */
    private const LIST_PER_PAGE = 100;

    /** Upper limit of pages fetched for one list, so a bad response cannot loop forever. */
    private const LIST_MAX_PAGES = 100;

    /** Creating a service or a WordPress instance can take the panel longer than other calls. */
    private const CREATE_TIMEOUT = 180;

    /** @var Request */
    protected $request;

    /**
     * Build a client from a WHMCS server model (a tblservers row).
     *
     * @param \Illuminate\Database\Eloquent\Model $server Server model with hostname, port and access hash.
     * @return PanelAlphaApi
     */
    public static function fromModel($server): PanelAlphaApi
    {
        return new self($server->toArray());
    }

    /**
     * @param array $params WHMCS module params or a tblservers row as an array.
     */
    public function __construct(array $params)
    {
        $this->request = new Request(self::withDecryptedAccessHash($params));
    }

    /**
     * Decrypt the access hash of a tblservers row on WHMCS 9.1+.
     *
     * WHMCS 9.1+ stores the access hash encrypted, so a tblservers row holds cipher text.
     * Module params from WHMCS (serveraccesshash) are already decrypted and are left as they are.
     *
     * @param array $params
     * @return array
     */
    private static function withDecryptedAccessHash(array $params): array
    {
        if (
            empty($params['id'])
            || !array_key_exists('accesshash', $params)
            || array_key_exists('serveraccesshash', $params)
            || !method_exists(\WHMCS\Product\Server::class, 'getDecryptedAccessHash')
        ) {
            return $params;
        }

        $params['accesshash'] = \WHMCS\Product\Server::getDecryptedAccessHash((int) $params['id']);

        return $params;
    }

    /**
     * Check that the panel answers and accepts the API token.
     *
     * @return void
     * @throws Exception When the request fails or the panel returns no data.
     */
    public function testConnection(): void
    {
        $endpoint = 'instances';
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        $this->request->curl->setTimeout(5);
        $result = $this->request->call($method, $endpoint);

        // An empty list is fine (a new panel has no instances); only a missing answer fails.
        if (!is_array($result)) {
            throw new Exception('Test Connection Failed');
        }
    }

    /**
     * Get all plans.
     *
     * @return array
     * @throws Exception
     */
    public function getPlans(): array
    {
        $this->request->setAction(__FUNCTION__);
        return $this->fetchPaginatedList('GET', 'plans');
    }

    /**
     * Get one plan.
     *
     * @param int $id Plan ID.
     * @return array
     * @throws Exception
     */
    public function getPlan(int $id): array
    {
        $endpoint = 'plans/' . $id;
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * Find a user by email address.
     *
     * @param string $email
     * @return array|null
     * @throws Exception
     */
    public function getUser(string $email): ?array
    {
        $endpoint = 'users/email?email=' . rawurlencode($email);
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * Get a user by ID.
     *
     * @param int $id PanelAlpha user ID.
     * @return array|null
     * @throws Exception
     */
    public function getUserById(int $id): ?array
    {
        $endpoint = 'users/' . $id;
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * Create an active service for a user.
     *
     * @param int $userId PanelAlpha user ID.
     * @param int $planId PanelAlpha plan ID.
     * @param int|null $instanceLimit Maximum number of instances, null for the plan default.
     * @param int|null $serverLocation Server location ID, null for the plan default.
     * @param array|null $hostingAccountConfig Extra hosting account settings.
     * @return array|null
     * @throws Exception
     */
    public function createService(
        int $userId,
        int $planId,
        ?int $instanceLimit,
        ?int $serverLocation,
        ?array $hostingAccountConfig,
    ): ?array {
        $endpoint = 'users/' . $userId . '/services';
        $method = 'POST';
        $data = [
            'plan_id' => $planId,
            'status' => 'active',
            'instance_limit' => $instanceLimit,
            'server_location' => $serverLocation,
            'hosting_account_config' => $hostingAccountConfig,
        ];
        $this->request->setAction(__FUNCTION__);
        return $this->callWithCreateTimeout($method, $endpoint, $data);
    }

    /**
     * Create a user.
     *
     * @param array $data User fields sent to the API.
     * @return array|null
     * @throws Exception
     */
    public function createUser(array $data): ?array
    {
        $endpoint = 'users';
        $method = 'POST';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint, $data);
    }

    /**
     * Update a user.
     *
     * @param int $id PanelAlpha user ID.
     * @param array $data User fields to change.
     * @return array|null
     * @throws Exception
     */
    public function updateUser(int $id, array $data): ?array
    {
        $endpoint = 'users/' . $id;
        $method = 'PUT';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint, $data);
    }

    /**
     * Create a WordPress instance in a service.
     *
     * @param string $domain
     * @param string $instanceName
     * @param string $theme
     * @param int $serviceId PanelAlpha service ID.
     * @param int $userId PanelAlpha user ID.
     * @return array|null
     * @throws Exception
     */
    public function createInstance(string $domain, string $instanceName, string $theme, int $serviceId, int $userId): ?array
    {
        $endpoint = 'instances';
        $method = 'POST';
        $data = [
            'name' => $instanceName,
            'domain' => $domain,
            'theme' => $theme,
            'user_id' => $userId,
            'service_id' => $serviceId,
        ];

        $this->request->setAction(__FUNCTION__);
        return $this->callWithCreateTimeout($method, $endpoint, $data);
    }

    /**
     * Send a request with the longer create timeout, then restore the previous timeout.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array|null
     * @throws Exception
     */
    private function callWithCreateTimeout(string $method, string $endpoint, array $data): ?array
    {
        $previous = $this->request->curl->getRequestTimeout();
        $this->request->curl->setRequestTimeout(self::CREATE_TIMEOUT);
        try {
            return $this->request->call($method, $endpoint, $data);
        } finally {
            $this->request->curl->setRequestTimeout($previous);
        }
    }

    /**
     * Get a service.
     *
     * @param int $serviceId PanelAlpha service ID.
     * @return array
     * @throws Exception
     */
    public function getService(int $serviceId): array
    {
        $endpoint = 'services/' . $serviceId;
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * Remove the trial flag from a service.
     *
     * @param int $serviceId PanelAlpha service ID.
     * @return array
     * @throws Exception
     */
    public function unsetServiceAsTrial(int $serviceId): array
    {
        $endpoint = 'services/' . $serviceId . '/unset-as-trial';
        $method = 'PUT';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * Get usage stats of one service.
     *
     * @param int $serviceId PanelAlpha service ID.
     * @return array|null
     * @throws Exception
     */
    public function getServiceStats(int $serviceId): ?array
    {
        $endpoint = 'services/' . $serviceId . '/stats';
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * Get usage stats of all services, keyed by PanelAlpha service ID.
     *
     * @return array
     * @throws Exception
     */
    public function getServicesStats(): array
    {
        try {
            $endpoint = 'services/stats';
            $method = 'GET';
            $this->request->setAction(__FUNCTION__);
            return $this->request->call($method, $endpoint);
        } catch (Exception $exception) {
            // Runs from the usage metrics cron, where the error is otherwise easy to miss.
            if (function_exists('logModuleCall')) {
                logModuleCall('panelalpha', 'getServicesStats', '', $exception->getMessage());
            }
            throw $exception;
        }
    }

    /**
     * Delete a WordPress instance.
     *
     * @param int $id Instance ID.
     * @return void
     * @throws Exception
     */
    public function deleteInstance(int $id): void
    {
        $endpoint = 'instances/' . $id;
        $method = 'DELETE';
        $this->request->setAction(__FUNCTION__);
        $this->request->call($method, $endpoint);
    }

    /**
     * Delete a service, forcing termination of its instances.
     *
     * @param int $serviceId PanelAlpha service ID.
     * @return void
     * @throws Exception
     */
    public function deleteService(int $serviceId): void
    {
        $endpoint = 'services/' . $serviceId;
        $method = 'DELETE';
        $data = [
            'force_termination' => true,
        ];
        $this->request->setAction(__FUNCTION__);
        $this->request->call($method, $endpoint, $data);
    }

    /**
     * Suspend a user's service.
     *
     * @param int $userId PanelAlpha user ID.
     * @param int $serviceId PanelAlpha service ID.
     * @return void
     * @throws Exception
     */
    public function suspendAccount(int $userId, int $serviceId): void
    {
        $endpoint = 'users/' . $userId . '/services/' . $serviceId . '/suspend';
        $method = 'PUT';
        $this->request->setAction(__FUNCTION__);
        $this->request->call($method, $endpoint);
    }

    /**
     * Unsuspend a user's service.
     *
     * @param int $userId PanelAlpha user ID.
     * @param int $serviceId PanelAlpha service ID.
     * @return void
     * @throws Exception
     */
    public function unsuspendAccount(int $userId, int $serviceId): void
    {
        $endpoint = 'users/' . $userId . '/services/' . $serviceId . '/unsuspend';
        $method = 'PUT';
        $this->request->setAction(__FUNCTION__);
        $this->request->call($method, $endpoint);
    }

    /**
     * Move a user's service to another plan.
     *
     * @param int $userId PanelAlpha user ID.
     * @param int $serviceId PanelAlpha service ID.
     * @param int $planId New PanelAlpha plan ID.
     * @param int|null $instanceLimit Maximum number of instances, null for the plan default.
     * @param array|null $hostingAccountConfig Extra hosting account settings.
     * @return void
     * @throws Exception
     */
    public function changePlan(
        int $userId,
        int $serviceId,
        int $planId,
        ?int $instanceLimit,
        ?array $hostingAccountConfig
    ): void {
        $endpoint = 'users/' . $userId . '/services/' . $serviceId . '/change-plan';
        $method = 'PUT';
        $data = [
            'plan_id' => $planId,
            'instance_limit' => $instanceLimit,
            'hosting_account_config' => $hostingAccountConfig,
        ];
        $this->request->setAction(__FUNCTION__);
        $this->request->call($method, $endpoint, $data);
    }

    /**
     * Get all services of a user.
     *
     * @param int $userId PanelAlpha user ID.
     * @return array|null
     * @throws Exception
     */
    public function getUserServices(int $userId): ?array
    {
        $endpoint = 'users/' . $userId . '/services';
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * Delete a user.
     *
     * @param int $userId PanelAlpha user ID.
     * @return void
     * @throws Exception
     */
    public function deleteUser(int $userId): void
    {
        $endpoint = 'users/' . $userId;
        $method = 'DELETE';
        $this->request->setAction(__FUNCTION__);
        $this->request->call($method, $endpoint);
    }

    /**
     * Get all packages.
     *
     * @return array
     * @throws Exception
     */
    public function getPackages(): array
    {
        $this->request->setAction(__FUNCTION__);
        return $this->fetchPaginatedList('GET', 'packages');
    }

    /**
     * Add a package to a service.
     *
     * @param int $serviceId PanelAlpha service ID.
     * @param int $packageId PanelAlpha package ID.
     * @return void
     * @throws Exception
     */
    public function addPackageToService(int $serviceId, int $packageId): void
    {
        $endpoint = 'service/' . $serviceId . '/package';
        $method = 'POST';
        $data = [
            'package_id' => $packageId,
        ];
        $this->request->setAction(__FUNCTION__);
        $this->request->call($method, $endpoint, $data);
    }

    /**
     * Remove a package from a service.
     *
     * @param int $service PanelAlpha service ID.
     * @param int $packageId PanelAlpha package ID.
     * @return void
     * @throws Exception
     */
    public function deletePackageFromService(int $service, int $packageId): void
    {
        $endpoint = 'service/' . $service . '/package/' . $packageId;
        $method = 'DELETE';
        $this->request->setAction(__FUNCTION__);
        $this->request->call($method, $endpoint);
    }

    /**
     * Get the URL of the PanelAlpha user login page.
     *
     * @return array
     * @throws Exception
     */
    public function getLoginUrl(): array
    {
        $endpoint = 'users/login-url';
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * Get all services.
     *
     * @return array
     * @throws Exception
     */
    public function getServices(): array
    {
        $this->request->setAction(__FUNCTION__);
        return $this->fetchPaginatedList('GET', 'services');
    }

    /**
     * Get all servers, optionally only those in one server group.
     *
     * @param int|null $serverGroupId
     * @return array
     * @throws Exception
     */
    public function getServers(?int $serverGroupId = null): array
    {
        $endpoint = 'servers';
        if ($serverGroupId !== null) {
            $endpoint .= '?group_id=' . $serverGroupId;
        }

        $this->request->setAction(__FUNCTION__);
        return $this->fetchPaginatedList('GET', $endpoint);
    }

    /**
     * Get the config of a server.
     *
     * @param int $id PanelAlpha server ID.
     * @return array
     * @throws Exception
     */
    public function getServerConfig(int $id): array
    {
        $endpoint = 'servers/' . $id . '/config';
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * Get an SSO token that lets an admin log in as the user.
     *
     * @param int $userId PanelAlpha user ID.
     * @return array
     * @throws Exception
     */
    public function getLoginAsUserSsoToken(int $userId): array
    {
        $endpoint = 'users/' . $userId . '/login-as-user-sso-token';
        $method = 'POST';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * Get an SSO token for the user.
     *
     * @param int $userId PanelAlpha user ID.
     * @return array
     * @throws Exception
     */
    public function getSsoToken(int $userId): array
    {
        $endpoint = 'users/' . $userId . '/sso-token';
        $method = 'POST';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * Get an SSO URL to the control panel of a server account, acting as the user.
     *
     * @param int $serverAccountId
     * @param int $userId PanelAlpha user ID.
     * @return array
     * @throws Exception
     */
    public function getControlPanelSsoUrl(int $serverAccountId, int $userId): array
    {
        $endpoint = 'server-accounts/' . $serverAccountId . '/control-panel-sso-url';
        $method = 'POST';
        $this->request->setAction(__FUNCTION__);
        return $this->request->callUserApi($method, $endpoint, [], $userId);
    }

    /**
     * Fetch every page of a list endpoint and merge the items.
     *
     * Stops at meta.last_page when the API sends it, otherwise at the first short page.
     *
     * @param string $method
     * @param string $endpoint Base path without pagination query params.
     * @return array
     * @throws Exception
     */
    private function fetchPaginatedList(string $method, string $endpoint): array
    {
        $allItems = [];
        $page = 1;
        $lastPage = null;

        while ($page <= self::LIST_MAX_PAGES) {
            $queryPrefix = str_contains($endpoint, '?') ? '&' : '?';
            $paginatedEndpoint = $endpoint . $queryPrefix . 'per_page=' . self::LIST_PER_PAGE . '&page=' . $page;

            $response = $this->request->callListResponse($method, $paginatedEndpoint) ?? [];
            $data = $response['data'] ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $allItems = array_merge($allItems, $data);

            if ($lastPage === null && isset($response['meta']['last_page'])) {
                $lastPage = (int) $response['meta']['last_page'];
            }

            if ($lastPage !== null) {
                if ($page >= $lastPage) {
                    break;
                }
            } elseif (count($data) < self::LIST_PER_PAGE) {
                break;
            }

            $page++;
        }

        return $allItems;
    }
}
