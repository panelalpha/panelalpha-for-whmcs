<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CreateAccountProvisioningTest extends TestCase
{
    /**
     * @param array<string, mixed> $fixtureConfig
     * @param array<string, mixed> $params
     * @return array{result: string, apiCalls: list<array{method: string, args: array}>, customFieldValues: array<int, array<string, string>>}
     */
    private function runCreateAccount(array $fixtureConfig, array $params): array
    {
        $projectRoot = dirname(__DIR__, 2);
        $bootstrap = dirname(__DIR__) . '/bootstrap.php';
        $localApiStub = dirname(__DIR__) . '/support/LocalApiStub.php';
        $fixture = dirname(__DIR__) . '/support/CreateAccountProvisioningFixture.php';
        $stubs = dirname(__DIR__) . '/support/CreateAccountProvisioningStubs.php';
        $panelalpha = $projectRoot . '/modules/servers/panelalpha/panelalpha.php';

        $code = sprintf(
            'require %s; require %s; require %s; require %s; require %s; \CreateAccountProvisioningFixture::reset();'
            . '$cfg = %s; \CreateAccountProvisioningFixture::$trialServicePayload = $cfg[\'trialServicePayload\'] ?? [];'
            . '\CreateAccountProvisioningFixture::$userByIdPayload = $cfg[\'userByIdPayload\'] ?? [];'
            . '\CreateAccountProvisioningFixture::$userByEmailPayload = $cfg[\'userByEmailPayload\'] ?? [];'
            . '\CreateAccountProvisioningFixture::$customFieldValues = $cfg[\'customFieldValues\'] ?? [];'
            . '\CreateAccountProvisioningFixture::$clientPanelalphaServices = $cfg[\'clientPanelalphaServices\'] ?? [];'
            . '$service = new \WHMCS\Module\Server\PanelAlpha\Models\Service();'
            . '$service->id = (int) ($cfg[\'serviceId\'] ?? 100);'
            . '$service->product = new \WHMCS\Module\Server\PanelAlpha\Models\Product();'
            . '$service->serverModel = (object) [\'hostname\' => \'panel.example\'];'
            . '\CreateAccountProvisioningFixture::$service = $service;'
            . '$GLOBALS[\'CONFIG\'] = [\'Language\' => \'english\', \'SystemURL\' => \'https://whmcs.example\'];'
            . '$params = %s;'
            . '$result = panelalpha_CreateAccount($params);'
            . 'echo json_encode(['
            . '\'result\' => $result,'
            . '\'apiCalls\' => \CreateAccountProvisioningFixture::$apiCalls,'
            . '\'customFieldValues\' => \CreateAccountProvisioningFixture::$customFieldValues,'
            . ']);',
            var_export($bootstrap, true),
            var_export($localApiStub, true),
            var_export($fixture, true),
            var_export($stubs, true),
            var_export($panelalpha, true),
            var_export($fixtureConfig, true),
            var_export($params, true)
        );

        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' 2>&1';
        $output = shell_exec($command);
        $this->assertIsString($output);
        if (!preg_match('/(\{.*\})\s*$/s', trim($output), $matches)) {
            $this->fail('Subprocess did not emit JSON payload: ' . $output);
        }
        $decoded = json_decode($matches[1], true);
        $this->assertIsArray($decoded, 'Subprocess output: ' . $output);

        return $decoded;
    }

    /** @param list<array{method: string, args: array}> $apiCalls */
    private function apiMethodCount(array $apiCalls, string $method): int
    {
        $count = 0;
        foreach ($apiCalls as $call) {
            if ($call['method'] === $method) {
                $count++;
            }
        }

        return $count;
    }

    public function testTrialUpgradeChangesPlanAndClearsTrialWhenClientEmailMatches(): void
    {
        $out = $this->runCreateAccount(
            [
                'serviceId' => 200,
                'customFieldValues' => [
                    200 => ['Service ID' => '77'],
                ],
                'trialServicePayload' => [
                    'is_trial' => true,
                    'user_id' => 5,
                    'status' => 'active',
                ],
                'userByIdPayload' => [
                    5 => [
                        'id' => 5,
                        'email' => 'client@example.com',
                        'first_name' => 'Ann',
                        'last_name' => 'Example',
                    ],
                ],
            ],
            [
                'serviceid' => 200,
                'pid' => 1,
                'userid' => 10,
                'domain' => 'example.com',
                'configoption1' => '12',
                'configoptions' => ['sites' => 3],
                'customfields' => [],
                'clientsdetails' => [
                    'email' => 'client@example.com',
                    'firstname' => 'Ann',
                    'lastname' => 'Example',
                    'language' => 'english',
                ],
                'addonId' => null,
            ]
        );

        $this->assertSame('success', $out['result']);
        $this->assertSame(1, $this->apiMethodCount($out['apiCalls'], 'changePlan'));
        $this->assertSame(1, $this->apiMethodCount($out['apiCalls'], 'unsetServiceAsTrial'));
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'createUser'));
        $this->assertSame('5', $out['customFieldValues'][200]['User ID']);
    }

    public function testTrialUpgradeMismatchClearsForgedServiceIdAndStopsWithAdminMessage(): void
    {
        $out = $this->runCreateAccount(
            [
                'serviceId' => 201,
                'customFieldValues' => [
                    201 => ['Service ID' => '88'],
                ],
                'trialServicePayload' => [
                    'is_trial' => true,
                    'user_id' => 6,
                    'status' => 'active',
                ],
                'userByIdPayload' => [
                    6 => [
                        'id' => 6,
                        'email' => 'trial-owner@example.com',
                    ],
                ],
            ],
            [
                'serviceid' => 201,
                'pid' => 1,
                'userid' => 11,
                'domain' => 'shop.example.com',
                'configoption1' => '15',
                'configoptions' => ['sites' => 2],
                'customfields' => [],
                'clientsdetails' => [
                    'email' => 'buyer@example.com',
                    'firstname' => 'Buyer',
                    'lastname' => 'Example',
                    'language' => 'english',
                ],
                'addonId' => null,
            ]
        );

        $this->assertStringContainsString('Trial upgrade refused', $out['result']);
        $this->assertStringContainsString('#88', $out['result']);
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'changePlan'));
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'unsetServiceAsTrial'));
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'createUser'));
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'createService'));
        $this->assertSame('', $out['customFieldValues'][201]['Service ID']);
    }

    public function testProvisioningWithoutExistingLinkOrEmailMatchCreatesNewPanelUser(): void
    {
        $out = $this->runCreateAccount(
            [
                'serviceId' => 202,
                'customFieldValues' => [],
                'clientPanelalphaServices' => [],
            ],
            [
                'serviceid' => 202,
                'pid' => 1,
                'userid' => 12,
                'domain' => 'new.example.com',
                'configoption1' => '9',
                'configoptions' => ['sites' => 1],
                'customfields' => [],
                'clientsdetails' => [
                    'email' => 'newclient@example.com',
                    'firstname' => 'New',
                    'lastname' => 'Client',
                    'language' => 'english',
                ],
                'addonId' => null,
            ]
        );

        $this->assertSame('success', $out['result']);
        $this->assertSame(1, $this->apiMethodCount($out['apiCalls'], 'getUser'));
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'getUserById'));
        $this->assertSame(1, $this->apiMethodCount($out['apiCalls'], 'createUser'));
        $this->assertSame('501', $out['customFieldValues'][202]['User ID']);
        $this->assertSame('9001', $out['customFieldValues'][202]['Service ID']);
    }

    /** @return array<string, mixed> */
    private function createParams(int $serviceId, int $clientId, int $serverId, string $email): array
    {
        return [
            'serviceid' => $serviceId,
            'pid' => 1,
            'userid' => $clientId,
            'serverid' => $serverId,
            'domain' => 'x.example.com',
            'configoption1' => '9',
            'configoptions' => ['sites' => 1],
            'customfields' => [],
            'clientsdetails' => [
                'email' => $email,
                'firstname' => 'A',
                'lastname' => 'B',
                'language' => 'english',
            ],
            'addonId' => null,
        ];
    }

    public function testExistingPanelUserWithSameEmailAndNoLinkFailsProvisioning(): void
    {
        $out = $this->runCreateAccount(
            [
                'serviceId' => 300,
                'userByEmailPayload' => ['same@example.com' => ['id' => 77, 'email' => 'same@example.com']],
            ],
            $this->createParams(300, 20, 4, 'same@example.com')
        );

        $this->assertStringContainsString('user #77', $out['result']);
        $this->assertStringContainsString('already exists', $out['result']);
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'createUser'));
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'createService'));
    }

    public function testNoPanelUserCreatesUser(): void
    {
        $out = $this->runCreateAccount(['serviceId' => 301], $this->createParams(301, 21, 4, 'none@example.com'));

        $this->assertSame('success', $out['result']);
        $this->assertSame(1, $this->apiMethodCount($out['apiCalls'], 'getUser'));
        $this->assertSame(1, $this->apiMethodCount($out['apiCalls'], 'createUser'));
    }

    public function testOwnUserIdFieldIsUsed(): void
    {
        $out = $this->runCreateAccount(
            [
                'serviceId' => 302,
                'customFieldValues' => [302 => ['User ID' => '55']],
                'userByIdPayload' => [55 => ['id' => 55, 'email' => 'own@example.com']],
                'userByEmailPayload' => ['own@example.com' => ['id' => 55, 'email' => 'own@example.com']],
            ],
            $this->createParams(302, 22, 4, 'own@example.com')
        );

        $this->assertSame('success', $out['result']);
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'createUser'));
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'getUser'));
        $this->assertSame('55', $out['customFieldValues'][302]['User ID']);
    }

    public function testOwnUserIdNotFoundOnPanelFailsWithoutCreatingUser(): void
    {
        $out = $this->runCreateAccount(
            ['serviceId' => 303, 'customFieldValues' => [303 => ['User ID' => '999']]],
            $this->createParams(303, 23, 4, 'x@example.com')
        );

        $this->assertStringContainsString('#999', $out['result']);
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'createUser'));
    }

    public function testLinkedUserIdFromServiceOnDifferentServerIsIgnored(): void
    {
        $out = $this->runCreateAccount(
            [
                'serviceId' => 304,
                'clientPanelalphaServices' => [(object) ['id' => 10, 'userid' => 24, 'server' => 9]],
                'customFieldValues' => [10 => ['User ID' => '42']],
                'userByIdPayload' => [42 => ['id' => 42, 'email' => 'other@example.com']],
            ],
            $this->createParams(304, 24, 4, 'new@example.com')
        );

        $this->assertSame('success', $out['result']);
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'getUserById'));
        $this->assertSame(1, $this->apiMethodCount($out['apiCalls'], 'createUser'));
    }

    public function testLinkedUserIdFromServiceOnSameServerIsUsed(): void
    {
        $out = $this->runCreateAccount(
            [
                'serviceId' => 305,
                'clientPanelalphaServices' => [(object) ['id' => 11, 'userid' => 25, 'server' => 4]],
                'customFieldValues' => [11 => ['User ID' => '42']],
                'userByIdPayload' => [42 => ['id' => 42, 'email' => 'same@example.com']],
            ],
            $this->createParams(305, 25, 4, 'same@example.com')
        );

        $this->assertSame('success', $out['result']);
        $this->assertSame(0, $this->apiMethodCount($out['apiCalls'], 'createUser'));
        $this->assertSame('42', $out['customFieldValues'][305]['User ID']);
    }
}
