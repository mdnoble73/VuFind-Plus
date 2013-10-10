{* Main Listing *}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div class="row-fluid">
	{* Narrow Search Options *}
	<div id="sidebar" class="span3">
		<div class="sidegroup well">
			{if $recordCount}
				<label for="sort"><strong>{translate text='Sort By'}</strong></label>

				<select id="sort" name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
					{foreach from=$sortList item=sortData key=sortLabel}
						<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc}</option>
					{/foreach}
				</select>
			{/if}
		</div>

		{if $sideRecommendations}
			{foreach from=$sideRecommendations item="recommendations"}
				{include file=$recommendations}
			{/foreach}
		{/if}
	</div>
	{* End Narrow Search Options *}
	
	<div id="main-content" class="span9">
		<div id="searchInfo">
			{* Recommendations *}
			{if $topRecommendations}
				{foreach from=$topRecommendations item="recommendations"}
					{include file=$recommendations}
				{/foreach}
			{/if}

			{* Listing Options *}
			<div class="resulthead">
				{if $recordCount}
					{translate text="Showing"}
					<b>{$recordStart}</b> - <b>{$recordEnd}</b>
					{translate text='of'} <b>{$recordCount}</b>
					{if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>,{/if}
				{/if}
				<span class="hidden-phone">
					,&nbsp;{translate text='query time'}: {$qtime}s
				</span>

				{if $spellingSuggestions}
					<br /><br /><div class="correction"><strong>{translate text='spell_suggest'}</strong>:<br/>
					{foreach from=$spellingSuggestions item=details key=term name=termLoop}
						{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="{$path}/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
					{/foreach}
					</div>
				{/if}
			</div>
			{* End Listing Options *}

			{if $subpage}
				{include file=$subpage}
			{else}
				{$pageContent}
			{/if}

			{if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
			
			<div class="searchtools well">
				<strong>{translate text='Search Tools'}:</strong>
				<a href="{$rssLink|escape}"><span class="silk feed">&nbsp;</span>{translate text='Get RSS Feed'}</a>
				<a href="{$path}/Search/Email" onclick="getLightbox('Search', 'Email', null, null, '{translate text="Email this"}'); return false;"><span class="silk email">&nbsp;</span>{translate text='Email this Search'}</a>
				{if $savedSearch}<a href="{$path}/MyResearch/SaveSearch?delete={$searchId}"><span class="silk delete">&nbsp;</span>{translate text='save_search_remove'}</a>{else}<a href="{$path}/MyResearch/SaveSearch?save={$searchId}"><span class="silk add">&nbsp;</span>{translate text='save_search'}</a>{/if}
				<a href="{$excelLink|escape}"><span class="silk table_go">&nbsp;</span>{translate text='Export To Excel'}</a>
			</div>
		</div>
		{* End Main Listing *}
		
	</div>
</div>
