<?php

namespace WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;

use WHMCS\Module\Server\PanelAlpha\Curl;

/**
 * Sends HTTP requests to the PanelAlpha API and logs them with secrets redacted.
 */
class Request
{
    /** Port used when the server has no port set. */
    public const DEFAULT_PORT = '8443';

    /** Body and query keys whose values are never written to the module log. */
    private const SENSITIVE_KEYS = [
        'password',
        'user_password',
        'token',
        'accesshash',
        'access_hash',
    ];

    /** Actions whose response "url" is a one-time login link and must not be logged. */
    private const SSO_URL_REDACT_ACTIONS = [
        'getControlPanelSsoUrl',
        'getSsoToken',
        'getLoginAsUserSsoToken',
    ];

    /** @var Curl */
    public $curl;

    /** @var string URL scheme, for example "https", or an empty string. */
    protected $protocol;

    /** @var string */
    protected $hostname;

    /** @var string */
    protected $port;

    /** @var bool Whether to verify the SSL certificate. */
    protected $secureMode;

    /** @var string|null API token sent as a Bearer token. */
    protected $token;

    /** @var string|null Name of the API action, used as the module log action. */
    protected $action;

    /**
     * @param array $params WHMCS module params (server*) or a tblservers row.
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
     * Set the action name used in the module log for the next requests.
     *
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
        return $params['serverhttpprefix'] ?? '';
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

        // Verify certificates unless the server explicitly turns it off.
        return true;
    }

    /**
     * @param array $params
     * @return string|null
     */
    protected function makeToken(array $params)
    {
        return $params['serveraccesshash'] ?? $params['accesshash'];
    }

    /**
     * Call the admin API and return the "data" part of the response.
     *
     * @param string $method HTTP method.
     * @param string $endpoint Path below /api/admin/, with query string if needed.
     * @param array $body
     * @return array|null
     * @throws \Exception
     */
    public function call(string $method, string $endpoint, array $body = []): ?array
    {
        return $this->parseResponse($this->send($method, $endpoint, $body));
    }

    /**
     * Call the admin API and return the full decoded response, used for paginated lists.
     *
     * @param string $method HTTP method.
     * @param string $endpoint Path below /api/admin/, with query string if needed.
     * @param array $body
     * @return array|null Full decoded JSON body, including "meta".
     * @throws \Exception
     */
    public function callListResponse(string $method, string $endpoint, array $body = []): ?array
    {
        $result = json_decode($this->send($method, $endpoint, $body), true);

        return is_array($result) ? $result : null;
    }

    /**
     * Call the user API, optionally acting as a given PanelAlpha user.
     *
     * @param string $method HTTP method.
     * @param string $endpoint Path below /api/.
     * @param array $body
     * @param int|null $userId PanelAlpha user ID sent in the X-PanelAlpha-User header.
     * @return array|null
     * @throws \Exception
     */
    public function callUserApi(string $method, string $endpoint, array $body = [], ?int $userId = null): ?array
    {
        $url = $this->createUri() . '/api/' . $endpoint;
        $options = $this->createOptions();

        if ($userId !== null) {
            $options[CURLOPT_HTTPHEADER][] = 'X-PanelAlpha-User: ' . $userId;
        }

        return $this->parseResponse($this->execute($method, $url, $body, $options));
    }

    /**
     * Send a request to the admin API.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return string Raw response body.
     * @throws \Exception
     */
    private function send(string $method, string $endpoint, array $body): string
    {
        $url = $this->createUri() . '/api/admin/' . $endpoint;

        return $this->execute($method, $url, $body, $this->createOptions());
    }

    /**
     * Send the request, log it and fail on a non-2xx response.
     *
     * @param string $method
     * @param string $url Full URL.
     * @param array $body
     * @param array $options Extra cURL options.
     * @return string Raw response body.
     * @throws \Exception
     */
    private function execute(string $method, string $url, array $body, array $options): string
    {
        $this->setSecureMode();

        try {
            $response = $this->curl->call($method, $url, $body, $options);
            $this->log();
            $this->processResponse();
        } catch (\Exception $e) {
            // Callers get a plain \Exception that carries only the message.
            throw new \Exception($e->getMessage());
        }

        return $response;
    }

