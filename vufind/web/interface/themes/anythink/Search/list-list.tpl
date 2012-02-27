<script type="text/javascript">
$(document).ready(function() {literal} { {/literal}
  // doGetStatusSummaries();
//  doGetRatingsAnythink();
  {if $user}
  doGetSaveStatuses();
  {/if}
{literal} }); {/literal}
</script>

<form id="addForm" action="{$url}/MyResearch/HoldMultiple">
  <div id="addFormContents">
    {foreach from=$recordSet item=record name="recordLoop"}
      <div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}even{else}odd{/if} record{$smarty.foreach.recordLoop.iteration}">
        {* This is raw HTML -- do not escape it: *}
        {$record}
      </div>
    {/foreach}
    <input type="hidden" name="type" value="hold" />
    {if !$enableBookCart}
    <input type="submit" name="placeHolds" value="Request Selected Items" />
    {/if}
  </div>
</form>

{if $strandsAPID}
  {* Add tracking to strands based on the user search string.  Only track searches that have results. *}
  <script type="text/javascript">
  {literal}
  if (typeof StrandsTrack=="undefined"){StrandsTrack=[];}
  StrandsTrack.push({
     event:"searched",
     searchstring: "{/literal}{$lookfor|escape:"url"}{literal}"
  });
  {/literal}
  </script>
{/if}
