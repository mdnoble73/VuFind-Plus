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
	</div>
	{include file="footer.tpl"}
</div>
