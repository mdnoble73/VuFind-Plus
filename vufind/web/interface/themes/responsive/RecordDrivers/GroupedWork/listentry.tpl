{strip}
	<div id="groupedRecord{$summId|escape}" class="resultsList" data-order="{$resultIndex}">
		<a name="record{$summId|escape:"url"}"></a>
		<div class="row">
		{if $showCovers}
		<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
			<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail{* img-responsive*}" alt="{translate text='Cover Image'}">
			{include file="GroupedWork/title-rating.tpl" ratingClass="" id=$summId ratingData=$summRating showNotInterested=false}
		</div>
		{/if}
		<div class="{if !$showCovers}col-xs-10 col-sm-10 col-md-10 col-lg-11{else}col-xs-7 col-sm-7 col-md-7 col-lg-9{/if}">
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					<a href="{$summUrl}" class="result-title notranslate">{$summTitle|removeTrailingPunctuation|escape}</a><br>
					{if $summTitleStatement}
						&nbsp;-&nbsp;{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight}
					{/if}
				</div>
			</div>

			{if $summAuthor}
				<div class="row">
					<div class="result-label col-md-3">Author: </div>
					<div class="result-value col-md-9 notranslate">
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

			{if $summSeries}
				<div class="series{$summISBN} row">
					<div class="result-label col-md-3">Series: </div>
					<div class="result-value col-md-9">
						<a href="{$path}/GroupedWork/{$summId}/Series">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
					</div>
				</div>
			{/if}

			{if $listEntryNotes}
				<div class="row">
					<div class="result-label col-md-3">Notes: </div>
					<div class="result-value col-md-9">
						{$listEntryNotes}
					</div>
				</div>
			{/if}

			<div class="row">
				<div class="result-label col-xs-12 hidden-md hidden-lg">Description: </div>
				<div class="result-value col-xs-12" id="descriptionValue{$summId|escape}">{$summDescription|truncate_html:450:"..."}</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{include file="GroupedWork/relatedManifestations.tpl" id=$summId}
				</div>
			</div>
		</div>

		<div class="col-sm-2 col-md-2 col-lg-1">
			{if $listEditAllowed}
			<div class="btn-group-vertical" role="group">
					<a href="{$path}/MyAccount/Edit?id={$summId|escape:"url"}{if !is_null($listSelected)}&amp;list_id={$listSelected|escape:"url"}{/if}" class="btn btn-default">{translate text='Edit'}</a>
					{* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
					<a href="{$path}/MyAccount/MyList/{$listSelected|escape:"url"}?delete={$summId|escape:"url"}" onclick="return confirm('Are you sure you want to delete this?');" class="btn btn-default">{translate text='Delete'}</a>

{* manual ordering of user lists. plb 5-27-2015
				{if $userSort}
					<div class="btn-group" role="group">
						<button class="btn btn-default dropdown-toggle" type="button" id="sortOrder{$resultIndex}" data-toggle="dropdown" aria-expanded="true">
							Order &nbsp;
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="sortOrder{$resultIndex}">
							<li>
								<a>
								<input class="form-control" type="number" size="4" name="weight[{$summId|escape:"url"}]" id="weight_{$summId|escape:"url"}"{if 1} value="{$resultIndex}"{/if}>
								</a>
							</li>
						</ul>
					</div>
				{/if} *}

			</div>

			{/if}
		</div>

		</div>

		<div class="resultActions row">
			{include file='GroupedWork/result-tools-horizontal.tpl' id=$summId shortId=$shortId summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
		</div>

	</div>
{/strip}