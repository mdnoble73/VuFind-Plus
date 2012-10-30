{strip}
<div class="searchHome">
	<div class="searchHomeContent">
		{if $widget}
			<div id="homePageLists">
				{include file='API/listWidgetTabs.tpl'}
			</div>
		{/if}
		
		
		<script type="text/javascript">
		widget = "CNLWidget";
		sid = "5808";
		list  = "CNL2";
		rotate = "YES";
		fade = "NO";
		title = "YES";
		</script>
		<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>
		
		<script type="text/javascript">
		widget = "CNLWidget";
		sid = "5808";
		list  = "NLNON";
		rotate = "YES";
		fade = "NO";
		title = "YES";
		
		</script>
		<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>
		
		<script type="text/javascript">
		widget = "CNLWidget";
		sid = "5808";
		list  = "NLNF";
		rotate = "YES";
		fade = "NO";
		title = "YES";
		
		</script>
		<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>
		
		<script type="text/javascript">
		widget = "CNLWidget";
		sid = "5808";
		list  = "NLAUDIO";
		rotate = "YES";
		fade = "NO";
		title = "YES";
		
		</script>
		<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>
		
		<script type="text/javascript">
		widget = "CNLWidget";
		sid = "5808";
		list  = "NLDVD";
		rotate = "YES";
		fade = "NO";
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