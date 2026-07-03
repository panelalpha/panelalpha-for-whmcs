<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\PanelAlpha\Lang;

class LangTest extends TestCase
{
    private ?string $tempDir = null;
    private ?string $moduleOverrideFile = null;
    private ?string $previousModuleOverride = null;

    protected function tearDown(): void
    {
        if ($this->moduleOverrideFile !== null) {
            if ($this->previousModuleOverride === null) {
                if (file_exists($this->moduleOverrideFile)) {
                    unlink($this->moduleOverrideFile);
                }

                $overrideDir = dirname($this->moduleOverrideFile);
                if (is_dir($overrideDir) && count(scandir($overrideDir)) === 2) {
                    rmdir($overrideDir);
                }
            } else {
                file_put_contents($this->moduleOverrideFile, $this->previousModuleOverride);
            }

            $this->moduleOverrideFile = null;
            $this->previousModuleOverride = null;
        }

        if ($this->tempDir !== null) {
            $this->removeDirectory($this->tempDir);
            $this->tempDir = null;
        }

        unset($_SESSION['Language'], $_LANG);
        parent::tearDown();
    }

    public function testGetLangDefault(): void
    {
        global $CONFIG;
        $CONFIG['Language'] = 'english';

        $lang = Lang::getLang();

        $this->assertIsArray($lang);
        $this->assertArrayHasKey('aa', $lang);
    }

    public function testGetLangSession(): void
    {
        $_SESSION['Language'] = 'english';

        $lang = Lang::getLang();

        $this->assertIsArray($lang);
        unset($_SESSION['Language']);
    }

    public function testModuleOverrideIsApplied(): void
    {
        global $CONFIG;
        $CONFIG['Language'] = 'english';

        $overrideDir = dirname(__DIR__, 2) . '/modules/servers/panelalpha/lang/overrides';
        if (!is_dir($overrideDir)) {
            mkdir($overrideDir, 0777, true);
        }

        $this->moduleOverrideFile = $overrideDir . '/english.php';
        $this->previousModuleOverride = file_exists($this->moduleOverrideFile)
            ? file_get_contents($this->moduleOverrideFile)
            : null;

        file_put_contents($this->moduleOverrideFile, <<<'PHP'
<?php
$_LANG['aa']['product']['module_settings']['mode']['basic'] = 'Module Override';
PHP
        );

        unset($_LANG);
        $lang = Lang::getLang();

        $this->assertSame('Module Override', $lang['aa']['product']['module_settings']['mode']['basic']);
    }

    public function testGlobalOverrideIsApplied(): void
    {
        if (defined('ROOTDIR')) {
            $this->markTestSkipped('ROOTDIR is already defined.');
        }

        global $CONFIG;
        $CONFIG['Language'] = 'english';

        $this->tempDir = sys_get_temp_dir() . '/panelalpha-lang-test-' . uniqid();
        $overrideDir = $this->tempDir . '/lang/overrides';
        mkdir($overrideDir, 0777, true);

        file_put_contents($overrideDir . '/english.php', <<<'PHP'
<?php
$_LANG['aa']['product']['module_settings']['mode']['basic'] = 'Custom Basic Mode';
PHP
        );

        $this->assertTrue(define('ROOTDIR', $this->tempDir));

        unset($_LANG);
        $lang = Lang::getLang();

        $this->assertSame('Custom Basic Mode', $lang['aa']['product']['module_settings']['mode']['basic']);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
