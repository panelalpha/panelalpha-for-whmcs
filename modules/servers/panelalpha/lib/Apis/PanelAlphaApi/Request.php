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
            throw new \Exception('Request failed');
        }
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