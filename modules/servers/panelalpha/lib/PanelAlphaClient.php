<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\PanelAlpha;

use GuzzleHttp\Exception\GuzzleException;
use stdClass;

class PanelAlphaClient
{
    protected string $apiUrl;
    protected string $apiToken;
    protected bool $secureMode;
    protected HttpClient $client;

    public function __construct(array $params)
    {
        $protocol = $params['serverhttpprefix'] ?? $params['secure'] === 'on' ? 'https' : 'http';
        $hostname = $params['serverhostname'] ?? $params['hostname'];
        if ($params['serverport'] || $params['port']) {
            $port = $params['serverport'] ?? $params['port'];
            $this->apiUrl = trim($protocol . '://' . $hostname . ":" . $port, '/');
        } else {
            $this->apiUrl = trim($protocol . '://' . $hostname, '/');
        }

        if (isset($params['serversecure']) && $params['serversecure'] === 'on') {
            $this->secureMode = true;
        } else if (isset($params['secure']) && $params['secure'] === 'on') {
            $this->secureMode = true;
        } else {
            $this->secureMode = false;
        }

        $this->apiToken = $params['serveraccesshash'] ?? $params['accesshash'];
        $this->client = new HttpClient();
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
     * @throws GuzzleException
     * @throws \Exception
     */
    public function testConnection(): void
    {
        $endpoint = 'instances';
        $method = 'GET';
        try {
            $this->request($method, $endpoint);
        } catch (\Exception $e) {
            throw new \Exception('Could not connect to the server');
        }
    }

    /**
     * @return array
     * @throws \Exception
     * @throws GuzzleException
     */
    public function getPlans(): array
    {
        $endpoint = 'plans';
        $method = 'GET';
        return $this->request($method, $endpoint);
    }

    /**
     * @param string $email
     * @return array|null
     * @throws GuzzleException
     */
    public function getUser(string $email): ?array
    {
        $endpoint = 'users/email?email=' . $email;
        $method = 'GET';
        return $this->request($method, $endpoint);
    }


    /**
     * @param array $user
     * @param int $planId
     * @return stdClass|null
     * @throws GuzzleException
     */
    public function createService(array $user, int $planId): ?array
    {
        $endpoint = 'users/' . $user['id'] . '/services';
        $method = 'POST';
        $data = [
            'plan_id' => $planId,
            'status' => 'active'
        ];
        return $this->request($method, $endpoint, $data);
    }

    /**
     * @param array $client
     * @return array|null
     * @throws GuzzleException
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
        return $this->request($method, $endpoint, $data);
    }

    /**
     * @param array $params
     * @param string $instanceName
     * @param string $theme
     * @param int $serviceId
     * @param int $userId
     * @return array|null
     * @throws GuzzleException
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
        return $this->request($method, $endpoint, $data);
    }


    /**
     * @param int $serviceId
     * @return array|null
     * @throws GuzzleException
     */
    public function getInstancesAssignedToService(int $serviceId): ?array
    {
        $endpoint = 'services/' . $serviceId . '/instances';
        $method = 'GET';
        return $this->request($method, $endpoint);
    }

    /**
     * @return array|null
     * @throws GuzzleException
     */
    public function getInstancesServices(): array
    {
        $endpoint = 'services/instances';
        $method = 'GET';
        return $this->request($method, $endpoint);
    }

    /**
     * @param int $id
     * @return void
     * @throws GuzzleException
     */
    public function deleteInstance(int $id): void
    {
        $endpoint = 'instances/' . $id;
        $method = 'DELETE';
        $this->request($method, $endpoint);
    }

    /**
     * @param int $serviceId
     * @return void
     * @throws GuzzleException
     */
    public function deleteService(int $serviceId): void
    {
        $endpoint = 'services/' . $serviceId;
        $method = 'DELETE';
        $this->request($method, $endpoint);
    }

    /**
     * @param int $userId
     * @param int $serviceId
     * @return void
     * @throws GuzzleException
     */
    public function suspendAccount(int $userId, int $serviceId): void
    {
        $endpoint = 'users/' . $userId . '/services/' . $serviceId . '/suspend';
        $method = 'PUT';
        $this->request($method, $endpoint);
    }

    /**
     * @param int $userId
     * @param int $serviceId
     * @return void
     * @throws GuzzleException
     */
    public function unsuspendAccount(int $userId, int $serviceId): void
    {
        $endpoint = 'users/' . $userId . '/services/' . $serviceId . '/unsuspend';
        $method = 'PUT';
        $this->request($method, $endpoint);
    }

    /**
     * @param int $userId
     * @param int $serviceId
     * @param int $planId
     * @return void
     * @throws GuzzleException
     */
    public function changePlan(int $userId, int $serviceId, int $planId): void
    {
        $endpoint = 'users/' . $userId . '/services/' . $serviceId . '/change-plan';
        $method = 'PUT';
        $data = [
            'plan_id' => $planId,
        ];
        $this->request($method, $endpoint, $data);
    }

    /**
     * @param int $userId
     * @return array|null
     * @throws GuzzleException
     */
    public function getUserServices(int $userId): ?array
    {
        $endpoint = 'users/' . $userId . '/services';
        $method = 'GET';
        return $this->request($method, $endpoint);
    }

    /**
     * @param int $userId
     * @return void
     * @throws GuzzleException
     */
    public function deleteUser(int $userId): void
    {
        $endpoint = 'users/' . $userId;
        $method = 'DELETE';
        $this->request($method, $endpoint);
    }

    /**
     * @return array
     * @throws GuzzleException
     */
    public function getPackages(): array
    {
        $endpoint = 'packages';
        $method = 'GET';
        return $this->request($method, $endpoint);
    }

    /**
     * @param int $serviceId
     * @param int $packageId
     * @return void
     * @throws GuzzleException
     */
    public function addPackageToService(int $serviceId, int $packageId): void
    {
        $endpoint = 'service/' . $serviceId . '/package';
        $method = 'POST';
        $data = [
            'package_id' => $packageId,
        ];
        $this->request($method, $endpoint, $data);
    }

    /**
     * @param int $service
     * @param int $packageId
     * @return void
     * @throws GuzzleException
     */
    public function deletePackageFromService(int $service, int $packageId): void
    {
        $endpoint = "service/{$service}/package/{$packageId}";
        $method = 'DELETE';
        $this->request($method, $endpoint);
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array|null
     * @throws GuzzleException
     * @throws \Exception
     */
    protected function request(string $method, string $endpoint, array $data = []): ?array
    {
        if (!$this->apiUrl) {
            throw new \Exception('Api url not set.');
        }
        $url = $this->apiUrl . '/api/admin/' . $endpoint;
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken
            ],
        ];
        if (!$this->secureMode) {
            $options['verify'] = false;
        }
        if (!empty($data)) {
            $options['json'] = $data;
        }

        try {
            $response = $this->client->execute($method, $url, $options);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        return $response['data'];
    }
}