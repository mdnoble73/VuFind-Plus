{strip}
<div class="searchheader">
	<div class="searchcontent">
		<div id="toptabs">
			<span class="toptab" title="Discover what's happening at the library for all ages."><a href="http://www.steamboatlibrary.org/events">EVENTS</a></span>
			<span class="toptab" title="Discover Book &amp; Movie suggestions for all ages."><a href="http://www.steamboatlibrary.org/books-and-media/books">BOOKS &amp; MEDIA</a></span>
			<span class="toptab" title="Download Audio books, eBooks, Movies, Music and News."><a href="http://www.steamboatlibrary.org/downloads">DOWNLOAD</a></span>
			<span class="toptab" title="Services for all ages and groups."><a href="http://www.steamboatlibrary.org/services">SERVICES</a></span>
			<span class="toptab" title="Book a Meeting Room, Get a Library Card, Use a Computer, and more."><a href="http://www.steamboatlibrary.org/how-do-i">HOW DO I?</a></span>
			<span class="toptab" title="Login to View Your Account, Renew Books, and more."><a href="/MyResearch/Home">MY ACCOUNT</a></span>
		</div>
		{include file='login-block.tpl'}

		<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Catalog Home" title="Return to Catalog Home" class="alignleft"  id="header_logo"/></a>

		<div class="clearer">&nbsp;</div>
	</div>
</div>
{/strip}