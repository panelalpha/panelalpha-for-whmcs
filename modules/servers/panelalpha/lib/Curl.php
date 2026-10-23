<?php

namespace WHMCS\Module\Server\PanelAlpha;

/**
 * Small wrapper around one cURL handle that keeps details of the last call for logging.
 */
class Curl
{
    /** @var \CurlHandle */
    private $handle;

    /**
     * @var array Details of the last call: method, url, requestHeaders, request,
     *            responseHeaders, response, curlInfo and time.
     */
    private $lastCall;

    /** @var string Not used by the module itself. */
    public $action = '';

    /** @var bool */
    private $verifySsl = true;

    /** @var array cURL options applied to every request. */
    public $defaultOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ];

    public function __construct()
    {
        $this->handle = curl_init();
        $this->reset();
    }

    public function __destruct()
    {
        curl_close($this->handle);
    }

    /**
     * Set both the connect timeout and the total request timeout.
     *
     * @param int $seconds
     * @return void
     */
    public function setTimeout($seconds): void
    {
        $this->defaultOptions[CURLOPT_CONNECTTIMEOUT] = $seconds;
        $this->defaultOptions[CURLOPT_TIMEOUT] = $seconds;
    }

    /**
     * Set the total time allowed for one request; the connect timeout stays as it is.
     *
     * @param int $seconds
     * @return void
     */
    public function setRequestTimeout(int $seconds): void
    {
        $this->defaultOptions[CURLOPT_TIMEOUT] = $seconds;
    }

    /**
     * @return int Total request timeout in seconds.
     */
    public function getRequestTimeout(): int
    {
        return (int) $this->defaultOptions[CURLOPT_TIMEOUT];
    }

    /**
     * @return array Details of the last call.
     */
    public function getLastCall()
    {
        return $this->lastCall;
    }

    /**
     * @return int|null HTTP status code of the last call, null before any call.
     */
    public function getLastHttpCode()
    {
        return $this->lastCall['curlInfo']['http_code'];
    }

    /**
     * Turn SSL certificate and host verification on or off for the next calls.
     *
     * @param bool $verify
     * @return void
     */
    public function verifySsl(bool $verify = true): void
    {
        $this->verifySsl = $verify;
    }

    /**
     * Reset the handle to the default options and clear the last call details.
     *
     * @return void
     */
    private function reset(): void
    {
        curl_reset($this->handle);
        curl_setopt_array($this->handle, $this->defaultOptions);
        if (!$this->verifySsl) {
            curl_setopt_array($this->handle, [
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
        }

        $this->lastCall = [
            'method' => null,
            'url' => null,
            'requestHeaders' => null,
            'request' => null,
            'responseHeaders' => null,
            'response' => null,
            'curlInfo' => null,
            'time' => null,
        ];
    }

    /**
     * Send a request and return the response body.
     *
     * The body is sent as JSON. For the log it is kept as a query string,
     * which Request redacts before it is written.
     *
     * @param string $method HTTP method.
     * @param string $url Full URL.
     * @param string|array|null $body
     * @param array $options Extra cURL options for this call.
     * @return string Response body without headers.
     * @throws \Exception When the connection fails.
     */
    public function call($method, $url, $body = null, $options = []): string
    {
        $this->reset();

        curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $method);
        $this->lastCall['method'] = $method;

        curl_setopt($this->handle, CURLOPT_URL, $url);
        $this->lastCall['url'] = $url;

        if (!empty($body)) {
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, json_encode($body));
            if (is_array($body)) {
                $this->lastCall['request'] = http_build_query($body);
            } else {
                $this->lastCall['request'] = $body;
            }
        }

        curl_setopt_array($this->handle, $options);

        $startTime = microtime(true);
        $output = curl_exec($this->handle);
        $this->lastCall['time'] = microtime(true) - $startTime;

        $info = curl_getinfo($this->handle);
        $this->lastCall['curlInfo'] = $info;

        if (!empty(curl_error($this->handle))) {
            throw new \Exception('Connection Error: ' . curl_error($this->handle));
        }

        if ($output === false) {
            throw new \Exception('Connection Failed');
        }

        $this->lastCall['requestHeaders'] = $info['request_header'] ?? '';
        $this->lastCall['response'] = substr($output, $info['header_size']);
        $this->lastCall['responseHeaders'] = substr($output, 0, $info['header_size']);

        return $this->lastCall['response'];
    }
}
