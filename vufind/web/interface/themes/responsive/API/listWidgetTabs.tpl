{strip}
<div id="listWidget{$widget->id}" class="ui-tabs listWidget {$widget->style}">
	{if count($widget->lists) > 1}
		{if !isset($widget->listDisplayType) || $widget->listDisplayType == 'tabs'}
			{* Display Tabs *}
			<ul class="nav nav-tabs" role="tablist">
				{foreach from=$widget->lists item=list name=listWidgetList}
					{assign var="active" value=$smarty.foreach.listWidgetList.first}
					{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $user) || ($list->displayFor == 'notLoggedIn' && !$user)}
					<li {if $active}class="active"{/if}>
						<a href="#list-{$list->name|regex_replace:'/\W/':''|escape:url}" role="tab" data-toggle="tab" data-index="{$smarty.foreach.listWidgetList.index}">{$list->name}</a>
					</li>
					{/if}
				{/foreach}
			</ul>
		{else}
			<div class='listWidgetSelector'>
				<select class="availableLists" id="availableLists{$widget->id}" onchange="changeSelectedList();return false;">
					{foreach from=$widget->lists item=list}
					{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $user) || ($list->displayFor == 'notLoggedIn' && !$user)}
					<option value="list-{$list->name|regex_replace:'/\W/':''|escape:url}">{$list->name}</option>
					{/if}
					{/foreach}
				</select>
			</div>
		{/if}
	{/if}
	<div class="tab-content">
	{assign var="listIndex" value="0"}
	{foreach from=$widget->lists item=list name=listWidgetList}
		{assign var="active" value=$smarty.foreach.listWidgetList.first}
		{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $user && $user->disableRecommendations == 0) || ($list->displayFor == 'notLoggedIn' && !$user)}
			{assign var="listIndex" value=$listIndex+1}
			{assign var="listName" value=$list->name|regex_replace:'/\W/':''|escape:url}
			{assign var="scrollerName" value="$listName"}
			{assign var="wrapperId" value="$listName"}
			{assign var="scrollerVariable" value="listScroller$listName"}
			{if $list->links}
				{assign var="Links" value=$list->links}
			{else}
				{assign var="fullListLink" value=$list->fullListLink()}
			{/if}

			{if count($widget->lists) == 1}
				{assign var="scrollerTitle" value=$list->name}
			{/if}
			{if !isset($widget->listDisplayType) || $widget->listDisplayType == 'tabs'}
				{assign var="display" value="true"}
			{else}
				{if $listIndex == 1}
					{assign var="display" value="true"}
				{else}
					{assign var="display" value="false"}
				{/if}
			{/if}
			{if $widget->style == 'horizontal'}
				{include file='titleScroller.tpl'}
			{elseif $widget->style == 'vertical'}
				{include file='verticalTitleScroller.tpl'}
			{elseif $widget->style == 'single-with-next'}
				{include file='singleWithNextTitleWidget.tpl'}
			{else}
				{include file='singleTitleWidget.tpl'}
			{/if}
		{/if}
	{/foreach}
	</div>

	<script type="text/javascript">
		{* Load title scrollers *}

		{foreach from=$widget->lists item=list}
			{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $user) || ($list->displayFor == 'notLoggedIn' && !$user)}
				var listScroller{$list->name|regex_replace:'/\W/':''|escape:url};
			{/if}
		{/foreach}
		{literal}

		$(document).ready(function(){
			{/literal}{if count($widget->lists) > 1 && (!isset($widget->listDisplayType) || $widget->listDisplayType == 'tabs')}{literal}
			$('#listWidget{/literal}{$widget->id}{literal} a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
				//alert(e.target); // activated tab
				//alert(e.relatedTarget); // previous tab
				showList($(e.target).data('index'));
			});
			{/literal}
			{/if}
			{assign var=index value=0}
			{foreach from=$widget->lists item=list name=listLoop}
		 		{assign var="listName" value=$list->name|regex_replace:'/\W/':''|escape:url}
				{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $user) || ($list->displayFor == 'notLoggedIn' && !$user)}
					{if $index == 0}
						listScroller{$listName} = new TitleScroller('titleScroller{$listName}', '{$listName}', 'list{$listName}', {if $widget->autoRotate==1}true{else}false{/if}, '{$widget->style}');
						listScroller{$listName}.loadTitlesFrom('{$path}/Search/AJAX?method=GetListTitles%26id={$list->source|escape:url}%26scrollerName={$listName}%26coverSize={$widget->coverSize}%26showRatings={$widget->showRatings}', false);
					{/if}
					{assign var=index value=$index+1}
				{/if}
			{/foreach}

			{literal}
			// if mobile device, add swipe event

			// Widget Specific Events
			$('#listWidget{/literal}{$widget->id}{literal} .scrollerBodyContainer')
							.css('border','2px solid blue')
							.touchwipe({
				// Horizontal style
				wipeLeft : function(){
					console.log('Swipe Left Event triggered'); // debugging
//					alert('Swipe Left!');
					{/literal}{$scrollerVariable}{literal}.swipeToLeft();
				},
				wipeRight: function() {
					console.log('Swipe Right Event triggered'); // debugging
//					alert('Swipe Right!');
					{/literal}{$scrollerVariable}{literal}.swipeToRight();
				}
			});
