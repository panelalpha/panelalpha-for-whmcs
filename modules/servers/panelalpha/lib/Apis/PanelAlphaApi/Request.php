<?php

namespace WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;

use WHMCS\Module\Server\PanelAlpha\Curl;

class Request
{
    public const DEFAULT_PORT = '8443';
    private const SENSITIVE_KEYS = [
        'password',
        'user_password',
        'token',
        'accesshash',
        'access_hash',
    ];

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
        if (array_key_exists('serversecure', $params)) {
            return in_array($params['serversecure'], ['on', 1, '1', true], true);
        }

        if (array_key_exists('secure', $params)) {
            return in_array($params['secure'], ['on', 1, '1', true], true);
        }

        // Default to verifying certificates unless explicitly disabled.
        return true;
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
        // Allow disabling verification (e.g. self-signed certs) via the server "Secure" toggle.
        $this->curl->verifySsl($this->secureMode);
    }

    /**
     * @return void
     */
    protected function log(): void
    {
        $lastCall = $this->sanitizeLastCall($this->curl->getLastCall());

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
     * @param array $lastCall
     * @return array
     */
    protected function sanitizeLastCall(array $lastCall): array
    {
        $lastCall['requestHeaders'] = $this->redactSecrets($lastCall['requestHeaders'] ?? '');
        $lastCall['responseHeaders'] = $this->redactSecrets($lastCall['responseHeaders'] ?? '');
        $lastCall['request'] = $this->redactPayload($lastCall['request'] ?? '');
        $lastCall['response'] = $this->redactPayload($lastCall['response'] ?? '');

        return $lastCall;
    }

    /**
     * Redact sensitive headers before logging.
     *
     * @param string $payload
     * @return string
     */
    protected function redactSecrets(string $payload): string
    {
        $payload = preg_replace('/Authorization:\\s*Bearer\\s+[^\\r\\n]+/i', 'Authorization: Bearer [redacted]', $payload);
        $payload = preg_replace('/X-PanelAlpha-User:\\s*[^\\r\\n]+/i', 'X-PanelAlpha-User: [redacted]', $payload);
        $payload = $this->redactKeyValueStrings($payload);

        return $payload;
    }

    /**
     * Remove secrets from request/response bodies.
     *
     * @param string|null $payload
     * @return string
     */
    protected function redactPayload(?string $payload): string
    {
        if (empty($payload)) {
            return $payload ?? '';
        }

        $sanitizedJson = $this->redactJsonPayload($payload);
        if ($sanitizedJson !== null) {
            return $sanitizedJson;
        }

        $sanitizedQuery = $this->redactQueryString($payload);
        if ($sanitizedQuery !== null) {
            return $sanitizedQuery;
        }

        return $this->redactSecrets($payload);
    }

    /**
     * Redact secrets from JSON payloads.
     *
     * @param string $payload
     * @return string|null
     */
    protected function redactJsonPayload(string $payload): ?string
    {
        $decoded = json_decode($payload, true);
        if ($decoded === null || json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return null;
        }

        $this->redactArrayValues($decoded);

        return json_encode($decoded);
    }

    /**
     * Redact secrets from query string payloads.
     *
     * @param string $payload
     * @return string|null
     */
    protected function redactQueryString(string $payload): ?string
    {
        if (strpos($payload, '=') === false) {
            return null;
        }

        parse_str($payload, $parsed);

        if (empty($parsed)) {
            return null;
        }

        $this->redactArrayValues($parsed);

        return urldecode(http_build_query($parsed));
    }

    /**
     * Redact nested array values using the sensitive keys list.
     *
     * @param array $payload
     * @return void
     */
    protected function redactArrayValues(array &$payload): void
    {
        foreach ($payload as $key => &$value) {
            if (is_array($value)) {
                $this->redactArrayValues($value);
                continue;
            }

            if (in_array(strtolower((string)$key), self::SENSITIVE_KEYS, true)) {
                $value = '[redacted]';
            }
        }
    }

    /**
     * Redact key/value pairs when payload parsing is not possible.
     *
     * @param string $payload
     * @return string
     */
    protected function redactKeyValueStrings(string $payload): string
    {
        $sensitivePattern = implode('|', array_map(fn($value) => preg_quote($value, '/'), self::SENSITIVE_KEYS));

        $payload = preg_replace_callback(
            '/("?(?:' . $sensitivePattern . ')"?\\s*:\\s*)(\"[^\"]*\"|[^,\\s}]+)/i',
            static function (array $matches): string {
                $quote = substr($matches[2], 0, 1) === '"' ? '"' : '';
                return $matches[1] . $quote . '[redacted]' . ($quote ? '"' : '');
            },
            $payload
        );

        $payload = preg_replace(
            '/((?:\\?|&|;|^)(?:' . $sensitivePattern . ')=)([^&;\\s]+)/i',
            '$1[redacted]',
            $payload
        );

        return $payload;
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
