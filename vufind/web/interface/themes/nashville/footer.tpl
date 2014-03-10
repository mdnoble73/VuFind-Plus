{strip}
{* Your footer *}

<!-- Book Lists above footer -->
<div id="footerBookLists">
<div class="titleScrollerHeader"><span class="listTitle resultInformationLabel">Browse Popular Lists</span></div>
<div class="footerListCol"><h2>{translate text='New Books Movies Music'}</h2>
	<ul>
				<li><a href="{$path}/Search/Results?lookfor=&type=Subject&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A%22Books%22&filter[]=literary_form_full%3A%22Fiction%22&filter[]=genre_facet%3A%22Fiction%22sort=days_since_added+asc%2Cyear+descview=list&searchSource=local&amp;filter[]=time_since_added%3A&quot;Week&quot;">New Fiction</a></li>
                <li><a href="{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A&quot;Week&quot;&amp;filter[]=format_category%3A&quot;Audio+Books&quot;sort=days_since_added+asc%2Cyear+descfilter[]=publishDate:[{$lastYear} TO *]">New Audio Books</a></li>
                <li><a href="{$path}/Search/Results?lookfor=%2A&type=Keyword&type=Keyword&filter[]=format_category%3A%22Movies%22&filter[]=topic_facet%3A%22Feature+films%22&filter[]=publishDate:[{$lastYear} TO *]sort=days_since_added+asc%2Cyear+descsearchSource=local&amp;filter[]=time_since_added%3A&quot;Week&quot;">New Movies</a></li>	                   
		<li><a href='{$path}/Search/Results?lookfor=%22Television+Series%22+OR+%22Television+Programs%22+OR+%22Television+Adaptations%22+OR+%22Television+mini+-+series%22+NOT+%22Children&#39;s%22&type=Subject&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A%22Movies%22sort=days_since_added+asc%2Cyear+descview=list&searchSource=local&amp;filter[]=time_since_added%3A&quot;Week&quot;'>New TV Series &amp; Shows</a></li>
				<li><a href="{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A&quot;Week&quot;&amp;filter[]=literary_form_full%3A&quot;Non+Fiction&quot;&amp;sort=year+desc%2Ctitle+asc&filter[]=publishDate:[{$lastYear} TO *]&quot">New NonFiction</a></li>	
		<li><a href="{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=time_since_added%3A&quot;Week&quot;&amp;sort=year&filter[]=publishDate:[{$lastYear} TO *]">New This Week</a></li>
	</ul>
</div>

<div class="footerListCol"><h2>Popular Fiction</h2>
	<ul>
		<li><a href='{$path}/Search/Results?lookfor=%22graphic+novels%22+OR+%22anime%22+OR+%22manga%22&type=Subject&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A"Books"sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>Anime, Manga &amp; Graphic Novels</a></li> 
		<li><a href='{$path}/Search/Results?lookfor=%22mystery%22+OR+%22suspense+fiction%22&type=Subject&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A"Books"&filter[]=literary_form_full%3A"Fiction"&filter[]=genre_facet%3A"Fiction"sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>Mystery &amp; Suspense</a></li>    
        <li><a href='{$path}/Search/Results?lookfor=%22love+stories%22+OR+%22romance%22&type=Subject&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A"Books"&filter[]=literary_form_full%3A"Fiction"&filter[]=genre_facet%3A"Fiction"sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>Romance</a></li>  
		<li><a href='{$path}/Search/Results?lookfor=%22science+fiction%22+OR+%22fantasy+fiction%22&type=Subject&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A"Books"&filter[]=literary_form_full%3A"Fiction"&filter[]=genre_facet%3A"Fiction"sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>Science Fiction &amp; Fantasy</a></li> 
		<li><a href='{$path}/Search/Results?lookfor=%22horror+fiction%22&type=Subject&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A"Books"&filter[]=literary_form_full%3A"Fiction"&filter[]=genre_facet%3A"Fiction"sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>Horror</a></li>
	</ul>
</div>

<div class="footerListCol"><h2>{translate text='Popular NonFiction'}</h2>
	<ul><a href='{$path}/Search/Results?lookfor=%22employment%22+OR+%22vocational+guidance%22+OR+%22job+hunting%22&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A"Books"&filter[]=literary_form_full%3A"Non+Fiction"sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>Careers &amp; Employment</a></li>
		<li><a href='{$path}/Search/Results?lookfor=%22Microsoft+Windows%22+OR+%22Operating+systems%22+OR+%22Microsoft+Office%22+OR+%22Word+processing%22+OR+%22Mac+OS%22&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A"Books"&filter[]=literary_form_full%3A"Non+Fiction"sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>Computers</a></li>
		<li><a href='{$path}/Search/Results?lookfor=%22Cookbooks%22+OR+%22Cooking%22+OR+%22Baking%22&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A"Books"&filter[]=literary_form_full%3A"Non+Fiction"sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>Cookbooks</a></li>
        <li><a href='{$path}/Search/Results?lookfor=%22Garden+Ornaments+and+Furniture%22+OR+%22Gardening%22+OR+%22Vegetable+Gardening%22+OR+%22Container+Gardening%22&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A"Books"&filter[]=literary_form_full%3A"Non+Fiction"sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>Gardening</a></li 
        ><li><a href='{$path}/Search/Results?lookfor=%22Dwellings+--+Remodeling%22+OR+%22Remodeling%22&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A"Books"&filter[]=literary_form_full%3A"Non+Fiction"sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>Home Improvement</a></li>
	</ul>
