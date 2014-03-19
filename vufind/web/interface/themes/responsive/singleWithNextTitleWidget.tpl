<div id="list-{$wrapperId}" {if $display == 'false'}style="display:none"{/if} class="titleScroller singleTitleWithNextWidget {if $widget->coverSize == 'medium'}mediumScroller{/if} {if $widget->showRatings}scrollerWithRatings{/if}">
	<div id="{$wrapperId}" class="titleScrollerWrapper singleTitleWidgetWrapper">
		<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
			<div class="rightScrollerButton" onclick="{$scrollerVariable}.scrollToRight();"></div>
			<div class="scrollerBodyContainer">
				<div class="scrollerBody" style="display:none"></div>
				<div class="scrollerLoadingContainer">
					<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{img filename="loading_large.gif"}" alt="Loading..." />
				</div>
			</div>
			<div class="clearer"></div>
			{if $widget->showTitle}
				<div id="titleScrollerSelectedTitle{$scrollerName}" class="titleScrollerSelectedTitle"></div>
			{/if}
			{if $widget->showAuthor}
				<div id="titleScrollerSelectedAuthor{$scrollerName}" class="titleScrollerSelectedAuthor"></div>
			{/if}
		</div>
	</div>
</div>
<script type="text/javascript">
	$("#list-" + '{$wrapperId}'+" .rightScrollerButton").button(
					{literal}
					{icons: {primary:'ui-icon-triangle-1-e'}, text: false}
					{/literal}
	);
</script>