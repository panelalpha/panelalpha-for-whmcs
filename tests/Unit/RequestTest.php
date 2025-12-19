<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi\Request;
use ReflectionClass;

class RequestTest extends TestCase
{
    public function testConstructorSetsPropertiesCorrectly()
    {
        $params = [
            'serverhttpprefix' => 'https',
            'serverhostname' => 'example.com',
            'serverport' => '8080',
            'serversecure' => true,
            'serveraccesshash' => 'mytoken'
        ];

        $request = new Request($params);
        $reflection = new ReflectionClass($request);

        $protocol = $reflection->getProperty('protocol');
        $protocol->setAccessible(true);
        $this->assertEquals('https', $protocol->getValue($request));

        $hostname = $reflection->getProperty('hostname');
        $hostname->setAccessible(true);
        $this->assertEquals('example.com', $hostname->getValue($request));

        $port = $reflection->getProperty('port');
        $port->setAccessible(true);
        $this->assertEquals('8080', $port->getValue($request));

        $secureMode = $reflection->getProperty('secureMode');
        $secureMode->setAccessible(true);
        $this->assertTrue($secureMode->getValue($request));

        $token = $reflection->getProperty('token');
        $token->setAccessible(true);
        $this->assertEquals('mytoken', $token->getValue($request));
    }

    public function testConstructorDefaults()
    {
        $params = [
            'hostname' => 'example.com',
            'accesshash' => 'mytoken'
        ];

        $request = new Request($params);
        $reflection = new ReflectionClass($request);

        $port = $reflection->getProperty('port');
        $port->setAccessible(true);
        $this->assertEquals('8443', $port->getValue($request));

        $secureMode = $reflection->getProperty('secureMode');
        $secureMode->setAccessible(true);
        $this->assertTrue($secureMode->getValue($request));
    }

    public function testConstructorCanDisableSecureMode()
    {
        $params = [
            'hostname' => 'example.com',
            'accesshash' => 'mytoken',
            'serversecure' => 0
        ];

        $request = new Request($params);
        $reflection = new ReflectionClass($request);

        $secureMode = $reflection->getProperty('secureMode');
        $secureMode->setAccessible(true);
        $this->assertFalse($secureMode->getValue($request));
    }
}
