<div id="list-{$wrapperId}" {if $display == 'false'}style="display:none"{/if} class="titleScroller singleTitleWidget">
	<div id="{$wrapperId}" class="titleScrollerWrapper singleTitleWidgetWrapper">
		<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
			<div class="scrollerBodyContainer">
				<div class="scrollerBody" style="display:none"></div>
				<div class="scrollerLoadingContainer">
					<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{img filename="loading_large.gif"}" alt="Loading..." />
				</div>
			</div>
			<div class="clearer"></div>
			<div id="titleScrollerSelectedTitle{$scrollerName}" class="titleScrollerSelectedTitle"></div>
			<div id="titleScrollerSelectedAuthor{$scrollerName}" class="titleScrollerSelectedAuthor"></div>
		</div>
	</div>
</div>