</div>

<div class="footerListCol"><h2>{translate text='For Kids'}</h2>
	<ul>
		<li><a href='{$path}/Search/Results?lookfor=&basicType=Keyword&filter[]=target_audience_full%3A"Preschool+%280-5%29"&filter[]=illustrated%3A"Illustrated"&filter[]=format_category%3A"Books"&filter[]=genre_facet%3A"Juvenile+fiction"&filter[]=publishDate:[{$lastYear} TO *]sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>New Picture Books</a></li>
		<li><a href='{$path}/Search/Results?lookfor=%22Children%27s+Films%22&type=Subject&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A%22Movies%22sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>New Children&#39;s Movies</a></li>	
        <li><a href='{$path}/Search/Results?lookfor=%22Children%27s+Television+Series%22+OR+%22Children%27s+Television+Programs%22&type=Subject&basicType=Subject&filter[]=publishDate:[{$lastYear} TO *]&filter[]=format_category%3A%22Movies%22sort=days_since_added+asc%2Cyear+descview=list&searchSource=local'>New Children&#39;s TV Shows</a></li>
	
	</ul>
</div>

<div class="clearer"></div>

{if !$inLibrary}
<div class="footerCol"><h2>{*{translate text='Find More'}*}Browse Our Online Collections</h2>
	<ul>
		<!-- <li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li> -->
		<!-- <li><a href="{$path}/Search/Reserves">{translate text='Course Reserves'}</a></li> -->
		<!-- <li><a href="{$path}/Search/NewItem">{translate text='New Items'}</a></li> -->
		<li><a href="http://emedia.library.nashville.org/" rel="external" >OverDrive eBooks, Audiobooks, Video</a></li>
	        <li><a href="https://www.rbdigital.com/nashvilletn/service/zinio/landing?">Zinio Online Magazines</a></li>
	        <li><a href="http://www.freegalmusic.com">Freegal Music</a></li>
	        <li><a href="http://www.hoopladigital.com">Hoopla Digital Music, Video, Audiobooks</a></li>
	        <li><a href="http://www.tumblebooks.com/library/auto_login.asp?U=nashpublic&P=libra">Tumblebooks for Kids</a></li>
	        <li><a href-"http://0-bkflix.grolier.com.waldo.library.nashville.org"/>BookFLIX for Kids</a></li>
	</ul>
</div>
{/if}


<div class="footerCol" id="footerCol1"><h2>Can't Find What You're Looking For?</h2>
	<ul>
		{if isset($illLink)}
<!--				<li><a href="{$illLink}" rel="external" onclick="window.open (this.href, 'child'); return false">Request from {translate text='Interlibrary Loan'}</a></li>
-->				<li><a href="{$illLink}">Request from {translate text='Interlibrary Loan'}</a></li>
		{/if}
		{if isset($suggestAPurchaseLink)}
				<!-- <li><a href="{$suggestAPurchaseLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Suggest a Purchase'}</a></li> -->
                <li><a rel="external" target="_blank" href="http://www.library.nashville.org/bmm/bmm_books_suggestionform.asp">{translate text='Suggest a Purchase'}</a></li>
		{/if}
		<!-- <li><a href="{$path}/Help/Home?topic=faq" onclick="window.open('{$path}/Help/Home?topic=faq', 'Help', 'width=625, height=510, scrollbars=yes'); return false;">{translate text='FAQs'}</a></li> -->
		<!-- <li><a href="{$path}/Help/Suggestion">{translate text='Make a Suggestion'}</a></li> -->
      <li><a href="http://npl.worldcat.org">Search Worldcat</a></li>        
     <!-- {if $inLibrary}<li><a href="https://www.surveymonkey.com/s/VuFindFeedbackOPAC" class="languageBlockLink">Give Us Feedback</a></li>{/if} -->
	</ul>
</div>

<div class="footerCol"><h2>{translate text='Search Options'}</h2>
	<ul>
		{if $user}
		<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
		{/if}
		<li><a href="{$path}">{translate text='Standard Search'}</a></li>
		<li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
        <!-- <li><a href="{$path}/Help/Home?topic=search" onclick="window.open('{$path}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">{translate text='Search Tips'}</a></li> -->
	</ul>
</div>

</div><!-- /footerHelpInformation -->

<div class="clearer"></div>
<div id="copyright">

    {if $inLibrary}<li><a href="https://www.volgistics.com/ex/portal.dll/?FROM=15495">Volunteer Login</a></li>
	{else}
    <a href="#" class='mobile-view'>{translate text="Go to Mobile View"}</a>
    {/if}
</div>

{if !$productionServer}
<div class='location_info'>{$physicalLocation} ({$activeIp}) - {$deviceName}</div>
{/if}
<div class='version_info'>v. {$gitBranch}</div>
<div class="version_info"><a href="https://github.com/mdnoble73/VuFind-Plus">VuFind-Plus Open Source Catalog</a></div>


{/strip}

