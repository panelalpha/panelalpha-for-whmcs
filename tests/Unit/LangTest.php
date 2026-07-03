<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\PanelAlpha\Lang;

class LangTest extends TestCase
{
    private ?string $tempDir = null;

    protected function tearDown(): void
    {
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

    /**
     * @runInSeparateProcess
     */
    public function testGlobalOverrideIsApplied(): void
    {
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

        define('ROOTDIR', $this->tempDir);

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
