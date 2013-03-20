{strip}
<div class="searchheader">
	<div class="searchcontent">
		<div id="toptabs">
			<span class="toptab"><a href="{$path}/MyResearch/Home">My Account</a></span>
			<span class="toptab"><a href="http://www.englewoodgov.org/inside-city-hall/city-departments/library/ebooks">Ebooks</a></span>
			<span class="toptab"><a href="http://www.englewoodgov.org/inside-city-hall/city-departments/library/what-s-happening">Events</a></span>
			<span class="toptab"><a href="http://www.englewoodgov.org/inside-city-hall/city-departments/library/children-s-corner">Kids</a></span>
		</div>
		{include file='login-block.tpl'}

		<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Catalog Home" title="Return to Catalog Home" class="alignleft"  id="header_logo"/></a>

		<div class="clearer">&nbsp;</div>
	</div>
</div>
{/strip}