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

        $globalOverridePath = self::getGlobalOverridePath($languageFile);
        if ($globalOverridePath !== '' && file_exists($globalOverridePath)) {
            require $globalOverridePath;
        }

        $moduleOverridePath = $languageDir . 'overrides/' . $languageFile . '.php';
        if (file_exists($moduleOverridePath)) {
            require $moduleOverridePath;
        }

        return $_LANG ?? [];
    }

    private static function getGlobalOverridePath(string $languageFile): string
    {
        if (!defined('ROOTDIR')) {
            return '';
        }

        return ROOTDIR . '/lang/overrides/' . $languageFile . '.php';
    }
}
