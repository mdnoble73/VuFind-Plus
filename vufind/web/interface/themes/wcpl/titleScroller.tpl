<div id="list-{$wrapperId}">
	<div id="{$wrapperId}" class="titleScrollerWrapper">
		{if $scrollerTitle || $fullListLink}
		<div id="list-{$wrapperId}Header" class="titleScrollerHeader">
			<span class="listTitle resultInformationLabel">{if $scrollerTitle}{$scrollerTitle|escape:"html"}{/if}</span>
			{if $fullListLink}
			<a href='{$fullListLink}'><span class='seriesLink'>View as List</span></a>
			{/if}
		</div>
		{/if}
		<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
			<div class="leftScrollerButton enabled" onclick="{$scrollerVariable}.scrollToLeft();"></div>
			<div class="rightScrollerButton" onclick="{$scrollerVariable}.scrollToRight();"></div>
			<div class="scrollerBodyContainer">
				<div class="scrollerBody" style="display:none"></div>
				<div class="scrollerLoadingContainer">
					<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{$url}/interface/themes/{$theme}/images/loading_large.gif" alt="Loading..." />
				</div>
			</div>
			<div class="clearer"></div>
			<div id="titleScrollerSelectedTitle{$scrollerName}" class="titleScrollerSelectedTitle"></div>
			<div id="titleScrollerSelectedAuthor{$scrollerName}" class="titleScrollerSelectedAuthor"></div>
		</div>    
	</div>
</div>