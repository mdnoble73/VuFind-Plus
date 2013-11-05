{* Your footer *}
<div class="footerCol"><p><strong>{translate text='Featured Items'}</strong></p>
	<ul>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_ccu%3A"Month"&amp;filter[]=format_category%3A"Books"'>{translate text='New Books'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_ccu%3A"Month"&amp;filter[]=format_category%3A"eBook"'>{translate text='New eBooks'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Search Options'}</strong></p>
	<ul>
		{if $user}
		<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
		{/if}
		<li><a href="{$path}/Search/Results">{translate text='Standard Search'}</a></li>
		<li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Find More'}</strong></p>
	<ul>
		<li><a href="http://sierra.marmot.org/screens/course_s25.html">{translate text='Course Reserves'}</a></li>
		{if !($action == 'Home' && $module == 'Search')}
			<li><a href="http://www.ccu.edu/library">{translate text='CCU Library Home Page'}</a></li>
		{/if}
		<li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li>
		<!-- <li><a href="{$path}/Search/Reserves">{translate text='Course Reserves'}</a></li>
		<li><a href="{$path}/Search/NewItem">{translate text='New Items'}</a></li> -->
		{if !($action == 'Home' && $module == 'Search')}
			<li><a href="http://marmot.lib.overdrive.com" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Download Books &amp; More'}</a></li>
		{/if}
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Need Help?'}</strong></p>
	<ul>
		<li><a href="{$path}/Help/Home?topic=search" onclick="window.open(this.href, 'Help', 'width=625, height=510'); return false;">{translate text='Search Tips'}</a></li>
		{if !($action == 'Home' && $module == 'Search')}
			<li><a href="{$askALibrarianLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Chat with a Librarian'}</a></li>
		{/if}
		{if isset($illLink)}
				<li><a href="{$illLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Interlibrary Loan'}</a></li>
		{/if}
		{if isset($suggestAPurchaseLink)}
				<li><a href="{$suggestAPurchaseLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Suggest a Purchase'}</a></li>
		{/if}
		<li><a href="{$path}/Help/Home?topic=faq" onclick="window.open('{$path}/Help/Home?topic=faq', 'Help', 'width=625, height=510, scrollbars=yes'); return false;">{translate text='FAQs'}</a></li>
		<li><a href="{$path}/Help/Suggestion">{translate text='Make a Suggestion'}</a></li>
	</ul>
</div>
{if !($action == 'Home' && $module == 'Search')}
<div class="footerCol">
	<br/><br/>
	<a href="https://docs.google.com/forms/d/1EdM9CrA3IbJ6Bw2FdPoj7vnlElQ9EolOPB1jr3Q_sbQ/viewform">
		<img src="{$path}/interface/themes/ccu/images/BookALibrarianLogo.PNG" alt="Book a Librarian">
	</a>
</div>
{/if}
<br class="clearer"/>
{if !$productionServer}
<div class='location_info'>{$physicalLocation}</div>
{/if}