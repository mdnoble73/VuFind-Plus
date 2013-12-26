<div id="page-content" class="content">
	<form name='placeHoldForm' id='placeHoldForm' action="{$path}/Record/{$id|escape:"url"}/Hold" method="post">
		<div>
			<div class="holdsSummary">
				<h3>Placing hold on <span id='newHoldCount'>1</span> title.</h3>
				<input type="hidden" name="holdCount" id="holdCount" value="1"/>
				<div class="pageWarning" id="overHoldCountWarning" {if !$showOverHoldLimit}style="display:none"{/if}>Warning: You have reached the maximum of <span class='maxHolds'>{$maxHolds}</span> holds for your account.  You must cancel a hold before you can place a hold on this title.</div>
				<div id='holdError' class="pageWarning" style='display: none'></div>
			</div>
			{if $holdDisclaimer}
				<div id="holdDisclaimer">{$holdDisclaimer}</div>
			{/if}
			<p class="note">
				Holds allow you to request that a title be delivered to your holds pickup library.
				Once the title arrives at your library you will be sent an e-mail, receive a phone call, or receive a postcard informing you that the title is ready for you.
			</p>
			{if (!isset($profile)) }
				<div id ='loginUsernameRow' class='loginFormRow'>
					<div class='loginLabel'>{translate text='Username'}: </div>
					<div class='loginField'><input type="text" name="username" id="username" value="{$username|escape}" size="15"/></div>
				</div>
				<div id ='loginPasswordRow' class='loginFormRow'>
					<div class='loginLabel'>{translate text='Password'}: </div>
					<div class='loginField'><input type="password" name="password" id="password" size="15"/></div>
				</div>
				<div id='loginSubmitButtonRow' class='loginFormRow'>
					<div class='loginLabel'>&nbsp;</div>
					<div class='loginField center'>
						<input id="loginButton" type="button" onclick="GetPreferredBranches('{$id|escape}');" value="Login"/>
					</div>
				</div>
			{/if}
			<div id='holdOptions' {if (!isset($profile)) }style='display:none'{/if}>
				<div id='pickupLocationOptions'>
					<div class='loginLabel'>{translate text="I want to pick this up at"}: </div>
					<div class='loginField'>
						<select name="campus" id="campus">
							{if count($pickupLocations) > 0}
								{foreach from=$pickupLocations item=location}
									<option value="{$location->code}" {if $location->selected == "selected"}selected="selected"{/if}>{$location->displayName}</option>
								{/foreach}
							{else} 
								<option>placeholder</option>
							{/if}
						</select>
					</div>
				</div>
<!--
				{if $showHoldCancelDate == 1}
					<div id='cancelHoldDate' class='loginFormRow'>
						<div class='loginLabel'>{translate text="Automatically cancel this hold if not filled by"}:</div>
						<div class='loginField'>
							<input type="text" name="canceldate" id="canceldate" size="10" {if $defaultNotNeededAfterDays}value="{$defaultNotNeededAfterDays}"{/if}>
						</div>
						<div class='loginFormRow'>
							<i>If this date is reached, the hold will automatically be cancelled for you.	This is a great way to handle time sensitive materials for term papers, etc. If not set, the cancel date will automatically be set 6 months from today.</i>
						</div>
					</div>
				{/if}
-->
				<br />
				<input type="hidden" name="holdType" value="hold" />
				<input type="submit" name="submit" id="requestTitleButton" value="{translate text='Submit Hold Request'}" {if (!isset($profile))}disabled="disabled"{/if} />
<!--				<input type="checkbox" name="autologout" id="autologout" {if $inLibrary == true}checked="checked"{/if}/> <label for="autologout">Log me out after requesting the item.</label>
-->
			</div> 
		</div>
	</form>
</div>
<script	type="text/javascript">
	{literal}
	$(function() {
		$( "#canceldate" ).datepicker({ minDate: 0, showOn: "button", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
	});
	{/literal}
</script>

