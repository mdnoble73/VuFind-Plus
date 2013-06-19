{strip}
<div class="footer">
	<div class="row-fluid">
			<div class="span3 offset2" id="footer1"><h4>{translate text='Featured Items'}</h4>
				<ul class="unstyled">
					<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A"Month"&amp;filter[]=literary_form_full%3A"Fiction"'>{translate text='New Fiction'}</a></li>
					<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A"Month"&amp;filter[]=literary_form_full%3A"Non+Fiction"'>{translate text='New Non-Fiction'}</a></li>
					<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A"Month"&amp;filter[]=format%3A"DVD"'>{translate text='New DVDs'}</a></li>
					<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A"Month"&amp;filter[]=format_category%3A"Audio+Books"'>{translate text='New Audio Books &amp; CDs'}</a></li>
					<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A"Week"'>{translate text='New This Week'}</a></li>
				</ul>
			</div>
			<div class="span3" id="footer2"><h4>{translate text='Search Options'}</h4>
				<ul class="unstyled">
					{if $user}
					<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
					{/if}
					<li><a href="{$path}/Search/Results">{translate text='Standard Search'}</a></li>
					<li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
				</ul>
			</div>
			<div class="span3" id="footer3"><h4>{translate text='Need Help?'}</h4>
				<ul class="unstyled">
					<li><a href="{$path}/Help/Home?topic=search" class="modalDialogTrigger" data-title="{translate text='Search Tips'}">{translate text='Search Tips'}</a></li>
					<li><a href="{$path}/Help/Home?topic=faq" class="modalDialogTrigger" data-title="{translate text='FAQs'}">{translate text='FAQs'}</a></li>
					<li><a href="{$path}/Help/eContentHelp?lightbox" class="modalDialogTrigger" data-title="{translate text='eBooks &amp; eAudio'}">{translate text='eBooks &amp; eAudio'}</a></li>
					{if $enableMaterialsRequest}
						<li><a href="{$path}/MaterialsRequest/NewRequest">{translate text='Suggest a Purchase'}</a></li>
					{/if}
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
