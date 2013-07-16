{strip}
{if $pageTemplate=='history.tpl' && $user}
	{* if we're in /Search/History and logged in, then use MyResearch footer navbar instead *}
	{include file='MyResearch/footer-navbar.tpl'}
{else}
	<div data-role="navbar">
		<ul>
			{* show Bag button *}
			<li><a id="openBookCart" href="{$path}/Cart/Home" class="book_bag_btn" data-rel="dialog" data-transition="flip">{translate text="Book Cart"} (<span class="cart_size">0</span>)</a></li>
			{* show Language button *}
			<li><a data-rel="dialog" href="#Language-dialog" data-transition="pop">{translate text="Language"}</a></li>

			{* always show Account button *}
			<li><a rel="external" href="{$path}/MyResearch/Home">{translate text="Account"}</a></li>

			{* show Logout if logged in *}
			{if $user}
				<li><a rel="external" href="{$path}/MyResearch/Logout">{translate text="Logout"}</a></li>
			{/if}
		</ul>
	</div>
{/if}
{/strip}