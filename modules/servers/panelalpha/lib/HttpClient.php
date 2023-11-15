<?php

namespace WHMCS\Module\Server\PanelAlpha;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HttpClient
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @return mixed
     * @throws GuzzleException
     * @throws \Exception
     */
    public function execute(string $method, string $url, array $options)
    {
        try {
            $response = $this->client->request($method, $url, $options);
        } catch (\Exception $e) {
            if ($e->getCode() === 401) {
                throw new \Exception('Unauthorized');
            }
            throw new \Exception($e->getMessage());
        }
        return json_decode($response->getBody()->getContents(), true);
    }
}