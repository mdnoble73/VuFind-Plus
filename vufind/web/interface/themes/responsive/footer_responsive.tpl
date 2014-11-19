{strip}
<div class="navbar navbar-static-bottom">
	<div class="navbar-inner">
		<div class="row">
			<div class="col-sm-6 text-left" id="install-info">
				{if !$productionServer}
					<small class='location_info'>{$physicalLocation} ({$activeIp}) - {$deviceName}</small>
				{/if}
				<small class='version_info'>{if $debug} / {/if}v. {$gitBranch}</small>
				{if $debug}
					<small class='session_info'> / session. {$session}</small>
				{/if}
			</div>
			<div class="col-sm-6 text-right" id="connect-with-us-info">
				{if $twitterLink || $facebookLink || $generalContactLink}
					<span id="connect-with-us-label" class="large">CONNECT WITH US</span>
					{if $twitterLink}
						<a href="{$twitterLink}" class="connect-icon"><img src="{img filename='twitter.png'}" class="img-rounded"></a>
					{/if}
					{if $facebookLink}
						<a href="{$facebookLink}" class="connect-icon"><img src="{img filename='facebook.png'}" class="img-rounded"></a>
					{/if}
					{if $generalContactLink}
						<a href="{$generalContactLink}" class="connect-icon"><img src="{img filename='email-contact.png'}" class="img-rounded"></a>
					{/if}
				{/if}
			</div>
		</div>
	</div>

</div>
{/strip}
