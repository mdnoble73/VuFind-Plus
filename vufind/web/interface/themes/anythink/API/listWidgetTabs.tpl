<div id="listWidget{$widget->id}" class="cf listWidget">
	{if count($widget->lists) > 1}
		{if !isset($widget->listDisplayType) || $widget->listDisplayType == 'tabs'}
			{* Display Tabs *}
		  <ul>
				{foreach from=$widget->lists item=list}
				{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $user) || ($list->displayFor == 'notLoggedIn' && !$user)}
		  	<li><a href="#list-{$list->name|regex_replace:'/\W/':''|escape:url}">{$list->name}</a></li>
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
  
  {assign var="listIndex" value="0"}
  {foreach from=$widget->lists item=list}
  	{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $user && $user->disableRecommendations == 0) || ($list->displayFor == 'notLoggedIn' && !$user)}
  	  {assign var="listIndex" value=$listIndex+1}
  		{assign var="listName" value=$list->name|regex_replace:'/\W/':''|escape:url}
  		{assign var="scrollerName" value="$listName"}
			{assign var="wrapperId" value="$listName"}
			{assign var="scrollerVariable" value="listScroller$listName"}
			{assign var="fullListLink" value=$list->fullListLink}
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
			{if $widget->showMultipleTitles == 1}
				{include file=titleScroller.tpl}
			{else}
				{include file=singleTitleWidget.tpl}
			{/if}
  	{/if}
  {/foreach}
  
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
      $('#listWidget{/literal}{$widget->id}{literal}').tabs({ selected: 0 });
      {/literal}
      {/if}
      {assign var=index value=0}
      {foreach from=$widget->lists item=list name=listLoop}
     		{assign var="listName" value=$list->name|regex_replace:'/\W/':''|escape:url}
      	{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $user) || ($list->displayFor == 'notLoggedIn' && !$user)}
      		{if $index == 0}
	      	  listScroller{$listName} = new TitleScroller('titleScroller{$listName}', '{$listName}', 'list{$listName}', {if $widget->showTitleDescriptions==1}true{else}false{/if}, '{$widget->onSelectCallback}', {if $widget->autoRotate==1}true{else}false{/if});
			  	  listScroller{$listName}.loadTitlesFrom('{$url}/Search/AJAX?method=GetListTitles&id={$list->source}&scrollerName={$listName}', false);
			  	{/if}
		  	  {assign var=index value=$index+1}
	    	{/if}
    		
      {/foreach}

      {if !isset($widget->listDisplayType) || $widget->listDisplayType == 'tabs'}
      $('#listWidget{$widget->id}').bind('tabsshow', function(event, ui) {literal}{
      	showList(ui.index);
      });
      {/literal}{/if}{literal}
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
				  			listScroller{$listName} = new TitleScroller('titleScroller{$listName}', '{$listName}', 'list{$listName}', {if $widget->showTitleDescriptions==1}true{else}false{/if}, '{$widget->onSelectCallback}');
					  		listScroller{$listName}.loadTitlesFrom('{$url}/Search/AJAX?method=GetListTitles&id={$list->source}&scrollerName={$listName}', false);
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