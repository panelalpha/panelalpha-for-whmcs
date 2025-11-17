{extends file="ui/cards/card.tpl"}

{block name="title"}{{$LANG['aa']['product']['module_settings']['plan_settings']['title']}}{/block}

{block name="content"}
    {assign var='selectedPlan' value=null}
    {foreach from=$plans item=p}
        {if $p['id'] == $product->configoption1}
            {assign var='selectedPlan' value=$p}
        {/if}
    {/foreach}
  <!-- Plan selector -->
  <div class="pa-row pa-row--3">
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file='ui/cards/card-row-label.tpl'
          title=$LANG['aa']['product']['module_settings']['plan_settings']['plan_selector']['title']
          }
      </div>
    </div>
    <div class="pa-col">
      <div class="pa-form-row">
        <div class="pa-form-control pa-form-control--full">
            {include file='components/product/product-plan-selector.tpl'}
        </div>
      </div>
    </div>

    <div class="pa-col">
      <div class="pa-form-row">
      </div>
    </div>
  </div>
  <!-- Plan settings -->
  <div class="pa-row pa-row--3">
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file='ui/cards/card-row-label.tpl'
          title=$LANG['aa']['product']['module_settings']['plan_settings']['plan_settings']['title']
          subtitle=$LANG['aa']['product']['module_settings']['plan_settings']['plan_settings']['subtitle']
          }
      </div>
    </div>

    <div class="pa-col">
      <div class="pa-form-row">
        <div class="pa-form-control pa-form-control--full">

          <input type="hidden" name="configoption[6]" value=""/>

          <!-- ASK FOR DOMAIN ON ONBOARDING -->
          <input type="hidden" name="configoption[7]" id="onboarding-ask-for-domain"
                 value="{$product->configoption7|default:''}"/>

          <!-- ADVANCED MODE STATE -->
          <input type="hidden" name="configoption[11]" id="advanced-mode-state"
                 value="{$product->configoption11|default:'0'}"/>

          <!-- HOSTING ACCOUNT CONFIG JSON -->
          <input type="hidden" name="configoption[12]" id="hosting-account-config-json"
                 value="{$product->configoption12|default:'{}'}"/>

          <!-- INSTANCES LIMIT -->
          <div class="plan-settings-cell-value" style="margin-bottom: 6px;">
            <span class="plan-settings">{{$LANG['aa']['product']['module_settings']['plan_settings']['instance_limit']['title']}}:</span>
            <span id="instanceLimit">{$selectedPlan['instance_limit']}</span>
          </div>

          <div class="plan-settings-cell-value" style="margin-bottom: 6px;">
            <span class="plan-settings">{{$LANG['aa']['product']['module_settings']['plan_settings']['onboarding']['title']}}:</span>
            <span id="onboarding-name">{$LANG['aa']['product']['onboarding']['name'][{$selectedPlan['config']['onboarding']['method']}]}</span>
            <span>{include file="components/tooltips/info-tooltip.tpl" id="onboarding-description" text="{$LANG['aa']['product']['onboarding']['description'][{$selectedPlan['config']['onboarding']['method']}]}"}</span>
          </div>

          <!-- SERVER GROUP -->
            {if $selectedPlan['server_group_name']}
              <div id="server" class="plan-settings-cell-value" style="margin-bottom: 6px;">
                <span class="plan-settings">{{$LANG['aa']['product']['module_settings']['plan_settings']['server_group']['title']}}:</span>
                <span id="server-group">{$selectedPlan['server_group_name']}</span>
                <span style="margin-left: 4px;">
                  <span>({{$LANG['aa']['product']['assign_rule']['text']}}:</span>
                  <span id="server-assign-rule">{$LANG['aa']['product']['assign_rule']['name'][{$selectedPlan['server_assign_rule']}]}</span><span>)</span>
                  <span>{include file="components/tooltips/info-tooltip.tpl" id="assign-rule-description" text="{$LANG['aa']['product']['assign_rule']['description'][{$selectedPlan['server_assign_rule']}]}"}</span>
                </span>
              </div>
            {else}
              <div id="server" class="plan-settings-cell-value" style="margin-bottom: 6px;">
                <span class="plan-settings">{{$LANG['aa']['product']['module_settings']['plan_settings']['server_group']['title']}}:</span>
                <span id="server-group">{{$LANG['aa']['product']['assign_rule']['all_servers']}}</span>
                <span style="margin-left: 4px;">
                  <span>(</span>
                  <span>{{$LANG['aa']['product']['assign_rule']['text']}}:</span>
                  <span id="server-assign-rule">{$LANG['aa']['product']['assign_rule']['name'][{$selectedPlan['server_assign_rule']}]}</span>
                  <span>)</span>
                  <span>{include file="components/tooltips/info-tooltip.tpl" id="assign-rule-description" text="{$LANG['aa']['product']['assign_rule']['description'][{$selectedPlan['server_assign_rule']}]}"}</span>
                </span>
              </div>
            {/if}

          <!-- SERVER TYPE -->
          <div class="plan-settings-cell-value" style="margin-bottom: 6px;">
            <span class="plan-settings">{{$LANG['aa']['product']['module_settings']['plan_settings']['server_type']['title']}}:</span>
            <div id="server-icon"
                 class="server-icon"
                 data-server="{$selectedPlan['server_type']}"
                 style="height: 22px; width: 25px; display: inline-block; margin-right: 5px;"
            ></div>
            <span id="server-type"> {$LANG['aa']['product']['server'][{$selectedPlan['server_type']}]}</span>
          </div>

          <!-- DNS SERVER TYPE -->
          <div class="plan-settings-cell-value" style="margin-bottom: 6px;">
            <span class="plan-settings">{{$LANG['aa']['product']['module_settings']['plan_settings']['dns_server']['title']}}:</span>
              {if $selectedPlan['dns_server_internal']}
                <div id="dns-server-icon"
                     class="server-icon"
                     data-server="{$selectedPlan['server_type']}"
                     style="height: 22px; width: 25px; display: inline-block; margin-right: 5px;"
                ></div>
                <span id="dns-server">{$LANG['aa']['product']['dns_server'][{$selectedPlan['server_type']}]}</span>
              {elseif $selectedPlan['dns_server_type']}
                <div id="dns-server-icon"
                     class="server-icon"
                     data-server="{$selectedPlan['dns_server_type']}"
                     style="height: 22px; width: 25px; display: inline-block; margin-right: 5px;"
                ></div>
                <span id="dns-server">{$LANG['aa']['product']['dns_server'][{$selectedPlan['dns_server_type']}]} ({$selectedPlan['dns_server_name']})</span>
              {else}
                <div id="dns-server-icon"
                     style="display: none; height: 22px; width: 25px;"
                     class="server-icon"
                ></div>
                <span id="dns-server" class="text-none">{{$LANG['general']['none']}}</span>
              {/if}
          </div>

          <!-- EMAIL SERVER TYPE -->
          <div class="plan-settings-cell-value">
            <span class="plan-settings">{{$LANG['aa']['product']['module_settings']['plan_settings']['email_server']['title']}}:</span>
              {if $selectedPlan['email_server_internal']}
                <div id="email-server-icon"
                     class="server-icon"
                     data-server="{$selectedPlan['server_type']}"
                     style="height: 22px; width: 25px; display: inline-block;"
                ></div>
                <span id="email-server">{$LANG['aa']['product']['email_server'][{$selectedPlan['server_type']}]}</span>
              {elseif $selectedPlan['email_server_type']}
                  {if $LANG['aa']['product']['email_server'][{$selectedPlan['email_server_type']}] eq ''}
                    <div id="email-server-icon"
                         class="server-icon"
                         style="height: 22px; width: 25px; display: none;"
                    ></div>
                    <span id="email-server">{$selectedPlan['email_server_type']} ({$selectedPlan['email_server_name']})</span>
                  {else}
                    <div id="email-server-icon"
                         class="server-icon"
                         data-server="{$selectedPlan['email_server_type']}"
                         style="height: 22px; width: 25px; display: inline-block; margin-right: 5px;"
                    ></div>
                    <span id="email-server">{$LANG['aa']['product']['email_server'][{$selectedPlan['email_server_type']}] } ({$selectedPlan['email_server_name']})</span>
                  {/if}
              {else}
                <div id="email-server-icon"
                     style="display: none; height: 22px; width: 25px;"
                     class="server-icon"
                ></div>
                <span id="email-server" class="text-none">{{$LANG['general']['none']}}</span>
              {/if}
          </div>
        </div>
      </div>
    </div>
    <div class="pa-col">
      <div class="pa-form-row">
        <div id="account-config" class="pa-form-control pa-form-control--full">
            {foreach $selectedPlan['hosting_account_config'] as $config}
              <div class="account-config plan-settings-cell-value" style="margin-bottom: 6px;">
                <span class="plan-settings plan-settings--hosting-config"
                      id="{$config['name']}">{$LANG['aa']['product']['server']['config']['key'][{$config['name']}]}:
                </span>
                {if $config['type'] === 'text'}
                  <span style="margin-left: 4px;" id="{$config['value']}">{if $config['value'] !== null}{$config['value']|capitalize}{else}-{/if}</span>
                {elseif $config['type'] === 'select'}
                  {if !empty({$LANG['aa']['product']['server']['config']['value'][{$config['value']}]})}
                    <span style="margin-left: 4px;"
                          id="{$config['value']}">{$LANG['aa']['product']['server']['config']['value'][{$config['value']}]}</span>
                  {else}
                    <span style="margin-left: 4px;" id="{$config['value']}">{if $config['value'] !== null}{$config['value']|capitalize}{else}-{/if}</span>
                  {/if}
                {elseif $config['type'] === 'checkbox'}
                  {if $config['value'] === true}
                    <div class="icon"
                         data-icon="checkIcon"
                         style="height: 20px; width: 20px; display: inline-block;"
                    ></div>
                  {elseif $config['value'] === false || $config['value'] === null}
                    <div class="icon"
                         data-icon="uncheckIcon"
                         style="height: 20px; width: 20px; display: inline-block;"
                    ></div>
                  {/if}
                {elseif $config['type'] === 'textarea' && $config['value'] !== null}
                  <div class="textarea-display">
                    <pre style="background-color: #f4f4f4; padding: 4px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; white-space: pre-wrap; word-wrap: break-word; margin-bottom: 0; font-size: 11px;"><code>{$config['value']}</code></pre>
                  </div>
                {/if}
              </div>
            {/foreach}
        </div>
      </div>
    </div>
  </div>
  <!-- CUSTOM HOSTING ACCOUNT CONFIG -->
  <div id="hosting-account-config-section" class=" advanced-mode-section pa-row pa-row--3" style="display: none;">
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file='ui/cards/card-row-label.tpl'
          title=$LANG['aa']['product']['module_settings']['hosting_account_configuration']['title']
          subtitle=$LANG['aa']['product']['module_settings']['hosting_account_configuration']['subtitle']
          }
      </div>
    </div>

    <div class="pa-col">
      <div class="pa-form-row">
        <div id="hosting-account-config" class="pa-form-control pa-form-control--full">
            {foreach $selectedPlan['hosting_account_config'] as $config}
              <div class="account-config plan-settings-cell-value" style="margin-bottom: 6px;">
                <label class="plan-settings plan-settings--hosting-config"
                       for="{$config['name']}"
                >{$LANG['aa']['product']['server']['config']['key'][{$config['name']}]}:</label>
                  {if $config['type'] === 'text' || $config['type'] === 'select'}
                    <input type="text" class="form-control input-inline input-inline--hosting-config"
                           id="{$config['name']}"
                           data-field-name="{$config['name']}"
                           value="{$config['value']}"
                    />
                  {elseif $config['type'] === 'textarea'}
                     <textarea class="form-control input-inline input-inline--hosting-config"
                          id="{$config['name']}"
                          data-field-name="{$config['name']}"
                          rows="4"
                      >{$config['value']}</textarea>
                  {elseif $config['type'] === 'checkbox'}
                      {include
                      file="ui/switcher.tpl"
                      id="{$config['name']}switcher"
                      name="{$config['name']}_switcher_field"
                      class="pa-switch--right"
                      checked=($config['value'] == '1')
                      }
                    <input type="hidden"
                           data-field-name="{$config['name']}"
                           id="{$config['name']}_config_field"
                           value="{if $config['value'] == '1'}1{else}0{/if}"/>
                  {elseif $config['type'] === 'textarea'}
                    <span>asdasdas</span>
                  {/if}
              </div>
            {/foreach}
        </div>
      </div>
    </div>
    <div class="pa-col">
      <div class="pa-form-row">
      </div>
    </div>
  </div>
  <div id="configurable-options-section" class="advanced-mode-section pa-row pa-row--3" style="display: none;">
    <div class="pa-col">
      <div class="pa-form-row">
          {include
          file='ui/cards/card-row-label.tpl'
          title=$LANG['aa']['product']['module_settings']['configurable_options']['title']
          subtitle=$LANG['aa']['product']['module_settings']['configurable_options']['subtitle']
          }
      </div>
    </div>
    <div class="pa-col configurable-options-content-wrapper">
      <div class="configurable-options-layout">
        <div class="configurable-options-left">
            {foreach $selectedPlan['configurable_options'] as $option}
              <div class="configurable-option-row">
                <div class="configurable-option-name">
                  <strong>{$option}|{$LANG['aa']['product']['server']['config']['key'][{$option}]}</strong>
                </div>
              </div>
            {/foreach}
        </div>
      </div>
      <div class="configurable-options-button-container">
        <button
                id="generate-configurable-option-button"
                class="btn btn-success configurable-options-button-full-width"
                style="font-size: 13px;"
        >{$LANG['aa']['button']['generate_configurable_options']}</button>
      </div>
    </div>
  </div>
{/block}

{block name="advanced"}
<div class="text-right">
<a href="#" id="toggle-advanced-mode" class="btn btn-link btn-sm">
    {$LANG['aa']['product']['module_settings']['mode']['advanced']}
</a>
</div>
{/block}

