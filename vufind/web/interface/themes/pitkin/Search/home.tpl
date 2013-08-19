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
						group="current";
						list  = "NLNF";
						group = "current";
						rotate = "YES";
						fade = "NO";
						title = "New Fiction";
					</script>

					<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>
				</div>
			</div>

			<div class="bookletters-widget">
				<div class="bookletters-body">
					<script type="text/javascript">
						widget = "CNLWidget";
						sid = "7550";
						group="current";
						list  = "NLNON";
						group="current";
						rotate = "YES";
						fade = "NO";
						title = "New Non Fiction";
					</script>
					<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>
				</div>
			</div>

			<div class="bookletters-widget">
				<div class="bookletters-body">
					<script type="text/javascript">
						widget = "CNLWidget";
						sid = "7550";
						group="current";
						list  = "NLTS";
						group="current";
						rotate = "YES";
						fade = "NO";
						title = "Teens";
					</script>
					<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>
				</div>
			</div>

			<div class="bookletters-widget">
				<div class="bookletters-body">
					<script type="text/javascript">
						widget = "CNLWidget";
						sid = "7550";
						group="current";
						list  = "NLGC";
						group="current";
						rotate = "YES";
						fade = "NO";
						title = "Picture Books";
					</script>
					<script type="text/javascript" src="http://library.booksite.com/widgetloader.js"></script>
				</div>
			</div>

			<div class="bookletters-widget">
				<div class="bookletters-body">
					<script type="text/javascript">
						widget = "CNLWidget";
						sid = "7550";
						group="current";
						list  = "NLCC";
						group = "current";
						rotate = "YES";
						fade = "NO";
						title = "Chapter Books";
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