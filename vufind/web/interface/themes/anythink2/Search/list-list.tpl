<script type="text/javascript">
{literal}
$(document).ready(function() { 
  doGetStatusSummaries();
  doGetRatings();
  {/literal}{if $user}{literal}
  doGetSaveStatuses();
  {/literal}{/if}{literal}
});
{/literal}
</script>

<form id="addForm" action="{$url}/MyResearch/HoldMultiple">
  <div id="addFormContents">
    {foreach from=$recordSet item=record name="recordLoop"}
      <div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
        {* This is raw HTML -- do not escape it: *}
        {$record}
      </div>
    {/foreach}

    {if !$enableBookCart}
    <input type="submit" name="placeHolds" value="Request Selected Items" class="requestSelectedItems"/>
    {/if}
  </div>
</form>

{if $showStrands}
{* Add tracking to strands based on the user search string.  Only track searches that have results. *}
<script type="text/javascript">
{literal}
//This code can actually be used anytime to achieve an "Ajax" submission whenever called
if (typeof StrandsTrack=="undefined"){StrandsTrack=[];}
StrandsTrack.push({
   event:"searched",
   searchstring: "{/literal}{$lookfor|escape:"url"}{literal}"
});
{/literal}
</script>
{/if}
