<link rel="stylesheet"
      href="{$config['SystemURL']}/modules/servers/panelalpha/resources/css/module-admin.css"/>
<link rel="stylesheet" href="{$config['SystemURL']}/modules/servers/panelalpha/resources/css/icons.css">

<div class="pa-cards-wrap">
    {include file="components/product/product-plan-settings.tpl"}
    {include file="components/product/product-settings.tpl"}
    <p class="version">v{$version}</p>
</div>
{include file="components/product/product-metrics.tpl" usageItems=$usageItems}

<script>
    {literal}
		window.config = window.config || {};
    {/literal}
		window.config.SystemURL = '{$config.SystemURL}';
		window.config.lang = {$LANG|json_encode};
</script>
<script src="{$config.SystemURL}/modules/servers/panelalpha/resources/js/module-admin.js"></script>