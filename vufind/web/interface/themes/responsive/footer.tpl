{strip}
<div class="navbar navbar-static-bottom">
	<div class="navbar-inner">
		<div class="row-fluid">
				<div class="span3 offset2 footer-column" id="footer1">
					<h4 data-toggle="collapse" data-target="#footerContents1">{translate text='New Titles of Interest'}</h4>
					<ul class="unstyled collapse footerContents" id="footerContents1">
						<li><a href='{$path}/Search/Results?lookfor=&amp;type=AllFields&amp;filter[]=system_list%3A%22New+Adult+General+Fiction%22&amp;sort=id_sort+desc"'>{translate text='Adult General Fiction'}</a></li>
						<li><a href='{$path}/Search/Results?lookfor=&amp;type=AllFields&amp;filter[]=system_list%3A%22New+Books+for+Young+Children%22&amp;sort=id_sort+desc"'>{translate text='Picture Books &amp Beginning Readers'}</a></li>
						<li><a href='{$path}/Search/Results?lookfor=&amp;type=AllFields&amp;filter[]=system_list%3A%22New+Children&#039;s+Fiction%22&amp;sort=id_sort+desc"'>{translate text='Children\'s Fiction'}</a></li>
						<li><a href='{$path}/Search/Results?lookfor=&amp;type=AllFields&amp;filter[]=system_list%3A%22New+Books+for+Young+Adults%22&amp;sort=id_sort+desc"'>{translate text='Young Adult'}</a></li>
						<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=format_category%3AeBooks&amp;sort=id_sort+desc"'>{translate text='eBooks'}</a></li>
                        <li><a href="{$path}/Search/New_more" title="{translate text='New Books'}" class="modalDialogTrigger">{translate text='more...'}</a></li>
					</ul>
				</div>
				<div class="span3 footer-column" id="footer2"><h4 data-toggle="collapse" data-target="#footerContents2">{translate text='Further Research'}</h4>
					<ul class="unstyled collapse footerContents" id="footerContents2">
						<li><a href="#searchForm" onclick="$('#lookfor').scrollTop();$('#lookfor').trigger('focus');return false;">{translate text='Standard Search'}</a></li>
						{*
						<li><a href="{$path}/Browse/Home">{translate text='Browse'}</a></li>
						*}
						<li><a href="{$path}/Search/AdvancedPopup" title="{translate text='Advanced Search'}" class="modalDialogTrigger">{translate text='Advanced Search'}</a></li>
						{if $user}
							<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
						{/if}
                        <li><a href='http://ke2nk8za8p.cs.serialssolutions.com.ezproxy.co.wake.nc.us/' target="_blank">{translate text='OneSEARCH Databases'}</a></li>
                        <li><a href='http://ke2nk8za8p.search.serialssolutions.com.ezproxy.co.wake.nc.us/' target="_blank">{translate text='Magazine &amp Journal Database'}</a></li>
					</ul>
				</div>
				<div class="span3 footer-column" id="footer3"><h4 data-toggle="collapse" data-target="#footerContents3">{translate text='Need Help?'}</h4>
					<ul class="unstyled collapse footerContents" id="footerContents3">
						<li><a href="{$path}/Help/Home?topic=search" class="modalDialogTrigger" data-title="{translate text='Search Tips'}">{translate text='Search Tips'}</a></li>
						<li><a href="{$path}/Help/Home?topic=faq" class="modalDialogTrigger" data-title="{translate text='FAQs'}">{translate text='FAQs'}</a></li>
						<li><a href="{$path}/Help/eContentHelp?lightbox" class="modalDialogTrigger" data-title="{translate text='eBooks &amp; eAudio'}">{translate text='eBooks &amp; eAudio'}</a></li>
						{if $enableMaterialsRequest}
							<li><a href="{$path}/MaterialsRequest/NewRequest">{translate text='Suggest a Purchase'}</a></li>
						{/if}
						<li><a href="{$path}/Help/Suggestion?lightbox" class="modalDialogTrigger" data-title="{translate text='Make a Suggestion'}">{translate text='Make a Suggestion'}</a></li>
					</ul>
				</div>

                <div class="span3 footer-column" id="footer4"><h4 data-toggle="collapse" data-target="#footerContents4">{translate text='Stay Connected'}</h4>
                    <ul class="unstyled collapse footerContents" id="footerContents4">
                         <li><a href="http://www.facebook.com/wcplonline" target="_blank" title="WCPL Facebook Page">{translate text='Facebook'}</a></li>
                         <li><a href="http://www.youtube.com/wcplonline" target="_blank" title="WCPL YouTube Channel">{translate text='YouTube'}</a></li>
                         <li><a href='http://www.twitter.com/wcplonline' target="_blank" title="WCPL Twitter Page">{translate text='Twitter'}</a></li>
                         <li><a href='http://askwcpl.wakegov.com/' target="_blank" title="AskWCPL Answers Database">{translate text='AskWCPL Answers Database'}</a></li>
                    </ul>
                 </div>
		</div>

		{if !$productionServer}
			<div class="row-fluid text-center">
				<div class='location_info'><small>{$physicalLocation} ({$activeIp}) - {$deviceName}</small></div>
				<div class='version_info'><small>v. {$gitBranch}</small></div>
			</div>
		{/if}
	</div>
</div>
{/strip}
