{strip}
<div id="list-{$wrapperId}" {if $display == 'false'}style="display:none"{/if} class="titleScroller row">
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
			{elseif strlen($fullListLink) > 0}
				<div class='linkTab' style="float:right">
					<a href='{$fullListLink}'><span class='seriesLink'>View All</span></a>
				</div>
			{/if}

		</div>
		{/if}
		<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
			<div class="leftScrollerButton enabled btn" onclick="{$scrollerVariable}.scrollToLeft();"><i class="glyphicon glyphicon-chevron-left"></i></div>
			<div class="rightScrollerButton btn" onclick="{$scrollerVariable}.scrollToRight();"><i class="glyphicon glyphicon-chevron-right"></i></div>
			<div class="scrollerBodyContainer">
				<div class="scrollerBody" style="display:none"></div>
				<div class="scrollerLoadingContainer">
					<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{img filename="loading_large.gif"}" alt="Loading..." />
				</div>
			</div>
			<div class="clearer"></div>
			<div id="titleScrollerSelectedTitle{$scrollerName}" class="titleScrollerSelectedTitle notranslate"></div>
			<div id="titleScrollerSelectedAuthor{$scrollerName}" class="titleScrollerSelectedAuthor notranslate"></div>
		</div>    
	</div>
</div>

{/strip}
