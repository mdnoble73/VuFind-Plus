<form id="addForm" action="{$path}/MyResearch/HoldMultiple">
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
	<div class='selectAllControls'>
		<a href="#" onclick="$('.titleSelect').not(':checked').attr('checked', true).trigger('click').attr('checked', true);return false;">Select All</a> /
		<a href="#" onclick="$('.titleSelect:checked').attr('checked', false).trigger('click').attr('checked', false);return false;">Deselect All</a>
	</div>
	
{if !$enableBookCart}
		<input type="hidden" name="type" value="hold" />
		<input type="submit" name="placeHolds" value="Request Selected Items" class="requestSelectedItems"/>
{/if}
</form>

<script type="text/javascript">
$(document).ready(function() {literal} { {/literal}
	doGetStatusSummaries();
	{if $user}
	doGetSaveStatuses();
	{/if}
	doGetRatings();
{literal} }); {/literal}
</script>
