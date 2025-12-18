<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\PanelAlpha\Helper;

class ServerHelperTest extends TestCase
{
    public function testGenerateRandomString()
    {
        $length = 20;
        $string = Helper::generateRandomString($length);
        $this->assertEquals($length, strlen($string));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $string);
    }

    public function testGenerateSecurePassword()
    {
        $length = 16;
        $string = Helper::generateSecurePassword($length);
        $this->assertEquals($length, strlen($string));
        $this->assertMatchesRegularExpression('/[a-z]/', $string);
        $this->assertMatchesRegularExpression('/[A-Z]/', $string);
        $this->assertMatchesRegularExpression('/[0-9]/', $string);
        $this->assertMatchesRegularExpression('/[!@#$%^&*()\\-_=+\\[\\]{};:,.<>?]/', $string);
    }
}
