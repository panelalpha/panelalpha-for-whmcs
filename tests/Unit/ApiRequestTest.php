<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi\Request;
use WHMCS\Module\Server\PanelAlpha\Curl;
use Mockery;

class ApiRequestTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCallSuccess()
    {
        $params = [
            'serverhttpprefix' => 'https',
            'serverhostname' => 'example.com',
            'serverport' => '8443',
            'serveraccesshash' => 'token',
            'serversecure' => true
        ];

        $request = new Request($params);
        
        // Mock Curl
        $curlMock = Mockery::mock(Curl::class);
        $curlMock->shouldReceive('verifySsl')->with(true)->once(); // Secure mode is enforced
        $curlMock->shouldReceive('call')
            ->with('GET', 'https://example.com:8443/api/admin/test', [], Mockery::type('array'))
            ->once()
            ->andReturn('{"data": {"status": "success"}}');
        
        $curlMock->shouldReceive('getLastCall')->andReturn([
            'requestHeaders' => '',
            'request' => '',
            'responseHeaders' => '',
            'response' => '{"data": {"status": "success"}}'
        ]);
        
        $curlMock->shouldReceive('getLastHttpCode')->andReturn(200);

        // Inject Mock
        $request->curl = $curlMock;
        $request->setAction('test');

        $response = $request->call('GET', 'test');
        
        $this->assertEquals(['status' => 'success'], $response);
    }

    public function testCallUnauthenticated()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthenticated');

        $params = [
            'serverhostname' => 'example.com',
            'serveraccesshash' => 'token'
        ];

        $request = new Request($params);
        
        $curlMock = Mockery::mock(Curl::class);
        $curlMock->shouldReceive('verifySsl')->with(true)->once();
        $curlMock->shouldReceive('call')->andReturn('');
        $curlMock->shouldReceive('getLastCall')->andReturn(['requestHeaders'=>'','request'=>'','responseHeaders'=>'','response'=>'']);
        $curlMock->shouldReceive('getLastHttpCode')->andReturn(401);

        $request->curl = $curlMock;
        $request->setAction('test');

        $request->call('GET', 'test');
    }

    public function testCallErrorResponse()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Something went wrong');

        $params = [
            'serverhostname' => 'example.com',
            'serveraccesshash' => 'token'
        ];

        $request = new Request($params);
        
        $curlMock = Mockery::mock(Curl::class);
        $curlMock->shouldReceive('verifySsl')->with(true)->once();
        $curlMock->shouldReceive('call')->andReturn('{"message": "Something went wrong"}');
        $curlMock->shouldReceive('getLastCall')->andReturn([
            'requestHeaders' => '',
            'request' => '',
            'responseHeaders' => '',
            'response' => '{"message": "Something went wrong"}'
        ]);
        $curlMock->shouldReceive('getLastHttpCode')->andReturn(500);

        $request->curl = $curlMock;
        $request->setAction('test');

        $request->call('GET', 'test');
    }
}
