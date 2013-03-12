{strip}
<div data-role="navbar">
	<ul>
		{if $user}
			<li><a rel="external" {if $pageTemplate=="favorites.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/Favorites">{translate text='Lists'}</a></li>
			<li><a rel="external" {if $pageTemplate=="readingHistory.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/ReadingHistory">{translate text='Reading History'}</a></li>
			<li><a rel="external" {if $pageTemplate=="history.tpl"} class="ui-btn-active"{/if} href="{$path}/Search/History?require_login">{translate text='Search History'}</a></li>
			<li><a rel="external" href="{$path}/MyResearch/Logout">{translate text="Logout"}</a></li>
		{else}
			<li><a id="openBookCart" href="{$path}/Cart/Home" class="book_bag_btn" data-rel="dialog" data-transition="flip">{translate text="Book Cart"} (<span class="cart_size">0</span>)</a></li>
			<li><a data-rel="dialog" href="#Language-dialog" data-transition="pop">{translate text="Language"}</a></li>
			<li><a rel="external" href="{$path}/MyResearch/Home" id="accountLinkFooter">{translate text="Account"}</a></li>
		{/if}
	</ul>
</div>
{/strip}