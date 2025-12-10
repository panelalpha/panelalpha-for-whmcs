<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Addon\PanelAlpha\Helper;

class HelperTest extends TestCase
{
    public function testGenerateRandomStringDefaultLength()
    {
        $string = Helper::generateRandomString();
        $this->assertEquals(32, strlen($string));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $string);
    }

    public function testGenerateRandomStringCustomLength()
    {
        $length = 16;
        $string = Helper::generateRandomString($length);
        $this->assertEquals($length, strlen($string));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $string);
    }

    public function testValidateApiTokenSuccess()
    {
        Helper::$config['api_token'] = 'test_token';
        $_SERVER['HTTP_API_TOKEN'] = 'test_token';

        // Should not throw exception or exit
        $this->assertNull(Helper::validateApiToken());
    }

    public function testValidateIpAddressSuccess()
    {
        Helper::$config['allow_from'] = '127.0.0.1';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // Should not throw exception or exit
        $this->assertNull(Helper::validateIpAddress());
    }

    public function testValidateIpAddressWildcard()
    {
        Helper::$config['allow_from'] = '*';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        // Should not throw exception or exit
        $this->assertNull(Helper::validateIpAddress());
    }
}
