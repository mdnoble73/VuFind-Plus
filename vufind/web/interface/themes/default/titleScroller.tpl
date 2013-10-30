{strip}
<div id="list-{$wrapperId}" {if $display == 'false'}style="display:none"{/if} class="titleScroller {if $widget->coverSize == 'medium'}mediumScroller{/if}">
	<div id="{$wrapperId}" class="titleScrollerWrapper">
		{if $scrollerTitle || $Links}
		<div id="list-{$wrapperId}Header" class="titleScrollerHeader">
			<span class="listTitle resultInformationLabel">{if $scrollerTitle}{$scrollerTitle|escape:"html"}{/if}</span>
			
      
			{if $Links}
				{foreach from=$Links item=link}
					<div class='linkTab'>
						<a href='{$link->link}'><span class='seriesLink'>{$link->name}</span></a>
					</div>
				{/foreach}
			{else if $fullListLink}
				<div class='linkTab' style="float:right">
					<a href='{$fullListLink}'><span class='seriesLink'>View All</span></a>
				</div>
			{/if}
			
		</div>
		{/if}
		<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
			<div class="leftScrollerButton enabled" onclick="{$scrollerVariable}.scrollToLeft();"></div>
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

{/strip}
<script type="text/javascript">
	$("#list-"+ '{$wrapperId}'+" .leftScrollerButton").button(
					{literal}
					{icons: {primary:'ui-icon-triangle-1-w'}, text: false}
					{/literal}
	);
	$("#list-" + '{$wrapperId}'+" .rightScrollerButton").button(
					{literal}
					{icons: {primary:'ui-icon-triangle-1-e'}, text: false}
					{/literal}
	);
</script>