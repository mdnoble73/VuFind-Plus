{strip}
<div class="searchheader">
	<div class="searchcontent">
		<div id="toptabs">
			<span class="toptab"><a href="http://www.steamboatlibrary.org/events">EVENTS</a></span>
			<span class="toptab"><a href="http://www.steamboatlibrary.org/books-and-media">BOOKS &amp; MEDIA</a></span>
			<span class="toptab"><a href="http://www.steamboatlibrary.org/downloads">DOWNLOAD</a></span>
			<span class="toptab"><a href="http://www.steamboatlibrary.org/services">SERVICES</a></span>
			<span class="toptab"><a href="http://www.steamboatlibrary.org/how-do-i">HOW DO I?</a></span>
			<span class="toptab"><a href="/MyResearch/Home">MY ACCOUNT</a></span>
		</div>
		{include file='login-block.tpl'}

		<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Catalog Home" title="Return to Catalog Home" class="alignleft"  id="header_logo"/></a>

		<div class="clearer">&nbsp;</div>
	</div>
</div>
{/strip}