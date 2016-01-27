{strip}
<div id="groupedRecord{$summId|escape}" class="resultsList">
	<div class="row">
	{*{assign var="displayCovers" value=false}*}
	{*{if $action != 'SuggestedTitles' || ($showCovers && $action == 'SuggestedTitles')}*}
	{if $showCovers}
		{*{assign var="displayCovers" value=true}*}
	<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
		{if $user->disableCoverArt != 1}
		{*<div class='descriptionContent{$summShortId|escape}' style='display:none'>{$summDescription}</div>*}
			<a href="{$summUrl}">
				<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail{* img-responsive // shouldn't be needed *}" alt="{translate text='Cover Image'}">
			</a>
		{/if}
		{if $showRatings}
		{include file="GroupedWork/title-rating.tpl" ratingClass="" id=$summId ratingData=$summRating}
		{/if}
	</div>
	{/if}

	{if isset($summExplain)}
		<div class="hidden" id="scoreExplanationValue{$summId|escape}">{$summExplain}</div>
	{/if}

	<div class="{if !$showCovers}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">{* May turn out to be more than one situation to consider here *}
		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="{$summUrl}" class="result-title notranslate">{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}</a>
				{if $summTitleStatement}
					&nbsp;-&nbsp;{$summTitleStatement|removeTrailingPunctuation|highlight|truncate:180:"..."}
				{/if}
				{if isset($summScore)}
					&nbsp;(<a href="#" onclick="return VuFind.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
				{/if}
			</div>
		</div>

		{if $summAuthor}
			<div class="row">
				<div class="result-label col-xs-3">Author: </div>
				<div class="result-value col-xs-9 notranslate">
					{if is_array($summAuthor)}
						{foreach from=$summAuthor item=author}
							<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight}</a>
						{/foreach}
					{else}
						<a href="{$path}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight}</a>
					{/if}
				</div>
			</div>
		{/if}

		{assign var=indexedSeries value=$recordDriver->getIndexedSeries()}
		{if $summSeries || $indexedSeries}
			<div class="series{$summISBN} row">
				<div class="result-label col-xs-3">Series: </div>
				<div class="result-value col-xs-9">
					{if $summSeries}
					<a href="{$path}/GroupedWork/{$summId}/Series">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}<br>
					{/if}
					{if $indexedSeries}
						{assign var=showMoreSeries value=false}
						{if count($indexedSeries) >= 5}
							{assign var=showMoreSeries value=true}
						{/if}
						{foreach from=$indexedSeries item=seriesItem name=loop}
							<a href="{$path}/Search/Results?basicType=Series&lookfor=%22{$seriesItem|escape:"url"}%22">{$seriesItem|escape}</a><br/>
							{if $showMoreSeries && $smarty.foreach.loop.iteration == 2}
								<a onclick="$('#moreSeries_{$summId}').show();$('#moreSeriesLink_{$summId}').hide();" id="moreSeriesLink_{$summId}">More Series...</a>
								<div id="moreSeries_{$summId}" style="display:none">
							{/if}
						{/foreach}
						{if $showMoreSeries}
							</div>
						{/if}
					{/if}
				</div>
			</div>
		{/if}

		{if $summEdition}
			<div class="row">
				<div class="result-label col-xs-3">Edition: </div>
				<div class="result-value col-xs-9">
					{$summEdition}
				</div>
			</div>
		{/if}

		{if $summPublisher}
			<div class="row">
				<div class="result-label col-xs-3">Publisher: </div>
				<div class="result-value col-xs-9">
					{$summPublisher}
				</div>
			</div>
		{/if}

		{if $summPubDate}
			<div class="row">
				<div class="result-label col-xs-3">Pub. Date: </div>
				<div class="result-value col-xs-9">
					{$summPubDate|escape}
				</div>
			</div>
		{/if}

		{if $summLanguage}
			<div class="row">
				<div class="result-label col-xs-3">Language: </div>
				<div class="result-value col-xs-9">
					{if is_array($summLanguage)}
						{', '|implode:$summLanguage}
					{else}
						{$summLanguage}
					{/if}
				</div>
			</div>
		{/if}

		{if $summSnippets}
			{foreach from=$summSnippets item=snippet}
				<div class="row">
					<div class="result-label col-xs-3">{translate text=$snippet.caption}: </div>
					<div class="result-value col-xs-9">
						{if !empty($snippet.snippet)}<span class="quotestart">&#8220;</span>...{$snippet.snippet|highlight}...<span class="quoteend">&#8221;</span><br />{/if}
					</div>
				</div>
			{/foreach}
		{/if}

		<div class="row">
			<div class="col-xs-12">
				{include file="GroupedWork/relatedManifestations.tpl" id=$summId}
			</div>
		</div>

		<div class="row">
			<div class="col-xs-12 result-value" id="descriptionValue{$summId|escape}">{$summDescription|highlight|truncate_html:450:"..."}</div>
		</div>


	</div>

	</div>

		<div class="resultActions row">
			{*{include file='GroupedWork/result-tools-horizontal.tpl' id=$summId shortId=$shortId summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}*}
			{include file='GroupedWork/result-tools-horizontal.tpl' id=$summId shortId=$shortId ratingData=$summRating recordUrl=$summUrl}
			{* TODO: id & shortId shouldn't be needed to be specified here, otherwise need to note when used.
			  summTitle only used by cart div, which is disabled as of now. 12-28-2015 plb
			 *}
		</div>
	{*</div>*}

	{if $summCOinS}<span class="Z3988" title="{$summCOinS|escape}" style="display:none">&nbsp;</span>{/if}
</div>
{/strip}