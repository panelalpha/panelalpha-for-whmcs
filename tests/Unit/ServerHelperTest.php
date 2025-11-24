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
}
