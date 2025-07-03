<?php

namespace WHMCS\Module\Server\PanelAlpha\Apis;

use Exception;
use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi\Request;
use stdClass;

class PanelAlphaApi
{
    public static function fromModel($server): PanelAlphaApi
    {
        return new self($server->toArray());
    }

    protected $request;

    public function __construct(array $params)
    {
        $this->request = new Request($params);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testConnection(): void
    {
        $endpoint = 'instances';
        $method = 'GET';
        try {
            $this->request->setAction(__FUNCTION__);
            $this->request->curl->setTimeout(5);
            $result = $this->request->call($method, $endpoint);

            if (!is_array($result) && empty($result['data'])) {
                throw new Exception('Test Connection Failed');
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getPlans(): array
    {
        $endpoint = 'plans?per_page=100';
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * @param int $id
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
     * @param string $email
     * @return array|null
     * @throws Exception
     */
    public function getUser(string $email): ?array
    {
        $endpoint = 'users/email?email=' . $email;
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    public function getUserById(int $id): ?array
    {
        $endpoint = 'users/' . $id;
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * @param array $user
     * @param int $planId
     * @param int|null $instanceLimit
     * @return stdClass|null
     * @throws Exception
     */
    public function createService(array $user, int $planId, ?int $instanceLimit): ?array
    {
        $endpoint = 'users/' . $user['id'] . '/services';
        $method = 'POST';
        $data = [
            'plan_id' => $planId,
            'status' => 'active',
            'instance_limit' => $instanceLimit,
        ];
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint, $data);
    }

    /**
     * @param array $data
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

    public function updateUser(int $id, array $data): ?array
    {
        $endpoint = 'users/' . $id;
        $method = 'PUT';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint, $data);
    }

    /**
     * @param array $params
     * @param string $instanceName
     * @param string $theme
     * @param int $serviceId
     * @param int $userId
     * @param string|null $location
     * @return array|null
     * @throws Exception
     */
    public function createInstance(array $params, string $instanceName, string $theme, int $serviceId, int $userId, ?string $location = null): ?array
    {
        $endpoint = 'instances';
        $method = 'POST';
        $data = [
            'name' => $instanceName,
            'domain' => $params['domain'],
            'theme' => $theme,
            'user_id' => $userId,
            'service_id' => $serviceId,
        ];

        if (!empty($location)) {
            $data['user_configurable_options']['geo_affinity'] = $location;
        }

        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint, $data);
    }

    public function getService(int $serviceId): array
    {
        $endpoint = 'services/' . $serviceId;
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    public function unsetServiceAsTrial(int $serviceId): array
    {
        $endpoint = 'services/' . $serviceId . '/unset-as-trial';
        $method = 'PUT';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * @param int $serviceId
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
     * @return array|null
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
        }
        return [];
    }

    /**
     * @param int $id
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
     * @param int $serviceId
     * @return void
     * @throws Exception
     */
    public function deleteService(int $serviceId): void
    {
        $endpoint = 'services/' . $serviceId;
        $method = 'DELETE';
        $data = [
            'force_termination' => true
        ];
        $this->request->setAction(__FUNCTION__);
        $this->request->call($method, $endpoint, $data);
    }

    /**
     * @param int $userId
     * @param int $serviceId
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
     * @param int $userId
     * @param int $serviceId
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
     * @param int $userId
     * @param int $serviceId
     * @param int $planId
     * @return void
     * @throws Exception
     */
    public function changePlan(int $userId, int $serviceId, int $planId, ?int $instanceLimit): void
    {
        $endpoint = 'users/' . $userId . '/services/' . $serviceId . '/change-plan';
        $method = 'PUT';
        $data = [
            'plan_id' => $planId,
            'instance_limit' => $instanceLimit,
        ];
        $this->request->setAction(__FUNCTION__);
        $this->request->call($method, $endpoint, $data);
    }

    /**
     * @param int $userId
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
     * @param int $userId
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
     * @return array
     * @throws Exception
     */
    public function getPackages(): array
    {
        $endpoint = 'packages?per_page=100';
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * @param int $serviceId
     * @param int $packageId
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
     * @param int $service
     * @param int $packageId
     * @return void
     * @throws Exception
     */
    public function deletePackageFromService(int $service, int $packageId): void
    {
        $endpoint = "service/{$service}/package/{$packageId}";
        $method = 'DELETE';
        $this->request->setAction(__FUNCTION__);
        $this->request->call($method, $endpoint);
    }

    /**
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
     * @return array
     * @throws Exception
     */
    public function getServices(): array
    {
        $endpoint = 'services?per_page=100';
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    /**
     * @param int|null $serverGroupId
     * @return array
     * @throws Exception
     */
    public function getServers(?int $serverGroupId = null): array
    {
        $endpoint = 'servers?per_page=100';
        if ($serverGroupId !== null) {
            $endpoint .= '&group_id=' . $serverGroupId;
        }

        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    public function getServerConfig(int $id): array
    {
        $endpoint = 'servers/' . $id . '/config';
        $method = 'GET';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    public function getLoginAsUserSsoToken(int $userId): array
    {
        $endpoint = 'users/' . $userId . '/login-as-user-sso-token';
        $method = 'POST';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }

    public function getSsoToken(int $userId): array
    {
        $endpoint = 'users/' . $userId . '/sso-token';
        $method = 'POST';
        $this->request->setAction(__FUNCTION__);
        return $this->request->call($method, $endpoint);
    }
}
