{foreach from=$prospectorResults item=prospectorResult name="recordLoop"}
	<div class='result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration} row'>
		{if $showCovers}
			<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
				{if $disableCoverArt != 1 && $prospectorResult.cover}
					<a href="{$prospectorResult.link}">
						<img src="{$prospectorResult.cover}" class="listResultImage img-thumbnail" alt="{translate text='Cover Image'}">
					</a>
				{/if}
			</div>
		{/if}

		<div class="{if !$showCovers}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$smarty.foreach.recordLoop.iteration})</span>&nbsp;
					<a href="{$prospectorResult.link}" class="result-title notranslate">
						{if !$prospectorResult.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$prospectorResult.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
					</a>
				</div>
			</div>

			{if $prospectorResult.author}
				<div class="row">
					<div class="result-label col-tn-3">{translate text='Author'}:</div>
					<div class="col-tn-9 result-value">{$prospectorResult.author|escape}</div>
				</div>
			{/if}

			{if $prospectorResult.pubDate}
				<div class="row">

					<div class="result-label col-tn-3">Published: </div>
					<div class="col-tn-9 result-value">
						{$prospectorResult.pubDate}
					</div>
				</div>
			{/if}
		</div>
	</div>
{/foreach}
