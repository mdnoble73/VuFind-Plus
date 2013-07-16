{strip}
<div class="searchHome">
	<div class="searchHomeContent">
		{if $widget}
		<div id="homePageLists">{include file='API/listWidgetTabs.tpl'}</div>
		{/if}

		{* Daily Pick *}
		<script type="text/javascript">
		widget = "CNLWidget";
		sid = "7342";
		group="current";
		list  = "NLBLD";
		group = "current";
		rotate = "YES";
		fade = "YES";
		title = "YES";
		</script>
		<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>

		{* New Fiction *}
		<script type="text/javascript">
		widget = "CNLWidget";
		sid = "7342";
		group="current";
		list  = "NLNF";
		group = "current";
		rotate = "YES";
		fade = "YES";
		title = "YES";
		</script>
		<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>

		{* New Nonfiction *}
		<script type="text/javascript">
		widget = "CNLWidget";
		sid = "7342";
		group="current";
		list  = "NLNON";
		group = "current";
		rotate = "YES";
		fade = "YES";
		title = "YES";
		</script>
		<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>

		{* Teen Scene *}
		<script type="text/javascript">
		widget = "CNLWidget";
		sid = "7342";
		group="current";
		list  = "NLTS";
		group = "current";
		rotate = "YES";
		fade = "YES";
		title = "YES";
		</script>
		<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>

		{* eBooks & eAudiobooks *}
		<script type="text/javascript">
		widget = "CNLWidget";
		sid = "7342";
		group="EB13";
		list  = "CNL1";
		group = "EB13";
		rotate = "YES";
		fade = "YES";
		title = "YES";
		</script>
		<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>

		<div class="searchHomeForm">
			<div id='homeSearchLabel'>Search the {$librarySystemName} Catalog</div>
			{include file="Search/searchbox.tpl"}
		</div>


	</div>
</div>
{/strip}
