{strip}
<div data-role="page" id="Search-home">
	{include file="header.tpl"}
	<div data-role="content">
		{include file="Search/searchbox.tpl"}
		<!-- <ul data-role="listview" data-inset="true" data-dividertheme="b">
			<li data-role="list-divider">{translate text='Find More'}</li>
			<li><a rel="external" href="{$path}/Search/Reserves">{translate text='Course Reserves'}</a></li>
			<li><a rel="external" href="{$path}/Search/NewItem">{translate text='New Items'}</a></li>	 
		</ul>-->
		
		{*
		<ul data-role="listview" data-inset="true" data-dividertheme="b">
			<li data-role="list-divider">{translate text='Need Help?'}</li>
			<li><a href="{$path}/Help/Home?topic=search" data-rel="dialog">{translate text='Search Tips'}</a></li>
			<li><a href="#">{translate text='Ask a Librarian'}</a></li>
			<li><a href="#">{translate text='FAQs'}</a></li>
		</ul>
		*}
		<h3>Featured Searches For Adults</h3>
		<div data-role="controlgroup">
			<a data-role="button" rel="external" href='{$path}/Search/Results?lookfor=&amp;basicType=Keyword&filter[]=format_category:"Books"&filter[]=publishDate:[2013+TO+*]"&filter[]=literary_form_full:"Fiction"&filter[]=target_audience_full:"Adult"'>New Fiction</a>
			<a data-role="button" rel="external" href='{$path}/Search/Results?lookfor=&amp;basicType=Keyword&filter[]=format_category:"Books"&filter[]=publishDate:[2013+TO+*]"&filter[]=literary_form_full:"Non+Fiction"&filter[]=target_audience_full:"Adult"'>New Non-Fiction</a>
			<a data-role="button" rel="external" href='{$path}/Search/Results?lookfor=&amp;basicType=Keyword&filter[]=format_category:"Movies"&filter[]=publishDate:[2013+TO+*]"&filter[]=target_audience_full:"Adult"'>New Adult Movies</a>
			<a data-role="button" rel="external" href='{$path}/Search/Results?lookfor=&amp;basicType=Keyword&filter[]=format_category:"eBook"&filter[]=publishDate:[2013+TO+*]"&filter[]=target_audience_full:"Adult"'>New eBooks</a>
			<a data-role="button" rel="external" href='{$path}/Search/Results?lookfor=&amp;basicType=Keyword&filter[]=format_category:"Audio+Books"&filter[]=publishDate:[2013+TO+*]"&filter[]=target_audience_full:"Adult"'>New Audio Books</a>
		</div>
		<h3>Featured Searches For Kids</h3>
		<div data-role="controlgroup">
			<a data-role="button" rel="external" href='{$path}/Search/Results?lookfor=&amp;basicType=Keyword&filter[]=format_category:"Books"&filter[]=publishDate:[2013+TO+*]"&filter[]=literary_form_full:"Fiction"&filter[]=target_audience_full:"Juvenile"'>New Fiction</a>
			<a data-role="button" rel="external" href='{$path}/Search/Results?lookfor=&amp;basicType=Keyword&filter[]=format_category:"Books"&filter[]=publishDate:[2013+TO+*]"&filter[]=literary_form_full:"Non+Fiction"&filter[]=target_audience_full:"Juvenile"'>New Non-Fiction</a>
			<a data-role="button" rel="external" href='{$path}/Search/Results?lookfor=&amp;basicType=Keyword&filter[]=format_category:"Movies"&filter[]=publishDate:[2013+TO+*]"&filter[]=target_audience_full:"Juvenile"'>New Movies</a>
			<a data-role="button" rel="external" href='{$path}/Search/Results?lookfor=&amp;basicType=Keyword&filter[]=format_category:"eBook"&filter[]=publishDate:[2013+TO+*]"&filter[]=literary_form_full:"Fiction"&filter[]=target_audience_full:"Adult"'>New eBooks</a>
			<a data-role="button" rel="external" href='{$path}/Search/Results?lookfor=&amp;basicType=Keyword&filter[]=format_category:"Audio+Books"&filter[]=publishDate:[2013+TO+*]"&filter[]=literary_form_full:"Fiction"&filter[]=target_audience_full:"Adult"'>New Audio Books</a>
		</div>
			
	</div>
	{include file="footer.tpl"}
</div>
{/strip}