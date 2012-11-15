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
<div class="footerCol"><p><strong>{translate text='About us'}</strong></p>
	<ul>
		<li>
			1000 Englewood Parkway<br/>
			First Floor Englewood Civic Center<br/>
			Englewood, CO 80110 <br/>
			303-762-2560<br/>
			<a href="mailto:epl@englewoodgov.org">E-mail</a>
		</li>
		<li><a href="http://www.englewoodpubliclibrary.org">{translate text='Library Home'}</a></li>
		<li><a href="http://www.englewoodgov.org">{translate text='City of Englewood'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Find More'}</strong></p>
	<ul>
		<li><a href="http://www.englewoodgov.org/Index.aspx?page=1076" rel="external">{translate text='eResearch'}</a></li>
		<li><a href="http://www.englewoodgov.org/Index.aspx?page=1168" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Download Books'}</a></li>
	</ul>
</div>

<div class="footerCol"><p><strong>{translate text='Need Help?'}</strong></p>
	<ul>
		<li><a href="http://www.englewoodgov.org/Index.aspx?page=1131" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Request A Title'}</a></li>
		<li><a href="mailto:epl@englewoodgov.org" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Ask a Librarian'}</a></li>
	</ul>
</div>
<div class="footerCol">
	
</div>
<div class="footerCol">
	<p><strong>{translate text='Hours of Operation'}</strong></p>
	<p>
   Monday - Thursday: <br/>
      10:00 am to 8:30 pm<br/>
   Friday - Saturday: <br/>
      10:00 am to 5:00 pm<br/>
   Sunday:<br/>
      1:00 pm to 5:00 pm<br/>
<a href="http://www.englewoodgov.org/Index.aspx?page=1082">Days We're Closed</a> 
</p>
</div>
<div class="clearer"></div>
{if !$productionServer}
<div class='location_info'>{$physicalLocation}</div>
{/if}