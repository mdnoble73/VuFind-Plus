{strip}
<div class="searchheader">
	<div class="searchcontent">
		<div id="toptabs">
			<span class="toptab"><a href="http://www.gcld.org/">HOME</a></span>
			<span class="toptab"><a href="http://www.gcld.org/content/locations/landing-page">LOCATIONS</a></span>
			<span class="toptab"><a href="http://www.gcld.org/content/pages/booksandmore">BOOKS &amp; MORE</a></span>
			<span class="toptab"><a href="http://gcld.org/ebooks">EBOOKS &amp; MORE</a></span>
			<span class="toptab"><a href="http://www.gcld.org/programs">PROGRAMS</a></span>
			<span class="toptab"><a href="http://www.gcld.org/content/pages/library-services-landing-page">SERVICES</a></span>
			<span class="toptab"><a href="http://www.gcld.org/content/pages/aboutus">ABOUT US</a></span>
			<span class="toptab"><a href="http://www.gcld.org/content/pages/support-library-landing-page">SUPPORT US</a></span>
		</div>
		{include file='login-block.tpl'}

		<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Catalog Home" title="Return to Catalog Home" id="header_logo"/></a>

		<div class="clearer">&nbsp;</div>
	</div>
</div>
{/strip}