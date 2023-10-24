<?php

namespace WHMCS\Module\Server\PanelAlpha;

class Lang
{
    public static function getLang(){

        $language_dir = __DIR__ .'/lang/';
        $language_dir = str_replace('/lib', '', $language_dir);
        global $CONFIG;
        $language     = $_SESSION['Language'] ?? $CONFIG['Language'];
        $languageFile = file_exists($language_dir.$language.'.php') ? $language : 'english';

        if (file_exists(dirname(__FILE__).'/lang/english.php'))
        {
            include __DIR__ .'/lang/english.php';
        }

        require $language_dir.$languageFile.'.php';
        return $_LANG ?? [];
    }
}
