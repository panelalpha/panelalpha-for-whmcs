<?php

namespace WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;

use WHMCS\Module\Server\PanelAlpha\Curl;

class Request
{
    public const DEFAULT_PORT = '8443';

    public $curl;

    protected $protocol;
    protected $hostname;
    protected $port;

    protected $secureMode;
    protected $token;

    protected $action;

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->curl = new Curl();
        $this->protocol = $this->makeProtocol($params);
        $this->hostname = $this->makeHostname($params);
        $this->port = $this->makePort($params);
        $this->secureMode = $this->makeSecureMode($params);
        $this->token = $this->makeToken($params);
    }

    /**
     * @param string $action
     * @return void
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function makeProtocol(array $params): string
    {
        return $params['serverhttpprefix'] ?? "";
    }

    /**
     * @param array $params
     * @return string
     */
    protected function makeHostname(array $params): string
    {
        return $params['serverhostname'] ?? $params['hostname'];
    }

    /**
     * @param array $params
     * @return string
     */
    protected function makePort(array $params): string
    {
        if (!empty($params['serverport'])) {
            return $params['serverport'];
        }
        if (!empty($params['port'])) {
            return $params['port'];
        }
        return self::DEFAULT_PORT;
    }

    /**
     * @param array $params
     * @return bool
     */
    protected function makeSecureMode(array $params): bool
    {
        return (!empty($params['serversecure']))
            || (!empty($params['secure']) && $params['secure'] === 'on');
    }

    /**
     * @param array $params
     * @return mixed
     */
    protected function makeToken(array $params)
    {
        return $params['serveraccesshash'] ?? $params['accesshash'];
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return array|null
     * @throws \Exception
     */
    public function call(string $method, string $endpoint, array $body = []): ?array
    {
        $url = $this->createUri() . '/api/admin/' . $endpoint;
        $options = $this->createOptions();
        $this->setSecureMode();

        try {
            $response = $this->curl->call($method, $url, $body, $options);
            $this->log();
            $this->processResponse();

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        return $this->parseResponse($response);
    }

    /**
     * @return string
     */
    protected function createUri(): string
    {
        if ($this->protocol) {
            return $this->protocol . "://" . $this->hostname . ":" . $this->port;
        }
        return $this->hostname . ":" . $this->port;
    }


    /**
     * @return array[]
     */
    protected function createOptions(): array
    {
        return [
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->token
            ]
        ];
    }

    /**
     * @return void
     */
    protected function setSecureMode(): void
    {
        if (!$this->secureMode) {
            $this->curl->verifySsl(false);
        }
    }

    /**
     * @return void
     */
    protected function log(): void
    {
        $lastCall = $this->curl->getLastCall();

        logModuleCall(
            'panelalpha',
            $this->action,
            $lastCall['requestHeaders'] . $lastCall['request'],
            $lastCall['responseHeaders'] . $lastCall['response']
        );
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function processResponse(): void
    {
        if ($this->curl->getLastHttpCode() === 401) {
            throw new \Exception('Unauthenticated');
        }

        if ($this->curl->getLastHttpCode() < 200 || $this->curl->getLastHttpCode() > 299) {
            $message  = $this->curl->getLastCall()['response'] ?? null;
            if ($message !== null) {
                $message = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
            }
            if (is_array($message) && isset($message['message'])) {
                throw new \Exception($message['message']);
            }
            throw new \Exception('Request failed');
        }
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @param int|null $userId
     * @return array|null
     * @throws \Exception
     */
    public function callUserApi(string $method, string $endpoint, array $body = [], ?int $userId = null): ?array
    {
        $url = $this->createUri() . '/api/' . $endpoint;
        $options = $this->createOptions();
        
        // Add X-PanelAlpha-User header if userId is provided
        if ($userId !== null) {
            $options[CURLOPT_HTTPHEADER][] = 'X-PanelAlpha-User: ' . $userId;
        }
        
        $this->setSecureMode();

        try {
            $response = $this->curl->call($method, $url, $body, $options);
            $this->log();
            $this->processResponse();

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        return $this->parseResponse($response);
    }

    /**
     * @param string $response
     * @return array|null
     */
    protected function parseResponse(string $response): ?array
    {
        $result = json_decode($response, true);
        return $result['data'] ?? null;
    }

}
