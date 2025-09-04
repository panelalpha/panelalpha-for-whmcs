{* Bootstrap Switch Toggle *}
{* Params:
   - name: form field name for hidden input
   - id: (optional) DOM id for the checkbox. Auto-generated if missing.
   - checked: boolean/int/string; truthy => checked
   - class: (optional) extra classes for the checkbox
   - hiddenValue: value for hidden input when unchecked
   - valueOn: (optional) value for checked state (default '1')
   - size: (optional) switch size: 'mini', 'small', 'normal', 'large' (default 'small')
   - onColor: (optional) color for ON state (default 'success')
   - inverted: (optional) inverted logic - when switch ON, hidden gets valueOff
*}
{if isset($id) && $id ne ''}
    {assign var=_toggleId value=$id}
{else}
    {assign var=_toggleId value='pa_toggle_'|cat:$smarty.now}
{/if}
{assign var=_valueOn value=$valueOn|default:'1'}
{assign var=_valueOff value=$valueOff|default:$hiddenValue}
{assign var=_size value=$size|default:'small'}
{assign var=_onColor value=$onColor|default:'success'}
{assign var=_inverted value=$inverted|default:false}

<input type="checkbox"
       id="{$_toggleId|escape:'html'}"
       {if isset($class) && $class ne ''}class="{$class|escape:'html'}"{/if}
        {if $checked}checked="checked"{/if}
/>
<input type="hidden"
       id="{$_toggleId|cat:'_hidden'|escape:'html'}"
       name="{$name|escape:'html'}"
       value="{if $checked}{$_valueOn|escape:'html'}{else}{$_valueOff|escape:'html'}{/if}"
/>

{literal}
<script>(function () {
		if (typeof window.jQuery === 'undefined') return;
		const $ = window.jQuery;

		const toggleId = '{/literal}{$_toggleId|escape:'javascript'}{literal}';
		const hiddenId = '{/literal}{$_toggleId|cat:'_hidden'|escape:'javascript'}{literal}';
		const onVal = '{/literal}{$_valueOn|escape:'javascript'}{literal}';
		const offVal = '{/literal}{$_valueOff|escape:'javascript'}{literal}';
		const size = '{/literal}{$_size|escape:'javascript'}{literal}';
		const onColor = '{/literal}{$_onColor|escape:'javascript'}{literal}';
		const inverted = {/literal}{if $_inverted}true{else}false{/if}{literal};

		function initToggle() {

			const $toggle = $('#' + toggleId);
			const $hidden = $('#' + hiddenId);

			if (!$toggle.length || !$hidden.length) return;

			function isOn(val) {
				return val === 1 || val === '1' || val === true || val === 'true' || val === 'on';
			}

			let initialState;
			if (inverted) {
				initialState = ($hidden.val() == onVal) ? true : false;
			} else {
				initialState = isOn($hidden.val());
			}

			$toggle.bootstrapSwitch({
				size: size,
				onColor: onColor,
				state: initialState,
				onInit: function () {
					this.value = $hidden.val();
				}
			});

			$toggle.off('switchChange.bootstrapSwitch.toggle').on('switchChange.bootstrapSwitch.toggle', function (event, state) {
				if (inverted) {
					$hidden.val(state ? onVal : offVal);
				} else {
					$hidden.val(state ? offVal : onVal);
				}
			});
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initToggle);
		} else {
			initToggle();
		}

		$(document).ajaxStop(function () {
			setTimeout(initToggle, 100);
		});
	})();</script>
{/literal}