{strip}
	<div>
		{if $recordDriver->getPrimaryAuthor()}
			<div class="row">
				<div class="result-label col-md-3">Author: </div>
				<div class="col-md-9 result-value notranslate">
					<a href="{$path}/Author/Home?author={$recordDriver->getPrimaryAuthor()|escape:"url"}">{$recordDriver->getPrimaryAuthor()|highlight:$lookfor}</a>
				</div>
			</div>
		{/if}
		{if $recordDriver->getSeries()}
			<div class="series{$summISBN} row">
				<div class="result-label col-md-3">Series: </div>
				<div class="col-md-9 result-value">
					{assign var=summSeries value=$recordDriver->getSeries()}
					<a href="{$path}/Search/Results?lookfor={$summSeries.seriesTitle|urlencode}">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
				</div>
			</div>
		{/if}
		{if $recordDriver->getDescription()}
			<div class="row">
				<div class="col-sm-12">
					<span class="result-label">Description: </span>
				</div>
				<div class="col-sm-12">
					{$recordDriver->getDescription()|stripTags:'<b><p><i><em><strong><ul><li><ol>'}{*Leave unescaped because some syndetics reviews have html in them *}
				</div>
			</div>
		{/if}
	</div>
{/strip}