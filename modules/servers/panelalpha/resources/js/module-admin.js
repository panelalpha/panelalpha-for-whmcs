/**
 * Product module settings page in the WHMCS admin area.
 */
(function () {
	/**
	 * Returns a translation from window.WHMCS_LANG by dotted path.
	 *
	 * @param {string} path Dotted key, for example "aa.product.server.config.key.disk".
	 * @param {string} [defaultText=''] Text returned when the key is missing.
	 * @returns {string}
	 */
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

	/**
	 * Uppercases the first character of a value.
	 *
	 * @param {*} value Plan config values can be numbers (for example disk_space_limit).
	 * @returns {string}
	 */
	function capitalize(value) {
		const str = String(value);
		return str.charAt(0).toUpperCase() + str.slice(1);
	}

	/**
	 * Fills the plan summary and the hosting account config fields when a plan is selected.
	 *
	 * @param {jQuery} $
	 */
	function initPlanSelector($) {
		const selectElement = $('#select-plan');
		if (!selectElement.length) return;
		selectElement.off('change.panelalpha').on('change.panelalpha', function () {
			const selectedOption = $(this).find('option:selected');

			// Instance Limit
			const instanceLimit = selectedOption.data('instance_limit');
			$('#instanceLimit').html(instanceLimit);

			// Onboarding
			const onboardingType = selectedOption.data('onboarding_type');
			$('#onboarding-name').html(getLangText(`aa.product.onboarding.name.${onboardingType}`));
			$('#onboarding-description').attr('data-original-title', getLangText(`aa.product.onboarding.description.${onboardingType}`));

			// Ask for Domain
			const askForDomain = selectedOption.data('onboarding_ask_for_domain');
			const askForDomainValue = (askForDomain === true || askForDomain === 1 || askForDomain === '1' || askForDomain === 'true') ? 'on' : '';
			$('#onboarding-ask-for-domain').val(askForDomainValue);

			// Server Group
			const serverGroupName = selectedOption.data('server_group') ?? null;
			if (serverGroupName) {
				$('#server-group').html(serverGroupName);
			} else {
				$('#server-group').html(getLangText(`aa.product.server_group.name.all`));
			}

			const serverAssignRule = selectedOption.data('server_assign_rule');
			$('#server-assign-rule').html(getLangText(`aa.product.assign_rule.name.${serverAssignRule}`));
			$('#assign-rule-description').attr('data-original-title', getLangText(`aa.product.assign_rule.description.${serverAssignRule}`));

			// Server
			const serverType = selectedOption.data('server_type');
			$('#server-type').html(getLangText(`aa.product.server.${serverType}`));
			$('#server-icon').attr('data-server', serverType);

			// DNS Server
			const isDnsServerInternal = selectedOption.data('dns_server_internal') ?? false;
			const dnsServerType = selectedOption.data('dns_server_type') ?? null;
			if (isDnsServerInternal) {
				$('#dns-server').removeClass('text-none').html(getLangText(`aa.product.dns_server.${serverType}`));
				$('#dns-server-icon').show().css('display', 'inline-block').attr('data-server', serverType);
			} else if (dnsServerType) {
				const dnsServerName = selectedOption.data('dns_server_name');
				$('#dns-server').removeClass('text-none').html(getLangText(`aa.product.dns_server.${dnsServerType}`) + ` (${dnsServerName})`);
				$('#dns-server-icon').show().css('display', 'inline-block').attr('data-server', dnsServerType);
			} else {
				$('#dns-server').addClass('text-none').html(getLangText(`general.none`));
				$('#dns-server-icon').hide();
			}

			// Email Server
			const isEmailServerInternal = selectedOption.data('email_server_internal') ?? false;
			const emailServerType = selectedOption.data('email_server_type') ?? null;
			if (isEmailServerInternal) {
				$('#email-server').removeClass('text-none').html(getLangText(`aa.product.email_server.${serverType}`));
				$('#email-server-icon').show().css('display', 'inline-block').attr('data-server', serverType);
			} else if (emailServerType) {
				const emailServerName = selectedOption.data('email_server_name');
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
			const accountConfig = selectedOption.data('account_config');
			const config = Array.isArray(accountConfig) ? accountConfig : [];
			config.forEach((field) => {
				const row = $('<div>').addClass('account-config plan-settings-cell-value').css('margin-bottom', '6px');

				const label = $('<span>')
					.addClass('plan-settings plan-settings--hosting-config')
					.attr('id', field.name)
					.text(getLangText(`aa.product.server.config.key.${field.name}`) + ':');

				const valueEl = $('<span>').css('margin-left', '4px').attr('id', field.name);

				switch (field.type) {
					case 'text':
						valueEl.text(field.value !== null ? capitalize(field.value) : '-');
						break;

					case 'select': {
						const selectValue = getLangText(`aa.product.server.config.value.${field.value}`);
						valueEl.text(selectValue || (field.value !== null ? capitalize(field.value) : '-'));
						break;
					}

					case 'checkbox':
						if (field.value === true) {
							valueEl.append($('<div>').addClass('icon').attr('data-icon', 'checkIcon').css('height', '20px').css('width', '20px').css('display', 'inline-block'));
							if (field.name === 'allow_user_choose_location') {
								valueEl.append($('<button type="button" id="button-allow-user-choose-location" class="btn btn-default btn-xs">').css('margin-left', '12px').text(getLangText('aa.button.show_location_on_order_form', 'Show Location On Order Form')));
							}
						} else {
							valueEl.append($('<div>').addClass('icon').attr('data-icon', 'uncheckIcon').css('height', '20px').css('width', '20px').css('display', 'inline-block'));
						}
						break;

					case 'textarea':
						if (field.value !== null) {
							const textareaDisplay = $('<div>').addClass('textarea-display');
							const pre = $('<pre>').css({
								'background-color': '#f4f4f4',
								'padding': '4px',
								'border': '1px solid #ddd',
								'border-radius': '4px',
								'font-family': 'monospace',
								'white-space': 'pre-wrap',
								'word-wrap': 'break-word',
								'margin-bottom': '0',
								'font-size': '11px'
							}).append($('<code>').text(field.value));
							textareaDisplay.append(pre);
							row.append(label, textareaDisplay);
							$('#account-config').append(row);
							return;
						}
						break;

					default:
						valueEl.text('-');
						break;
				}

				row.append(label, valueEl);
				$('#account-config').append(row);
			});

			// Custom Hosting Account Configuration
			$('#hosting-account-config').empty();
			const hostingAccountConfig = Array.isArray(accountConfig) ? accountConfig : [];
			hostingAccountConfig.forEach((field) => {
				const row = $('<div>').addClass('account-config plan-settings-cell-value').css('margin-bottom', '6px');

				const label = $('<label>')
					.addClass('plan-settings plan-settings--hosting-config')
					.attr('for', field.name)
					.text(getLangText(`aa.product.server.config.key.${field.name}`) + ':');

				if (field.type === 'text' || field.type === 'select') {
					const input = $('<input>')
						.attr('type', field.type === 'select' ? 'select' : 'text')
						.addClass('form-control input-inline input-inline--hosting-config')
						.attr('id', field.name)
						.attr('data-field-name', field.name)
						.val(field.value);

					input.on('input change', function () {
						updateHostingAccountConfigJSON();
					});

					row.append(label, input);
				} else if (field.type === 'textarea') {
					const textarea = $('<textarea>')
						.addClass('form-control input-inline input-inline--hosting-config')
						.attr('id', field.name)
						.attr('data-field-name', field.name)
						.attr('rows', 4)
						.val(field.value);

					textarea.on('input change', function () {
						updateHostingAccountConfigJSON();
					});

					row.append(label, textarea);
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
			const configurableOptions = selectedOption.data('configurable-options');
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

		// configoption[6] stores the onboarding type, so keep it in sync with the label shown for the plan.
		const onboardingType = $('#onboarding-name');
		$('[name="configoption[6]"]').val(onboardingType.text());

		// DOMSubtreeModified is no longer fired by current browsers, so watch the label instead.
		if (onboardingType.length && !onboardingType.data('panelalphaObserver')) {
			const observer = new MutationObserver(function () {
				$('[name="configoption[6]"]').val(onboardingType.text());
			});
			observer.observe(onboardingType[0], { childList: true, characterData: true, subtree: true });
			onboardingType.data('panelalphaObserver', observer);
		}
		selectElement.trigger('change.panelalpha');
	}

	/**
	 * Turns the default theme field into a select2 search over the WordPress.org theme directory.
	 *
	 * @param {jQuery} $
	 */
	function initThemeSelector($) {
		$('#select-default-theme').select2({
			width: 300,
			placeholder: 'Select default instance theme',
			minimumInputLength: 4,
			ajax: {
				url: function (params) {
					return 'https://api.wordpress.org/themes/info/1.1/?action=query_themes&request[search]=' + encodeURIComponent(params.term || '');
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

	/**
	 * Keeps the hidden "automatic" config option in sync with its visible checkbox.
	 *
	 * @param {jQuery} $
	 */
	function initAutomaticInstanceProvisioningTracking($) {
		const automaticCheckbox = $('[name="configoption[2]"]');

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

	/**
	 * Shows or hides the optional advanced fields.
	 *
	 * @param {boolean} state
	 */
	function toggleAdvancedOptions(state) {
		if (state) {
			$('.config-field-advanced-optional').show();
		} else {
			$('.config-field-advanced-optional').hide();
		}
	}

	/**
	 * Writes all hosting account config fields as a query string into #hosting-account-config-json.
	 */
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

		$('#hosting-account-config textarea[data-field-name], #hosting-account-config-section textarea[data-field-name]').each(function () {
			const fieldName = $(this).attr('data-field-name');
			const value = $(this).val();
			configData[fieldName] = value;
		});

		$('#hosting-account-config .pa-switch input[type="checkbox"], #hosting-account-config-section .pa-switch input[type="checkbox"]').each(function () {
			let fieldName = $(this).attr('id');
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
	};

	/**
	 * Restores the hosting account config fields from the saved query string.
	 */
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

				const textareaInput = $(`#hosting-account-config textarea[data-field-name="${fieldName}"], #hosting-account-config-section textarea[data-field-name="${fieldName}"]`);
				if (textareaInput.length) {
					textareaInput.val(value);
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

	/**
	 * Sets up the button that shows or hides the advanced mode section.
	 *
	 * @param {jQuery} $
	 */
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

	$(document).on('input change', '#hosting-account-config textarea[data-field-name], #hosting-account-config-section textarea[data-field-name]', function () {
		updateHostingAccountConfigJSON();
	});

	// Post a new form so only the action and CSRF token are sent, not the whole product form.
	$(document).on('click', '#generate-configurable-option-button', function (event) {
		event.preventDefault();

		const tokenInput = document.querySelector('input[name="token"]');
		if (!tokenInput || !tokenInput.value) {
			return;
		}

		const form = document.createElement('form');
		form.method = 'POST';
		form.action = window.location.pathname + window.location.search;

		const customInput = document.createElement('input');
		customInput.type = 'hidden';
		customInput.name = 'custom';
		customInput.value = 'generate-configurable-options';
		form.appendChild(customInput);

		const tokenField = document.createElement('input');
		tokenField.type = 'hidden';
		tokenField.name = 'token';
		tokenField.value = tokenInput.value;
		form.appendChild(tokenField);

		document.body.appendChild(form);
		form.submit();
	});

	// Creates the "Location" custom field from the locations of the selected plan's server.
	$(document).on('click', '#button-allow-user-choose-location', function (event) {
		event.preventDefault();

		const tokenInput = document.querySelector('input[name="token"]');
		if (!tokenInput || !tokenInput.value) {
			return;
		}

		const form = document.createElement('form');
		form.method = 'POST';
		form.action = window.location.pathname + window.location.search;

		const fields = {
			custom: 'create-location-custom-field',
			plan_id: $('#select-plan').val() || '',
			token: tokenInput.value,
		};
		Object.keys(fields).forEach(function (name) {
			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			input.value = fields[name];
			form.appendChild(input);
		});

		document.body.appendChild(form);
		form.submit();
	});

	/**
	 * Initializes the page. Runs on DOMContentLoaded and again after every AJAX call,
	 * because WHMCS reloads the module settings tab with AJAX.
	 */
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
