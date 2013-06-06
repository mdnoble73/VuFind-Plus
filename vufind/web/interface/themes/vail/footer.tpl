{* Your footer *}
<div class="footerCol"><p><strong>{translate text='Search Options'}</strong></p>
	<ul>
		{if $user}
		<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
		{/if}
		<li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_vail%3A"Month"&amp;filter[]=literary_form_full%3A"Fiction"&amp;filter[]=format_category%3A"Books"&amp;sort=title'>{translate text='New Fiction Books'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_vail%3A"Month"&amp;filter[]=literary_form_full%3A"Non+Fiction"&amp;filter[]=format_category%3A"Books"&amp;sort=title'>{translate text='New Non-Fiction Books'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_vail%3A"Quarter"&amp;filter[]=format%3A"DVD"&amp;sort=title'>{translate text='New DVDs'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_vail%3A"Quarter"&amp;filter[]=format_category%3A"Audio+Books"&amp;filter[]=format%3A"CD"&amp;sort=title'>{translate text='New Audio Books &amp; CDs'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Find More'}</strong></p>
	<ul>
		<li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li>
		<!-- <li><a href="{$path}/Search/Reserves">{translate text='Course Reserves'}</a></li>
		<li><a href="{$path}/Search/NewItem">{translate text='New Items'}</a></li> -->
		<li><a href="http://marmot.lib.overdrive.com" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Download Books &amp; More'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Need Help?'}</strong></p>
	<ul>
		<li><a href="{$path}/Help/Home?topic=search" onclick="window.open('{$path}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">{translate text='Search Tips'}</a></li>
		<li><a href="{$askALibrarianLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Ask a Librarian'}</a></li>
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
<div class="footerCol"><p><strong>{translate text='About us'}</strong></p>
	<ul>
		<li><a href="http://vail.colibraries.org">{translate text='Library Home'}</a></li>
		<li>
			292 West Meadow Drive<br/>
			Vail, CO 81657<br/>
			(970) 479-2184<br/>
		</li>
		<li><a href="mailto:libinfo@vailgov.com">{translate text='E-mail'}</a></li>
	</ul>
</div>
<div class="footerCol">
	<p><strong>{translate text='Hours of Operation'}</strong></p>
	<ul>
		<li>
		<b>Monday - Thursday: </b><br/>
		&nbsp;&nbsp;10 am to 8 pm<br/>
		<b>Friday - Sunday: </b><br/>
		&nbsp;&nbsp;11 am to 6 pm<br/>
		</li>
		<li><b>Closed:</b> New Year's Day, Memorial Day,<br/>4th of July, Labor Day, Thanksgiving Day,<br/>Christmas Day</li> 
	</ul>
</div>
<div class="clearer"></div>
{if !$productionServer}
<div class='location_info'>{$physicalLocation}</div>
{/if}