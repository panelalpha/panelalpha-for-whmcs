<?php

namespace WHMCS\Module\Server\PanelAlpha;

/**
 * Smarty instance that renders the module templates.
 *
 * @method $this assign(string $key, mixed $value)
 * @method string fetch(string $template)
 */
class Smarty extends \Smarty
{
    /**
     * Use the module templates folder and the WHMCS compile folder.
     */
    public function __construct()
    {
        parent::__construct();
        global $templates_compiledir;
        $this->template_dir = __DIR__ . '/../resources/templates';
        $this->compile_dir = $templates_compiledir;
    }
}
