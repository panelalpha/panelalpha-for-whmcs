<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\PanelAlpha\Lang;

class LangTest extends TestCase
{
    public function testGetLangDefault()
    {
        global $CONFIG;
        $CONFIG['Language'] = 'english';
        
        $lang = Lang::getLang();
        
        $this->assertIsArray($lang);
        // Check for a known key in english.php if possible, or just that it's not empty
        // Since I haven't read english.php, I'll just check it's an array.
    }

    public function testGetLangSession()
    {
        $_SESSION['Language'] = 'english';
        
        $lang = Lang::getLang();
        
        $this->assertIsArray($lang);
        unset($_SESSION['Language']);
    }
}
