<?php
set_time_limit(0);
define('DS', DIRECTORY_SEPARATOR);
define('WHMCS_MAIN_DIR', substr(dirname(__FILE__), 0, strpos(dirname(__FILE__), 'modules' . DS . 'servers')));
define('ADDON_DIR', substr(dirname(__FILE__), 0, strpos(dirname(__FILE__), 'lib')));

require_once WHMCS_MAIN_DIR . DS . 'init.php';
require_once ADDON_DIR . 'vendor/autoload.php';

use WHMCS\Module\Server\PanelAlpha\Helper;
use WHMCS\Module\Server\PanelAlpha\Models\Service;
use WHMCS\Module\Server\PanelAlpha\PanelAlphaClient;
use GuzzleHttp\Client;

$service = Service::findOrFail($_REQUEST['id']);
$server = $service->product->getServer();
$userId = Helper::getCustomField($_REQUEST['id'], 'User ID');

$connection = new PanelAlphaClient($server);

$client = new Client();

$hostname = $server['port'] ? $server['hostname'] . ':' . $server['port'] : $server['hostname'];

$promise = $client->postAsync('https://' . $hostname . '/api/admin/users/' . $userId . '/sso-token', [
    'headers' => [
        'Authorization' => 'Bearer ' . $server['accesshash']
    ],
    'verify' => $server['secure'] === 'on'
])->then(function ($response) {
    $data = json_decode($response->getBody()->getContents());
    return $data->data;
});
$data = $promise->wait();
header("Location: {$data->url}/sso-login?token={$data->token}");
exit();