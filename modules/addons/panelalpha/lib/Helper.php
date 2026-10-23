<?php

namespace WHMCS\Module\Addon\PanelAlpha;

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\PanelAlpha\Models\Client;
use WHMCS\Module\Addon\PanelAlpha\Models\DnsManager\Zone;

/**
 * Shared helpers for the PanelAlpha addon API endpoint.
 */
class Helper
{
    /**
     * Cached addon settings, keyed by setting name.
     *
     * @var array<string, string>
     */
    public static $config = [];

    /**
     * Boots WHMCS so the endpoint can use its database and local API.
     *
     * @return void
     */
    public static function initWhmcs()
    {
        require_once dirname(__FILE__, 5) . '/init.php';
    }

    /**
     * Returns the addon settings, loading them from the database on first use.
     *
     * @return array<string, string>
     */
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

    /**
     * Updates an existing addon setting. Unknown keys are ignored.
     *
     * @param string $key   Setting name.
     * @param string $value New value.
     * @return void
     */
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

    /**
     * Generates a random alphanumeric string using a secure random source.
     *
     * @param int $length Number of characters.
     * @return string
     * @throws \Exception When no secure random source is available.
     */
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

    /**
     * Stops the request with 405 unless it is a POST.
     *
     * @return void
     */
    public static function validateHttpMethod()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }
        self::jsonResponse(['error' => 'Method Not Allowed'], 405);
    }

    /**
     * Stops the request with 403 unless the client IP is in the "Allow From" list.
     *
     * @return void
     */
    public static function validateIpAddress()
    {
        $config = self::config();
        $allowFrom = !empty($config['allow_from']) ? $config['allow_from'] : "";
        // Admins often write "1.2.3.4, 5.6.7.8", so spaces around the commas are ignored.
        $ipWhitelist = array_filter(array_map('trim', explode(',', $allowFrom)), 'strlen');
        if (in_array('*', $ipWhitelist, true)) {
            return;
        }
        if (in_array($_SERVER['REMOTE_ADDR'] ?? '', $ipWhitelist, true)) {
            return;
        }
        self::jsonResponse(['error' => 'Forbidden'], 403);
    }

    /**
     * Stops the request with 401 unless the API-Token header matches the configured token.
     *
     * @return void
     */
    public static function validateApiToken()
    {
        $config = self::config();
        $token = !empty($_SERVER['HTTP_API_TOKEN']) ? $_SERVER['HTTP_API_TOKEN'] : "";

        if (
            !empty($config['api_token'])
            && !empty($token)
            && hash_equals($config['api_token'], $token)
        ) {
            return;
        }

        self::jsonResponse(['error' => 'Unauthorized'], 401);
    }

    /**
     * Sends a JSON response and ends the request.
     *
     * @param mixed $data Data to encode.
     * @param int   $code HTTP status code.
     * @return never
     */
    public static function jsonResponse($data, $code = 200)
    {
        @ob_clean();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        die();
    }

    /**
     * Returns the WHMCS client, services and invoices linked to a PanelAlpha user.
     *
     * Returns null when no service is found or when the services belong to more
     * than one WHMCS client, so data is never mixed between clients.
     *
     * @param int $userId PanelAlpha user id (the "User ID" custom field value).
     * @return array<string, mixed>|null
     */
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
                ON `service_value`.`fieldid` = `service_field`.`id`
                AND `service_value`.`relid` = `tblhosting`.`id`
            ORDER BY `tblhosting`.`id`
        ", [$userId]);

        if (empty($whmcsServices)) {
            return null;
        }

        $clientUserIds = [];
        foreach ($whmcsServices as $hosting) {
            $clientUserIds[(int) $hosting->userid] = true;
        }
        if (count($clientUserIds) > 1) {
            return null;
        }
        $clientUserId = (int) array_key_first($clientUserIds);

        $whmcsClients = Capsule::select("
            SELECT `tblclients`.*, `tblcurrencies`.`code` AS `currency_code`
            FROM `tblclients`
            JOIN `tblcurrencies`
                ON `tblcurrencies`.`id` = `tblclients`.`currency`
            WHERE `tblclients`.`id` = ?
        ", [$clientUserId]);
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
                'subtotal_formatted' => (string)formatCurrency($invoice->subtotal, $whmcsClient->currency),
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

    /**
     * Sends an immediate cancellation request for the WHMCS service linked to a PanelAlpha service.
     *
     * @param int         $serviceId      PanelAlpha service id (the "Service ID" custom field value).
     * @param string      $reason         Cancellation reason.
     * @param string|null $serverHostname Hostname of the calling panel, used to pick the right
     *                                    service when several servers use the same service id.
     * @return void
     * @throws \Exception When no service or more than one service matches, or WHMCS rejects the request.
     */
    public static function cancelService(int $serviceId, string $reason, ?string $serverHostname = null): void
    {
        $whmcsServices = Capsule::select("
            SELECT `tblhosting`.id, `tblservers`.`hostname` AS `server_hostname`
            FROM `tblhosting`
            JOIN `tblproducts`
                ON `tblproducts`.`id` = `tblhosting`.`packageid`
                AND `tblproducts`.`servertype` = 'panelalpha'
            LEFT JOIN `tblservers`
                ON `tblservers`.`id` = `tblhosting`.`server`
            JOIN `tblcustomfields`
                ON `tblcustomfields`.`type` = 'product'
                AND `tblcustomfields`.`relid` = `tblproducts`.`id`
                AND `tblcustomfields`.`fieldname` = 'Service ID'
            JOIN `tblcustomfieldsvalues`
                ON `tblcustomfieldsvalues`.`fieldid` = `tblcustomfields`.`id`
                AND `tblcustomfieldsvalues`.`relid` = `tblhosting`.`id`
                AND `tblcustomfieldsvalues`.`value` = ?
        ", [$serviceId]);

        if ($serverHostname !== null && $serverHostname !== '') {
            $wantedHost = self::normalizeHost($serverHostname);
            $whmcsServices = array_values(array_filter(
                $whmcsServices,
                static fn ($row) => self::normalizeHost((string) ($row->server_hostname ?? '')) === $wantedHost
            ));
        }

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
        if ($result['result'] !== 'success') {
            throw new \Exception($result['message']);
        }
    }

    /**
     * Normalizes a hostname or URL to "host" or "host:port" in lower case.
     *
     * @param string $hostname Hostname or URL.
     * @return string
     */
    private static function normalizeHost(string $hostname): string
    {
        $hostname = strtolower(trim($hostname));
        if (!str_contains($hostname, '://')) {
            $hostname = '//' . $hostname;
        }
        $host = parse_url($hostname, PHP_URL_HOST);
        if (!is_string($host)) {
            return rtrim($hostname, '/');
        }
        $port = parse_url($hostname, PHP_URL_PORT);

        return $port === null ? $host : $host . ':' . $port;
    }

    /**
     * Returns the SQL joining an invoice item to a PanelAlpha user and its bindings.
     * Without a panel user id (legacy panel) the join is left out.
     *
     * @param int      $invoiceId   Invoice id, used only for the log entry.
     * @param int|null $panelUserId PanelAlpha user id, or null for a legacy panel.
     * @param string   $action      API action name, used only for the log entry.
     * @return array{0: string, 1: array<int, int>}
     */
    private static function invoicePanelUserJoin(int $invoiceId, ?int $panelUserId, string $action): array
    {
        if ($panelUserId === null) {
            logActivity("PanelAlpha: {$action} called without user_id (legacy panel), invoice #{$invoiceId}");

            return ['', []];
        }

        return [
            "JOIN `tblcustomfields` `user_field`
                ON `user_field`.`type` = 'product'
                AND `user_field`.`relid` = `tblproducts`.`id`
                AND `user_field`.`fieldname` = 'User ID'
            JOIN `tblcustomfieldsvalues` `user_value`
                ON `user_value`.`fieldid` = `user_field`.`id`
                AND `user_value`.`relid` = `tblhosting`.`id`
                AND `user_value`.`value` = ?",
            [$panelUserId],
        ];
    }

    /**
     * Returns an SSO link that opens an unpaid PanelAlpha invoice in the client area.
     *
     * @param int      $invoiceId   Invoice id.
     * @param int|null $panelUserId PanelAlpha user id the invoice must belong to, or null for a legacy panel.
     * @return string|null Null when no matching unpaid invoice is found.
     * @throws \Exception When WHMCS cannot create the SSO token.
     */
    public static function getPayInvoiceUrl(int $invoiceId, ?int $panelUserId = null): ?string
    {
        [$userJoin, $userBindings] = self::invoicePanelUserJoin($invoiceId, $panelUserId, 'get_pay_invoice_url');
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
            {$userJoin}
            WHERE `tblinvoices`.`status` = 'Unpaid'
                AND `tblinvoices`.`id` = ?
        ", [...$userBindings, $invoiceId]);
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

        if ($result['result'] !== 'success') {
            throw new \Exception($result['message']);
        }
        return $result['redirect_url'];
    }

    /**
     * Sends a PanelAlpha invoice as a PDF download and ends the request.
     *
     * Responds with 404 when the invoice is not found or does not belong to the given user.
     *
     * @param int      $invoiceId   Invoice id.
     * @param int|null $panelUserId PanelAlpha user id the invoice must belong to, or null for a legacy panel.
     * @return void
     */
    public static function downloadInvoice(int $invoiceId, ?int $panelUserId = null): void
    {
        [$userJoin, $userBindings] = self::invoicePanelUserJoin($invoiceId, $panelUserId, 'download_invoice');
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
            {$userJoin}
            WHERE `tblinvoices`.`id` = ?
        ", [...$userBindings, $invoiceId]);
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

    /**
     * Loads a DNS zone. With a `user_email` request parameter the zone must belong to that client;
     * without it (legacy panel) the zone is loaded by id only.
     * Ends the request with 404 or 422 when the zone cannot be returned.
     *
     * @param int    $zoneId Zone id.
     * @param string $action API action name, used only for the log entry.
     * @return Zone
     */
    public static function requireDnsZoneOwnedByClientEmail(int $zoneId, string $action = 'dns_manager'): Zone
    {
        if (empty($_REQUEST['user_email'])) {
            logActivity("PanelAlpha: {$action} called without user_email (legacy panel), zone #{$zoneId}");
            $zone = Zone::find($zoneId);
            if ($zone === null) {
                self::jsonResponse(['error' => 'Zone not found'], 404);
            }

            return $zone;
        }
        $userEmail = filter_var($_REQUEST['user_email'], FILTER_VALIDATE_EMAIL);
        if ($userEmail === false) {
            self::jsonResponse(['error' => 'Invalid value for parameter \'user_email\''], 422);
        }
        $client = Client::where('email', $userEmail)->first();
        if ($client === null) {
            self::jsonResponse(['error' => 'Client not found'], 404);
        }
        $zone = Zone::find($zoneId);
        if ($zone === null || (int) $zone->clientid !== (int) $client->id) {
            self::jsonResponse(['error' => 'Zone not found'], 404);
        }

        return $zone;
    }

    /**
     * Calls the WHMCS local API as the first full administrator.
     *
     * @param string               $command Local API command.
     * @param array<string, mixed> $data    Command parameters.
     * @return array<string, mixed>
     */
    public static function localApi($command, $data = [])
    {
        return localAPI($command, $data, self::getAdmin());
    }

    /**
     * Returns the username of the first administrator with the Full Administrator role.
     *
     * @return string|null
     */
    private static function getAdmin()
    {
        return Capsule::table('tbladmins')->where('roleid', '1')->value('username');
    }
}
