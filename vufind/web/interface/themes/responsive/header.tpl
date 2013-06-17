{strip}
<div class="masthead">
	<div class="row-fluid">
		<div class="span9">
			{if $showTopSearchBox || $widget}
				<div class="row-fluid">
					<div class="span12 text-left">
						<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}">
							<img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Catalog Home" title="Return to Catalog Home" class="alignleft"  id="header_logo"/>
						</a>
					</div>
				</div>
			{/if}
		</div>
		<div class="span3">
			{include file='login-block.tpl'}
		</div>
	</div>
</div>
{/strip}