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
<div class="footerCol"><p><strong>{translate text='Search Options'}</strong></p>
	<ul>
		{if $user}
		<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
		{/if}
		<li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Find More'}</strong></p>
	<ul>
		<li><a href="http://www.steamboatlibrary.org/services/research-databases" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Databases &amp; Websites'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/books-and-media/books" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Great Reads'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/downloads" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Download Books &amp; More'}</a></li>
		<li><a href="http://www.steamboatlibrary.org/" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Library Homepage'}</a></li>
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
		<li><a href="{$path}/Help/Home?topic=faq" onclick="window.open('{$path}/Help/Home?topic=faq', 'Help', 'width=625, height=510'); return false;">{translate text='FAQs'}</a></li>
		<li><a href="{$path}/Help/Suggestion">{translate text='Make a Suggestion'}</a></li>
	</ul>
</div>
<br class="clearer"/>
{if !$productionServer}
<div class='location_info'>{$physicalLocation}</div>
{/if}
