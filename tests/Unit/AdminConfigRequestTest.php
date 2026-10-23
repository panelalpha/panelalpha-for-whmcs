<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class AdminConfigRequestTest extends TestCase
{
    private function loadGuard(): void
    {
        require_once __DIR__ . '/../../modules/servers/panelalpha/lib/AdminConfigRequest.php';
    }

    private function stubCheckToken(): void
    {
        if (function_exists('check_token')) {
            return;
        }

        eval('namespace { function check_token($tokenName) { return $GLOBALS[\'panelalpha_test_check_token_result\'] ?? false; } }');
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD'], $GLOBALS['panelalpha_test_check_token_result']);
        parent::tearDown();
    }

    public function testGetRequestReturnsFalseEvenWhenTokenWouldPass(): void
    {
        $this->stubCheckToken();
        $this->loadGuard();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $GLOBALS['panelalpha_test_check_token_result'] = true;

        $this->assertFalse(panelalpha_admin_config_write_allowed());
    }

    public function testPostWithInvalidTokenReturnsFalse(): void
    {
        $this->stubCheckToken();
        $this->loadGuard();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $GLOBALS['panelalpha_test_check_token_result'] = false;

        $this->assertFalse(panelalpha_admin_config_write_allowed());
    }

    public function testPostWithValidTokenReturnsTrue(): void
    {
        $this->stubCheckToken();
        $this->loadGuard();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $GLOBALS['panelalpha_test_check_token_result'] = true;

        $this->assertTrue(panelalpha_admin_config_write_allowed());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testPostWithoutCheckTokenFunctionReturnsFalse(): void
    {
        $this->loadGuard();

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertFalse(panelalpha_admin_config_write_allowed());
    }
}
