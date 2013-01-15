<form name="addForm" action="">
<table style="width:752px; border-bottom:1px solid #eee;">
  <tr>
  {foreach from=$recordSet item=record name="recordLoop"}
   <td width="25%">{* This is raw HTML -- do not escape it: *}{$record}</td>
    {if (($smarty.foreach.recordLoop.iteration % 4) == 0) && (!$smarty.foreach.recordLoop.last)}</tr><tr>{/if}
  {/foreach}
  </tr>
</table>
</form>

<script type="text/javascript">
  doGetStatuses({literal}{{/literal}
    available: '<span class="available">{translate text='Available'}<\/span>',
    unavailable: '<span class="checkedout">{translate text='Checked Out'}<\/span>',
    unknown: '<span class="unknown">{translate text='Unknown'}<\/span>',
    reserve: '{translate text='on_reserve'}'
  {literal}}{/literal});
  {if $user}
  doGetSaveStatuses();
  {/if}
</script>
