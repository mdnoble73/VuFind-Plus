{strip}
{* Your footer *}
<div class="footerCol"><p><strong>{translate text='Featured Items'}</strong></p>
	<ul>
		<li>New Fiction</li>
        <li>New Non-Fiction</li>
        <li>New DVDs</li>
        <li>New Audio Books & CDs</li>
        <li>New This Week</li>
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
		<li><a href="{$path}/Help/Suggestion">{translate text='Make a Suggestion'}</a></li>
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
