{strip}
<div class="searchheader">
	<div class="searchcontent">
		{include file='login-block.tpl'}

		{if true}
			<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Catalog Home" title="Return to Catalog Home"  id="header_logo"/></a>
		{/if}

		<div class="clearer">&nbsp;</div>
	</div>
</div>
{/strip}