<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DnsZoneBindingTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/support/LocalApiStub.php';
        require_once dirname(__DIR__) . '/support/DnsZoneTestFixture.php';
        require_once dirname(__DIR__) . '/support/DnsManagerModelStub.php';
    }

    protected function setUp(): void
    {
        \LocalApiTestFixture::reset();
        \DnsZoneTestFixture::reset();
    }

    public function testListRecordsLegacyModeWorksWithoutUserEmail(): void
    {
        $output = $this->runApiAction(
            [
                'action' => 'dns_manager_list_records',
                'zone_id' => '10',
            ],
            [
                'zonesById' => [10 => (object) ['id' => 10, 'clientid' => 1, 'name' => 'example.com']],
            ]
        );

        $this->assertStringContainsString('"result": "success"', $output);
        $this->assertStringContainsString('"zone": []', $output);
    }

    public function testListRecordsLegacyModeReturns404WhenZoneDoesNotExist(): void
    {
        $output = $this->runApiAction(
            [
                'action' => 'dns_manager_list_records',
                'zone_id' => '10',
            ]
        );

        $this->assertStringContainsString('Zone not found', $output);
    }

    public function testCreateRecordLegacyModeWorksWithoutUserEmail(): void
    {
        $result = $this->runApiActionWithLocalApiMetrics(
            [
                'action' => 'dns_manager_create_record',
                'zone_id' => '12',
                'record' => ['type' => 'A', 'name' => 'www', 'content' => '1.2.3.4'],
            ],
            [
                'zonesById' => [12 => (object) ['id' => 12, 'clientid' => 5, 'name' => 'zone.com']],
            ]
        );

        $this->assertSame(1, $result['localApiCalls']);
        $this->assertStringNotContainsString('Zone not found', $result['output']);
    }

    public function testRemoveZoneStillRequiresUserEmail(): void
    {
        $result = $this->runApiActionWithLocalApiMetrics(
            [
                'action' => 'dns_manager_remove_zone',
                'zone_id' => '15',
            ],
            [
                'zonesById' => [15 => (object) ['id' => 15, 'clientid' => 5, 'name' => 'zone.com']],
            ]
        );

        $this->assertSame(0, $result['localApiCalls']);
        $this->assertStringContainsString("Parameter 'user_email' is required", $result['output']);
    }

    public function testRemoveZoneReturns404WhenZoneBelongsToAnotherClient(): void
    {
        $result = $this->runApiActionWithLocalApiMetrics(
            [
                'action' => 'dns_manager_remove_zone',
                'zone_id' => '15',
                'user_email' => 'owner@example.com',
            ],
            [
                'zonesById' => [15 => (object) ['id' => 15, 'clientid' => 5, 'name' => 'zone.com']],
                'clientsByEmail' => ['owner@example.com' => (object) ['id' => 1, 'email' => 'owner@example.com']],
            ]
        );

        $this->assertSame(0, $result['localApiCalls']);
        $this->assertStringContainsString('Zone not found', $result['output']);
    }

    public function testRemoveZoneCallsLocalApiWhenZoneMatchesClient(): void
    {
        $result = $this->runApiActionWithLocalApiMetrics(
            [
                'action' => 'dns_manager_remove_zone',
                'zone_id' => '15',
                'user_email' => 'owner@example.com',
            ],
            [
                'zonesById' => [15 => (object) ['id' => 15, 'clientid' => 1, 'name' => 'zone.com']],
                'clientsByEmail' => ['owner@example.com' => (object) ['id' => 1, 'email' => 'owner@example.com']],
            ]
        );

        $this->assertSame(1, $result['localApiCalls']);
        $this->assertStringNotContainsString('Zone not found', $result['output']);
    }

    public function testInvalidUserEmailReturns422(): void
    {
        $output = $this->runApiAction(
            [
                'action' => 'dns_manager_list_records',
                'zone_id' => '10',
                'user_email' => 'not-an-email',
            ],
            [
                'zonesById' => [10 => (object) ['id' => 10, 'clientid' => 1, 'name' => 'example.com']],
            ]
        );

        $this->assertStringContainsString("Invalid value for parameter 'user_email'", $output);
    }

    public function testUnknownClientEmailReturns404(): void
    {
        $output = $this->runApiAction(
            [
                'action' => 'dns_manager_list_records',
                'zone_id' => '10',
                'user_email' => 'nobody@example.com',
            ],
            [
                'zonesById' => [10 => (object) ['id' => 10, 'clientid' => 1, 'name' => 'example.com']],
            ]
        );

        $this->assertStringContainsString('Client not found', $output);
    }

    public function testListRecordsReturns404WhenZoneBelongsToAnotherClient(): void
    {
        $output = $this->runApiAction(
            [
                'action' => 'dns_manager_list_records',
                'zone_id' => '10',
                'user_email' => 'owner@example.com',
            ],
            [
                'zonesById' => [10 => (object) ['id' => 10, 'clientid' => 99, 'name' => 'example.com']],
                'clientsByEmail' => ['owner@example.com' => (object) ['id' => 1, 'email' => 'owner@example.com']],
            ]
        );

        $this->assertStringContainsString('Zone not found', $output);
        $this->assertStringNotContainsString('"zone": []', $output);
    }

    public function testListRecordsCallsLocalApiWhenZoneMatchesClient(): void
    {
        $output = $this->runApiAction(
            [
                'action' => 'dns_manager_list_records',
                'zone_id' => '10',
                'user_email' => 'owner@example.com',
            ],
            [
                'zonesById' => [10 => (object) ['id' => 10, 'clientid' => 1, 'name' => 'example.com']],
                'clientsByEmail' => ['owner@example.com' => (object) ['id' => 1, 'email' => 'owner@example.com']],
            ]
        );

        $this->assertStringContainsString('"result": "success"', $output);
        $this->assertStringContainsString('"zone": []', $output);
    }

    public function testGetZoneByIdReturns404WhenZoneBelongsToAnotherClient(): void
    {
        $output = $this->runApiAction(
            [
                'action' => 'dns_manager_get_zone',
                'zone_id' => '11',
                'user_email' => 'owner@example.com',
            ],
            [
                'zonesById' => [11 => (object) ['id' => 11, 'clientid' => 2, 'name' => 'other.com']],
                'clientsByEmail' => ['owner@example.com' => (object) ['id' => 1, 'email' => 'owner@example.com']],
            ]
        );

        $this->assertStringContainsString('Zone not found', $output);
    }

    public function testGetZoneByIdDoesNotInvokeDnsManagerWhenOwnershipFails(): void
    {
        $result = $this->runApiActionWithLocalApiMetrics(
            [
                'action' => 'dns_manager_get_zone',
                'zone_id' => '11',
                'user_email' => 'owner@example.com',
            ],
            [
                'zonesById' => [11 => (object) ['id' => 11, 'clientid' => 2, 'name' => 'other.com']],
                'clientsByEmail' => ['owner@example.com' => (object) ['id' => 1, 'email' => 'owner@example.com']],
            ]
        );

        $this->assertSame(0, $result['localApiCalls']);
        $this->assertStringContainsString('Zone not found', $result['output']);
    }

    public function testCreateRecordDoesNotCallLocalApiWhenOwnershipFails(): void
    {
        $output = $this->runApiAction(
            [
                'action' => 'dns_manager_create_record',
                'zone_id' => '12',
                'user_email' => 'owner@example.com',
                'record' => ['type' => 'A', 'name' => 'www', 'content' => '1.2.3.4'],
            ],
            [
                'zonesById' => [12 => (object) ['id' => 12, 'clientid' => 5, 'name' => 'zone.com']],
                'clientsByEmail' => ['owner@example.com' => (object) ['id' => 1, 'email' => 'owner@example.com']],
            ]
        );

        $this->assertStringContainsString('Zone not found', $output);
        $this->assertStringNotContainsString('"zone": []', $output);
    }

    public function testRemoveRecordDoesNotCallLocalApiWhenOwnershipFails(): void
    {
        $result = $this->runApiActionWithLocalApiMetrics(
            [
                'action' => 'dns_manager_remove_record',
                'zone_id' => '13',
                'user_email' => 'owner@example.com',
                'record' => ['type' => 'A', 'name' => 'www', 'content' => '1.2.3.4'],
            ],
            [
                'zonesById' => [13 => (object) ['id' => 13, 'clientid' => 8, 'name' => 'stolen.com']],
                'clientsByEmail' => ['owner@example.com' => (object) ['id' => 1, 'email' => 'owner@example.com']],
            ]
        );

        $this->assertSame(0, $result['localApiCalls']);
        $this->assertStringContainsString('Zone not found', $result['output']);
        $this->assertStringNotContainsString('"zone": []', $result['output']);
    }

    public function testUpdateRecordDoesNotCallLocalApiWhenOwnershipFails(): void
    {
        $result = $this->runApiActionWithLocalApiMetrics(
            [
                'action' => 'dns_manager_update_record',
                'zone_id' => '14',
                'user_email' => 'owner@example.com',
                'record' => ['type' => 'A', 'name' => 'www', 'content' => '5.6.7.8'],
            ],
            [
                'zonesById' => [14 => (object) ['id' => 14, 'clientid' => 8, 'name' => 'stolen.com']],
                'clientsByEmail' => ['owner@example.com' => (object) ['id' => 1, 'email' => 'owner@example.com']],
            ]
        );

        $this->assertSame(0, $result['localApiCalls']);
        $this->assertStringContainsString('Zone not found', $result['output']);
        $this->assertStringNotContainsString('"zone": []', $result['output']);
    }

    /**
     * @param array<string, mixed> $request
     * @param array{zonesById?: array<int, object>, clientsByEmail?: array<string, object>} $fixture
     * @return array{output: string, localApiCalls: int}
     */
    private function runApiActionWithLocalApiMetrics(array $request, array $fixture = []): array
    {
        $projectRoot = dirname(__DIR__, 2);
        $initPath = $projectRoot . '/init.php';
        $createdInit = false;
        if (!is_file($initPath)) {
            file_put_contents($initPath, '<?php if (!defined(\'ROOTDIR\')) { define(\'ROOTDIR\', __DIR__); }');
            $createdInit = true;
        }

        $metricsFile = tempnam(sys_get_temp_dir(), 'pa_dns_metrics_');
        $this->assertIsString($metricsFile);

        $bootstrap = dirname(__DIR__) . '/bootstrap.php';
        $localApiStub = dirname(__DIR__) . '/support/LocalApiStub.php';
        $dnsFixture = dirname(__DIR__) . '/support/DnsZoneTestFixture.php';
        $dnsModelStub = dirname(__DIR__) . '/support/DnsManagerModelStub.php';
        $apiPath = $projectRoot . '/modules/addons/panelalpha/api.php';
        $zonesById = $fixture['zonesById'] ?? [];
        $clientsByEmail = $fixture['clientsByEmail'] ?? [];
        $code = sprintf(
            'require %s; require %s; require %s; require %s; \LocalApiTestFixture::reset(); \DnsZoneTestFixture::reset(); \DnsZoneTestFixture::$zonesById = %s; \DnsZoneTestFixture::$clientsByEmail = %s; \WHMCS\Module\Addon\PanelAlpha\Helper::$config = [\'api_token\' => \'t\', \'allow_from\' => \'*\']; $_SERVER[\'REQUEST_METHOD\'] = \'POST\'; $_SERVER[\'REMOTE_ADDR\'] = \'127.0.0.1\'; $_SERVER[\'HTTP_API_TOKEN\'] = \'t\'; $_REQUEST = %s;'
            . '$metricsFile = %s;'
            . 'register_shutdown_function(static function () use ($metricsFile): void {'
            . 'file_put_contents($metricsFile, (string) count(\LocalApiTestFixture::$calls));'
            . '});'
            . 'include %s;',
            var_export($bootstrap, true),
            var_export($localApiStub, true),
            var_export($dnsFixture, true),
            var_export($dnsModelStub, true),
            var_export($zonesById, true),
            var_export($clientsByEmail, true),
            var_export($request, true),
            var_export($metricsFile, true),
            var_export($apiPath, true)
        );
        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' 2>&1';
        $rawOutput = shell_exec($command);

        if ($createdInit) {
            unlink($initPath);
        }

        $localApiCalls = is_file($metricsFile) ? (int) file_get_contents($metricsFile) : -1;
        if (is_file($metricsFile)) {
            unlink($metricsFile);
        }

        $this->assertIsString($rawOutput);

        return [
            'output' => $rawOutput,
            'localApiCalls' => $localApiCalls,
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @param array{zonesById?: array<int, object>, clientsByEmail?: array<string, object>} $fixture
     */
    private function runApiAction(array $request, array $fixture = []): string
    {
        $projectRoot = dirname(__DIR__, 2);
        $initPath = $projectRoot . '/init.php';
        $createdInit = false;
        if (!is_file($initPath)) {
            file_put_contents($initPath, '<?php if (!defined(\'ROOTDIR\')) { define(\'ROOTDIR\', __DIR__); }');
            $createdInit = true;
        }

        $bootstrap = dirname(__DIR__) . '/bootstrap.php';
        $localApiStub = dirname(__DIR__) . '/support/LocalApiStub.php';
        $dnsFixture = dirname(__DIR__) . '/support/DnsZoneTestFixture.php';
        $dnsModelStub = dirname(__DIR__) . '/support/DnsManagerModelStub.php';
        $apiPath = $projectRoot . '/modules/addons/panelalpha/api.php';
        $zonesById = $fixture['zonesById'] ?? [];
        $clientsByEmail = $fixture['clientsByEmail'] ?? [];
        $code = sprintf(
            'require %s; require %s; require %s; require %s; \LocalApiTestFixture::reset(); \DnsZoneTestFixture::reset(); \DnsZoneTestFixture::$zonesById = %s; \DnsZoneTestFixture::$clientsByEmail = %s; \WHMCS\Module\Addon\PanelAlpha\Helper::$config = [\'api_token\' => \'t\', \'allow_from\' => \'*\']; $_SERVER[\'REQUEST_METHOD\'] = \'POST\'; $_SERVER[\'REMOTE_ADDR\'] = \'127.0.0.1\'; $_SERVER[\'HTTP_API_TOKEN\'] = \'t\'; $_REQUEST = %s; include %s;',
            var_export($bootstrap, true),
            var_export($localApiStub, true),
            var_export($dnsFixture, true),
            var_export($dnsModelStub, true),
            var_export($zonesById, true),
            var_export($clientsByEmail, true),
            var_export($request, true),
            var_export($apiPath, true)
        );
        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' 2>&1';
        $output = shell_exec($command);

        if ($createdInit) {
            unlink($initPath);
        }

        return is_string($output) ? $output : '';
    }
}
