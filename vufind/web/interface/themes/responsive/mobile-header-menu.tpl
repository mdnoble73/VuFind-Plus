{strip}
	{* Small view-port menu *}
	<div id="mobile-menu" class="visible-xs btn-group btn-group-sm">
		{if $user}{* Logged In *}
			<a href="#account-menu" class="btn btn-sm btn-default" title="Account" onclick="if($('#myAccountPanel').is('.collapsed')$('#myAccountPanel').collapse('show');">
				<span class="account-icon"></span>
			</a>
			<a href="{$path}/MyAccount/Logout" id="logoutLink" class="btn btn-sm btn-default" title="{translate text="Log Out"}">
				<span class="logout-icon"></span>
				{*LOGOUT*}
			</a>
		{else} {* Not Logged In *}
			{*<button class="btn btn-sm btn-default" title="Log in"><span class="login-icon"></span></button>*}
			<a href="{$path}/MyAccount/Home" id="loginLink" class="btn btn-sm btn-default" data-login="true" title="{translate text="Login"}" onclick="return VuFind.Account.followLinkIfLoggedIn(this);">
				<span class="account-icon"></span>
				{*<span class="login-icon"></span>*}
				{*LOGIN*}
			</a>
		{/if}
	</div>
	<script type="text/javascript">
		{literal}
		$(function(){
			var mobileMenu = $('#mobile-menu'),
					switchPosition = mobileMenu.offset().top - 5; /* subtracting the offset at which it becomes a fixed element;*/
				/*Meant to remain constant for the event handler below.*/
			$(window).scroll(function(){
				var fixedOffset = mobileMenu.offset().top,
						notFixedScrolledPosition = $(this).scrollTop();
				/*Toggle into an embedded mode*/
				if (mobileMenu.is('.sticky-mobile-menu') && fixedOffset <= switchPosition) {
					mobileMenu.removeClass('sticky-mobile-menu');
				}
				/*Toggle into a fixed mode*/
				if (!mobileMenu.is('.sticky-mobile-menu') && notFixedScrolledPosition >= switchPosition) {
					mobileMenu.addClass('sticky-mobile-menu');
				}
			})
		});
		{/literal}
	</script>
{/strip}