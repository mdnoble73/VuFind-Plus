{strip}
<div id="list-{$wrapperId}" {if $display == 'false'}style="display:none"{/if} class="verticalTitleScroller {if $widget->coverSize == 'medium'}mediumScroller{/if}">
	<div id="{$wrapperId}" class="titleScrollerWrapper">
		{if $scrollerTitle}
		<div id="list-{$wrapperId}Header" class="titleScrollerHeader">
			<span class="listTitle resultInformationLabel">{if $scrollerTitle}{$scrollerTitle|escape:"html"}{/if}</span>
		</div>
		{/if}
		<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
			<div class="scrollerButtonUp btn btn-primary" onclick="{$scrollerVariable}.scrollToLeft();"><i class="glyphicon glyphicon-chevron-up"></i></div>
			<div class="scrollerBodyContainer">
				<div class="scrollerBody" style="display:none"></div>
				<div class="scrollerLoadingContainer">
					<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{img filename="loading_large.gif"}" alt="Loading..." />
				</div>
			</div>
			<div class="clearer"></div>
			<div class="scrollerButtonDown btn btn-primary" onclick="{$scrollerVariable}.scrollToRight();"><i class="glyphicon glyphicon-chevron-down"></i></div>
		</div>
	</div>
</div>
{/strip}