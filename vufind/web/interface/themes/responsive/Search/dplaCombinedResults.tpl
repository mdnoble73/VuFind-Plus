{strip}
	<div id="dplaSearchResults">
		{foreach from=$searchResults item=result name="recordLoop"}
			<div class="dplaResult row result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
				{if $showCovers}
					<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
						{if $result.object}
							<img src="{$result.object}" class="listResultImage img-thumbnail img-responsive"/>
						{/if}
					</div>
				{/if}
				<div class="{if !$showCovers}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">
					<div class="row">
						<div class="col-xs-12">
							<span class="result-index">{$smarty.foreach.recordLoop.iteration})</span>&nbsp;
							<a href="{$result.link}" class="result-title notranslate">
								{if !$result.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$result.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
							</a>
						</div>
					</div>

					{if $result.format}
						<div class="row">
							<div class="result-label col-tn-3">{translate text='Format'}:</div>
							<div class="col-tn-9 result-value">{$result.format|escape}</div>
						</div>
					{/if}

					{if $result.publisher}
						<div class="row">
							<div class="result-label col-tn-3">{translate text='Publisher'}:</div>
							<div class="col-tn-9 result-value">{$result.publisher|escape}</div>
						</div>
					{/if}

					{if $result.date}
						<div class="row">
							<div class="result-label col-tn-3">{translate text='Date'}:</div>
							<div class="col-tn-9 result-value">{$result.date|escape}</div>
						</div>
					{/if}
				</div>
			</div>
		{/foreach}
	</div>
{/strip}