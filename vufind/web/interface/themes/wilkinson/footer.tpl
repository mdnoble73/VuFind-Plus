        {* Your footer *}
        <div><p><strong>{translate text='Featured Items'}</strong></p>
          <ul>
            <li><a href="{$path}/Search/Results?lookfor=&type=Keyword&filter[]=institution%3A%22Telluride%22&filter[]=literary_form%3A%22Non+Fiction%22&filter[]=local_time_since_added_wilkinson%3A%22Month%22&filter[]=format%3A%22Book%22&sort=year">{translate text='New Non-Fiction'}</a></li>
            <li><a href="{$path}/Search/Results?lookfor=&type=Keyword&filter[]=institution%3A%22Telluride%22&filter[]=literary_form%3A%22Fiction%22&filter[]=local_time_since_added_wilkinson%3A%22Month%22&filter[]=format%3A%22Book%22&sort=year">{translate text='New Fiction'}</a></li>
            <li><a href="{$path}/Search/Results?lookfor=&type=Keyword&filter[]=institution%3A%22Telluride%22&filter[]=local_time_since_added_wilkinson%3A%22Month%22&filter[]=format%3A%22DVD%22&sort=year">{translate text='New DVDs'}</a></li>
          </ul>
        </div>
        <div><p><strong>{translate text='Search Options'}</strong></p>
          <ul>
            {if $user}
            <li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
            {/if}
            <li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
          </ul>
        </div>
        <div><p><strong>{translate text='Find More'}</strong></p>
          <ul>
			<li><a href="http://www.telluridelibrary.org" rel="external" onclick="window.open (this.href, 'child'); return false">Library Website</a></li>
            <li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li>
            <!-- <li><a href="{$path}/Search/Reserves">{translate text='Course Reserves'}</a></li>
            <li><a href="{$path}/Search/NewItem">{translate text='New Items'}</a></li> -->
            <li><a href="http://marmot.lib.overdrive.com" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Digital Downloads'}</a></li>
            <li><a href="http://www.telluridelibrary.org/e-resources.html">E-Resources</a></li>
            <li><a href="http://www.wplnow.org">WPL Now</a></li>
            <li><a href="http://www.wplkids.com">WPL Kids</a></li>
            <li><a href="http://www.wplteens.com">WPL Teen</a></li>
          </ul>
        </div>
        <div><p><strong>{translate text='Need Help?'}</strong></p>
          <ul>
            <li><a href="{$url}/Help/Home?topic=search" onclick="window.open('{$url}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">{translate text='Search Tips'}</a></li>
            <li><a href="{$askALibrarianLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Ask a Librarian'}</a></li>
            {if isset($illLink)}
                <li><a href="{$illLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Interlibrary Loan'}</a></li>
            {/if}
            {if isset($suggestAPurchaseLink)}
                <li><a href="{$suggestAPurchaseLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Suggest a Purchase'}</a></li>
            {/if}
            <li><a href="{$url}/Help/Home?topic=faq" onclick="window.open('{$url}/Help/Home?topic=faq', 'Help', 'width=625, height=510'); return false;">{translate text='FAQs'}</a></li>
            <li><a href="{$path}/Help/Suggestion">{translate text='Make a Suggestion'}</a></li>
          </ul>
        </div>
        <br clear="all">
        {if !$productionServer}
        <div class='location_info'>{$physicalLocation}</div>
        {/if}
        
        {* Add Google Analytics*}
        {literal}
        <script type="text/javascript">
				  var _gaq = _gaq || [];
				  _gaq.push(['_setAccount', 'UA-10641564-2']);
				  _gaq.push(['_setDomainName', '.marmot.org']);
				  _gaq.push(['_trackPageview']);
				  _gaq.push(['_trackPageLoadTime']);
				
				  (function() {
				    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				  })();
				
				</script>
				{/literal}