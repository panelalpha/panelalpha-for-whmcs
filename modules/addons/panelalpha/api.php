<?php

/**
 * HTTP endpoint called by the PanelAlpha panel.
 *
 * Every request must be a POST from an allowed IP address with a valid API-Token
 * header. The `action` parameter selects the operation. Helper::jsonResponse()
 * sends the response and ends the request.
 */

use WHMCS\Module\Addon\PanelAlpha\Helper;
use WHMCS\Module\Addon\PanelAlpha\Models\Client;
use WHMCS\Module\Addon\PanelAlpha\Models\DnsManager\GlobalSetting;
use WHMCS\Module\Addon\PanelAlpha\Models\DnsManager\Zone;

require_once dirname(__FILE__) . '/lib/Helper.php';

try {
    Helper::initWhmcs();
    Helper::validateHttpMethod();
    Helper::validateIpAddress();
    Helper::validateApiToken();

    if (empty($_REQUEST['action'])) {
        Helper::jsonResponse(["error" => "Parameter 'action' is required"], 422);
    }

    switch ($_REQUEST['action']) {
        case 'get_user_details':
            if (empty($_REQUEST['user_id'])) {
                Helper::jsonResponse(["error" => "Parameter 'user_id' is required"], 422);
            }
            $userId = filter_var($_REQUEST['user_id'], FILTER_VALIDATE_INT);
            if ($userId === false) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'user_id'"], 422);
            }
            $details = Helper::getUserDetails($userId);
            if (empty($details)) {
                Helper::jsonResponse("Not Found", 404);
            }
            Helper::jsonResponse($details);
            break;
        case 'cancel_service':
            if (empty($_REQUEST['service_id'])) {
                Helper::jsonResponse(["error" => "Parameter 'service_id' is required"], 422);
            }
            $serviceId = filter_var($_REQUEST['service_id'], FILTER_VALIDATE_INT);
            if ($serviceId === false) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'service_id'"], 422);
            }
            $reason = "";
            if (!empty($_REQUEST['reason'])) {
                if (!is_string($_REQUEST['reason'])) {
                    Helper::jsonResponse(["error" => "Invalid value for parameter 'reason'"], 422);
                }
                $reason = $_REQUEST['reason'];
            }
            $serverHostname = null;
            if (!empty($_REQUEST['server_hostname'])) {
                if (!is_string($_REQUEST['server_hostname'])) {
                    Helper::jsonResponse(["error" => "Invalid value for parameter 'server_hostname'"], 422);
                }
                $serverHostname = $_REQUEST['server_hostname'];
            }
            try {
                Helper::cancelService($serviceId, $reason, $serverHostname);
                Helper::jsonResponse(["success" => true]);
            } catch (\Exception $e) {
                Helper::jsonResponse(["error" => $e->getMessage()], 422);
            }
            break;
        case 'get_pay_invoice_url':
            if (empty($_REQUEST['invoice_id'])) {
                Helper::jsonResponse(["error" => "Parameter 'invoice_id' is required"], 422);
            }
            $invoiceId = filter_var($_REQUEST['invoice_id'], FILTER_VALIDATE_INT);
            if ($invoiceId === false) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'invoice_id'"], 422);
            }
            $userId = null;
            if (!empty($_REQUEST['user_id'])) {
                $userId = filter_var($_REQUEST['user_id'], FILTER_VALIDATE_INT);
                if ($userId === false) {
                    Helper::jsonResponse("Not Found", 404);
                }
            }
            $url = Helper::getPayInvoiceUrl($invoiceId, $userId);
            if (empty($url)) {
                Helper::jsonResponse("Not Found", 404);
            }
            Helper::jsonResponse([
                'url' => $url,
            ]);
            break;
        case 'download_invoice':
            if (empty($_REQUEST['invoice_id'])) {
                Helper::jsonResponse(["error" => "Parameter 'invoice_id' is required"], 422);
            }
            $invoiceId = filter_var($_REQUEST['invoice_id'], FILTER_VALIDATE_INT);
            if ($invoiceId === false) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'invoice_id'"], 422);
            }
            $userId = null;
            if (!empty($_REQUEST['user_id'])) {
                $userId = filter_var($_REQUEST['user_id'], FILTER_VALIDATE_INT);
                if ($userId === false) {
                    Helper::jsonResponse("Not Found", 404);
                }
            }
            Helper::downloadInvoice($invoiceId, $userId);
            break;
        case 'ping':
            Helper::jsonResponse("pong");
            break;

        // DNS Manager For WHMCS
        case 'dns_manager_test_connection':
            $result = Helper::localApi('dnsmanager', [
                'dnsaction' => 'getServerList',
            ]);

            if ($result['result'] !== 'success') {
                Helper::jsonResponse($result);
            }

            if (GlobalSetting::get('zone_create_custom_ip') !== 'on') {
                Helper::jsonResponse([
                    'result' => 'error',
                    'message' => 'Custom IP is disabled. Please enable it in the module settings.'
                ]);
            }

            Helper::jsonResponse([
                'result' => 'success',
                'message' => 'Connection successful',
            ]);
            break;

        case 'dns_manager_list_zones':
            $result = Helper::localApi('dnsmanager', [
                'dnsaction' => 'getZoneList',
            ]);
            Helper::jsonResponse($result);
            break;

        case 'dns_manager_get_zone':
            if (!empty($_REQUEST['zone_id'])) {
                $zoneId = filter_var($_REQUEST['zone_id'], FILTER_VALIDATE_INT);
                if ($zoneId === false) {
                    Helper::jsonResponse(["error" => "Invalid value for parameter 'zone_id'"], 422);
                }

                $zone = Helper::requireDnsZoneOwnedByClientEmail($zoneId, $_REQUEST['action']);
                Helper::jsonResponse($zone);
            }

            if (!empty($_REQUEST['zone_name'])) {
                if (!is_string($_REQUEST['zone_name'])) {
                    Helper::jsonResponse(["error" => "Invalid value for parameter 'zone_name'"], 422);
                }
                $zoneName = $_REQUEST['zone_name'];

                if (empty($_REQUEST['user_email'])) {
                    Helper::jsonResponse(["error" => "Parameter 'user_email' is required"], 422);
                }
                $userEmail = filter_var($_REQUEST['user_email'], FILTER_VALIDATE_EMAIL);
                if ($userEmail === false) {
                    Helper::jsonResponse(["error" => "Invalid value for parameter 'user_email'"], 422);
                }
                $client = Client::where('email', $userEmail)->first();
                if ($client === null) {
                    Helper::jsonResponse(["error" => "Client not found"], 404);
                }

                $zone = Zone::where('name', $zoneName)->where('clientid', $client->id)->first();
                if ($zone === null) {
                    Helper::jsonResponse(["error" => "Zone not found"], 404);
                }
                Helper::jsonResponse($zone);
            }

            Helper::jsonResponse(["error" => "Parameter 'zone_name' or 'zone_id' is required"], 422);
            break;

        case 'dns_manager_create_zone':
            if (empty($_REQUEST['zone_name'])) {
                Helper::jsonResponse(["error" => "Parameter 'zone_name' is required"], 422);
            }
            if (!is_string($_REQUEST['zone_name'])) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'zone_name'"], 422);
            }
            $zoneName = $_REQUEST['zone_name'];

            if (empty($_REQUEST['user_email'])) {
                Helper::jsonResponse(["error" => "Parameter 'user_email' is required"], 422);
            }
            $userEmail = filter_var($_REQUEST['user_email'], FILTER_VALIDATE_EMAIL);
            if ($userEmail === false) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'user_email'"], 422);
            }
            $client = Client::where('email', $userEmail)->first();
            if ($client === null) {
                Helper::jsonResponse(["error" => "Client not found"], 404);
            }

            if (empty($_REQUEST['ip_address'])) {
                Helper::jsonResponse(["error" => "Parameter 'ip_address' is required"], 422);
            }
            if (!is_string($_REQUEST['ip_address'])) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'ip_address'"], 422);
            }
            $ipAddress = filter_var($_REQUEST['ip_address'], FILTER_VALIDATE_IP);
            if ($ipAddress === false) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'ip_address'"], 422);
            }

            Helper::localApi('dnsmanager', [
                'dnsaction' => 'createZone',
                'zone_name' => $zoneName,
                'type' => '0',
                'relid' => '0',
                'userid' => (string)$client->id,
                'zone_ip' => $ipAddress,
            ]);

            $zone = Zone::where('name', $zoneName)->where('clientid', $client->id)->first();
            if ($zone === null) {
                Helper::jsonResponse(["error" => "Zone not found"], 404);
            }

            run_hook('PanelalphaDnsZoneCreated', [
                'zone_id' => $zone->id,
                'zone_name' => $zoneName,
            ]);

            Helper::jsonResponse($zone);
            break;

        case 'dns_manager_remove_zone':
            if (empty($_REQUEST['zone_id'])) {
                Helper::jsonResponse(["error" => "Parameter 'zone_id' is required"], 422);
            }
            $zoneId = filter_var($_REQUEST['zone_id'], FILTER_VALIDATE_INT);
            if ($zoneId === false) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'zone_id'"], 422);
            }

            // Removing a zone is destructive, so the legacy lookup without an owner is not allowed here.
            if (empty($_REQUEST['user_email'])) {
                Helper::jsonResponse(["error" => "Parameter 'user_email' is required"], 422);
            }
            $zone = Helper::requireDnsZoneOwnedByClientEmail($zoneId, $_REQUEST['action']);

            $result = Helper::localApi('dnsmanager', [
                'dnsaction' => 'removeZone',
                'zone_id' => (string)$zoneId,
                'userid' => (string)$zone->clientid,
            ]);
            Helper::jsonResponse($result);
            break;

        case 'dns_manager_list_records':
            if (empty($_REQUEST['zone_id'])) {
                Helper::jsonResponse(["error" => "Parameter 'zone_id' is required"], 422);
            }
            $zoneId = filter_var($_REQUEST['zone_id'], FILTER_VALIDATE_INT);
            if ($zoneId === false) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'zone_id'"], 422);
            }

            Helper::requireDnsZoneOwnedByClientEmail($zoneId, $_REQUEST['action']);

            $result = Helper::localApi('dnsmanager', [
                'dnsaction' => 'getZone',
                'zone_id' => $zoneId,
            ]);
            Helper::jsonResponse($result);
            break;

        case 'dns_manager_create_record':
            if (empty($_REQUEST['zone_id'])) {
                Helper::jsonResponse(["error" => "Parameter 'zone_id' is required"], 422);
            }
            $zoneId = filter_var($_REQUEST['zone_id'], FILTER_VALIDATE_INT);
            if ($zoneId === false) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'zone_id'"], 422);
            }

            if (empty($_REQUEST['record'])) {
                Helper::jsonResponse(["error" => "Parameter 'record' is required"], 422);
            }
            if (!is_array($_REQUEST['record'])) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'record'"], 422);
            }
            $record = $_REQUEST['record'];

            Helper::requireDnsZoneOwnedByClientEmail($zoneId, $_REQUEST['action']);

            $result = Helper::localApi('dnsmanager', [
                'dnsaction' => 'addRecord',
                'zone_id' => (string)$zoneId,
                'record' => $record,
            ]);
            Helper::jsonResponse($result);
            break;

        case 'dns_manager_remove_record':
            if (empty($_REQUEST['zone_id'])) {
                Helper::jsonResponse(["error" => "Parameter 'zone_id' is required"], 422);
            }
            $zoneId = filter_var($_REQUEST['zone_id'], FILTER_VALIDATE_INT);
            if ($zoneId === false) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'zone_id'"], 422);
            }

            if (empty($_REQUEST['record'])) {
                Helper::jsonResponse(["error" => "Parameter 'record' is required"], 422);
            }
            if (!is_array($_REQUEST['record'])) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'record'"], 422);
            }
            $record = $_REQUEST['record'];

            Helper::requireDnsZoneOwnedByClientEmail($zoneId, $_REQUEST['action']);

            $result = Helper::localApi('dnsmanager', [
                'dnsaction' => 'removeRecord',
                'zone_id' => (string)$zoneId,
                'record' => $record,
            ]);
            Helper::jsonResponse($result);
            break;

        case 'dns_manager_update_record':
            if (empty($_REQUEST['zone_id'])) {
                Helper::jsonResponse(["error" => "Parameter 'zone_id' is required"], 422);
            }
            $zoneId = filter_var($_REQUEST['zone_id'], FILTER_VALIDATE_INT);
            if ($zoneId === false) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'zone_id'"], 422);
            }

            if (empty($_REQUEST['record'])) {
                Helper::jsonResponse(["error" => "Parameter 'record' is required"], 422);
            }
            if (!is_array($_REQUEST['record'])) {
                Helper::jsonResponse(["error" => "Invalid value for parameter 'record'"], 422);
            }
            $record = $_REQUEST['record'];

            Helper::requireDnsZoneOwnedByClientEmail($zoneId, $_REQUEST['action']);

            $result = Helper::localApi('dnsmanager', [
                'dnsaction' => 'editRecord',
                'zone_id' => (string)$zoneId,
                'record' => $record,
            ]);
            Helper::jsonResponse($result);
            break;

        default:
            Helper::jsonResponse(["error" => "Invalid value for parameter 'action'"], 422);
            break;
    }
} catch (\Exception $e) {
    Helper::jsonResponse(['error' => 'Oops! Something went wrong.'], 500);
}
