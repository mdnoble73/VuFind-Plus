{strip}
	<div id="similarSeriesNoveList" class="striped div-striped">
		{foreach from=$similarSeries item=series name="recordLoop"}
			<div class="novelist-similar-item">
				<div class="novelist-similar-item-header notranslate"><a href="/Search/Results?lookfor={$series.title|escape:url}">{$series.title}</a> by <a class="notranslate" href="Search/Results?lookfor={$series.author|escape:url}">{$series.author}</a></div>
				<div class="novelist-similar-item-reason">
					{$series.reason}
				</div>
			</div>
		{/foreach}
	</div>
{/strip}