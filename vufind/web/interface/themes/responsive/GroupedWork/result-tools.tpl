{strip}
	<div class="text-center row">
		{if $showFavorites == 1}
			<button onclick="return VuFind.GroupedWork.showSaveToListForm(this, '{$recordDriver->getPermanentId()|escape}');" class="btn btn-sm addtolistlink">{translate text='Add to favorites'}</button>
		{/if}
	</div>
	<div class="text-center row">
		{include file="GroupedWork/share-tools.tpl"}

	</div>
{/strip}