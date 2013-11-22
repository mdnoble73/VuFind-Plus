{* Your footer *}
{*
<div class="footerCol"><p><strong>{translate text='Featured Items'}</strong></p>
	<ul>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_rampart%3A"Week"'>{translate text='New This Week'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_rampart%3A"Month"&amp;filter[]=literary_form_full%3A"Fiction"'>{translate text='New Fiction'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_rampart%3A"Month"&amp;filter[]=literary_form_full%3A"Non+Fiction"'>{translate text='New Non-Fiction'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_rampart%3A"Month"&amp;filter[]=format%3A"DVD"'>{translate text='New DVDs'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_rampart%3A"Month"&amp;filter[]=format_category%3A"Audio+Books"'>{translate text='New Audio Books &amp; CDs'}</a></li>
	</ul>
</div>
*}
<div class="footerCol"><p><strong>{translate text='Find More'}</strong></p>
	<ul>
		{if $user}
			<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
		{/if}
		<li><a href="{$path}/Search/Results">{translate text='Standard Search'}</a></li>
		<li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
		<li><a href="http://rampart.colibraries.org/eresources.html" rel="external">{translate text='eResources'}</a></li>
		<li><a href="http://rampart.colibraries.org/research" rel="external"">{translate text='Research Databases'}</a></li>
	</ul>
</div>

<div class="footerCol"><p><strong>{translate text='Need Help?'}</strong></p>
	<ul>
		<li><a href="http://rampart.colibraries.org/">{translate text='Library Home'}</a></li>
		<li><a href="{$path}/MaterialsRequest/NewRequest" >{translate text='Request A Title'}</a></li>
		<li><a href="http://rampart.colibraries.org/web-forms/ref-question" >{translate text='Ask a Librarian'}</a></li>
		<li><a href="http://rampart.colibraries.org/contact-us" >{translate text='Contact Us'}</a></li>

	</ul>
</div>
<div class="footerCol">
	<p><strong>{translate text='Woodland Park Public Library'}</strong></p>
	<ul>
		<li>(719) 687-9281</li>
		<li>
			<b>Monday:</b> Closed<br/>
			<b>Tues. - Thurs.:</b> 10am - 7pm<br/>
			<b>Friday:</b> 10am - 6pm<br/>
			<b>Saturday:</b> 10am - 4pm<br/>
			<b>Sunday:</b> 1pm - 4pm<br/>
		</li>
		<li><a href="http://rampart.colibraries.org/about-us/woodland-park-public-library/woodland-park-public-library.html">Address and info</a></li>
	</ul>
</div>
<div class="footerCol">
	<p><strong>{translate text='Florissant Public Library'}</strong></p>
	<ul>
		<li>(719) 748-3939</li>
		<li>
			<b>Monday:</b> 10am-5pm<br/>
			<b>Tuesday:</b> Closed<br/>
			<b>Wed. - Fri.:</b> 10am - 5pm<br/>
			<b>Saturday:</b> 10am - 2pm<br/>
			<b>Sunday:</b> Closed<br/>
		</li>
		<li><a href="http://rampart.colibraries.org/about-us/florissant-public-library/florissant-public-library.html">Address and info</a></li>
	</ul>
</div>
<div class="clearer"></div>
{if !$productionServer}
<div class='location_info'>{$physicalLocation} ({$activeIp})</div>
{/if}