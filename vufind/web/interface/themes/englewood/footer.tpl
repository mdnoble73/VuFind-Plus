{* Your footer *}
<div class="footerCol"><p><strong>{translate text='Featured Items'}</strong></p>
	<ul>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_englewood%3A"Week"'>{translate text='New This Week'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_englewood%3A"Month"&amp;filter[]=literary_form_full%3A"Fiction"'>{translate text='New Fiction'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_englewood%3A"Month"&amp;filter[]=literary_form_full%3A"Non+Fiction"'>{translate text='New Non-Fiction'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_englewood%3A"Month"&amp;filter[]=format%3A"DVD"'>{translate text='New DVDs'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_englewood%3A"Month"&amp;filter[]=format_category%3A"Audio"'>{translate text='New Audio Books &amp; CDs'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Find More'}</strong></p>
	<ul>
		<li><a href="http://www.englewoodgov.org/inside-city-hall/city-departments/library/look-it-up" rel="external">{translate text='eResearch'}</a></li>
		<li><a href="http://www.englewoodgov.org/inside-city-hall/city-departments/library/ebooks" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Download Books'}</a></li>
	</ul>
</div>

<div class="footerCol"><p><strong>{translate text='Need Help?'}</strong></p>
	<ul>
		<li><a href="http://www.englewoodgov.org/inside-city-hall/city-departments/library/library-title-request-form.1271" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Request A Title'}</a></li>
		<li><a href="mailto:epl@englewoodgov.org" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Ask a Librarian'}</a></li>
	</ul>
</div>
<div class="footerCol">
	
</div>
<div class="footerCol"><p><strong>{translate text='About us'}</strong></p>
	<ul>
		<li><a href="http://www.englewoodpubliclibrary.org">{translate text='Library Home'}</a></li>
		<li>
			1000 Englewood Parkway<br/>
			First Floor &#8226; Englewood Civic Center<br/>
			Englewood, CO 80110 <br/>
			<b>303-762-2560</b><br/>
		</li>
		<li><a href="mailto:epl@englewoodgov.org">{translate text='E-mail'}</a></li>
		<li><a href="http://www.englewoodgov.org">{translate text='City of Englewood'}</a></li>
	</ul>
</div>
<div class="footerCol">
	<p><strong>{translate text='Hours of Operation'}</strong></p>
	<ul>
		<li>
		<b>Monday - Thursday: </b><br/>
		&nbsp;&nbsp;10:00 am to 8:30 pm<br/>
		<b>Friday - Saturday: </b><br/>
		&nbsp;&nbsp;10:00 am to 5:00 pm<br/>
		<b>Sunday:</b><br/>
		&nbsp;&nbsp;1:00 pm to 5:00 pm<br/>
		</li>
		<li><a href="http://www.englewoodgov.org/inside-city-hall/city-departments/library/2013-library-closures">Days We're Closed</a></li> 
	</ul>
</div>
<div class="clearer"></div>
{if !$productionServer}
<div class='location_info'>{$physicalLocation} ({$activeIp})</div>
{/if}