{strip}
	<h4>Similar Titles</h4>
	<div id="similarTitlesNovelist" class="striped div-striped">
		{foreach from=$similarTitles item=similarTitle name="recordLoop"}
			<div class="">
				{* This is raw HTML -- do not escape it: *}
				<h3>{if $similarTitle.fullRecordLink}<a href='{$similarTitle.fullRecordLink}'>{/if}{$similarTitle.title}{if $similarTitle.fullRecordLink}</a>{/if}
					by <a href="/Search/Results?lookfor={$similarTitle.author|escape:url}">{$similarTitle.author}</a></h3>
				<div class="reason">
					{$similarTitle.reason}
				</div>
			</div>
		{/foreach}
	</div>
{/strip}