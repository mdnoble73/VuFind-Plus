{strip}
	<div id="groupedRecord{$summId|escape}" class="resultsList">

		{if isset($summExplain)}
			<div class="hidden" id="scoreExplanationValue{$summId|escape}">{$summExplain}</div>
		{/if}

		<div class="row">
			{if $showCovers}
				<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
					{if $user->disableCoverArt != 1}
						<a href="{$summUrl}">
							<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail{* img-responsive // shouldn't be needed *}" alt="{translate text='Cover Image'}">
						</a>
					{/if}
					{if $showRatings}
						{include file="GroupedWork/title-rating.tpl" ratingClass="" id=$summId ratingData=$summRating}
					{/if}

						<div class="visible-xs center-block">
{*
							{if !$hasHiddenFormats && count($relatedManifestations) != 1}
								*}
{*<div class="row">*}{*

								<div *}
{*class="col-xs-6"*}{*
 class="center-block" style="width: 120px;">
									<button class="hidethisdiv{$summId|escape} view-xs-button btn btn-info btn-sm btn-block" onclick="VuFind.showElementInPopup('Formats', '#relatedManifestationsValue{$summId|escape}')">View All Formats</button>
								</div>
								*}
{*</div>*}{*

							{/if}
*}

							{*<div class="row">*}
							<div {*class="col-xs-6"*} {*class="center-block" style="width: 120px;"*}>
								<button class="view-xs-button btn btn-info btn-sm btn-block" onclick="VuFind.showElementInPopup('Description', '#descriptionValue{$summId|escape}')">Description</button>
							</div>
							{*</div>*}
						</div>

				</div>
			{/if}

			<div class="{if !$showCovers}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">{* May turn out to be more than one situation to consider here *}
				{* Title Row *}
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

				{* Mobile buttons *}

				{*<div class="col-xs-3 col-xs-offset-9">*}
					{*<div class="visible-xs">*}
						{*{if !$hasHiddenFormats && count($relatedManifestations) != 1}*}
							{*<div>*}
								{*<button class="view-xs-button btn btn-info btn-xs" onclick="VuFind.showElementInPopup('Formats', '#relatedManifestationsValue{$summId|escape}')">Formats</button>*}
							{*</div>*}
						{*{/if}*}

						{*<div>*}
							{*<button class="view-xs-button btn btn-info btn-xs" onclick="VuFind.showElementInPopup('Description', '#descriptionValue{$summId|escape}')">Description</button>*}
						{*</div>*}
					{*</div>*}
				{*</div>*}




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
								{if count($indexedSeries) >= 5}
									{assign var=showMoreSeries value="true"}
								{/if}
								{foreach from=$indexedSeries item=seriesItem name=loop}
									<a href="{$path}/Search/Results?basicType=Series&lookfor=%22{$seriesItem|escape:"url"}%22">{$seriesItem|escape}</a><br>
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
								{if !empty($snippet.snippet)}<span class="quotestart">&#8220;</span>...{$snippet.snippet|highlight}...<span class="quoteend">&#8221;</span><br>{/if}
							</div>
						</div>
					{/foreach}
				{/if}


				{* Short Mobile Entry for Formats when there aren't hidden formats *}
				<div class="row visible-xs">

					{* Determine if there were hidden Formats for this entry *}
					{assign var=hasHiddenFormats value=false}
					{foreach from=$relatedManifestations item=relatedManifestation}
					{if $relatedManifestation.hideByDefault}
						{assign var=hasHiddenFormats value=true}
					{/if}
					{/foreach}

					{* If there weren't hidden formats, show this short Entry (mobile view only). The exception is single format manifestations, they
					   won't have any hidden formats and will be displayed *}
					{if !$hasHiddenFormats && count($relatedManifestations) != 1}
						<div class="hidethisdiv{$summId|escape} result-label col-xs-3">
							Formats
						</div>
						<div class="hidethisdiv{$summId|escape} result-value col-xs-9">
							<a href="#" onclick="$('#relatedManifestationsValue{$summId|escape},.hidethisdiv{$summId|escape}').toggleClass('hidden-xs');return false;">
								{implode subject=$relatedManifestations|@array_keys glue=", "}
							</a>
						</div>
					{/if}

				</div>

				{* Formats Section *}
				<div class="row">
					<div class="{if !$hasHiddenFormats && count($relatedManifestations) != 1}hidden-xs {/if}col-sm-12" id="relatedManifestationsValue{$summId|escape}">
						{* Hide Formats section on mobile view, unless there is a single format or a format has been selected by the user *}
						{* relatedManifestationsValue ID is used by the Formats button *}

						{include file="GroupedWork/relatedManifestations.tpl" id=$summId}

					</div>
				</div>

				{*<div class="col-xs-9 col-xs-offset-3">*}
				<div class="col-xs-12">
					<div class="visible-xs center-block">
						{if !$hasHiddenFormats && count($relatedManifestations) != 1}
							{*<div class="row">*}
								<div {*class="col-xs-6"*} class="center-block" style="width: 120px;">
									<button class="hidethisdiv{$summId|escape} view-xs-button btn btn-info btn-sm btn-block" onclick="VuFind.showElementInPopup('Formats', '#relatedManifestationsValue{$summId|escape}')">View All Formats</button>
								</div>
							{*</div>*}
						{/if}

						{*<div class="row">*}
{*							<div *}{*class="col-xs-6"*}{* class="center-block" style="width: 120px;">
								<button class="view-xs-button btn btn-info btn-sm btn-block" onclick="VuFind.showElementInPopup('Description', '#descriptionValue{$summId|escape}')">Description</button>
							</div>*}
						{*</div>*}
					</div>
				</div>

				{* Description Section *}
				<div class="row">
					{* Hide in mobile view *}
					<div class="result-value hidden-xs col-sm-12" id="descriptionValue{$summId|escape}">
						<br>
						{$summDescription|highlight|truncate_html:450:"..."}
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
						{include file='GroupedWork/result-tools-horizontal.tpl' id=$summId shortId=$shortId ratingData=$summRating recordUrl=$summUrl}
						{* TODO: id & shortId shouldn't be needed to be specified here, otherwise need to note when used.
							summTitle only used by cart div, which is disabled as of now. 12-28-2015 plb *}
					</div>
				</div>

			</div>

		</div>

{*
		<div class="resultActions row">
			<div class="col-xs-12 col-md-9 col-md-offset-3 col-lg-10 col-lg-offset-2">

				*}
{* Mobile buttons *}{*

				<div class="visible-xs">
					{if !$hasHiddenFormats && count($relatedManifestations) != 1}
						<button class="view-xs-button btn btn-info btn-xs" onclick="VuFind.showElementInPopup('Formats', '#relatedManifestationsValue{$summId|escape}')">Formats</button>
					{/if}

						<button class="view-xs-button btn btn-info btn-xs" onclick="VuFind.showElementInPopup('Description', '#descriptionValue{$summId|escape}')">Description</button>
				</div>

			{include file='GroupedWork/result-tools-horizontal.tpl' id=$summId shortId=$shortId ratingData=$summRating recordUrl=$summUrl}
			*}
{* TODO: id & shortId shouldn't be needed to be specified here, otherwise need to note when used.
			  summTitle only used by cart div, which is disabled as of now. 12-28-2015 plb *}{*

			</div>
		</div>
*}

		{if $summCOinS}<span class="Z3988" title="{$summCOinS|escape}" style="display:none">&nbsp;</span>{/if}
	</div>
{/strip}