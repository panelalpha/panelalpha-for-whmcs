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

    public function testValidateApiTokenRejectsWrongToken()
    {
        $output = $this->runValidateApiTokenInSubprocess('test_token', 'wrong_token');
        $this->assertStringContainsString('"error": "Unauthorized"', $output);
    }

    public function testValidateApiTokenRejectsDifferentLengthToken()
    {
        $output = $this->runValidateApiTokenInSubprocess('test_token', 'short');
        $this->assertStringContainsString('"error": "Unauthorized"', $output);
    }

    private function runValidateApiTokenInSubprocess(string $configToken, string $requestToken): string
    {
        $bootstrap = dirname(__DIR__) . '/bootstrap.php';
        $code = sprintf(
            'require %s; \WHMCS\Module\Addon\PanelAlpha\Helper::$config[\'api_token\'] = %s; $_SERVER[\'HTTP_API_TOKEN\'] = %s; \WHMCS\Module\Addon\PanelAlpha\Helper::validateApiToken();',
            var_export($bootstrap, true),
            var_export($configToken, true),
            var_export($requestToken, true)
        );
        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' 2>&1';
        $output = shell_exec($command);

        return is_string($output) ? $output : '';
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