    /**
     * @return string Base URL: [protocol://]hostname:port
     */
    protected function createUri(): string
    {
        if ($this->protocol) {
            return $this->protocol . '://' . $this->hostname . ':' . $this->port;
        }
        return $this->hostname . ':' . $this->port;
    }

    /**
     * @return array cURL options with the JSON and authorization headers.
     */
    protected function createOptions(): array
    {
        return [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token,
            ],
        ];
    }

    /**
     * @return void
     */
    protected function setSecureMode(): void
    {
        // The server "Secure" toggle can turn verification off, for example for self-signed certificates.
        $this->curl->verifySsl($this->secureMode);
    }

    /**
     * Write the last call to the WHMCS module log with secrets redacted.
     *
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
     * Throw when the last response has a non-2xx status code.
     *
     * @return void
     * @throws \Exception With the API error message when the response has one.
     */
    protected function processResponse(): void
    {
        if ($this->curl->getLastHttpCode() === 401) {
            throw new \Exception('Unauthenticated');
        }

        $httpCode = $this->curl->getLastHttpCode();
        if ($httpCode < 200 || $httpCode > 299) {
            // Error pages from a proxy (e.g. an HTML 502) are not JSON.
            $body = json_decode((string) ($this->curl->getLastCall()['response'] ?? ''), true);
            if (is_array($body) && isset($body['message'])) {
                throw new \Exception($body['message']);
            }
            throw new \Exception('Request failed (HTTP ' . $httpCode . ')');
        }
    }

    /**
     * Redact secrets in all parts of a call before it is logged.
     *
     * @param array $lastCall Call details from Curl::getLastCall().
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
     * Redact the token, the acting user and sensitive key/value pairs in raw text.
     *
     * @param string $payload
     * @return string
     */
    protected function redactSecrets(string $payload): string
    {
        $payload = preg_replace('/Authorization:\\s*Bearer\\s+[^\\r\\n]+/i', 'Authorization: Bearer [redacted]', $payload);
        $payload = preg_replace('/X-PanelAlpha-User:\\s*[^\\r\\n]+/i', 'X-PanelAlpha-User: [redacted]', $payload);

        return $this->redactKeyValueStrings($payload);
    }

    /**
     * Redact secrets in a request or response body (JSON, query string or plain text).
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
     * Redact secrets in a JSON body.
     *
     * @param string $payload
     * @return string|null Null when the payload is not a JSON object or array.
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
     * Redact secrets in a query string body.
     *
     * @param string $payload
     * @return string|null Null when the payload is not a query string.
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
     * Replace values of sensitive keys, at any depth, with "[redacted]".
     *
     * @param array $payload Changed in place.
     * @return void
     */
    protected function redactArrayValues(array &$payload): void
    {
        foreach ($payload as $key => &$value) {
            if (is_array($value)) {
                $this->redactArrayValues($value);
                continue;
            }

            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $value = '[redacted]';
                continue;
            }

            if ($this->shouldRedactSsoUrl() && strtolower((string) $key) === 'url') {
                $value = '[redacted]';
            }
        }
    }

    /**
     * @return bool True when the current action returns a one-time login URL.
     */
    protected function shouldRedactSsoUrl(): bool
    {
        return in_array($this->action, self::SSO_URL_REDACT_ACTIONS, true);
    }

    /**
     * Redact sensitive key/value pairs in text that could not be parsed.
     *
     * Covers JSON-like "key": value pairs and key=value query pairs.
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

        return preg_replace(
            '/((?:\\?|&|;|^)(?:' . $sensitivePattern . ')=)([^&;\\s]+)/i',
            '$1[redacted]',
            $payload
        );
    }

    /**
     * Return the "data" part of a JSON response.
     *
     * @param string $response Raw response body.
     * @return array|null
     */
    protected function parseResponse(string $response): ?array
    {
        $result = json_decode($response, true);
        return $result['data'] ?? null;
    }
}
