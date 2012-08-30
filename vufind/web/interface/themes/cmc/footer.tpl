{* Your footer *}
<div class="footerCol"><div><strong>{translate text='Featured Items'}</strong></div>
	<ul>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_cmc%3A"Month"&amp;filter[]=literary_form_full%3A"Fiction"'>{translate text='New Fiction'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_cmc%3A"Month"&amp;filter[]=literary_form_full%3A"Non+Fiction"'>{translate text='New Non-Fiction'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_cmc%3A"Month"&amp;filter[]=format%3A"DVD"'>{translate text='New DVDs'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_cmc%3A"Month"&amp;filter[]=format_category%3A"Audio"'>{translate text='New Audio Books &amp; CDs'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_cmc%3A"Week"'>{translate text='New This Week'}</a></li>
		<li><a href='http://cmc.opac.marmot.org/MyResearch/MyList/6190'>{translate text='Business Titles: Alpine Campus'}</a></li>
		<li><a href='http://cmc.opac.marmot.org/MyResearch/MyList/5889'>{translate text='Sustainability Titles: Alpine Campus'}</a></li>
	</ul>
</div>
<div class="footerCol"><div><strong>{translate text='Search Options'}</strong></div>
	<ul>
		{if $user}
		<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
		{/if}
		<li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
		{*
		<li><a href="http://coloradomtn.edu/cms/One.aspx?portalId=2935482&pageId=3851004">LibX Toolbar</a></li>
		*}
	</ul>
</div>
<div class="footerCol"><div><strong>{translate text='Find More'}</strong></div>
	<ul>
		<li><a href="{$homeLink}">CMC Libraries Home Page</a></li>
		<li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li>
		<!-- <li><a href="{$path}/Search/Reserves">{translate text='Course Reserves'}</a></li>
		<li><a href="{$path}/Search/NewItem">{translate text='New Items'}</a></li> -->
		<li><a href="http://marmot.lib.overdrive.com" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Download Books &amp; More'}</a></li>
	</ul>
</div>
<div class="footerCol"><div><strong>{translate text='Need Help?'}</strong></div>
	<ul>
		<li><a href="{$path}/Help/Home?topic=search" onclick="window.open('{$path}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">{translate text='Search Tips'}</a></li>
		<li><a href="{$askALibrarianLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Ask a Librarian'}</a></li>
		{if isset($illLink)}
				<li><a href="{$illLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Interlibrary Loan'}</a></li>
		{/if}
		{if isset($suggestAPurchaseLink)}
				<li><a href="{$suggestAPurchaseLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Suggest a Purchase'}</a></li>
		{/if}
		<li><a href="{$path}/Help/Home?topic=faq" onclick="window.open('{$path}/Help/Home?topic=faq', 'Help', 'width=625, height=510'); return false;">{translate text='FAQs'}</a></li>
		<li><a href="{$path}/Help/Suggestion">{translate text='Make a Suggestion'}</a></li>
	</ul>
</div>
<br clear="all">
{if !$productionServer}
<div class='location_info'>{$physicalLocation}</div>
{/if}

{* Add Google Analytics*}
{literal}
<script type="text/javascript">
	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', 'UA-10641564-2']);
	_gaq.push(['_setDomainName', '.marmot.org']);
	_gaq.push(['_trackPageview']);
	_gaq.push(['_trackPageLoadTime']);

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();

</script>
{/literal}
