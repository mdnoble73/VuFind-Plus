<div id="record{$summId|escape}">
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
		<div class="resultItemLine1">
			<a href="{$path}/MyResearch/MyList/{$summShortId}" class="title">{if !$summTitle}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
			{if $summTitleStatement}
				<div class="searchResultSectionInfo">
					{$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
				</div>
			{/if}
		</div>
	
		<div class="resultItemLine2">
			{if $summDescription}
				{$summDescription|truncate:500:"..."|highlight:$lookfor}
			{/if}
		</div>
		
		<div class="resultItemLine3">
			{if $summNumTitles}
				{$summNumTitles} titles are in this list.
			{/if}
		</div>
				
	</div>
	<div class="clearer"></div>
</div>
