(function () {
	function getLangText(path, defaultText = '') {
		if (!window.WHMCS_LANG) return defaultText;

		const keys = path.split('.');
		let current = window.WHMCS_LANG;

		for (const key of keys) {
			if (current && typeof current === 'object' && key in current) {
				current = current[key];
			} else {
				return defaultText;
			}
		}

		return current || defaultText;
	}

	function initPlanSelector($) {
		const selectElement = $('#select-plan');
		if (!selectElement.length) return;
		selectElement.off('change.panelalpha').on('change.panelalpha', function () {
			let selectedOption = $(this).find('option:selected');

			// Instance Limit
			let instanceLimit = selectedOption.data('instance_limit');
			$('#instanceLimit').html(instanceLimit);

			// Onboarding
			let onboardingType = selectedOption.data('onboarding_type');
			$('#onboarding-name').html(getLangText(`aa.product.onboarding.name.${onboardingType}`));
			$('#onboarding-description').attr('data-original-title', getLangText(`aa.product.onboarding.description.${onboardingType}`));
			$('#onboarding-ask-for-domain').val(selectedOption.data('onboarding_ask_for_domain'))

			// Server Group
			let serverGroupName = selectedOption.data('server_group') ?? null;
			if (serverGroupName) {
				$('#server-group').html(serverGroupName);
			} else {
				$('#server-group').html(getLangText(`aa.product.server_group.name.all`));
			}

			let serverAssignRule = selectedOption.data('server_assign_rule');
			$('#server-assign-rule').html(getLangText(`aa.product.assign_rule.name.${serverAssignRule}`));
			$('#assign-rule-description').attr('data-original-title', getLangText(`aa.product.assign_rule.description.${serverAssignRule}`));

			// Server
			let serverType = selectedOption.data('server_type');
			$('#server-type').html(getLangText(`aa.product.server.${serverType}`));
			$('#server-icon').attr('data-server', serverType);

			// DNS Server
			let isDnsServerInternal = selectedOption.data('dns_server_internal') ?? false;
			let dnsServerType = selectedOption.data('dns_server_type') ?? null;
			if (isDnsServerInternal) {
				$('#dns-server').removeClass('text-none').html(getLangText(`aa.product.dns_server.${serverType}`));
				$('#dns-server-icon').show().css('display', 'inline-block').attr('data-server', serverType);
			} else if (dnsServerType) {
				let dnsServerName = selectedOption.data('dns_server_name');
				$('#dns-server').removeClass('text-none').html(getLangText(`aa.product.dns_server.${dnsServerType}`) + ` (${dnsServerName})`);
				$('#dns-server-icon').show().css('display', 'inline-block').attr('data-server', dnsServerType);
			} else {
				$('#dns-server').addClass('text-none').html(getLangText(`general.none`));
				$('#dns-server-icon').hide();
			}

			// Email Server
			let isEmailServerInternal = selectedOption.data('email_server_internal') ?? false;
			let emailServerType = selectedOption.data('email_server_type') ?? null;
			if (isEmailServerInternal) {
				$('#email-server').removeClass('text-none').html(getLangText(`aa.product.email_server.${serverType}`));
				$('#email-server-icon').show().css('display', 'inline-block').attr('data-server', serverType);
			} else if (emailServerType) {
				let emailServerName = selectedOption.data('email_server_name');
				$('#email-server').removeClass('text-none').html(getLangText(`aa.product.email_server.${emailServerType}`, emailServerType) + ` (${emailServerName})`);
				$('#email-server-icon').show().css('display', 'inline-block').attr('data-server', emailServerType);

				// Hide icon if a custom email server name is used
				if (getLangText(`aa.product.email_server.${emailServerType}`, emailServerType) === emailServerType) {
					$('#email-server-icon').hide();
				}
			} else {
				$('#email-server').addClass('text-none').html(getLangText(`general.none`));
				$('#email-server-icon').hide();
			}

			// Hosting Account Config
			$('#account-config').empty();
			let accountConfig = selectedOption.data('account_config');
			let config = JSON.parse(JSON.stringify(accountConfig));
			config.forEach((field) => {
				const row = $('<div>').addClass('account-config plan-settings-cell-value').css('margin-bottom', '6px');

				const label = $('<span>')
					.addClass('plan-settings')
					.attr('id', field.name)
					.text(getLangText(`aa.product.server.config.key.${field.name}`) + ':');

				let valueEl = $('<span>').attr('id', field.name).css('margin-left', '4px');

				if (!field.value) {
					valueEl.append($('<span>').addClass('icon').attr('data-icon', 'uncheckIcon').css('height', '20px').css('width', '20px').css('display', 'inline-block'));
				} else if (field.value === true) {
					valueEl.append($('<span>').addClass('icon').attr('data-icon', 'checkIcon').css('height', '20px').css('width', '20px').css('display', 'inline-block'));
				} else {
					valueEl.text(getLangText(`aa.product.server.config.value.${field.value}`, field.value));
				}

				row.append(label, valueEl);
				$('#account-config').append(row);
			});

			// Custom Hosting Account Configuration
			$('#hosting-account-config').empty();
			let hostingAccountConfig = JSON.parse(JSON.stringify(accountConfig));
			hostingAccountConfig.forEach((field) => {
				const row = $('<div>').addClass('account-config plan-settings-cell-value').css('margin-bottom', '6px');

				const label = $('<label>')
					.addClass('plan-settings plan-settings--hosting-config')
					.attr('for', field.name)
					.text(getLangText(`aa.product.server.config.key.${field.name}`) + ':');

				if (field.type === 'text' || field.type === 'select') {
					const input = $('<input>')
						.attr('type', 'text')
						.addClass('form-control input-inline input-inline--hosting-config')
						.attr('id', field.name)
						.attr('data-field-name', field.name)
						.val(field.value);

					input.on('input change', function () {
						updateHostingAccountConfigJSON();
					});

					row.append(label, input);
				} else if (field.type === 'checkbox') {
					const switcherWrapper = $('<span>').addClass('pa-form-control');
					const switcherLabel = $('<label>')
						.addClass('pa-switch pa-switch--right')
						.attr('for', field.name + 'switcher');
					const switcherInput = $('<input>')
						.attr('type', 'checkbox')
						.attr('id', field.name + 'switcher')
						.attr('data-field-name', field.name)
						.prop('checked', field.value == '1');
					const switcherSlider = $('<span>').addClass('pa-slider');

					switcherLabel.append(switcherInput, switcherSlider);
					switcherWrapper.append(switcherLabel);

					switcherInput.on('change', function () {
						updateHostingAccountConfigJSON();
					});

					row.append(label, switcherWrapper);
				}

				$('#hosting-account-config').append(row);
			});

			// Configurable Options
			$('.configurable-options-left').empty();
			let configurableOptions = selectedOption.data('configurable-options');
			if (configurableOptions && Array.isArray(configurableOptions)) {
				configurableOptions.forEach((option) => {
					const optionRow = $('<div>').addClass('configurable-option-row');
					const optionName = $('<div>').addClass('configurable-option-name')
						.html(`<strong>${option}|${getLangText(`aa.product.server.config.key.${option}`)}</strong>`);

					optionRow.append(optionName);
					$('.configurable-options-left').append(optionRow);
				});
			}

			loadSavedConfigData();
			updateHostingAccountConfigJSON();
		});

		// ToDo check this
		const onboardingType = $('#onboarding-name');
		$('[name="configoption[6]"]').val(onboardingType.text());

		onboardingType.off('DOMSubtreeModified.panelalpha').on("DOMSubtreeModified.panelalpha", function () {
			let onboardingTypeValue = $(this).text();
			$('[name="configoption[6]"]').val(onboardingTypeValue);
		});
	}

	function initThemeSelector($) {
		$('#select-default-theme').select2({
			width: 300,
			placeholder: 'Select default instance theme',
			minimumInputLength: 4,
			ajax: {
				url: function (params) {
					return 'https://api.wordpress.org/themes/info/1.1/?action=query_themes&request[search]=' + params.term;
				},
				dataType: 'json',
				global: false,
				processResults: function (data) {
					const results = data.themes.map(item => ({text: item.name, id: item.slug}));
					return {results: results};
				},
			},
		});
	}

	function initAutomaticInstanceProvisioningTracking($) {
		let automaticCheckbox = $('[name="configoption[2]"]');

		function isOn(val) {
			return val === 1 || val === '1' || val === true || val === 'true' || val === 'on';
		}

		const automaticElement = $('#automatic');
		automaticElement.prop('checked', isOn(automaticCheckbox.val()));

		automaticElement.off('change.panelalpha').on('change.panelalpha', function () {
			const isChecked = $(this).is(':checked');
			automaticCheckbox.val(isChecked ? 'on' : '');
			toggleAdvancedOptions(isChecked);
		});
	}

	function toggleAdvancedOptions(state) {
		if (state) {
			$('.config-field-advanced-optional').show();
		} else {
			$('.config-field-advanced-optional').hide();
		}
	}

	window.updateHostingAccountConfigJSON = function updateHostingAccountConfigJSON() {
		const configData = {};

		$('#hosting-account-config input[data-field-name], #hosting-account-config-section input[data-field-name]').each(function () {
			const fieldName = $(this).attr('data-field-name');
			let value;

			if ($(this).attr('type') === 'checkbox') {
				value = $(this).is(':checked') ? '1' : '0';
			} else {
				value = $(this).val();
			}

			configData[fieldName] = value;
		});

		$('#hosting-account-config .pa-switch input[type="checkbox"], #hosting-account-config-section .pa-switch input[type="checkbox"]').each(function () {
			let fieldName = $(this).attr('id');
			// Remove 'switcher' suffix if present
			if (fieldName.endsWith('switcher')) {
				fieldName = fieldName.replace(/switcher$/, '');
			}
			const hiddenField = $('#' + fieldName + '_config_field');
			if (hiddenField.length) {
				const value = $(this).is(':checked') ? '1' : '0';
				hiddenField.val(value);
				configData[fieldName] = value;
			}
		});

		const configString = Object.keys(configData).map(key =>
			encodeURIComponent(key) + '=' + encodeURIComponent(configData[key])
		).join('&');

		$('#hosting-account-config-json').val(configString);
	}


	function loadSavedConfigData() {
		try {
			const savedDataString = $('#hosting-account-config-json').val() || '';
			const savedData = {};

			if (savedDataString && savedDataString.length > 0) {
				savedDataString.split('&').forEach(pair => {
					if (pair.includes('=')) {
						const [key, value] = pair.split('=').map(decodeURIComponent);
						if (key && value !== undefined) {
							savedData[key] = value;
						}
					}
				});
			}

			Object.keys(savedData).forEach(fieldName => {
				const value = savedData[fieldName];

				const textInput = $(`#hosting-account-config input[data-field-name="${fieldName}"], #hosting-account-config-section input[data-field-name="${fieldName}"]`);
				if (textInput.length && textInput.attr('type') !== 'hidden') {
					textInput.val(value);
				}

				const switchInput = $(`#hosting-account-config #${fieldName}switcher, #hosting-account-config-section #${fieldName}switcher`);
				if (switchInput.length && switchInput.attr('type') === 'checkbox') {
					switchInput.prop('checked', value == '1');
				}

				const hiddenField = $(`#${fieldName}_config_field`);
				if (hiddenField.length) {
					hiddenField.val(value);
				}
			});
		} catch (e) {
			console.error('Error loading saved config data:', e);
		}
	}


	function initAdvancedModeToggle($) {
		const $section = $('.advanced-mode-section');
		const $advancedModeState = $('#advanced-mode-state');
		const $toggleButton = $('#toggle-advanced-mode');

		if ($advancedModeState.val() === '1') {
			$section.show();
			$toggleButton.text(getLangText('aa.product.module_settings.mode.basic'));
			setTimeout(function () {
				loadSavedConfigData();
				updateHostingAccountConfigJSON();
			}, 100);
		} else {
			$section.hide();
			$toggleButton.text(getLangText('aa.product.module_settings.mode.advanced'));
		}

		$(document).off('click.panelalpha', '#toggle-advanced-mode').on('click.panelalpha', '#toggle-advanced-mode', function (event) {
			event.preventDefault();
			const $this = $(this);

			if ($section.is(':visible')) {
				$section.slideUp(400);
				$this.text(getLangText('aa.product.module_settings.mode.advanced'));
				$advancedModeState.val('0');
			} else {
				$section.slideDown(400);
				$this.text(getLangText('aa.product.module_settings.mode.basic'));
				$advancedModeState.val('1');

				setTimeout(function () {
					loadSavedConfigData();
					updateHostingAccountConfigJSON();
				}, 500);
			}
		});
	}

	$(document).on('change', '#hosting-account-config .pa-switch input[type="checkbox"], #hosting-account-config-section .pa-switch input[type="checkbox"]', function () {
		updateHostingAccountConfigJSON();
	});

	$(document).on('input change', '#hosting-account-config input[data-field-name], #hosting-account-config-section input[data-field-name]', function () {
		updateHostingAccountConfigJSON();
	});


	$(document).on('click', '#generate-configurable-option-button', function(event) {
		event.preventDefault();

		const url = new URL(window.location.href);
		url.searchParams.append('custom', 'generate-configurable-options');

		window.location.assign(url.toString());
	});


	function initAll() {
		if (typeof window.jQuery === 'undefined') return;
		const $ = window.jQuery;

		window.WHMCS_SYSTEM_URL = (window.config && window.config.SystemURL) ? window.config.SystemURL : '';
		window.WHMCS_LANG = (window.config && window.config.lang) ? window.config.lang : {};

		initPlanSelector($);
		initThemeSelector($);
		initAutomaticInstanceProvisioningTracking($);
		initAdvancedModeToggle($);

		setTimeout(function () {
			loadSavedConfigData();
		}, 200);
	}

	document.addEventListener('DOMContentLoaded', function () {
		initAll();
	});

	if (window.jQuery) {
		jQuery(document).ajaxStop(function () {
			initAll();
		});
	}
})();
