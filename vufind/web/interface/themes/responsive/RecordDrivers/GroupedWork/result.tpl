{strip}
<div id="groupedRecord{$summId|escape}" class="resultsList row">
	<div class="imageColumn col-md-3">
		<div class="row">
			<div class="selectTitle hidden-phone col-md-4">
				<label for="selected{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultIndex checkbox"><strong>{$resultIndex}</strong>
					<input type="checkbox" class="titleSelect" name="selected[{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}]" id="selected{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" {if $enableBookCart}onclick="toggleInBag('{$summId|escape}', '{$summTitle|replace:'"':''|replace:'&':'and'|escape:'javascript'}', this);"{/if} />&nbsp;
				</label>
			</div>
			{/strip}
			<div class="col-md-7 text-center">
				{if $user->disableCoverArt != 1}
					{*<div class='descriptionContent{$summShortId|escape}' style='display:none'>{$summDescription}</div>*}
					<a href="{$summUrl}">
						<img src="{$bookCoverUrlMedium}"
						     class="listResultImage img-polaroid {*descriptionTrigger*}"
						     alt="{translate text='Cover Image'}"
						     {*data-record_id="{$summId}"
						     data-source="VuFind"
						     data-content_class=".descriptionContent{$summShortId|escape}"*}/>
					</a>
				{/if}
				{strip}
				{include file="Record/title-rating.tpl" ratingClass="" recordId=$summId shortId=$summShortId ratingData=$summRating}
			</div>
		</div>
	</div>

	{if isset($summExplain)}
		<div class="hidden" id="scoreExplanationValue{$summId|escape}">{$summExplain}</div>
	{/if}

	<div class="col-md-9">
		<div class="row">
			{if isset($summScore)}
				(<a href="#" onclick="return VuFind.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
			{/if}
			<strong>
				<a href="{$summUrl}" class="title">{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					&nbsp;-&nbsp;{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
				{/if}
			</strong>
		</div>

		<div class="row">
			<div class="resultDetails col-md-9">
				{if $summAuthor}
					<div class="row">
						<div class="result-label col-md-3">Author: </div>
						<div class="col-md-9 result-value">
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
						<div class="result-label col-md-3">Series: </div>
						<div class="col-md-9 result-value">{$summSeries.seriesTitle}{if $summSeries.volume} volume {$summSeries.volume}{/if}</div>
					</div>
				{/if}

				<div class="row well-small">
					<div class="col-md-12">
						{include file="GroupedWork/relatedManifestations.tpl" id=$summId}
					</div>
				</div>

				<div class="row well-small">
					{if strlen($summDescription) > 300}
						<div class="col-md-12 result-value" id="descriptionValue{$summId|escape}">{$summDescription|truncate_html:300}
						<a href='#' onclick='$("#descriptionValue{$summId|escape}").html($("#fullSummary{$summId|escape}").html())'>More</a>
						</div>
						<div class="hidden" id="fullSummary{$summId|escape}">{$summDescription}</div>
					{else}
						<div class="col-md-12 result-value" id="descriptionValue{$summId|escape}">{$summDescription}</div>
					{/if}
				</div>

			</div>

			<div class="resultActions col-md-3">
				{include file='GroupedWork/result-tools.tpl' id=$summId shortId=$shortId summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
			</div>
		</div>
	</div>

	{*
	<script type="text/javascript">
		/* VuFind.ResultsList.addIdToStatusList('{$summId|escape}', 'VuFind', '{$useUnscopedHoldingsSummary}'); */
		{if $summISBN}
		VuFind.ResultsList.addGroupedIdToSeriesList('{$summId}');
		{/if}
	</script>
	*}
</div>
{/strip}