<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Addon\PanelAlpha\Helper;

class CancelServiceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/support/LocalApiStub.php';
    }

    protected function setUp(): void
    {
        \LocalApiTestFixture::reset();
    }

    protected function tearDown(): void
    {
        \CapsuleTestFixture::$selectHandler = null;
        parent::tearDown();
    }

    /**
     * @param array<int, object> $rows
     */
    private function stubRows(array $rows, ?string &$sql = null): void
    {
        \CapsuleTestFixture::$selectHandler = function (string $query, array $bindings) use ($rows, &$sql) {
            $sql = $query;

            return $rows;
        };
    }

    public function testServiceWithoutServerIsFoundWhenNoHostnameGiven(): void
    {
        $sql = null;
        $this->stubRows([(object) ['id' => 7, 'server_hostname' => null]], $sql);

        Helper::cancelService(100, 'reason');

        $this->assertStringContainsString('LEFT JOIN `tblservers`', $sql);
        $this->assertCount(1, \LocalApiTestFixture::$calls);
        $this->assertSame(7, \LocalApiTestFixture::$calls[0]['data']['serviceid']);
    }

    public function testHostnameMatchesStoredHostnameWithSchemeAndSlash(): void
    {
        $this->stubRows([(object) ['id' => 7, 'server_hostname' => 'https://Panel.Example.com/']]);

        Helper::cancelService(100, 'reason', 'panel.example.com');

        $this->assertCount(1, \LocalApiTestFixture::$calls);
        $this->assertSame(7, \LocalApiTestFixture::$calls[0]['data']['serviceid']);
    }

    public function testHostnameMatchesStoredHostnameWithPath(): void
    {
        $this->stubRows([(object) ['id' => 7, 'server_hostname' => 'panel.example.com/admin']]);

        Helper::cancelService(100, 'reason', 'https://panel.example.com');

        $this->assertCount(1, \LocalApiTestFixture::$calls);
    }

    public function testNonMatchingHostnameThrowsServiceNotFound(): void
    {
        $this->stubRows([(object) ['id' => 7, 'server_hostname' => 'https://panel.example.com']]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service not found');

        try {
            Helper::cancelService(100, 'reason', 'other.example.com');
        } finally {
            $this->assertSame([], \LocalApiTestFixture::$calls);
        }
    }

    public function testServiceWithoutServerDoesNotMatchHostnameFilter(): void
    {
        $this->stubRows([(object) ['id' => 7, 'server_hostname' => null]]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service not found');

        Helper::cancelService(100, 'reason', 'panel.example.com');
    }
}
