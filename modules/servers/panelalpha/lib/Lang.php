<?php

namespace WHMCS\Module\Server\PanelAlpha;

class Lang
{
    public static function getLang(): array
    {
        $languageDir = dirname(__DIR__) . '/lang/';

        global $CONFIG;
        $language = $_SESSION['Language'] ?? $CONFIG['Language'];
        $languageFile = file_exists($languageDir . $language . '.php') ? $language : 'english';

        if ($languageFile !== 'english' && file_exists($languageDir . 'english.php')) {
            require $languageDir . 'english.php';
        }

        require $languageDir . $languageFile . '.php';

        self::loadOverrideFile(self::getGlobalOverridePath($languageFile));
        self::loadOverrideFile($languageDir . 'overrides/' . $languageFile . '.php');

        return $_LANG ?? [];
    }

    private static function getGlobalOverridePath(string $languageFile): string
    {
        if (!defined('ROOTDIR')) {
            return '';
        }

        return ROOTDIR . '/lang/overrides/' . $languageFile . '.php';
    }

    private static function loadOverrideFile(string $path): void
    {
        if ($path !== '' && file_exists($path)) {
            require $path;
        }
    }
}
