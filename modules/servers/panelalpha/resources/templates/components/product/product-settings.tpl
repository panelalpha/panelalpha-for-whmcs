{extends file="ui/cards/card.tpl"}

{block name="title"}{{$LANG['aa']['product']['module_settings']['product_settings']['title']}}{/block}

{block name="content"}
  <div class="pa-row pa-row--3 pa-row--3-20-20-60">
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file='ui/cards/card-row-label.tpl'
          title=$LANG['aa']['product']['module_settings']['product_settings']['automatic_instance_provisioning']['title']
          subtitle=$LANG['aa']['product']['module_settings']['product_settings']['automatic_instance_provisioning']['subtitle']
          }
      </div>
    </div>
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file="ui/switcher.tpl"
          id="automatic"
          name="configoption[2]"
          class="pa-switch--right"
          checked=($product->configoption2 == 'on')
          valueOn="on"
          valueOff=""
          }
      </div>
    </div>
    <div class="pa-col">
      <div class="pa-form-row" style="justify-content: flex-end;">
        <div class="config-field-advanced">
          <div class="config-field-advanced-optional"{if $product->configoption2 != 'on'} style="display:none;"{/if}>
            <div class="modules-settings-advanced">
              <label class="modules-settings-advanced-label pa-field-title"
                     for="select-default-theme">{{$LANG['aa']['product']['module_settings']['product_settings']['default_instance_theme']['label']}}</label>
              <select id="select-default-theme" class="form-control" name="configoption[3]">
                  {if $product->configoption3}
                    <option value="{$product->configoption3}"
                            selected="selected">{$product->configoption3|replace:'-':" "|capitalize}
                    </option>
                  {/if}
              </select>
            </div>
            <div id="default-instance-name" class="modules-settings-advanced">
              <label class="modules-settings-advanced-label pa-field-title"
                     for="default-instance-name">{{$LANG['aa']['product']['module_settings']['product_settings']['default_instance_name']['label']}}</label>
              <input id="default-instance-name"
                     type="text"
                     class="form-control input-inline input-300"
                     name="configoption[9]"
                     value="{$product->configoption9}"
                     placeholder="{{$LANG['aa']['product']['module_settings']['product_settings']['default_instance_name']['placeholder']}}"/>
            </div>
            <div class="modules-settings-advanced">
              <label class="modules-settings-advanced-label pa-field-title">{{$LANG['aa']['product']['module_settings']['product_settings']['show_instance_name_on_order_form']['label']}}</label>
                {include
                file="ui/switcher.tpl"
                id="show-instance-name-on-order-form"
                name="configoption[8]"
                class="pa-switch--right"
                checked=($product->configoption8 == 'on')
                valueOn="on"
                valueOff=""
                }
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="pa-row pa-row--3 pa-row--3-20-20-60">
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file='ui/cards/card-row-label.tpl'
          title=$LANG['aa']['product']['module_settings']['product_settings']['manual_termination']['title']
          subtitle=$LANG['aa']['product']['module_settings']['product_settings']['manual_termination']['subtitle']
          }
      </div>
    </div>
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file="ui/switcher.tpl"
          id="manual-termination"
          name="configoption[4]"
          class="pa-switch--right"
          checked=($product->configoption4 == 'on')
          valueOn="on"
          valueOff=""
          }
      </div>
    </div>
    <div class="pa-col">
      <div class="pa-form-row">
      </div>
    </div>
  </div>
  <div class="pa-row pa-row--3 pa-row--3-20-20-60">
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file='ui/cards/card-row-label.tpl'
          title=$LANG['aa']['product']['module_settings']['product_settings']['main_menu_sso']['title']
          subtitle=$LANG['aa']['product']['module_settings']['product_settings']['main_menu_sso']['subtitle']
          }
      </div>
    </div>
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file="ui/switcher.tpl"
          id="sso"
          name="configoption[5]"
          class="pa-switch--right"
          checked=($product->configoption5 == 'on')
          valueOn="on"
          valueOff=""
          }
      </div>
    </div>
    <div class="pa-col">
      <div class="pa-form-row">
      </div>
    </div>
  </div>
  <div class="pa-row pa-row--3 pa-row--3-20-20-60">
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file='ui/cards/card-row-label.tpl'
          title=$LANG['aa']['product']['module_settings']['product_settings']['automatic_set_number_sites']['title']
          subtitle=$LANG['aa']['product']['module_settings']['product_settings']['automatic_set_number_sites']['subtitle']
          }
      </div>
    </div>
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file="ui/switcher.tpl"
          id="number-of-sites"
          name="configoption[10]"
          class="pa-switch--right"
          checked=($product->configoption10 == 'on')
          valueOn="on"
          valueOff=""
          }
      </div>
    </div>
    <div class="pa-col">
      <div class="pa-form-row">
      </div>
    </div>
  </div>
{/block}