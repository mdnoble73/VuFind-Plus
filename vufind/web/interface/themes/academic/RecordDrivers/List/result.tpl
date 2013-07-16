{strip}
<div id="record{$summId|escape}">
	<div class="resultIndex">{$resultIndex}</div>
	<div class="selectTitle">
		<input type="checkbox" name="selected[{$summId|escape:"url"}]" id="selected{$summId|escape:"url"}" style="display:none" />&nbsp;
	</div>

	<div class="resultsList">
		{if $user->disableCoverArt != 1}
			<a href="{$path}/MyResearch/MyList/{$summShortId}" class="alignleft listResultImage">
				<img src="{img filename="lists.png"}" alt="{translate text='No Cover Image'}"/><br />
			</a>
		{/if}

		<div class="resultitem">
			<div class="resultItemLine1 title">
				<a href="{$path}/MyResearch/MyList/{$summShortId}" class="title">{if !$summTitle}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					<div class="searchResultSectionInfo">
						{$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
					</div>
				{/if}
			</div>

			<div class="resultItemLine2 description">
				{if $summDescription}
					<span class="resultLabel">{translate text='Description'}:</span><span class="resultValue">{$summDescription|truncate:500:"..."|highlight:$lookfor}</span>
				{/if}
			</div>

			<div class="resultItemLine3 numTitles">
				{if $summNumTitles}
					<span class="resultLabel">{translate text='Size'}:</span><span class="resultValue">{$summNumTitles} titles are in this list.</span>
				{/if}
			</div>
		</div>
	</div>
	<div class="clearer"></div>
</div>
{/strip}