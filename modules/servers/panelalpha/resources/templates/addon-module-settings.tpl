<style>
    .plan-settings {
        display: inline-block;
        width: 120px;
        font-weight: bold;
    }

    .package-settings {
        display: inline-block;
        width: 220px;
        font-weight: bold;
    }

    .subtitle {
        font-size: 12px;
        color: gray;
    }

    .version {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        padding-right: 8px;
        font-size: 12px;
        color: gray;
    }
</style>

<script type="text/javascript">
	$(document).ajaxStop(function () {
		const selectPackage = $('#select-package');
		selectPackage.on('change', function () {
			let selectedOption = $(this).find('option:selected');
			$('#plugin_automation').html(selectedOption.data('plugin_automation'));
			$('#theme_automation').html(selectedOption.data('theme_automation'));
			$('#plugins').html(selectedOption.data('plugins'));
			$('#themes').html(selectedOption.data('themes'));
		});
	});
</script>

<table class="form module-settings" width="100%" border="0" cellspacing="2" cellpadding="3" id="tblModuleSettings">
    <tbody>
    <tr>
        <td class="fieldlabel" width="20%">Package</td>
        <td class="fieldarea" style="position: relative;">
            <select id="select-package" name="panelalpha-package" class="form-control select-inline">
                {foreach $packages as $package}
                    <option value="{$package['id']}"
                            data-plugin_automation="{$LANG['aa']['addon']['module_settings']['plugin_automation'][{$package['plugin_automation']}]}"
                            data-theme_automation="{$LANG['aa']['addon']['module_settings']['theme_automation'][{$package['theme_automation']}]}"
                            data-plugins="{$package['pluginNames']}"
                            data-themes="{$package['themeNames']}"
                            {if $package['id'] == $selectedPackage['id']}
                                selected
                            {/if}
                    >{$package['name']}</option>
                {/foreach}
            </select>
            {if $version}
                <p class="version">v{$version}</p>
            {/if}
        </td>
    </tr>
    </tbody>
</table>


<table class="form module-settings" width="100%" border="0" cellspacing="2" cellpadding="3" id="tblModulePlanSettings">
    <tbody>
    <tr>
        <td class="fieldlabel" width="20%">Package Configuration</td>
        <td><span class="package-settings">Plugin automation on assign:</span></td>
        <td width="70%"><span
                    id="plugin_automation">{$LANG['aa']['addon']['module_settings']['plugin_automation'][{$selectedPackage['plugin_automation']}]}</span>
        </td>
    </tr>
    <tr>
        <td class="fieldlabel subtitle">Package configuration from PanelAlpha</td>
        <td><span class="package-settings">Theme automation on assign:</span></td>
        <td width="70%"><span
                    id="theme_automation">{$MGLANG['aa']['addon']['module_settings']['theme_automation'][{{$selectedPackage['theme_automation']}}]}</span>
        </td>
    </tr>
    <tr>
        <td></td>
        <td style="vertical-align: top;"><span class="package-settings">Plugins included:</span></td>
        <td width="70%">
            <span id="plugins">
                {foreach $selectedPackagePlugins as $plugin}
                    {$plugin}
                    <br>
                {foreachelse}
                    <span style="color: gray">No Plugins<span>
                {/foreach}
            </span>
        </td>
    </tr>
    <tr>
        <td></td>
        <td style="vertical-align: top;"><span class="package-settings">Themes included:</span></td>
        <td width="70%">
            <span id="themes">
                {foreach $selectedPackageThemes as $theme}
                    {$theme}
                    <br>
                    {foreachelse}
                    <span style="color: gray">No Themes<span>
                {/foreach}
            </span>
        </td>
    </tr>
    </tbody>
</table>