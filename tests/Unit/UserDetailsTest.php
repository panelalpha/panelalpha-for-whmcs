<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Addon\PanelAlpha\Helper;

class UserDetailsTest extends TestCase
{
    protected function tearDown(): void
    {
        \CapsuleTestFixture::$selectHandler = null;
        parent::tearDown();
    }

    public function testGetUserDetailsUsesServiceIdCustomFieldAsPanelalphaId(): void
    {
        \CapsuleTestFixture::$selectHandler = function (string $sql, array $bindings) {
            if (stripos($sql, 'tblhosting') !== false && stripos($sql, 'Service ID') !== false) {
                $usesServiceFieldJoin = strpos($sql, '`service_value`.`fieldid` = `service_field`.`id`') !== false;
                $panelalphaId = $usesServiceFieldJoin ? '9' : '5';

                return [(object) [
                    'id' => 100,
                    'userid' => 42,
                    'amount' => '10.00',
                    'billingcycle' => 'Monthly',
                    'nextduedate' => '2026-11-01',
                    'nextinvoicedate' => '2026-11-01',
                    'domainstatus' => 'Active',
                    'panelalpha_id' => $panelalphaId,
                ]];
            }

            if (stripos($sql, 'tblclients') !== false) {
                return [(object) [
                    'id' => 42,
                    'firstname' => 'Jane',
                    'lastname' => 'Doe',
                    'companyname' => '',
                    'email' => 'jane@example.com',
                    'address1' => '1 Main St',
                    'city' => 'City',
                    'state' => '',
                    'postcode' => '12345',
                    'country' => 'US',
                    'currency' => 1,
                    'currency_code' => 'USD',
                ]];
            }

            if (stripos($sql, 'tblinvoices') !== false) {
                return [];
            }

            return [];
        };

        $user = Helper::getUserDetails('5');

        $this->assertNotNull($user);
        $this->assertCount(1, $user['services']);
        $this->assertSame('9', $user['services'][0]['panelalpha_id']);
    }

    public function testGetUserDetailsInvoiceFormattedSubtotalAndTotal(): void
    {
        \CapsuleTestFixture::$selectHandler = function (string $sql, array $bindings) {
            if (stripos($sql, 'tblhosting') !== false && stripos($sql, 'Service ID') !== false) {
                return [(object) [
                    'id' => 100,
                    'userid' => 42,
                    'amount' => '10.00',
                    'billingcycle' => 'Monthly',
                    'nextduedate' => '2026-11-01',
                    'nextinvoicedate' => '2026-11-01',
                    'domainstatus' => 'Active',
                    'panelalpha_id' => '9',
                ]];
            }

            if (stripos($sql, 'tblclients') !== false) {
                return [(object) [
                    'id' => 42,
                    'firstname' => 'Jane',
                    'lastname' => 'Doe',
                    'companyname' => '',
                    'email' => 'jane@example.com',
                    'address1' => '1 Main St',
                    'city' => 'City',
                    'state' => '',
                    'postcode' => '12345',
                    'country' => 'US',
                    'currency' => 1,
                    'currency_code' => 'USD',
                ]];
            }

            if (stripos($sql, 'tblinvoices') !== false) {
                return [(object) [
                    'id' => 200,
                    'invoicenum' => 'INV-200',
                    'date' => '2026-10-01',
                    'duedate' => '2026-10-15',
                    'datepaid' => '',
                    'subtotal' => '80.00',
                    'total' => '99.20',
                    'status' => 'Unpaid',
                    'relid' => 100,
                ]];
            }

            return [];
        };

        $user = Helper::getUserDetails('5');

        $this->assertNotNull($user);
        $this->assertCount(1, $user['invoices']);
        $invoice = $user['invoices'][0];
        $this->assertArrayHasKey('subtotal_formatted', $invoice);
        $this->assertArrayHasKey('total_formatted', $invoice);
        $this->assertSame('CUR1:80.00', $invoice['subtotal_formatted']);
        $this->assertSame('CUR1:99.20', $invoice['total_formatted']);
        $this->assertNotSame($invoice['subtotal_formatted'], $invoice['total_formatted']);
    }
}
