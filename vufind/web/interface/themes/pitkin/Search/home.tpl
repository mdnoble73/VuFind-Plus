{strip}
	<div class="searchHome">
		<div class="searchHomeContent">
			{if $widget}
				<div id="homePageLists">
					{include file='API/listWidgetTabs.tpl'}
				</div>
			{/if}

			<div class="bookletters-widget">
				<div class="bookletters-title">New Fiction</div>
				<div class="bookletters-body">
					<script type="text/javascript">
						widget = "CNLWidget";
						sid = "7550";
						group="current";
						list  = "NLNF";
						group = "current";
						rotate = "YES";
						fade = "NO";
						title = "NO";
					</script>

					<script type="text/javascript" src="https://library.booksite.com/widgetloader.js"></script>
				</div>
			</div>

			<div class="bookletters-widget">
				<div class="bookletters-title">New Nonfiction</div>
				<div class="bookletters-body">
					<script type="text/javascript">
						widget = "CNLWidget";
						sid = "7550";
						group="current";
						list  = "NLNON";
						group="current";
						rotate = "YES";
						fade = "NO";
						title = "NO";
					</script>
					<script type="text/javascript" src="https://library.booksite.com/widgetloader.js"></script>
				</div>
			</div>

			<div class="bookletters-widget">
				<div class="bookletters-title">Teen Scene</div>
				<div class="bookletters-body">
					<script type="text/javascript">
						widget = "CNLWidget";
						sid = "7550";
						group="current";
						list  = "NLTS";
						group="current";
						rotate = "YES";
						fade = "NO";
						title = "NO";
					</script>
					<script type="text/javascript" src="https://library.booksite.com/widgetloader.js"></script>
				</div>
			</div>

			<div class="bookletters-widget">
				<div class="bookletters-title">Picture Books</div>
				<div class="bookletters-body">
					<script type="text/javascript">
						widget = "CNLWidget";
						sid = "7550";
						group="current";
						list  = "NLGC";
						group="current";
						rotate = "YES";
						fade = "NO";
						title = "NO";
					</script>
					<script type="text/javascript" src="https://library.booksite.com/widgetloader.js"></script>
				</div>
			</div>

			<div class="bookletters-widget">
				<div class="bookletters-title">Chapter Books</div>
				<div class="bookletters-body">
					<script type="text/javascript">
						widget = "CNLWidget";
						sid = "7550";
						group="current";
						list  = "NLCC";
						group = "current";
						rotate = "YES";
						fade = "NO";
						title = "NO";
					</script>

					<script type="text/javascript" src="https://library.booksite.com/widgetloader.js"></script>
				</div>
			</div>

			<div class="searchHomeForm">
				<div id='homeSearchLabel'>Search the {$librarySystemName} Catalog</div>
				{include file="Search/searchbox.tpl"}
			</div>


		</div>
	</div>
{/strip}