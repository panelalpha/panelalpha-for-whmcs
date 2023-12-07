<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\PanelAlpha;

use stdClass;

class PanelAlphaClient
{
    protected $apiUrl;
    protected $apiToken;
    protected $secureMode;
    protected $curl;

    public const DEFAULT_PORT = '8443';

    public function __construct(array $params)
    {
        $this->curl = new Curl();

        $protocol = $params['serverhttpprefix'] ?? "";
        $hostname = $params['serverhostname'] ?? $params['hostname'];
        $port = "";
        if ($params['serverport'] || $params['port']) {
            $port = $params['serverport'] ?? $params['port'];
        }

        if ($protocol) {
            $this->apiUrl = $protocol . '://' . $hostname;
        } else {
            $this->apiUrl = $hostname;
        }

        if ($port) {
            $this->apiUrl .= ':' . $port;
        } else {
            $this->apiUrl .= ':' . self::DEFAULT_PORT;
        }

        $this->apiUrl = trim($this->apiUrl, '/');

        if ((isset($params['serversecure']) && $params['serversecure'] === 'on')
            || (isset($params['secure']) && $params['secure'] === 'on')) {
            $this->secureMode = true;
        } else {
            $this->secureMode = false;
        }

        $this->apiToken = $params['serveraccesshash'] ?? $params['accesshash'];
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function validate(): void
    {
        if (!(isset($this->apiUrl)) && isset($this->apiToken)) {
            throw new \Exception('No api url or api token');
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testConnection(): void
    {
        $endpoint = 'instances';
        $method = 'GET';
        try {
            $this->curl->setAction(__FUNCTION__);
            $this->request($method, $endpoint);
        } catch (\Exception $e) {
            throw new \Exception('Could not connect to the server');
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getPlans(): array
    {
        $endpoint = 'plans';
        $method = 'GET';
        $this->curl->setAction(__FUNCTION__);
        return $this->request($method, $endpoint);
    }

    /**
     * @param string $email
     * @return array|null
     * @throws \Exception
     */
    public function getUser(string $email): ?array
    {
        $endpoint = 'users/email?email=' . $email;
        $method = 'GET';
        $this->curl->setAction(__FUNCTION__);
        return $this->request($method, $endpoint);
    }


    /**
     * @param array $user
     * @param int $planId
     * @return stdClass|null
     * @throws \Exception
     */
    public function createService(array $user, int $planId): ?array
    {
        $endpoint = 'users/' . $user['id'] . '/services';
        $method = 'POST';
        $data = [
            'plan_id' => $planId,
            'status' => 'active'
        ];
        $this->curl->setAction(__FUNCTION__);
        return $this->request($method, $endpoint, $data);
    }

    /**
     * @param array $client
     * @return array|null
     * @throws \Exception
     */
    public function createUser(array $client): ?array
    {
        $endpoint = 'users';
        $method = 'POST';
        $data = [
            'first_name' => $client['firstname'],
            'last_name' => $client['lastname'],
            'company_name' => $client['companyname'] ?? "",
            'email' => $client['email'],
            'password' => Helper::generateRandomString(8)
        ];
        $this->curl->setAction(__FUNCTION__);
        return $this->request($method, $endpoint, $data);
    }

    /**
     * @param array $params
     * @param string $instanceName
     * @param string $theme
     * @param int $serviceId
     * @param int $userId
     * @return array|null
     * @throws \Exception
     */
    public function createInstance(array $params, string $instanceName, string $theme, int $serviceId, int $userId): ?array
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
        $this->curl->setAction(__FUNCTION__);
        return $this->request($method, $endpoint, $data);
    }


    /**
     * @param int $serviceId
     * @return array|null
     * @throws \Exception
     */
    public function getInstancesAssignedToService(int $serviceId): ?array
    {
        $endpoint = 'services/' . $serviceId . '/instances';
        $method = 'GET';
        $this->curl->setAction(__FUNCTION__);
        return $this->request($method, $endpoint);
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function getInstancesServices(): array
    {
        $endpoint = 'services/instances';
        $method = 'GET';
        $this->curl->setAction(__FUNCTION__);
        return $this->request($method, $endpoint);
    }

    /**
     * @param int $id
     * @return void
     * @throws \Exception
     */
    public function deleteInstance(int $id): void
    {
        $endpoint = 'instances/' . $id;
        $method = 'DELETE';
        $this->curl->setAction(__FUNCTION__);
        $this->request($method, $endpoint);
    }

    /**
     * @param int $serviceId
     * @return void
     * @throws \Exception
     */
    public function deleteService(int $serviceId): void
    {
        $endpoint = 'services/' . $serviceId;
        $method = 'DELETE';
        $this->curl->setAction(__FUNCTION__);
        $this->request($method, $endpoint);
    }

    /**
     * @param int $userId
     * @param int $serviceId
     * @return void
     * @throws \Exception
     */
    public function suspendAccount(int $userId, int $serviceId): void
    {
        $endpoint = 'users/' . $userId . '/services/' . $serviceId . '/suspend';
        $method = 'PUT';
        $this->curl->setAction(__FUNCTION__);
        $this->request($method, $endpoint);
    }

    /**
     * @param int $userId
     * @param int $serviceId
     * @return void
     * @throws \Exception
     */
    public function unsuspendAccount(int $userId, int $serviceId): void
    {
        $endpoint = 'users/' . $userId . '/services/' . $serviceId . '/unsuspend';
        $method = 'PUT';
        $this->curl->setAction(__FUNCTION__);
        $this->request($method, $endpoint);
    }

    /**
     * @param int $userId
     * @param int $serviceId
     * @param int $planId
     * @return void
     * @throws \Exception
     */
    public function changePlan(int $userId, int $serviceId, int $planId): void
    {
        $endpoint = 'users/' . $userId . '/services/' . $serviceId . '/change-plan';
        $method = 'PUT';
        $data = [
            'plan_id' => $planId,
        ];
        $this->curl->setAction(__FUNCTION__);
        $this->request($method, $endpoint, $data);
    }

    /**
     * @param int $userId
     * @return array|null
     * @throws \Exception
     */
    public function getUserServices(int $userId): ?array
    {
        $endpoint = 'users/' . $userId . '/services';
        $method = 'GET';
        $this->curl->setAction(__FUNCTION__);
        return $this->request($method, $endpoint);
    }

    /**
     * @param int $userId
     * @return void
     * @throws \Exception
     */
    public function deleteUser(int $userId): void
    {
        $endpoint = 'users/' . $userId;
        $method = 'DELETE';
        $this->curl->setAction(__FUNCTION__);
        $this->request($method, $endpoint);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getPackages(): array
    {
        $endpoint = 'packages';
        $method = 'GET';
        $this->curl->setAction(__FUNCTION__);
        return $this->request($method, $endpoint);
    }

    /**
     * @param int $serviceId
     * @param int $packageId
     * @return void
     * @throws \Exception
     */
    public function addPackageToService(int $serviceId, int $packageId): void
    {
        $endpoint = 'service/' . $serviceId . '/package';
        $method = 'POST';
        $data = [
            'package_id' => $packageId,
        ];
        $this->curl->setAction(__FUNCTION__);
        $this->request($method, $endpoint, $data);
    }

    /**
     * @param int $service
     * @param int $packageId
     * @return void
     * @throws \Exception
     */
    public function deletePackageFromService(int $service, int $packageId): void
    {
        $endpoint = "service/{$service}/package/{$packageId}";
        $method = 'DELETE';
        $this->curl->setAction(__FUNCTION__);
        $this->request($method, $endpoint);
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return array|null
     * @throws \Exception
     */
    protected function request(string $method, string $endpoint, array $body = []): ?array
    {
        if (!$this->apiUrl) {
            throw new \Exception('Api url not set.');
        }
        $url = $this->apiUrl . '/api/admin/' . $endpoint;
        $options = [
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->apiToken
            ]
        ];
        if (!$this->secureMode) {
            $this->curl->verifySsl(false);
        }

        try {
            $response = $this->curl->call($method, $url, $body, $options);
            $this->curl->log();

            if ($this->curl->getLastHttpCode() === 401) {
                throw new \Exception('Unauthenticated');
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        $result = json_decode($response, true);
        return $result['data'];
    }
}