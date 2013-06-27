{strip}
<div id="record{$summId|escape}">
	<div class="resultIndex">{$resultIndex}</div>
	<div class="selectTitle">
		<input type="checkbox" name="selected[{$summId|escape:"url"}]" id="selected{$summId|escape:"url"}" style="display:none" />&nbsp;
	</div>

	{if $user->disableCoverArt != 1}			
	<div class="imageColumn">
		<a href="{$path}/MyResearch/MyList/{$summShortId}">
		<img src="{img filename="lists.png"}" class="alignleft listResultImage" alt="{translate text='No Cover Image'}"/><br />
		</a>
	</div>
	{/if}
	
	<div class="resultDetails"> 
		<div class="resultItemLine1 title">
			<a href="{$path}/MyResearch/MyList/{$summShortId}" class="title">{if !$summTitle}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}</a>
			{if $summTitleStatement}
				<div class="searchResultSectionInfo">
					{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
				</div>
			{/if}
		</div>
	
		<div class="resultItemLine2 description">
			{if $summDescription}
				{$summDescription|truncate:500:"..."|highlight:$lookfor}
			{/if}
		</div>
		
		<div class="resultItemLine3 numTitles">
			{if $summNumTitles}
				{$summNumTitles} titles are in this list.
			{/if}
		</div>
				
	</div>
	<div class="clearer"></div>
</div>
{/strip}