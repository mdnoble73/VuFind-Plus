{strip}
<div class="searchHome">
	<div class="searchHomeContent">
		{if $widget}
			<div id="homePageLists">
				{include file='API/listWidgetTabs.tpl'}
			</div>
		{/if}

	<script type="text/javascript">
	widget = "NLWidget";
	sid = "7342";
	list = "NLBLD";
	rotate = "NO";
	fade = "NO";
	title = "NO";
	</script><script type="text/javascript" src="http://www.booksite.com/widget/widgetloader.js"></script><!--Fiction Best Sellers--><script type="text/javascript">
	widget = "NLWidget";
	sid = "7342";
	list = "NLBFH";
	rotate = "YES";
	fade = "YES";
	title = "NO";
	</script><script type="text/javascript" src="http://www.booksite.com/widget/widgetloader.js"></script><!--Nonfiction Best Sellers--><script type="text/javascript">
	widget = "NLWidget";
	sid = "7342";
	list = "NLBNFH";
	rotate = "YES";
	fade = "YES";
	title = "NO";
	</script><script type="text/javascript" src="http://www.booksite.com/widget/widgetloader.js"></script><!--Teen Scene--><script type="text/javascript">
	widget = "NLWidget";
	sid = "7342";
	list = "NLTS";
	rotate = "YES";
	fade = "YES";
	title = "NO";
	</script><script type="text/javascript" src="http://www.booksite.com/widget/widgetloader.js"></script>

		<div class="searchHomeForm">
			<div id='homeSearchLabel'>Search the {$librarySystemName} Catalog</div>
			{include file="Search/searchbox.tpl"}
		</div>


	</div>
</div>
{/strip}