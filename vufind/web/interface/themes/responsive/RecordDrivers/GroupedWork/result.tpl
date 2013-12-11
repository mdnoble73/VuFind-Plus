{strip}
<div id="groupedRecord{$summId|escape}" class="resultsList row-fluid">
	<div class="imageColumn span3">
		<div class="row-fluid">
			<div class="selectTitle hidden-phone span4">
				<label for="selected{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultIndex checkbox"><strong>{$resultIndex}</strong>
					<input type="checkbox" class="titleSelect" name="selected[{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}]" id="selected{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" {if $enableBookCart}onclick="toggleInBag('{$summId|escape}', '{$summTitle|replace:'"':''|replace:'&':'and'|escape:'javascript'}', this);"{/if} />&nbsp;
				</label>
			</div>
			{/strip}
			<div class="span7 text-center">
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

	<div class="span9">
		<div class="row-fluid">
			{if isset($summScore)}({$summScore}) {/if}
			<strong>
				<a href="{$summUrl}" class="title">{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					&nbsp;-&nbsp;{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
				{/if}
			</strong>
		</div>

		<div class="row-fluid">
			<div class="resultDetails span9">
				{if $summAuthor}
					<div class="row-fluid">
						<div class="result-label span3">Author: </div>
						<div class="span9 result-value">
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
					<div class="series{$summISBN} row-fluid">
						<div class="result-label span3">Series: </div>
						<div class="span9 result-value">{$summSeries.seriesTitle}{if $summSeries.volume} volume {$summSeries.volume}{/if}</div>
					</div>
				{/if}

				<div class="row-fluid well-small">
					<div class="span12">
						{include file="GroupedWork/relatedManifestations.tpl" id=$summId}
					</div>
				</div>

				<div class="row-fluid well-small">
					<div class="span12 result-value" id="descriptionValue{$summId|escape}">{$summDescription}</div>
				</div>
			</div>

			<div class="resultActions span3">
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