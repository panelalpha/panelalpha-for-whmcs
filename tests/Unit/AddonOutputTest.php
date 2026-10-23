<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AddonOutputTest extends TestCase
{
    private static $panelalphaLoaded = false;

    protected function setUp(): void
    {
        \CapsuleTestFixture::$queue = [
            (object) [
                'id' => 42,
                'userid' => 7,
                'domain' => 'evil.com" onmouseover="alert(1)',
                'domainstatus' => 'Active',
                'productname' => 'WP Host',
                'firstname' => '<img src=x onerror=alert(1)>',
                'lastname' => '',
                'companyname' => '',
            ],
            (object) [
                'id' => 1,
                'userid' => 2,
                'domain' => 'ada.example',
                'domainstatus' => 'Pending',
                'productname' => 'WordPress',
                'firstname' => 'Ada',
                'lastname' => 'Lovelace',
                'companyname' => '',
            ],
        ];

        if (!self::$panelalphaLoaded) {
            require_once __DIR__ . '/../../modules/addons/panelalpha/panelalpha.php';
            self::$panelalphaLoaded = true;
        }
    }

    protected function tearDown(): void
    {
        \CapsuleTestFixture::$queue = null;
    }

    public function testPanelalphaOutputEscapesHostileValuesAndPreservesNormalRows(): void
    {
        ob_start();
        panelalpha_output([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('&lt;img', $output);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $output);

        $this->assertStringContainsString('&quot;', $output);
        $this->assertStringNotContainsString('onmouseover="alert(1)', $output);

        $this->assertStringContainsString('Ada Lovelace', $output);
        $this->assertStringContainsString('ada.example', $output);
        $this->assertStringContainsString('clientsservices.php', $output);
    }
}