//			alert('swipe events loaded');
			// end of if mobile device
		});

		$(window).bind('beforeunload', function(e) {
			{/literal}
			{if !isset($widget->listDisplayType) || $widget->listDisplayType == 'tabs'}
				$('#listWidget{$widget->id}').tabs({literal}{ selected: 0 }{/literal});
			{else}
				var availableListsSelector = $("#availableLists{$widget->id}");
				var availableLists = availableListsSelector[0];
				var selectedOption = availableLists.options[0];
				var selectedValue = selectedOption.value;
				$('#availableLists{$widget->id}').val(selectedValue);
			{/if}
			{literal}
		});

		function changeSelectedList(){
			//Show the correct list
			var availableListsSelector = $("#availableLists{/literal}{$widget->id}{literal}");
			var availableLists = availableListsSelector[0];
			var selectedOption = availableLists.options[availableLists.selectedIndex];

			var selectedList = selectedOption.value;
			$("#listWidget{/literal}{$widget->id}{literal} > .titleScroller").hide();
			$("#" + selectedList).show();
			showList(availableLists.selectedIndex);
		}

		function showList(listIndex){
			{/literal}
			{assign var=index value=0}
			{foreach from=$widget->lists item=list name=listLoop}
				{assign var="listName" value=$list->name|regex_replace:'/\W/':''|escape:url}
				{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $user) || ($list->displayFor == 'notLoggedIn' && !$user)}
					{if $index == 0}
						if (listIndex == {$index}){literal}{{/literal}
							listScroller{$listName}.activateCurrentTitle();
						{literal}}{/literal}
					{else}
						else if (listIndex == {$index}){literal}{{/literal}
							if (listScroller{$listName} == null){literal}{{/literal}
								listScroller{$listName} = new TitleScroller('titleScroller{$listName}', '{$listName}', 'list{$listName}', {if $widget->autoRotate==1}true{else}false{/if}, '{$widget->style}');
								listScroller{$listName}.loadTitlesFrom('{$path}/Search/AJAX?method=GetListTitles%26id={$list->source|escape:url}%26scrollerName={$listName}%26coverSize={$widget->coverSize}%26showRatings={$widget->showRatings}', false);
							{literal}}else{{/literal}
								listScroller{$listName}.activateCurrentTitle();
							{literal}}{/literal}
						{literal}}{/literal}
					{/if}
					{assign var=index value=$index+1}
				{/if}
			{/foreach}
			{literal}
		}
		{/literal}
	</script>
</div>
{/strip}
