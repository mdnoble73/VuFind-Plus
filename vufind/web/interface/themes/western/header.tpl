<div class="searchheader">
	<div class="searchcontent">
		{if $module == 'Search' && $action == 'Home'}
		{else}
		<a href="{if $homeLink}{$homeLink}{else}{$url}{/if}"><img src="{img filename="logo_small.png"}" alt="Catalog Home" title="Return to Catalog Home" id="header_logo" /></a>
		{/if}
		{include file='login-block.tpl'}
	</div>
</div>