{strip}
<div id="record{$summId|escape}" class="resultsList row">
	<div class="col-md-1 hidden-phone">
		<div class="resultIndex">{$resultIndex}</div>
		<div class="selectTitle">
			<input type="checkbox" name="selected[{$summId|escape:"url"}]" id="selected{$summId|escape:"url"}" style="display:none" />&nbsp;
		</div>
	</div>

	<div class="imageColumn col-md-2 text-center">
		{if $user->disableCoverArt != 1}
			<a href="{$path}/MyResearch/MyList/{$summShortId}" class="alignleft listResultImage">
				<img src="{img filename="lists.png"}" alt="{translate text='No Cover Image'}"/><br />
			</a>
		{/if}
	</div>

	<div class="col-md-9">
		<div class="row">
			{if $summScore}({$summScore}) {/if}
			<strong>
				<a href="{$path}/MyResearch/MyList/{$summShortId}" class="title">{if !$summTitle}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					<div class="searchResultSectionInfo">
						&nbsp;-&nbsp;{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
					</div>
				{/if}
			</strong>
		</div>

		<div class="row">
			<div class="resultDetails col-md-9">
				{if $summDescription}
					<div class="row">
						<div class="result-label col-md-3">{translate text='Description'}:</div>
						<div class="col-md-9 result-value">
							{$summDescription|truncate:500:"..."|highlight:$lookfor}
						</div>
					</div>
				{/if}

				{if $summNumTitles}
					<div class="row">
						<div class="result-label col-md-3">{translate text='Size'}:</div>
						<div class="col-md-9 result-value">
							{$summNumTitles} titles are in this list.
						</div>
					</div>
				{/if}
			</div>

			<div class="resultActions col-md-3">
				{include file='List/result-tools.tpl' id=$summId shortId=$shortId summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
			</div>
		</div>
	</div>
</div>
{/strip}