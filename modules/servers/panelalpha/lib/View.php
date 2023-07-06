<?php

namespace WHMCS\Module\Server\PanelAlpha;

class View extends \Smarty
{
    public function __construct()
    {
        parent::__construct();
        global $templates_compiledir;
        $this->template_dir = __DIR__ . '/../templates';
        $this->compile_dir  = $templates_compiledir;
    }
}
