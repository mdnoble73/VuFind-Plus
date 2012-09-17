<script type="text/javascript">
$(document).ready(function() {literal} { {/literal}
  doGetStatusSummaries();
  doGetRatings();
  {if $user}
  doGetSaveStatuses();
  {/if}
{literal} }); {/literal}
</script>

<form id="addForm" action="{$path}/MyResearch/HoldMultiple">
	<div id="addFormContents">
		<div class='selectAllControls'>
		  {* Make sure to trigger the proper events when selecting and deselecting *}
		  <a href="#" onclick="$('.titleSelect').not(':checked').attr('checked', true).trigger('click').attr('checked', true);return false;">Select All</a> /
		  <a href="#" onclick="$('.titleSelect:checked').attr('checked', false).trigger('click').attr('checked', false);return false;">Deselect All</a>
		</div>
		{foreach from=$recordSet item=record name="recordLoop"}
		  <div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
		    {* This is raw HTML -- do not escape it: *}
		    {$record}
		  </div>
		{/foreach}
		
		<input type="hidden" name="type" value="hold" />
		<div class='selectAllControls'>
		  <a href="#" onclick="$('.titleSelect').not(':checked').attr('checked', true).trigger('click').attr('checked', true);return false;">Select All</a> /
		  <a href="#" onclick="$('.titleSelect:checked').attr('checked', false).trigger('click').attr('checked', false);return false;">Deselect All</a>
		</div>
		
		{if $enableMaterialsRequest}
		<div id="materialsRequestInfo">
    Can't find what you are looking for? Try our <a href="{$path}/MaterialsRequest/NewRequest">Materials Request Service</a>.</div>
    </div>
    {/if}
		
		{if !$enableBookCart}
		<input type="submit" name="placeHolds" value="Request Selected Items" class="requestSelectedItems"/>
		{/if}
	</div>
</form>

{if $showStrands}
{* Add tracking to strands based on the user search string.  Only track searches that have results. *}
{literal}
<script type="text/javascript">

//This code can actually be used anytime to achieve an "Ajax" submission whenever called
if (typeof StrandsTrack=="undefined"){StrandsTrack=[];}

StrandsTrack.push({
   event:"searched",
   searchstring: "{/literal}{$lookfor|escape:"url"}{literal}"
});

</script>
{/literal}
{/if}