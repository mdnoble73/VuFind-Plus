{strip}
<div class="navbar navbar-static-bottom">
	<div class="navbar-inner">
		<div class="row">
			{if !$productionServer}
				<div class="col-sm-6 text-left" id="install-info">
					<small class='location_info'>{$physicalLocation} ({$activeIp}) - {$deviceName}</small>
					<small class='version_info'> / v. {$gitBranch}</small>
				</div>
			{/if}
			<div class="col-sm-6 text-right" id="connect-with-us-info">
				<span id="connect-with-us-label" class="large">CONNECT WITH US</span>
				<a href="{$twitterUrl}" class="connect-icon"><img src="{img filename='twitter.png'}" class="img-rounded"></a>
				<a href="{$facebookUrl}" class="connect-icon"><img src="{img filename='facebook.png'}" class="img-rounded"></a>
				<a href="{$contactUrl}" class="connect-icon"><img src="{img filename='email-contact.png'}" class="img-rounded"></a>
			</div>
		</div>
		{if $google_translate_key}
			<div class="row">
				<div class="col-xs-12">
					{literal}
					<div id="google_translate_element">Translate this page &nbsp;</div><script type="text/javascript">
						function googleTranslateElementInit() {
							new google.translate.TranslateElement({
								pageLanguage: 'en',
								layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL
								{/literal}
								{if $google_included_languages}
								, includedLanguages: '{$google_included_languages}'
								{/if}
								{literal}
							}, 'google_translate_element');
						}
					</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
					{/literal}
				</div>
			</div>
		{/if}
	</div>

</div>
{/strip}
