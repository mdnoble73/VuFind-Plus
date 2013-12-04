{strip}
<form id="addForm" action="{$path}/MyResearch/HoldMultiple" class="">
	<div>
		<div class='selectAllControls hidden-phone'>
			{* Make sure to trigger the proper events when selecting and deselecting *}
			<a href="#" onclick="$('.titleSelect').not(':checked').trigger('click').attr('checked', true);return false;">Select All</a> /
			<a href="#" onclick="$('.titleSelect:checked').trigger('click').attr('checked', false);return false;">Deselect All</a>
		</div>

		{foreach from=$recordSet item=record name="recordLoop"}
			<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
				{* This is raw HTML -- do not escape it: *}
				{$record}
			</div>
		{/foreach}

		<div class='selectAllControls hidden-phone'>
			<a href="#" onclick="$('.titleSelect').not(':checked').trigger('click').attr('checked', true);return false;">Select All</a> /
			<a href="#" onclick="$('.titleSelect:checked').trigger('click').attr('checked', false);return false;">Deselect All</a>
		</div>

		<div class="hidden-phone">
			{if $showHoldButton}
				<input type="hidden" name="type" value="hold" />
				<input type="submit" class="btn" name="placeHolds" value="Request Selected Titles" class="requestSelectedItems" />
			{/if}
		</div>
	</div>
</form>
{/strip}
<script type="text/javascript">
	$(document).ready(function() {literal} { {/literal}
		VuFind.ResultsList.loadStatusSummaries();
		//VuFind.ResultsList.loadSeriesInfo();
		//VuFind.ResultsList.initializeDescriptions();
		{if $user}
		//doGetSaveStatuses();
		{/if}
	{literal} }); {/literal}
</script>
