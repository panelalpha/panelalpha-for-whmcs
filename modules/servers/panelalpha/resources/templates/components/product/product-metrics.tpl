<table class="form metric-settings" width="100%" border="0" cellspacing="2" cellpadding="3" id="tblMetric"
       style="margin-top: 10px">
  <tbody>
  <tr>
    <td width="150">{{$LANG['aa']['product']['module_settings']['metric_billing']['title']}}</td>
    <td class="fieldarea">
      <div class="config" id="metricsConfig">
        <div class="row">
            {foreach $usageItems as $item}
                {include file="components/product/product-metrics-item.tpl" item=$item}
            {/foreach}
        </div>
      </div>
    </td>
  </tr>
  </tbody>
</table>