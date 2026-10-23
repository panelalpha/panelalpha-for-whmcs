<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AccountLinkTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../bootstrap.php';
        require_once __DIR__ . '/../../modules/servers/panelalpha/lib/AccountLink.php';
    }

    public function testTrialOwnerMatchesWhenEmailsEqualCaseInsensitive(): void
    {
        $this->assertTrue(panelalpha_trial_owner_matches('User@Example.com', 'user@example.com'));
    }

    public function testTrialOwnerMismatch(): void
    {
        $this->assertFalse(panelalpha_trial_owner_matches('a@example.com', 'b@example.com'));
    }

    public function testTrialOwnerMissingOrEmptyEmail(): void
    {
        $this->assertFalse(panelalpha_trial_owner_matches(null, 'user@example.com'));
        $this->assertFalse(panelalpha_trial_owner_matches('user@example.com', null));
        $this->assertFalse(panelalpha_trial_owner_matches('', 'user@example.com'));
        $this->assertFalse(panelalpha_trial_owner_matches('user@example.com', ''));
    }

    public function testLinkedPanelUserIdWithNoStoredIds(): void
    {
        $this->assertNull(panelalpha_linked_panel_user_id([]));
        $this->assertNull(panelalpha_linked_panel_user_id(['', null, '0', '-1', 'abc']));
    }

    public function testLinkedPanelUserIdWithDuplicateIds(): void
    {
        $this->assertSame(9, panelalpha_linked_panel_user_id([9, 9, '9']));
    }

    public function testLinkedPanelUserIdWithConflictingIds(): void
    {
        $this->assertNull(panelalpha_linked_panel_user_id([9, 4]));
    }

    public function testClientPanelUserIdsReturnsEmptyWithoutServerId(): void
    {
        $this->assertSame([], panelalpha_client_panel_user_ids(10, 100, 0));
    }
}
