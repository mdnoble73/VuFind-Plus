<div id="record{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultsList">
	<div class="imageColumn">
			{if $user->disableCoverArt != 1}
			<div id='descriptionPlaceholder{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}' style='display:none'></div>
			<a href="{$path}/Record/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}">
			<img src="{$bookCoverUrl}" class="listResultImage" alt="{translate text='Cover Image'}"/>
			</a>
			{/if}
			{* Place hold link *}
			<div class='requestThisLink' id="placeHold{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" style="display:none">
				<a href="{$path}/Record/{$summId|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
			</div>
	</div>

	<div class="resultDetails">
		<div class="resultItemLine1">
		{if $summScore}({$summScore}) {/if}
		<a href="{$path}/Record/{$summId|escape:"url"}/Home?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
		{if $summTitleStatement}
			<div class="searchResultSectionInfo">
				{$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
			</div>
			{/if}
		</div>

		<div class="resultItemLine2">
			{if $summAuthor}
				{translate text='by'}
				{if is_array($summAuthor)}
					{foreach from=$summAuthor item=author}
						<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
					{/foreach}
				{else}
					<a href="{$path}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
				{/if}
			{/if}

			{if $summDate}{translate text='Published'} {$summDate.0|escape}{/if}
		</div>

		<div class="resultItemLine3">
			{if !empty($summSnippetCaption)}<b>{translate text=$summSnippetCaption}:</b>{/if}
			{if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span><br />{/if}
		</div>

		{if is_array($summFormats)}
			{foreach from=$summFormats item=format}
				<span class="iconlabel" >{translate text=$format}</span>&nbsp;
			{/foreach}
		{else}
			<span class="iconlabel">{translate text=$summFormats}</span>
		{/if}
	</div>

	<script type="text/javascript">
		$(document).ready(function(){literal} { {/literal}
			resultDescription('{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}','{$summId}');
		{literal} }); {/literal}
	</script>
</div>