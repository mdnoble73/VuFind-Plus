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
	</div>

</div>
{/strip}
