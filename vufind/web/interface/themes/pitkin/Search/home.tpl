{strip}
	<div class="searchHome">
		<div class="searchHomeContent">
			{if $widget}
				<div id="homePageLists">
					{include file='API/listWidgetTabs.tpl'}
				</div>
			{/if}

			<div class="bookletters-widget">
				<div class="bookletters-body">
					<script type="text/javascript">
						widget = "CNLWidget";
						sid = "7550";
						list  = "NLNF";
						rotate = "YES";
						fade = "NO";
						title = "YES";
					</script>
					<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>
				</div>
			</div>

			<div class="searchHomeForm">
				<div id='homeSearchLabel'>Search the {$librarySystemName} Catalog</div>
				{include file="Search/searchbox.tpl"}
			</div>


		</div>
	</div>
{/strip}