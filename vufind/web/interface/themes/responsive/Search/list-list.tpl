{strip}
<form id="addForm" action="{$path}/MyAccount/HoldMultiple" class="">
	<div>
		{foreach from=$recordSet item=record name="recordLoop"}
			<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
				{* This is raw HTML -- do not escape it: *}
				{$record}
			</div>
		{/foreach}
	</div>
</form>
{/strip}

