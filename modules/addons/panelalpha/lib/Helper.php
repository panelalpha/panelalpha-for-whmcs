<?php

namespace WHMCS\Module\Addon\PanelAlpha;

use WHMCS\Database\Capsule;

class Helper
{
    public static $config = [];

    public static function initWhmcs()
    {
        require_once dirname(__FILE__, 5) . '/init.php';
    }

    public static function config()
    {
        if (empty(self::$config)) {
            self::$config = [];
            $settings = Capsule::table('tbladdonmodules')->where('module', 'panelalpha')->get();
            foreach ($settings as $setting) {
                self::$config[$setting->setting] = $setting->value;
            }
        }
        return self::$config;
    }

    public static function updateConfig($key, $value)
    {
        if (!array_key_exists($key, self::config())) {
            return;
        }
        Capsule::table('tbladdonmodules')
            ->where('module', 'panelalpha')
            ->where('setting', $key)
            ->update([
                'value' => $value,
            ]);
        self::$config[$key] = $value;
    }

    public static function generateRandomString($length = 32)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function validateHttpMethod()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return;
        }
        self::jsonResponse(['error' => 'Method Not Allowed'], 405);
    }

    public static function validateIpAddress()
    {
        $config = self::config();
        $allowFrom = !empty($config['allow_from']) ? $config['allow_from'] : "";
        $ipWhitelist = explode(',', $allowFrom);
        if (in_array('*', $ipWhitelist)) {
            return;
        }
        if (in_array($_SERVER['REMOTE_ADDR'], $ipWhitelist)) {
            return;
        }
        self::jsonResponse(['error' => 'Forbidden'], 403);
    }

    public static function validateApiToken()
    {
        $config = self::config();
        $token = !empty($_SERVER['HTTP_API_TOKEN']) ? $_SERVER['HTTP_API_TOKEN'] : "";

        if (
            !empty($config['api_token'])
            && !empty($token)
            && $config['api_token'] === $token
        ) {
            return;
        }

        self::jsonResponse(['error' => 'Unauthorized'], 401);
    }

    public static function jsonResponse($data, $code = 200)
    {
        @ob_clean();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        die();
    }

    public static function dump($data)
    {
        echo "<pre>" . print_r($data, true) . "</pre>";
    }

    public static function dd($data)
    {
        die(self::dump($data));
    }

    public static function getUserDetails($userId): ?array
    {
        $whmcsServices = Capsule::select("
            SELECT `tblhosting`.*, `service_value`.`value` AS `panelalpha_id`
            FROM `tblhosting`
            JOIN `tblproducts`
                ON `tblproducts`.`id` = `tblhosting`.`packageid`
                AND `tblproducts`.`servertype` = 'panelalpha'
            JOIN `tblcustomfields`
                ON `tblcustomfields`.`type` = 'product'
                AND `tblcustomfields`.`relid` = `tblproducts`.`id`
                AND `tblcustomfields`.`fieldname` = 'User ID'
            JOIN `tblcustomfieldsvalues`
                ON `tblcustomfieldsvalues`.`fieldid` = `tblcustomfields`.`id`
                AND `tblcustomfieldsvalues`.`relid` = `tblhosting`.`id`
                AND `tblcustomfieldsvalues`.`value` = ?
            LEFT JOIN `tblcustomfields` `service_field`
                ON `service_field`.`type` = 'product'
                AND `service_field`.`relid` = `tblproducts`.`id`
                AND `service_field`.`fieldname` = 'Service ID'
            LEFT JOIN `tblcustomfieldsvalues` `service_value`
                ON `service_value`.`fieldid` = `tblcustomfields`.`id`
                AND `service_value`.`relid` = `tblhosting`.`id`
            ORDER BY `tblhosting`.`id`
        ", [$userId]);

        if (empty($whmcsServices)) {
            return null;
        }

        $whmcsClients = Capsule::select("
            SELECT `tblclients`.*, `tblcurrencies`.`code` AS `currency_code`
            FROM `tblclients`
            JOIN `tblcurrencies`
                ON `tblcurrencies`.`id` = `tblclients`.`currency`
            WHERE `tblclients`.`id` = ?
        ", [$whmcsServices[0]->userid]);
        if (empty($whmcsClients)) {
            return null;
        }
        $whmcsClient = reset($whmcsClients);

        $user = [
            'id' => $whmcsClient->id,
            'firstname' => $whmcsClient->firstname,
            'lastname' => $whmcsClient->lastname,
            'companyname' => $whmcsClient->companyname,
            'email' => $whmcsClient->email,
            'address' => $whmcsClient->address1,
            'city' => $whmcsClient->city,
            'state' => $whmcsClient->state,
            'postcode' => $whmcsClient->postcode,
            'country' => $whmcsClient->country,
        ];

        $services = [];
        $servicesIds = [];
        foreach ($whmcsServices as $hosting) {
            $servicesIds[] = $hosting->id;
            $services[] = [
                'id' => $hosting->id,
                'amount' => $hosting->amount,
                'amount_formatted' => (string)formatCurrency($hosting->amount, $whmcsClient->currency),
                'billingcycle' => $hosting->billingcycle,
                'nextduedate' => $hosting->nextduedate,
                'nextinvoicedate' => $hosting->nextinvoicedate,
                'status' => $hosting->domainstatus,
                'currency_code' => $whmcsClient->currency_code,
                'panelalpha_id' => $hosting->panelalpha_id,
            ];
        }
        $user['services'] = $services;
        $bindings = implode(',', array_fill(0, count($servicesIds), '?'));
        $whmcsInvoices = Capsule::select("
            SELECT `tblinvoices`.*, `tblinvoiceitems`.`relid`
            FROM `tblinvoices`
            JOIN `tblinvoiceitems`
                ON `tblinvoiceitems`.`invoiceid` = `tblinvoices`.`id`
                AND `tblinvoiceitems`.`type` = 'Hosting'
                AND `tblinvoiceitems`.`relid` IN ({$bindings})
            GROUP BY `tblinvoices`.`id`
            ORDER BY `tblinvoices`.`id`
        ", $servicesIds);
        $invoices = [];
        foreach ($whmcsInvoices as $invoice) {
            $invoices[] = [
                'id' => $invoice->id,
                'number' => $invoice->invoicenum,
                'date' => $invoice->date,
                'duedate' => $invoice->duedate,
                'datepaid' => $invoice->datepaid,
                'subtotal' => $invoice->subtotal,
                'total_formatted' => (string)formatCurrency($invoice->subtotal, $whmcsClient->currency),
                'total' => $invoice->total,
                'total_formatted' => (string)formatCurrency($invoice->total, $whmcsClient->currency),
                'status' => $invoice->status,
                'service_id' => $invoice->relid,
                'currency_code' => $whmcsClient->currency_code,
            ];
        }
        $user['invoices'] = $invoices;
        return $user;
    }

    public static function cancelService(int $serviceId, string $reason): void
    {
        $whmcsServices = Capsule::select("
            SELECT `tblhosting`.id
            FROM `tblhosting`
            JOIN `tblproducts`
                ON `tblproducts`.`id` = `tblhosting`.`packageid`
                AND `tblproducts`.`servertype` = 'panelalpha'
            JOIN `tblcustomfields`
                ON `tblcustomfields`.`type` = 'product'
                AND `tblcustomfields`.`relid` = `tblproducts`.`id`
                AND `tblcustomfields`.`fieldname` = 'Service ID'
            JOIN `tblcustomfieldsvalues`
                ON `tblcustomfieldsvalues`.`fieldid` = `tblcustomfields`.`id`
                AND `tblcustomfieldsvalues`.`relid` = `tblhosting`.`id`
                AND `tblcustomfieldsvalues`.`value` = ?
        ", [$serviceId]);

        if (empty($whmcsServices)) {
            throw new \Exception('Service not found');
        }
        if (count($whmcsServices) > 1) {
            throw new \Exception('Multiple services found, expected one');
        }

        $result = localAPI('AddCancelRequest', [
            'serviceid' => $whmcsServices[0]->id,
            'reason' => $reason,
            'type' => 'Immediate',
        ]);
        if ($result['result'] != 'success') {
            throw new \Exception($result['message']);
        }
    }

    public static function getPayInvoiceUrl(int $invoiceId): ?string
    {
        $invoiceitems = Capsule::select("
            SELECT `tblinvoices`.`userid`
            FROM `tblinvoiceitems`
            JOIN `tblinvoices`
                ON `tblinvoices`.`id` = `tblinvoiceitems`.`invoiceid`
            JOIN `tblhosting`
                ON `tblhosting`.`id` = `tblinvoiceitems`.`relid`
                AND `tblinvoiceitems`.`type` = 'Hosting'
            JOIN `tblproducts`
                ON `tblproducts`.`id` = `tblhosting`.`packageid`
                AND `tblproducts`.`servertype` = 'panelalpha'
            WHERE `tblinvoices`.`status` = 'Unpaid'
                AND `tblinvoices`.`id` = ?
        ", [$invoiceId]);
        if (empty($invoiceitems)) {
            return null;
        }
        $invoiceItem = reset($invoiceitems);
        $clientId = $invoiceItem->userid;

        $result = localAPI('CreateSsoToken', [
            'client_id' => $clientId,
            'destination' => 'sso:custom_redirect',
            'sso_redirect_path' => 'viewinvoice.php?id=' . $invoiceId,
        ]);

        if ($result['result'] != 'success') {
            throw new \Exception($result['message']);
        }
        return $result['redirect_url'];
    }

    public static function downloadInvoice(int $invoiceId): void
    {
        $invoiceitems = Capsule::select("
            SELECT `tblinvoices`.`id`, `tblinvoices`.`invoicenum`
            FROM `tblinvoiceitems`
            JOIN `tblinvoices`
                ON `tblinvoices`.`id` = `tblinvoiceitems`.`invoiceid`
            JOIN `tblhosting`
                ON `tblhosting`.`id` = `tblinvoiceitems`.`relid`
                AND `tblinvoiceitems`.`type` = 'Hosting'
            JOIN `tblproducts`
                ON `tblproducts`.`id` = `tblhosting`.`packageid`
                AND `tblproducts`.`servertype` = 'panelalpha'
            WHERE `tblinvoices`.`id` = ?
        ", [$invoiceId]);
        if (empty($invoiceitems)) {
            self::jsonResponse("Not Found", 404);
        }
        $invoiceItem = reset($invoiceitems);
        $invoiceNumber = $invoiceItem->invoicenum;
        if (!$invoiceNumber) {
            $invoiceNumber = $invoiceItem->id;
        }
        $invoiceNumber = preg_replace("|[\\\\/]+|", "-", $invoiceNumber);

        require_once ROOTDIR . "/includes/invoicefunctions.php";
        $pdf = \pdfInvoice($invoiceId);

        @ob_clean();
        header('Content-Disposition: attachment; filename="Invoice-' . $invoiceNumber . '.pdf"');
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($pdf));
        header('Connection: close');
        echo $pdf;
        die();
    }

    public static function localApi($command, $data = [])
    {
        return localAPI($command, $data, self::getAdmin());
    }

    private static function getAdmin()
    {
        return Capsule::table('tbladmins')->where('roleid', '1')->value('username');
    }
}
