{* Your footer *}
<div class="navbar navbar-static-bottom">
	<div class="navbar-inner">
		<div class="row-fluid">
			<div class="span3 footer-column" id="footer1">
				<h4 data-toggle="collapse" data-target="#footerContents1">{translate text='Featured Items'}</h4>
				<ul class="unstyled collapse footerContents" id="footerContents1">
					<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_cmc%3A"Month"&amp;filter[]=literary_form_full%3A"Fiction"'>{translate text='New Fiction'}</a></li>
					<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_cmc%3A"Month"&amp;filter[]=literary_form_full%3A"Non+Fiction"'>{translate text='New Non-Fiction'}</a></li>
					<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_cmc%3A"Month"&amp;filter[]=format%3A"DVD"'>{translate text='New DVDs'}</a></li>
					<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_cmc%3A"Month"&amp;filter[]=format_category%3A"Audio+Books"'>{translate text='New Audio Books &amp; CDs'}</a></li>
					<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_cmc%3A"Week"'>{translate text='New This Week'}</a></li>
					<li><a href='{$path}/MyResearch/MyList/6190'>{translate text='Business Titles: Steamboat Campus'}</a></li>
					<li><a href='{$path}/MyResearch/MyList/5889'>{translate text='Sustainability Titles: Steamboat Campus'}</a></li>
				</ul>
			</div>
			<div class="span3 footer-column" id="footer2">
				<h4 data-toggle="collapse" data-target="#footerContents2">{translate text='Search Options'}</h4>
				<ul class="unstyled collapse footerContents" id="footerContents2">
					{if $user}
					<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
					{/if}
					<li><a href="{$path}/Search/Results">{translate text='Standard Search'}</a></li>
					<li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
					{*
					<li><a href="http://coloradomtn.edu/cms/One.aspx?portalId=2935482&pageId=3851004">LibX Toolbar</a></li>
					*}
				</ul>
			</div>
			<div class="span3 footer-column" id="footer3">
				<h4 data-toggle="collapse" data-target="#footerContents3">{translate text='Find More'}</h4>
				<ul class="unstyled collapse footerContents" id="footerContents3">
					<li><a href="{$homeLink}">CMC Libraries Home Page</a></li>
					<li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li>
					<!-- <li><a href="{$path}/Search/Reserves">{translate text='Course Reserves'}</a></li>
					<li><a href="{$path}/Search/NewItem">{translate text='New Items'}</a></li> -->
					<li><a href="http://marmot.lib.overdrive.com" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Download Books &amp; More'}</a></li>
				</ul>
			</div>
			<div class="span3 footer-column" id="footer4">
				<h4 data-toggle="collapse" data-target="#footerContents4">{translate text='Need Help?'}</h4>
				<ul class="unstyled collapse footerContents" id="footerContents4">
					<li><a href="{$path}/Help/Home?topic=search" onclick="window.open('{$path}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">{translate text='Search Tips'}</a></li>
					<li><a href="{$askALibrarianLink|replace:"&":"&amp;"}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Ask a Librarian'}</a></li>
					{if isset($illLink)}
							<li><a href="{$illLink|replace:"&":"&amp;"}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Interlibrary Loan'}</a></li>
					{/if}
					{if isset($suggestAPurchaseLink)}
							<li><a href="{$suggestAPurchaseLink|replace:"&":"&amp;"}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Suggest a Purchase'}</a></li>
					{/if}
					<li><a href="{$path}/Help/Home?topic=faq" onclick="window.open('{$path}/Help/Home?topic=faq', 'Help', 'width=625, height=510, scrollbars=yes'); return false;">{translate text='FAQs'}</a></li>
					<li><a href="{$path}/Help/Suggestion">{translate text='Make a Suggestion'}</a></li>
				</ul>
			</div>
		</div>

		{if !$productionServer}
			<div class="row-fluid text-center">
				<div class='location_info'>{$physicalLocation}</div>
			</div>
		{/if}
	</div>
</div>