<?php

namespace WHMCS\Module\Server\PanelAlpha;

/**
 * Loads the module language strings.
 */
class Lang
{
    /**
     * Load the language strings for the current session or system language.
     *
     * English is loaded first so missing keys fall back to it. Overrides from
     * the WHMCS lang/overrides folder and from the module lang/overrides folder
     * are loaded last, in that order.
     *
     * @return array Language strings keyed by name.
     */
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

    /**
     * @param string $languageFile Language file name without extension.
     * @return string Path of the WHMCS override file, or an empty string outside WHMCS.
     */
    private static function getGlobalOverridePath(string $languageFile): string
    {
        if (!defined('ROOTDIR')) {
            return '';
        }

        return ROOTDIR . '/lang/overrides/' . $languageFile . '.php';
    }
}
