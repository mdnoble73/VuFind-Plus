{strip}

	{* TODO: put this in a help pop-up
	<div class="alert alert-info">Novelist provides detailed suggestions for titles you might like if you enjoyed this book.  Suggestions are based on recommendations from librarians and other contributors.</div>
	*}
	<div id="similarTitlesNovelist" class="jcarousel ajax-carousel">
		<ul>
			{foreach from=$similarTitles item=similarTitle name="recordLoop"}
				<li{* class="novelist-similar-item"*}>
					<div class="novelist-similar-item-header notranslate">
						{if $similarTitle.fullRecordLink}
							<a href='{$similarTitle.fullRecordLink}'>{$similarTitle.title|removeTrailingPunctuation}</a>
						{else}
							{$similarTitle.title|removeTrailingPunctuation}
						{/if}
						&nbsp;by <a href="/Search/Results?lookfor={$similarTitle.author|escape:url}"
						            class="notranslate">{$similarTitle.author}</a>
					</div>
					<div class="novelist-similar-item-reason">
						{$similarTitle.reason}
					</div>
				</li>
			{/foreach}
		</ul>
	</div>
{/strip}