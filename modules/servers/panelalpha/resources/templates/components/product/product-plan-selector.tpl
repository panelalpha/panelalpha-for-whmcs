<select id="select-plan" name="configoption[1]" class="form-control">
    {foreach $plans as $plan}
      <option value="{$plan['id']}"
              data-instance_limit="{$plan['instance_limit']}"
              data-onboarding_type="{$plan['config']['onboarding']['method']}"
              data-onboarding_ask_for_domain="{$plan['config']['onboarding']['ask_for_domain']}"
              data-server_group="{$plan['server_group_name']}"
              data-server_assign_rule="{$plan['server_assign_rule']}"
              data-server_type="{$plan['server_type']}"
              data-account_config='{$plan['hosting_account_config_json']}'
              data-configurable-options='{$plan['configurable_options_json']}'
              {if $plan['dns_server_type']}
                data-dns_server_type="{$plan['dns_server_type']}"
                data-dns_server_name="{$plan['dns_server_name']}"
              {elseif $plan['dns_server_internal']}
                data-dns_server_internal="true"
              {/if}
              {if $plan['email_server_type']}
                data-email_server_type="{$plan['email_server_type']}"
                data-email_server_name="{$plan['email_server_name']}"
              {elseif $plan['email_server_internal']}
                data-email_server_internal="true"
              {/if}
              {if $plan['id'] == $product->configoption1}
                selected
              {/if}
      >{$plan['name']}</option>
    {/foreach}
</select>