<?php

namespace WHMCS\Module\Server\PanelAlpha;

class Curl
{
    /** @var \CurlHandle */
    private $handle;

    /** @var array{curlInfo: array} $lastCall */
    private $lastCall;

    public $action = "";

    /** @var bool */
    private $verifySsl = true;

    /** @var array */
    public $defaultOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLINFO_HEADER_OUT => true,
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
     * @param int $seconds
     * @return void
     */
    public function setTimeout($seconds) {
        $this->defaultOptions[CURLOPT_CONNECTTIMEOUT] = $seconds;
    }

    /**
     * @return array
     */
    public function getLastCall()
    {
        return $this->lastCall;
    }

    /**
     * @return int
     */
    public function getLastHttpCode()
    {
        /** @var int */
        return $this->lastCall['curlInfo']['http_code'];
    }

    public function verifySsl(bool $verify = true): void
    {
        $this->verifySsl = $verify;
    }

    /**
     * @return void
     */
    private function reset()
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
     * @param string $method
     * @param string $url
     * @param string|array $body
     * @param array $options
     * @return string
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
            throw new \Exception("Connection Error: " . curl_error($this->handle));
        }

        if ($output === false) {
            throw new \Exception("Connection Failed");
        }

        $this->lastCall['requestHeaders']  = $info['request_header'] ?? '';
        $this->lastCall['response']        = substr($output, $info['header_size']);
        $this->lastCall['responseHeaders'] = substr($output, 0, $info['header_size']);

        return $this->lastCall['response'];
    }
}
