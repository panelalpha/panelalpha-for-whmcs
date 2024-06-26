<style>
    .plan-settings {
        display: inline-block;
        width: 120px;
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

    .config-field-advanced {
        display: flex;
        align-items: center;
    }

    .config-field-advanced-optional {
        margin-left: 16px;
    }

    .modules-settings-advanced {
        display: flex;
        align-items: center;
        background-color: #f8f8f8;
        color: #333;
        border-radius: 4px;
        height: 44px;
        margin: 4px 12px;
        padding: 0 12px;
    }

    .modules-settings-advanced-label {
        font-weight: normal;
        margin: 0 12px;
        width: 275px;
    }
</style>
<script type="text/javascript">
	$(document).ajaxStop(function () {
		const selectElement = $('#select-plan');
		selectElement.on('change', function () {
			let selectedOption = $(this).find('option:selected');

			$('#instanceLimit').html(selectedOption.data('instance_limit'));
			$('#onboarding-name').html(selectedOption.data('onboarding_name'));
			$('#onboarding-description').attr('data-original-title', selectedOption.data('onboarding_description'));
			$('#onboarding-ask-for-domain').val(selectedOption.data('onboarding_ask_for_domain'))
			$('#server-type').html(selectedOption.data('server_type_name'));
			$('#server-icon').attr('src', '{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/' + selectedOption.data('server_type') + '.svg');
			if (selectedOption.data('server_group')) {
				$('#server-group').html(selectedOption.data('server_group'));
			} else {
				$('#server-group').html("All servers");
			}
			$('#server-assign-rule').html(selectedOption.data('server_assign_rule'));
			$('#assign-rule-description').attr('data-original-title', selectedOption.data('server_assign_rule_description'));

			if (selectedOption.data('dns_server_internal')) {
				$('#dns-server-icon').show();
				$('#dns-server').html(selectedOption.data('server_type_name') + '\'s DNS Server');
				$('#dns-server-icon').attr('src', '{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/' + selectedOption.data('server_type') + '.svg');
			} else if (selectedOption.data('dns_server')) {
				$('#dns-server-icon').show();
				$('#dns-server').html(selectedOption.data('dns_server'));
				$('#dns-server-icon').attr('src', '{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/' + selectedOption.data('dns_server_name') + '.svg');
			} else {
				$('#dns-server').html("None");
				$('#dns-server-icon').hide();
			}

			if (selectedOption.data('email_server_internal')) {
				$('#email-server-icon').show();
				$('#email-server').html(selectedOption.data('server_type_name') + '\'s Email Server');
				$('#email-server-icon').attr('src', '{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/' + selectedOption.data('server_type') + '.svg');
			} else if (selectedOption.data('email_server')) {
				$('#email-server-icon').show();
				$('#email-server').html(selectedOption.data('email_server'));
				$('#email-server-icon').attr('src', '{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/' + selectedOption.data('email_server_name') + '.svg');
			} else {
				$('#email-server').html("None");
				$('#email-server-icon').hide();
			}

			$('.account-config').remove();
			let accountConfig = selectedOption.data('account_config');
			accountConfig = accountConfig.split(',');
			if (accountConfig[0]) {
				accountConfig.forEach(function (config) {
					let fields = config.split(':');

					let transformedText = ""
					if (fields[0] === 'whm_package') {
						transformedText = "WHM Package";
					} else {
						transformedText = fields[0].replace(/_/g, " ").replace(/\b\w/g, function (match) {
							return match.toUpperCase();
						});
					}

					let transformedContent = fields[1].charAt(0).toUpperCase() + fields[1].substring(1);

					const trElement = $('<tr>');
					trElement.addClass('account-config');
					const td1 = $('<td>');
					const td2 = $('<td>');
					const span1 = $('<span>').addClass('plan-settings').attr('id', transformedText).text(transformedText + ':');
					const span2 = $('<span>').attr('id', fields[1]).text(transformedContent);

					td2.append(span1, ' ', span2);
					trElement.append(td1, td2);
					$('#server').after(trElement);
				})
			}
		});

		const onboardingType = $('#onboarding-name');
		$('[name="configoption[6]"]').val(onboardingType.text());

		onboardingType.on("DOMSubtreeModified", function () {
			let onboardingTypeValue = $(this).text();
			$('[name="configoption[6]"]').val(onboardingTypeValue);
		});


		let automaticCheckbox = $('[name="configoption[2]"]');
		let manualTerminationCheckbox = $('[name="configoption[4]"]');
		let ssoCheckbox = $('[name="configoption[5]"]');


		$('#automatic').bootstrapSwitch({
			size: 'small',
			onColor: 'success',
			state: automaticCheckbox.val(),
			onInit: () => {
				this.value = automaticCheckbox.val();
			},
		})


		$('#manual-termination').bootstrapSwitch({
			size: 'small',
			onColor: 'success',
			state: manualTerminationCheckbox.val(),
			onInit: () => {
				this.value = manualTerminationCheckbox.val();
			},
		})
		$('#sso').bootstrapSwitch({
			size: 'small',
			onColor: 'success',
			state: ssoCheckbox.val(),
			onInit: () => {
				this.value = ssoCheckbox.val();
			},
		})

		$('#automatic').on('switchChange.bootstrapSwitch', function (event, state) {
			if (state) {
				automaticCheckbox.val('on');
				$('.config-field-advanced-optional').show();
			} else {
				automaticCheckbox.val('');
				$('.config-field-advanced-optional').hide();
			}
		});
		$('#manual-termination').on('switchChange.bootstrapSwitch', function (event, state) {
			if (state) {
				manualTerminationCheckbox.val('on');
			} else {
				manualTerminationCheckbox.val('');
			}
		});
		$('#sso').on('switchChange.bootstrapSwitch', function (event, state) {
			if (state) {
				ssoCheckbox.val('on');
			} else {
				ssoCheckbox.val('');
			}
		});

		//Automatic Instance Provisioning - Advanced
		if (automaticCheckbox.val() === 'on') {
			$('.config-field-advanced-optional').show();
		} else {
			$('.config-field-advanced-optional').hide();
		}

		let showInstanceNameFieldOnOrderForm = $('[name="configoption[8]"]');
		$('#show-instance-name-on-order-form').bootstrapSwitch({
			size: 'small',
			onColor: 'success',
			state: showInstanceNameFieldOnOrderForm.val(),
			onInit: () => {
				this.value = showInstanceNameFieldOnOrderForm.val()
			}
		})
		$('#show-instance-name-on-order-form').on('switchChange.bootstrapSwitch', function (event, state) {
			if (state) {
				showInstanceNameFieldOnOrderForm.val('on');
			} else {
				showInstanceNameFieldOnOrderForm.val('');
			}
		});
		$('#select-default-theme').select2({
			width: 300,
			placeholder: "Select default instance theme",
			minimumInputLength: 4,
			ajax: {
				url: function (params) {
					return 'https://api.wordpress.org/themes/info/1.1/?action=query_themes&request[search]=' + params.term;
				},
				dataType: 'json',
				global: false,
				processResults: function (data) {
					const results = data.themes.map(item => {
						return {
							text: item.name,
							id: item.slug
						};
					});
					return {
						results: results,
					}
				},
			},
		});


		/**
		 * Usage metric
		 */
		let activeInstancesMetricCheckbox = $('[name="metric[active_instances]"');
		let activeStatus;
		if (activeInstancesMetricCheckbox.val() == 1) {
			activeStatus = ''
		} else {
			activeStatus = 'on'
		}
		$('#metric_active_instances').bootstrapSwitch({
			size: 'small',
			onColor: 'success',
			state: activeStatus,
			onInit: () => {
				this.value = activeStatus
			},
		})
		$('#metric_active_instances').on('switchChange.bootstrapSwitch', function (event, state) {
			if (state) {
				activeInstancesMetricCheckbox.val(0)
			} else {
				activeInstancesMetricCheckbox.val(1)
			}
		});


		let remoteBackupsSizeMetricCheckbox = $('[name="metric[remote_backups_size]"');
		let activeRemoteBackupsSizeStatus;
		if (remoteBackupsSizeMetricCheckbox.val() == 1) {
			activeRemoteBackupsSizeStatus = ''
		} else {
			activeRemoteBackupsSizeStatus = 'on'
		}
		$('#metric_remote_backups_size').bootstrapSwitch({
			size: 'small',
			onColor: 'success',
			state: activeRemoteBackupsSizeStatus,
			onInit: () => {
				this.value = activeRemoteBackupsSizeStatus
			},
		})
		$('#metric_remote_backups_size').on('switchChange.bootstrapSwitch', function (event, state) {
			if (state) {
				remoteBackupsSizeMetricCheckbox.val(0)
			} else {
				remoteBackupsSizeMetricCheckbox.val(1)
			}
		});

		let DiskUsageMetricCheckbox = $('[name="metric[disk_usage]"');
		let diskUsageStatus;
		if (DiskUsageMetricCheckbox.val() == 1) {
			diskUsageStatus = ''
		} else {
			diskUsageStatus = 'on'
		}
		$('#metric_disk_usage').bootstrapSwitch({
			size: 'small',
			onColor: 'success',
			state: diskUsageStatus,
			onInit: () => {
				this.value = diskUsageStatus
			},
		})
		$('#metric_disk_usage').on('switchChange.bootstrapSwitch', function (event, state) {
			if (state) {
				DiskUsageMetricCheckbox.val(0)
			} else {
				DiskUsageMetricCheckbox.val(1)
			}
		});
	});
</script>

<table class="form module-settings" width="100%" border="0" cellspacing="2" cellpadding="3" id="tblModuleSettings">
  <tbody>
  <tr>
    <td class="fieldlabel" width="20%">PanelAlpha Plan</td>
    <td class="fieldarea" style="position: relative;">
      <select id="select-plan" name="configoption[1]" class="form-control select-inline">
          {foreach $plans as $plan}
            <option value="{$plan['id']}"
                    data-instance_limit="{$plan['instance_limit']}"
                    data-onboarding_name="{$MGLANG['aa']['product']['onboarding']['name'][{$plan['config']['onboarding']['method']}]}"
                    data-onboarding_description="{$MGLANG['aa']['product']['onboarding']['description'][{$plan['config']['onboarding']['method']}]}"
                    data-onboarding_ask_for_domain="{$plan['config']['onboarding']['ask_for_domain']}"
                    data-server_type="{$plan['server_type']}"
                    data-server_type_name="{$MGLANG['aa']['product']['server'][{$plan['server_type']}]}"
                    data-server_group="{$plan['server_group_name']}"
                    data-server_assign_rule="{$MGLANG['aa']['product']['assign_rule']['name'][{$plan['server_assign_rule']}]}"
                    data-server_assign_rule_description="{$MGLANG['aa']['product']['assign_rule']['description'][{$plan['server_assign_rule']}]}"
                    data-account_config="{$plan['server_config']}"
                    {if $plan['dns_server_type']}
                      data-dns_server="{$MGLANG['aa']['product']['dns_server'][{$plan['dns_server_type']}]} ({$plan['dns_server_name']})"
                      data-dns_server_name="{$plan['dns_server_type']}"
                    {elseif $plan['dns_server_internal']}
                      data-dns_server_internal="true"
                    {/if}
                    {if $plan['email_server_type']}
                      data-email_server="{$MGLANG['aa']['product']['email_server'][{$plan['email_server_type']}]} ({$plan['email_server_name']})"
                      data-email_server_name="{$plan['email_server_type']}"
                    {elseif $plan['email_server_internal']}
                      data-email_server_internal="true"
                    {/if}
                    {if $plan['id'] == $product->configoption1}
                      selected
                    {/if}
            >{$plan['name']}</option>
          {/foreach}
      </select>
        {if $version}
          <p class="version">v{$version}</p>
        {/if}
      <input type="hidden" name="configoption[6]" value="">
      <input id="onboarding-ask-for-domain" type="hidden" name="configoption[7]"
             value="{$selectedPlan['config']['onboarding']['ask_for_domain']}">
    </td>
  </tr>
  </tbody>
</table>

<table class="form module-settings" width="100%" border="0" cellspacing="2" cellpadding="3" id="tblModulePlanSettings">
  <tbody>
  <tr>
    <td class="fieldlabel" width="20%">Plan Settings</td>
    <td>
      <span class="plan-settings">Instances limit: </span>
      <span id="instanceLimit">{$selectedPlan['instance_limit']}</span>
    </td>
  </tr>
  <tr>
    <td class="fieldlabel subtitle">Plan configuration from PanelAlpha</td>
    <td>
      <span class="plan-settings">Onboarding: </span>
      <span id="onboarding-name">{$MGLANG['aa']['product']['onboarding']['name'][{$selectedPlan['config']['onboarding']['method']}]}</span>
      <span id="onboarding-description" style="margin-left: 4px;" data-toggle="tooltip" data-placement="right"
            title="{$MGLANG['aa']['product']['onboarding']['description'][{$selectedPlan['config']['onboarding']['method']}]}">
                <img src="{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/informationIcon.svg"
                     style="height: 16px;">
            </span>
    </td>
  </tr>
  <tr>
    <td></td>
    <td>
      <span class="plan-settings">Server Type: </span>
      <img id="server-icon" style="padding-bottom: 2px; height: 22px;"
           src="{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/{$selectedPlan['server_type']}.svg">
      <span id="server-type"> {$MGLANG['aa']['product']['server'][{$selectedPlan['server_type']}]}</span>
    </td>
  </tr>

  {if $selectedPlan['server_group_name']}
    <tr>
      <td></td>
      <td>
        <span class="plan-settings">Server Group: </span>
        <span id="server-group">{$selectedPlan['server_group_name']}</span>
        <span> (Assign Rule:
                <span id="server-assign-rule">{$MGLANG['aa']['product']['assign_rule']['name'][{$selectedPlan['server_assign_rule']}]}</span>)
            </span>
        <span id="assign-rule-description" style="margin-left: 4px;" data-toggle="tooltip"
              data-placement="right"
              title="{$MGLANG['aa']['product']['assign_rule']['description'][{$selectedPlan['server_assign_rule']}]}">
                <img src="{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/informationIcon.svg"
                     style="height: 16px;">
            </span>
      </td>
    </tr>
  {else}
    <tr id="server">
      <td></td>
      <td>
        <span class="plan-settings">Server Group: </span>
        <span id="server-group">All Servers</span>
        <span> (Assign Rule: <span
                  id="server-assign-rule">{$MGLANG['aa']['product']['assign_rule']['name'][{$selectedPlan['server_assign_rule']}]}</span>)</span>
        <span id="assign-rule-description" style="margin-left: 4px;" data-toggle="tooltip"
              data-placement="right"
              title="{$MGLANG['aa']['product']['assign_rule']['description'][{$selectedPlan['server_assign_rule']}]}">
                <img src="{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/informationIcon.svg"
                     style="height: 16px;">
            </span>
      </td>
    </tr>
  {/if}

  {foreach $selectedPlan['account_config'] as $key=>$value}
    <tr class="account-config">
      <td></td>
      <td>
          {if $key == 'whm_package'}
            <span class="plan-settings" id="{$key}">WHM Package:</span>
            <span id="{$value}">{$value|capitalize}</span>
          {else}
            <span class="plan-settings" id="{$key}">{$key|replace: '_':' '|capitalize}:</span>
            <span id="{$value}">{$value|capitalize}</span>
          {/if}
      </td>
    </tr>
  {/foreach}

  <tr>
    <td></td>
    <td>
      <span class="plan-settings">DNS Server:</span>
        {if $selectedPlan['dns_server_internal']}
          <img id="dns-server-icon"
               src="{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/{$selectedPlan['server_type']}.svg"
               style="padding-bottom: 2px; height: 22px;">
          <span id="dns-server">{$MGLANG['aa']['product']['server'][{$selectedPlan['server_type']}]}'s DNS Server</span>
        {elseif $selectedPlan['dns_server_type']}
          <img id="dns-server-icon"
               src="{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/{$selectedPlan['dns_server_type']}.svg"
               style="padding-bottom: 2px; height: 22px;">
          <span id="dns-server">{$MGLANG['aa']['product']['dns_server'][{$selectedPlan['dns_server_type']}]} ({$selectedPlan['dns_server_name']})</span>
        {else}
          <img id="dns-server-icon" style="display: none; padding-bottom: 2px; height: 22px;">
          <span id="dns-server">None</span>
        {/if}
    </td>
  </tr>
  <tr>
    <td></td>
    <td><span class="plan-settings">Email Server:</span>
        {if $selectedPlan['email_server_internal']}
          <img id="email-server-icon"
               src="{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/{$selectedPlan['server_type']}.svg"
               style="padding-bottom: 2px; height: 22px;">
          <span id="email-server">{$MGLANG['aa']['product']['server'][{$selectedPlan['server_type']}]}'s Email Server</span>
        {elseif $selectedPlan['email_server_type']}
          <img id="email-server-icon"
               src="{$config['SystemURL']}/modules/servers/panelalpha/templates/icons/{$selectedPlan['email_server_type']}.svg"
               style="padding-bottom: 2px; height: 22px;">
          <span id="email-server">{$MGLANG['aa']['product']['email_server'][{$selectedPlan['email_server_type']}]} ({$selectedPlan['email_server_name']})</span>
        {else}
          <img id="email-server-icon" style="display: none; padding-bottom: 2px; height: 22px;">
          <span id="email-server">None</span>
        {/if}
    </td>
  </tr>
  </tbody>
</table>

<table class="form module-settings" width="100%" border="0" cellspacing="2" cellpadding="3"
       id="tblModuleActionSettings">
  <tbody>
  <tr>
    <td class="fieldlabel" width="20%">Automatic instance provisioning<br>
      <span class="subtitle">Automatically installs a WordPress instance when the service is provisioned by WHMCS</span>
    </td>
    <td class="fieldarea">
      <div class="config-field-advanced">
        <div>
          <input id="automatic" type="checkbox" name="automatic" class="switch">
          <input type="hidden" name="configoption[2]" value="{$product->configoption2}">
        </div>
        <div class="config-field-advanced-optional">
          <div class="modules-settings-advanced">
            <label class="modules-settings-advanced-label">Default Instance Theme</label>
            <select id="select-default-theme" class="form-control" name="configoption[3]">
                {if $product->configoption3}
                  <option value="{$product->configoption3}"
                          selected="selected">{$product->configoption3|replace:'-':" "|capitalize}
                  </option>
                {/if}
            </select>
          </div>
          <div id="default-instance-name" class="modules-settings-advanced">
            <label class="modules-settings-advanced-label">Default Instance Name</label>
            <input type="text" class="form-control input-inline input-300" name="configoption[9]"
                   value="{$product->configoption9}" placeholder="Enter Default Instance Name"/>
          </div>
          <div class="modules-settings-advanced">
            <label class="modules-settings-advanced-label">Show Instance Name Field on Order Form</label>
            <input id="show-instance-name-on-order-form" type="checkbox" name="show-instance-name" class="switch">
            <input type="hidden" name="configoption[8]" value="{$product->configoption8}">
          </div>
        </div>
      </div>
    </td>
  </tr>
  <tr>
    <td class="fieldlabel" width="20%">Manual termination (recommended)<br>
      <span class="subtitle">If enabled WHMCS will NOT terminate the service in PanelAlpha. You will have to manually remove it from the system. It the safest option in a case that there's some mistake.</span>
    </td>
    <td class="fieldarea">
      <input id="manual-termination" type="checkbox" name="manual-termination" class="switch">
      <input type="hidden" name="configoption[4]" value="{$product->configoption4}">
  </tr>
  <tr>
    <td class="fieldlabel" width="20%">Panel Alpha SSO in main menu<br>
      <span class="subtitle">Display PanelAlpha SSO link in the main client area menu for customers who have an active service with this product</span>
    </td>
    <td class="fieldarea">
      <input id="sso" type="checkbox" name="sso" class="switch">
      <input type="hidden" name="configoption[5]" value="{$product->configoption5}">
    </td>
  </tr>
  </tbody>
</table>

<table class="form metric-settings" width="100%" border="0" cellspacing="2" cellpadding="3" id="tblMetric">
  <tbody>
  <tr>
    <td width="150"> Metric Billing</td>
    <td class="fieldarea">
      <div class="config" id="metricsConfig">
        <div class="row">
            {foreach $usageItems as $item}
              <div class="col-md-4">
                <div class="metric">
                  <div>
                    <span>{$item->metric|replace:'_':' '|capitalize}</span>
                    <span class="toggle">
                                    <input id="metric_{$item->metric}" type="checkbox" class="switch">
                                    <input type="hidden" name="metric[{$item->metric}]"
                                           value="{$item->is_hidden}"></span>
                  </div>
                  <span>
                                <a href="#" class="btn-link open-metric-pricing" data-metric="{$item->metric}">Configure Pricing</a>
                            </span>
                </div>
              </div>
            {/foreach}
        </div>
      </div>
    </td>
  </tr>
  </tbody>
</table>