{strip}
{* Your footer *}
<div class="footerCol"><p><strong>{translate text='Featured Items'}</strong></p>
	<ul>
		<li><a href="/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A&quot;Month&quot;&amp;filter[]=literary_form_full%3A&quot;Fiction&quot;&amp;sort=year">New Fiction</a></li>
		<li><a href="/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A&quot;Month&quot;&amp;filter[]=literary_form_full%3A&quot;Non+Fiction&quot;&amp;sort=year">New Non-Fiction</a></li>		
		<li><a href="/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A&quot;Month&quot;&amp;filter[]=format%3A&quot;DVD&quot;&amp;sort=year">New DVDs</a></li>
		<li><a href="/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A&quot;Month&quot;&amp;filter[]=format_category%3A&quot;Audio+Books&quot;&amp;sort=year">New Audio Books &amp; CDs</a></li>
		<li><a href="/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A&quot;Week&quot;&amp;sort=year">New This Week</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Search Options'}</strong></p>
	<ul>
		{if $user}
		<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
		{/if}
		<li><a href="{$path}">{translate text='Standard Search'}</a></li>
		<li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Find More'}</strong></p>
	<ul>
		<!-- <li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li> -->
		<!-- <li><a href="{$path}/Search/Reserves">{translate text='Course Reserves'}</a></li> -->
		<!-- <li><a href="{$path}/Search/NewItem">{translate text='New Items'}</a></li> -->
		<li><a href="http://emedia.library.nashville.org/" rel="external" >{translate text='Download Books &amp; More'}</a></li>
        <li><a href="https://www.rbdigital.com/nashvilletn/service/zinio/landing?">Online Magazines</a></li>
        <li><a href="http://www.freegalmusic.com">Freegal Music</a></li>
	</ul>
</div>
<div class="footerCol"><p><strong>{translate text='Need Help?'}</strong></p>
	<ul>
		<li><a href="{$path}/Help/Home?topic=search" onclick="window.open('{$path}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">{translate text='Search Tips'}</a></li>

		{if isset($illLink)}
				<li><a href="{$illLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Interlibrary Loan'}</a></li>
		{/if}
		{if isset($suggestAPurchaseLink)}
				<!-- <li><a href="{$suggestAPurchaseLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Suggest a Purchase'}</a></li> -->
                <li><a href="http://www.library.nashville.org/bmm/bmm_books_suggestionform.asp" rel="external" >{translate text='Suggest a Purchase'}</a></li>
		{/if}
		<li><a href="{$path}/Help/Home?topic=faq" onclick="window.open('{$path}/Help/Home?topic=faq', 'Help', 'width=625, height=510, scrollbars=yes'); return false;">{translate text='FAQs'}</a></li>
<!--		<li><a href="{$path}/Help/Suggestion">{translate text='Make a Suggestion'}</a></li> -->
		{if $inLibrary}<li><a href="https://www.volgistics.com/ex/portal.dll/?FROM=15495">Volunteers</a></li>{/if}
	</ul>
</div>
<div class="clearer"></div>
<div id="copyright">
	<a href="#" class='mobile-view'>{translate text="Go to Mobile View"}</a>
</div>

{if !$productionServer}
<div class='location_info'>{$physicalLocation} ({$activeIp}) - {$deviceName}</div>
{/if}
<div class='version_info'>v. {$gitBranch}</div>
{/strip}
