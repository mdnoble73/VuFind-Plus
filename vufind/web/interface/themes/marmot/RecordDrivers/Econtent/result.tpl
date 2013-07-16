{strip}
<div id="record{$summId|escape}" class="resultsList">
	<div class="resultIndex">{$resultIndex}</div>
	<div class="selectTitle">
		<input type="checkbox" name="selected[econtentRecord{$summId|escape:"url"}]" class="titleSelect" id="selectedEcontentRecord{$summId|escape:"url"}" {if $enableBookCart}onclick="toggleInBag('econtentRecord{$summId|escape:"url"}', '{$summTitle|replace:'"':''|replace:'&':'&amp;'|escape:'javascript'}', this);"{/if} />&nbsp;
	</div>
	
	<div class="imageColumn"> 
		<div id='descriptionPlaceholder{$summId|escape}' style='display:none' class='descriptionTooltip'></div>
		{if !isset($user->disableCoverArt) ||$user->disableCoverArt != 1}	
			<a href="{$summUrl}" id="descriptionTrigger{$summId|escape:"url"}">
				<img src="{$bookCoverUrl}" class="listResultImage" alt="{translate text='Cover Image'}"/>
			</a>
		{/if}
		{include file="EcontentRecord/title-rating.tpl" ratingClass="" recordId=$summId shortId=$summShortId ratingData=$summRating}

	</div>

<div class="resultDetails">
	<div class="resultItemLine1">
	{if $summScore}({$summScore}) {/if}
	<a href="{$summUrl}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
	{if $summTitleStatement}
		<div class="searchResultSectionInfo">
			{$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
		</div>
		{/if}
	</div>

	{if $summISBN}
		<div class="resultSeries">
			<div class="series{$summISBN}"></div>
		</div>
	{/if}

	<div class="resultItemLine2">
		{if $summAuthor}
			{translate text='by'}&nbsp;
			{if is_array($summAuthor)}
				{foreach from=$summAuthor item=author}
					<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
				{/foreach}
			{else}
				<a href="{$path}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
			{/if}
			&nbsp;
		{/if}
 
		{if $summDate}{translate text='Published'} {$summDate.0|escape}{/if}
	</div>
	
	<div class="resultItemLine3">
		{if !empty($summSnippetCaption)}<b>{translate text=$summSnippetCaption}:</b>{/if}
		{if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span><br />{/if}
	</div>

	<div class="resultItemLine4">
	{if is_array($summFormats)}
		{strip}
		{foreach from=$summFormats item=format name=formatLoop}
			{if $smarty.foreach.formatLoop.index != 0}, {/if}
			<span class="icon {$format|lower|regex_replace:"/[^a-z0-9]/":""}"></span><span class="iconlabel">{translate text=$format}</span>
		{/foreach}
		{/strip}
	{else}
		<span class="icon {$summFormats|lower|regex_replace:"/[^a-z0-9]/":""}"></span><span class="iconlabel">{translate text=$summFormats}</span>
	{/if}
	</div>
	
	<div id = "holdingsEContentSummary{$summId|escape:"url"}" class="holdingsSummary">
		<div class="statusSummary" id="statusSummary{$summId|escape:"url"}">
			<span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
		</div>
	</div>
</div>

<div class="resultActions">
	{include file='EcontentRecord/result-tools.tpl' id=$summId shortId=$shortId summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
</div>


<script type="text/javascript">
	addIdToStatusList('{$summId|escape:"javascript"}', {if strcasecmp($source, 'OverDrive') == 0}'OverDrive'{else}'eContent'{/if}, '{$useUnscopedHoldingsSummary}');
	{if $summISBN}
	getSeriesInfo('{$summISBN}');
	{/if}
	$(document).ready(function(){literal} { {/literal}
		resultDescription('{$summId}','{$summId}', 'eContent');
	{literal} }); {/literal}
</script>

</div>
{/strip}