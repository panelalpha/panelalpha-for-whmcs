<?php

use WHMCS\Module\Addon\PanelAlpha\Helper;

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
            try {
                Helper::cancelService($serviceId, $reason);
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
            $url = Helper::getPayInvoiceUrl($invoiceId);
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
            Helper::downloadInvoice($invoiceId);
            break;
        case 'ping':
            Helper::jsonResponse("pong");
            break;
        default:
            Helper::jsonResponse(["error" => "Invalid value for parameter 'action'"], 422);
            break;
    }
} catch (\Exception $e) {
    Helper::jsonResponse(['error' => 'Oops! Something went wrong.'], 500);
}
