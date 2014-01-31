{strip}
	<h4>Similar Series</h4>
	<div id="similarSeriesNoveList" class="striped div-striped">
		{foreach from=$similarSeries item=series name="recordLoop"}
			<div class="">
				{* This is raw HTML -- do not escape it: *}
				<h3><a href="/Search/Results?lookfor={$series.title|escape:url}">{$series.title}</a> by <a href="Search/Results?lookfor={$series.author|escape:url}">{$series.author}</a></h3>
				<div class="reason">
					{$series.reason}
				</div>
			</div>
		{/foreach}
	</div>
{/strip}