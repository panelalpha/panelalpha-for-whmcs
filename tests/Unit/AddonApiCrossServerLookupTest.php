<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Addon\PanelAlpha\Helper;

class AddonApiCrossServerLookupTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/support/LocalApiStub.php';
    }

    protected function setUp(): void
    {
        \LocalApiTestFixture::reset();
    }

    protected function tearDown(): void
    {
        \CapsuleTestFixture::$selectHandler = null;
        parent::tearDown();
    }

    public function testGetUserDetailsReturnsNullWhenPanelUserIdMatchesMultipleWhmcsClients(): void
    {
        \CapsuleTestFixture::$selectHandler = function (string $sql, array $bindings) {
            if (stripos($sql, 'tblhosting') !== false && stripos($sql, 'User ID') !== false) {
                return [
                    (object) [
                        'id' => 100,
                        'userid' => 1,
                        'amount' => '10.00',
                        'billingcycle' => 'Monthly',
                        'nextduedate' => '2026-11-01',
                        'nextinvoicedate' => '2026-11-01',
                        'domainstatus' => 'Active',
                        'panelalpha_id' => '9',
                    ],
                    (object) [
                        'id' => 200,
                        'userid' => 2,
                        'amount' => '20.00',
                        'billingcycle' => 'Monthly',
                        'nextduedate' => '2026-11-01',
                        'nextinvoicedate' => '2026-11-01',
                        'domainstatus' => 'Active',
                        'panelalpha_id' => '10',
                    ],
                ];
            }

            if (stripos($sql, 'tblclients') !== false) {
                return [(object) [
                    'id' => (int) $bindings[0],
                    'firstname' => 'Client',
                    'lastname' => (string) $bindings[0],
                    'companyname' => '',
                    'email' => 'client' . $bindings[0] . '@example.com',
                    'address1' => '',
                    'city' => '',
                    'state' => '',
                    'postcode' => '',
                    'country' => 'US',
                    'currency' => 1,
                    'currency_code' => 'USD',
                ]];
            }

            return [];
        };

        $this->assertNull(Helper::getUserDetails('5'));
    }

    public function testGetUserDetailsIncludesOnlyServicesForSingleWhmcsClient(): void
    {
        $invoiceServiceIds = null;
        \CapsuleTestFixture::$selectHandler = function (string $sql, array $bindings) use (&$invoiceServiceIds) {
            if (stripos($sql, 'tblhosting') !== false && stripos($sql, 'User ID') !== false) {
                return [
                    (object) [
                        'id' => 100,
                        'userid' => 42,
                        'amount' => '10.00',
                        'billingcycle' => 'Monthly',
                        'nextduedate' => '2026-11-01',
                        'nextinvoicedate' => '2026-11-01',
                        'domainstatus' => 'Active',
                        'panelalpha_id' => '9',
                    ],
                    (object) [
                        'id' => 101,
                        'userid' => 42,
                        'amount' => '15.00',
                        'billingcycle' => 'Monthly',
                        'nextduedate' => '2026-11-01',
                        'nextinvoicedate' => '2026-11-01',
                        'domainstatus' => 'Active',
                        'panelalpha_id' => '11',
                    ],
                ];
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
                $invoiceServiceIds = $bindings;

                return [];
            }

            return [];
        };

        $user = Helper::getUserDetails('5');

        $this->assertNotNull($user);
        $this->assertSame(42, $user['id']);
        $this->assertCount(2, $user['services']);
        $this->assertSame([100, 101], array_column($user['services'], 'id'));
        $this->assertSame([100, 101], $invoiceServiceIds);
    }

    public function testCancelServiceDoesNotCancelWhenServerHostnameDoesNotMatch(): void
    {
        \CapsuleTestFixture::$selectHandler = function (string $sql, array $bindings) {
            if (stripos($sql, 'Service ID') !== false && stripos($sql, 'tblhosting') !== false) {
                if (stripos($sql, 'tblservers') !== false && in_array('panel-b.example', $bindings, true)) {
                    return [];
                }

                return [(object) ['id' => 300]];
            }

            return [];
        };

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service not found');

        Helper::cancelService(77, 'test', 'panel-b.example');
    }
}
