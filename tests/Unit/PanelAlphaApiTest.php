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
        $requestMock->shouldReceive('call')
            ->with('GET', 'plans?per_page=100')
            ->once()
            ->andReturn($expectedPlans);

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
        $email = 'test@example.com';

        $expectedUser = ['id' => 1, 'email' => $email];

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('setAction')->with('getUser')->once();
        $requestMock->shouldReceive('call')
            ->with('GET', 'users/email?email=' . $email)
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
