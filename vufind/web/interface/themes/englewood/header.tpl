{strip}
<div class="searchheader">
	<div class="searchcontent">
		<div class="toptabs">
			<a href="{$path}/MyResearch/Home"><span class="tab button">My Account</span></a>
			<a href="http://www.englewoodgov.org/Index.aspx?page=1168"><span class="tab button">Ebooks</span></a>
			<a href="http://www.englewoodgov.org/Index.aspx?page=509"><span class="tab button">Events</span></a>
			<a href="http://www.englewoodgov.org/Index.aspx?page=224"><span class="tab button">Kids</span></a>
		</div>
		{include file='login-block.tpl'}

		{if $showTopSearchBox || $widget}
			<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Catalog Home" title="Return to Catalog Home" class="alignleft"  id="header_logo"/></a>
		{/if}

		<div class="clearer">&nbsp;</div>
	</div>
</div>
{/strip}