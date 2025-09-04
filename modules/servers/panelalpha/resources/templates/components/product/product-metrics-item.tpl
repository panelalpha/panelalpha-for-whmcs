<div class="col-md-4">
  <div class="metric">
    <div>
      <span>{$item->metric|replace:'_':' '|capitalize}</span>
      <span class="toggle">
          {include
          file="ui/toggle.tpl"
          id="metric_{$item->metric}"
          class="switch"
          checked="{if $item->is_hidden == '0'}1{else}0{/if}"
          name="metric[{$item->metric}]"
          hiddenValue=$item->is_hidden
          size="mini"
          inverted=true
          valueOn="0"
          valueOff="1"
          }
      </span>
    </div>
    <span>
      <a href="#" class="btn-link open-metric-pricing" style="font-size: 13px;"
         data-metric="{$item->metric}">{{$LANG['aa']['product']['module_settings']['metric_billing']['configure_pricing']}}</a>
    </span>
  </div>
</div>