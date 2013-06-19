{strip}
<div class="masthead">
	<div class="row-fluid">
		<div class="span9">
			{if $showTopSearchBox || $widget}
				<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}">
					<img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Catalog Home" title="Return to Catalog Home" id="header_logo"/>
				</a>
			{/if}
		</div>
		<div class="span3">
			{include file='login-block.tpl'}
		</div>
	</div>
</div>
{/strip}