{strip}
<div id="groupedRecord{$summId|escape}" class="resultsList row">
	<div class="col-xs-12 col-sm-3 col-md-3 col-lg-2 text-center">
		{if $user->disableCoverArt != 1}
		{*<div class='descriptionContent{$summShortId|escape}' style='display:none'>{$summDescription}</div>*}
			<a href="{$summUrl}">
				<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}" />
			</a>
		{/if}
		{if $showComments}
		{include file="GroupedWork/title-rating.tpl" ratingClass="" recordId=$summId shortId=$summShortId ratingData=$summRating}
		{/if}
	</div>

	{if isset($summExplain)}
		<div class="hidden" id="scoreExplanationValue{$summId|escape}">{$summExplain}</div>
	{/if}

	<div class="col-xs-12 col-sm-9 col-md-9 col-lg-10">
		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="{$summUrl}" class="result-title notranslate">{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					&nbsp;-&nbsp;{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
				{/if}
				{if isset($summScore)}
					(<a href="#" onclick="return VuFind.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
				{/if}
			</div>
		</div>

		{if $summAuthor}
			<div class="row">
				<div class="result-label col-xs-3">Author: </div>
				<div class="col-xs-9 result-value  notranslate">
					{if is_array($summAuthor)}
						{foreach from=$summAuthor item=author}
							<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
						{/foreach}
					{else}
						<a href="{$path}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
					{/if}
				</div>
			</div>
		{/if}

		{if $summSeries}
			<div class="series{$summISBN} row">
				<div class="result-label col-xs-3">Series: </div>
				<div class="col-xs-9 result-value">
					<a href="{$path}/GroupedWork/{$summId}/Series">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
				</div>
			</div>
		{/if}

		{if $summEdition}
			<div class="row">
				<div class="result-label col-xs-3">Edition: </div>
				<div class="col-xs-9 result-value">
					{$summEdition}
				</div>
			</div>
		{/if}

		{if $summPublisher}
			<div class="row">
				<div class="result-label col-xs-3">Publisher: </div>
				<div class="col-xs-9 result-value">
					{$summPublisher}
				</div>
			</div>
		{/if}

		{if $summPubDate}
			<div class="row">
				<div class="result-label col-xs-3">Pub. Date: </div>
				<div class="col-xs-9 result-value">
					{$summPubDate}
				</div>
			</div>
		{/if}

		{if $summLanguage}
			<div class="row">
				<div class="result-label col-xs-3">Language: </div>
				<div class="col-xs-9 result-value">
					{$summLanguage}
				</div>
			</div>
		{/if}

		<div class="row well-small">
			<div class="col-xs-12">
				{include file="GroupedWork/relatedManifestations.tpl" id=$summId}
			</div>
		</div>

		<div class="row well-small">
			<div class="col-xs-12 result-value" id="descriptionValue{$summId|escape}">{$summDescription|truncate_html:450}</div>
		</div>

		<div class="resultActions row">
			{include file='GroupedWork/result-tools-horizontal.tpl' id=$summId shortId=$shortId summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
		</div>
	</div>

	{if $summCOinS}<span class="Z3988" title="{$summCOinS|escape}" style="display:none">&nbsp;</span>{/if}
</div>
{/strip}