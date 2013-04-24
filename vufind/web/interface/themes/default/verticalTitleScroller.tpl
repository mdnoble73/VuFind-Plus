{strip}
<div id="list-{$wrapperId}" {if $display == 'false'}style="display:none"{/if} class="verticalTitleScroller">
	<div id="{$wrapperId}" class="titleScrollerWrapper">
		{if $scrollerTitle}
		<div id="list-{$wrapperId}Header" class="titleScrollerHeader">
			<span class="listTitle resultInformationLabel">{if $scrollerTitle}{$scrollerTitle|escape:"html"}{/if}</span>
		</div>
		{/if}
		<div id="titleScroller{$scrollerName}" class="titleScrollerBody">
			<div class="scrollerButtonUp" onclick="{$scrollerVariable}.scrollToLeft();">Up</div>
			<div class="scrollerBodyContainer">
				<div class="scrollerBody" style="display:none"></div>
				<div class="scrollerLoadingContainer">
					<img id="scrollerLoadingImage{$scrollerName}" class="scrollerLoading" src="{img filename="loading_large.gif"}" alt="Loading..." />
				</div>
			</div>
			<div class="clearer"></div>
			<div class="scrollerButtonDown" onclick="{$scrollerVariable}.scrollToRight();">Down</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$("#list-"+ '{$wrapperId}'+" .scrollerButtonUp").button(
	{literal}
					{icons: {primary:'ui-icon-triangle-1-n'}, text: false}
	{/literal}
	);
	$("#list-" + '{$wrapperId}'+" .scrollerButtonDown").button(
					{literal}
					{icons: {primary:'ui-icon-triangle-1-s'}, text: false}
					{/literal}
	);
</script>
{/strip}