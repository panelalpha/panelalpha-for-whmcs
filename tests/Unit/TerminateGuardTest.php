<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TerminateGuardTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../bootstrap.php';
        require_once __DIR__ . '/../../modules/servers/panelalpha/lib/TerminateGuard.php';
    }

    public function testAdminScriptWithManualTerminationAndUserIdReturnsDelete(): void
    {
        $this->assertSame(
            'delete',
            panelalpha_terminate_action(true, 'clientsservices.php', '42')
        );
    }

    public function testRequestUriStyleScriptNameWithManualTerminationReturnsManualReview(): void
    {
        $this->assertSame(
            'manual_review',
            panelalpha_terminate_action(true, 'clientsservices.php?userid=1&id=2', '42')
        );
    }

    public function testEmptyUserIdReturnsMissingUserEvenOnAdminScript(): void
    {
        $this->assertSame(
            'missing_user',
            panelalpha_terminate_action(true, 'clientsservices.php', '')
        );
        $this->assertSame(
            'missing_user',
            panelalpha_terminate_action(false, 'clientsservices.php', null)
        );
    }
}
