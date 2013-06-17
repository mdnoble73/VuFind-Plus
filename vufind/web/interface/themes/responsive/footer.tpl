{strip}
<div class="footer">
	<div class="row-fluid">
		<div class="span3 offset1"><h4>{translate text='Featured Items'}</h4>
			<ul class="unstyled">
				<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A"Month"&amp;filter[]=literary_form_full%3A"Fiction"'>{translate text='New Fiction'}</a></li>
				<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A"Month"&amp;filter[]=literary_form_full%3A"Non+Fiction"'>{translate text='New Non-Fiction'}</a></li>
				<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A"Month"&amp;filter[]=format%3A"DVD"'>{translate text='New DVDs'}</a></li>
				<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A"Month"&amp;filter[]=format_category%3A"Audio+Books"'>{translate text='New Audio Books &amp; CDs'}</a></li>
				<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A"Week"'>{translate text='New This Week'}</a></li>
			</ul>
		</div>
		<div class="span2"><h4>{translate text='Search Options'}</h4>
			<ul class="unstyled">
				{if $user}
				<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
				{/if}
				<li><a href="{$path}/Search/Results">{translate text='Standard Search'}</a></li>
				<li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
			</ul>
		</div>
		<div class="span2"><h4>{translate text='Find More'}</h4>
			<ul class="unstyled">
				<li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li>
				<!-- <li><a href="{$path}/Search/Reserves">{translate text='Course Reserves'}</a></li>
				<li><a href="{$path}/Search/NewItem">{translate text='New Items'}</a></li> -->
				<li><a href="http://marmot.lib.overdrive.com" rel="external" >{translate text='Download Books &amp; More'}</a></li>
			</ul>
		</div>
		<div class="span3"><h4>{translate text='Need Help?'}</h4>
			<ul class="unstyled">
				<li><a href="{$path}/Help/Home?topic=search" onclick="window.open('{$path}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">{translate text='Search Tips'}</a></li>

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
	</div>

	{if !$productionServer}
	<div class="row-fluid text-center">
		<div class='location_info'><small>{$physicalLocation} ({$activeIp}) - {$deviceName}</small></div>
		{/if}
		<div class='version_info'><small>v. {$gitBranch}</small></div>
	</div>
</div>
{/strip}
