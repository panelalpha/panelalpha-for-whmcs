{* PanelAlpha UI Switcher (toggle) *}
{* Params:
   - name: (optional) form field name. If provided, a hidden input will be synced to reflect ON/OFF values.
   - id: (optional) DOM id for the checkbox. Auto-generated if missing.
   - checked: (optional) boolean/int/string; truthy => ON
   - disabled: (optional) boolean; renders disabled state
   - class: (optional) extra classes for the wrapper
   - valueOn: (optional) value for ON state (default '1')
   - valueOff: (optional) value for OFF state (default '0')
   - help: (optional) inline hint shown next to the switch
*}
{if isset($id) && $id ne ''}
    {assign var=_switchId value=$id}
{else}
    {assign var=_switchId value='pa_sw_'|cat:$smarty.now}
{/if}
{assign var=_valueOn value=$valueOn|default:'1'}
{assign var=_valueOff value=$valueOff|default:'0'}

<span class="pa-form-control">
  <label class="pa-switch{if isset($class) && $class ne ''} {$class|escape:'html'}{/if}"
         for="{$_switchId|escape:'html'}">
    <input type="checkbox"
           id="{$_switchId|escape:'html'}"
           {if $checked}checked="checked"{/if}
            {if $disabled}disabled="disabled"{/if}
    />
    <span class="pa-slider"></span>
  </label>
  {if isset($help) && $help ne ''}
    <span class="pa-form-hint">{$help|escape:'html'}</span>
  {/if}

    {if isset($name) && $name ne ''}
      <input type="hidden"
             id="{$_switchId|cat:'_hidden'|escape:'html'}"
             name="{$name|escape:'html'}"
             value="{if $checked}{$_valueOn|escape:'html'}{else}{$_valueOff|escape:'html'}{/if}"/>




{literal}

      <script>(function () {
					const cb = document.getElementById('{/literal}{$_switchId|escape:'javascript'}{literal}');
					const hidden = document.getElementById('{/literal}{$_switchId|cat:'_hidden'|escape:'javascript'}{literal}');
					if (!cb || !hidden) return;
					const onVal = '{/literal}{$_valueOn|escape:'javascript'}{literal}';
					const offVal = '{/literal}{$_valueOff|escape:'javascript'}{literal}';

					function sync() {
						hidden.value = cb.checked ? onVal : offVal;
					}

					sync();
					cb.addEventListener('change', sync);
				})();</script>
    {/literal}
    {/if}
</span>
