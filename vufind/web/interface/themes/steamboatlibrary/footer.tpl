{* Your footer *}
<div class="footerCol"><p><strong>{translate text='Featured Items'}</strong></p>
	<ul>
		<li><a href='http://www.steamboatlibrary.org/books-and-media/books/staff-picks'>Staff Picks</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;type=Keyword&amp;filter[]=local_time_since_added_steamboatlibrary%3A%22Week%22&amp;filter[]=building%3A%22SSCL+Bud+Werner+Library%22&amp;filter[]=itype%3A%22Young+adult+fiction%22&amp;sort=relevance&amp;view=list&amp;searchSource=local'>{translate text='New Teen Reads'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;type=Keyword&amp;filter[]=local_time_since_added_steamboatlibrary%3A%22Week%22&amp;filter[]=building%3A%22SSCL+Bud+Werner+Library%22&amp;filter[]=itype%3A%22Easy+book%22&amp;sort=relevance&amp;view=list&amp;searchSource=local'>{translate text='New Books for Young Children'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;type=Keyword&amp;filter[]=local_time_since_added_steamboatlibrary%3A%22Week%22&amp;filter[]=building%3A%22SSCL+Bud+Werner+Library%22&amp;filter[]=itype%3A%22Juvenile+fiction%22&amp;sort=relevance&amp;view=list&amp;searchSource=local'>{translate text='New Books for Older Children'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=institution%3A%22Steamboat+Springs+Community+Libraries%22&amp;filter[]=format%3A%22Blu-ray%22&amp;filter[]=local_time_since_added_steamboatlibrary%3A%22Month%22'>{translate text='New Bluray'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;type=Keyword&amp;filter[]=local_time_since_added_steamboatlibrary%3A%22Week%22&amp;filter[]=building%3A%22SSCL+Bud+Werner+Library%22&amp;filter[]=itype%3A%22Music%22&amp;sort=relevance&amp;view=list&amp;searchSource=local'>{translate text='New Music'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='About Us'}</strong></p>
	<ul>
		<li><a href="http://www.steamboatlibrary.org/about-us/board-of-trustees-0">{translate text='Board of Trustees'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/about-us/history">{translate text='History'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/about-us/building">{translate text='Building'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/about-us/mission">{translate text='Mission'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/about-us/policies">{translate text='Policies'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/about-us/jobs">{translate text='Jobs'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Find Us'}</strong></p>
	<ul>
		<li><a href="http://www.steamboatlibrary.org/find-us/hours">{translate text='Hours'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/find-us/hours/driving-directions">{translate text='Driving Directions'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/find-us/hours/driving-directions/book-drop-locations">{translate text='Book Drop Locations'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/downloads/mobile-app">{translate text='Mobile App'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Support Us'}</strong></p>
	<ul>
		<li><a href="http://www.steamboatlibrary.org/support-us/book-donations">{translate text='Book Donations'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/support-us/volunteer">{translate text='Volunteer'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/support-us/donate">{translate text='Donate'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/support-us/thanks-to">{translate text='Thanks To'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Contact Us'}</strong></p>
	<ul>
		<li><a href="http://www.steamboatlibrary.org/questions-comments-suggestions/questions-comments-suggestions">{translate text='Questions, Comments, Suggestions'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/questions-comments-suggestions/ask-a-librarian">{translate text='Ask a Librarian'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/how-do-i/manage-my-account/request-a-title">{translate text='Request a Title'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/how-do-i/manage-my-account/request-a-title/suggest-a-purchase">{translate text='Suggest a Purchase'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/questions-comments-suggestions/staff-directory">{translate text='Staff Directory'}</a></li>
	</ul>
</div>

<br class="clearer"/>
{if !$productionServer}
<div class='location_info'>{$physicalLocation}</div>
{/if}
