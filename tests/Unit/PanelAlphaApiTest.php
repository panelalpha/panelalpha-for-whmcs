<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;
use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi\Request;
use Mockery;
use ReflectionClass;

class PanelAlphaApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testTestConnectionSuccess()
    {
        $params = ['serverhostname' => 'example.com', 'serveraccesshash' => 'token'];
        $api = new PanelAlphaApi($params);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('setAction')->with('testConnection')->once();
        $requestMock->curl = Mockery::mock(); // Mock public property curl
        $requestMock->curl->shouldReceive('setTimeout')->with(5);
        
        $requestMock->shouldReceive('call')
            ->with('GET', 'instances')
            ->once()
            ->andReturn(['data' => ['some' => 'data']]);

        // Inject Request Mock using Reflection
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($api, $requestMock);

        $api->testConnection();
        
        // If no exception is thrown, test passes
        $this->assertTrue(true);
    }

    public function testTestConnectionFailure()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test Connection Failed');

        $params = ['serverhostname' => 'example.com', 'serveraccesshash' => 'token'];
        $api = new PanelAlphaApi($params);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('setAction');
        $requestMock->curl = Mockery::mock();
        $requestMock->curl->shouldReceive('setTimeout');
        
        $requestMock->shouldReceive('call')
            ->with('GET', 'instances')
            ->andReturn(null); // Null result to trigger exception

        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($api, $requestMock);

        $api->testConnection();
    }

    public function testGetPlans()
    {
        $params = ['serverhostname' => 'example.com', 'serveraccesshash' => 'token'];
        $api = new PanelAlphaApi($params);

        $expectedPlans = [['id' => 1, 'name' => 'Plan A']];

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('setAction')->with('getPlans')->once();
        $requestMock->shouldReceive('callListResponse')
            ->with('GET', 'plans?per_page=100&page=1')
            ->once()
            ->andReturn([
                'data' => $expectedPlans,
                'meta' => ['last_page' => 1],
            ]);

        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($api, $requestMock);

        $plans = $api->getPlans();
        $this->assertEquals($expectedPlans, $plans);
    }

    public function testGetUser()
    {
        $params = ['serverhostname' => 'example.com', 'serveraccesshash' => 'token'];
        $api = new PanelAlphaApi($params);

        $cases = [
            'test@example.com' => 'users/email?email=test%40example.com',
            'jan+tag@example.com' => 'users/email?email=jan%2Btag%40example.com',
        ];

        foreach ($cases as $email => $expectedEndpoint) {
            $expectedUser = ['id' => 1, 'email' => $email];

            $requestMock = Mockery::mock(Request::class);
            $requestMock->shouldReceive('setAction')->with('getUser')->once();
            $requestMock->shouldReceive('call')
                ->with('GET', $expectedEndpoint)
                ->once()
                ->andReturn($expectedUser);

            $reflection = new ReflectionClass($api);
            $property = $reflection->getProperty('request');
            $property->setAccessible(true);
            $property->setValue($api, $requestMock);

            $user = $api->getUser($email);
            $this->assertEquals($expectedUser, $user);
        }
    }

    public function testGetServicesStopsAfterSingleRequestWhenFirstPageIsPartialAndMetaHasNoLastPage(): void
    {
        $params = ['serverhostname' => 'example.com', 'serveraccesshash' => 'token'];
        $api = new PanelAlphaApi($params);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('setAction')->with('getServices')->once();
        $requestMock->shouldReceive('callListResponse')
            ->with('GET', 'services?per_page=100&page=1')
            ->once()
            ->andReturn([
                'data' => [
                    ['id' => 7],
                    ['id' => 8],
                ],
            ]);

        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($api, $requestMock);

        $services = $api->getServices();

        $this->assertCount(2, $services);
        $this->assertSame(7, $services[0]['id']);
        $this->assertSame(8, $services[1]['id']);
    }

    public function testGetServicesRequestsSecondPageWhenFirstPageIsFullWithoutLastPageMeta(): void
    {
        $params = ['serverhostname' => 'example.com', 'serveraccesshash' => 'token'];
        $api = new PanelAlphaApi($params);

        $page1Data = array_map(static fn (int $id): array => ['id' => $id], range(1, 100));

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('setAction')->with('getServices')->once();
        $requestMock->shouldReceive('callListResponse')
            ->with('GET', 'services?per_page=100&page=1')
            ->once()
            ->andReturn(['data' => $page1Data]);
        $requestMock->shouldReceive('callListResponse')
            ->with('GET', 'services?per_page=100&page=2')
            ->once()
            ->andReturn(['data' => []]);

        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($api, $requestMock);

        $services = $api->getServices();

        $this->assertCount(100, $services);
        $this->assertSame(1, $services[0]['id']);
        $this->assertSame(100, $services[99]['id']);
    }

    public function testGetServicesFetchesAllPaginatedPages(): void
    {
        $params = ['serverhostname' => 'example.com', 'serveraccesshash' => 'token'];
        $api = new PanelAlphaApi($params);

        $page1Data = array_map(static fn (int $id): array => ['id' => $id], range(1, 100));
        $page2Data = [['id' => 101]];

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('setAction')->with('getServices')->once();
        $requestMock->shouldReceive('callListResponse')
            ->with('GET', 'services?per_page=100&page=1')
            ->once()
            ->andReturn([
                'data' => $page1Data,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 2,
                    'per_page' => 100,
                    'total' => 101,
                ],
            ]);
        $requestMock->shouldReceive('callListResponse')
            ->with('GET', 'services?per_page=100&page=2')
            ->once()
            ->andReturn([
                'data' => $page2Data,
                'meta' => [
                    'current_page' => 2,
                    'last_page' => 2,
                    'per_page' => 100,
                    'total' => 101,
                ],
            ]);

        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($api, $requestMock);

        $services = $api->getServices();

        $this->assertCount(101, $services);
        $this->assertSame(1, $services[0]['id']);
        $this->assertSame(100, $services[99]['id']);
        $this->assertSame(101, $services[100]['id']);
    }

    public function testGetServicesStatsRethrowsOnFailure(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API unavailable');

        $params = ['serverhostname' => 'example.com', 'serveraccesshash' => 'token'];
        $api = new PanelAlphaApi($params);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('setAction')->with('getServicesStats')->once();
        $requestMock->shouldReceive('call')
            ->with('GET', 'services/stats')
            ->once()
            ->andThrow(new \Exception('API unavailable'));

        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($api, $requestMock);

        $api->getServicesStats();
    }

    public function testCreateServiceUsesLongerTimeoutAndRestoresIt()
    {
        $api = new PanelAlphaApi(['serverhostname' => 'example.com', 'serveraccesshash' => 'token']);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->curl = new \WHMCS\Module\Server\PanelAlpha\Curl();
        $requestMock->shouldReceive('setAction')->with('createService')->once();
        $requestMock->shouldReceive('call')
            ->once()
            ->andReturnUsing(function () use ($requestMock) {
                $this->assertSame(180, $requestMock->curl->getRequestTimeout());
                return ['id' => 1];
            });

        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($api, $requestMock);

        $this->assertSame(['id' => 1], $api->createService(1, 2, null, null, null));
        $this->assertSame(60, $requestMock->curl->getRequestTimeout());
    }
}
